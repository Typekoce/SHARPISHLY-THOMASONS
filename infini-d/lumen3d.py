#!/usr/bin/env python3
"""Lumen3D - a small original 3D scene editor with keyframe animation.
Inspired by classic 90s desktop 3D tools. Pure Python 3 + Tkinter, no dependencies."""
import math, json, sys

# ---------- geometry ----------
def revolve(profile, seg=16, caps=True):
    rings = [[(r*math.cos(2*math.pi*i/seg), y, r*math.sin(2*math.pi*i/seg)) for i in range(seg)] for r, y in profile]
    verts = [v for ring in rings for v in ring]
    faces = []
    for k in range(len(rings)-1):
        for i in range(seg):
            j = (i+1) % seg
            faces.append((k*seg+i, k*seg+j, (k+1)*seg+j, (k+1)*seg+i))
    if caps:
        faces.append(tuple(range(seg-1, -1, -1)))
        n = (len(rings)-1)*seg
        faces.append(tuple(range(n, n+seg)))
    return verts, faces

def make_mesh(kind):
    if kind == "cube":
        v = [(x, y, z) for x in (-.5, .5) for y in (-.5, .5) for z in (-.5, .5)]
        f = [(0,1,3,2),(4,6,7,5),(0,4,5,1),(2,3,7,6),(0,2,6,4),(1,5,7,3)]
        return v, f
    if kind == "plane":
        return [(-1,0,-1),(1,0,-1),(1,0,1),(-1,0,1)], [(0,1,2,3)]
    if kind == "sphere":
        prof = [(.5*math.sin(math.pi*i/10), .5*math.cos(math.pi*i/10)) for i in range(11)]
        return revolve(prof, 16, caps=False)
    if kind == "cylinder":
        return revolve([(.5, .5), (.5, -.5)])
    if kind == "cone":
        return revolve([(0, .5), (.5, -.5)])
    raise ValueError(kind)

def rot_matrix(rx, ry, rz):
    rx, ry, rz = map(math.radians, (rx, ry, rz))
    cx, sx, cy, sy, cz, sz = math.cos(rx), math.sin(rx), math.cos(ry), math.sin(ry), math.cos(rz), math.sin(rz)
    # R = Rz * Ry * Rx
    return [[cz*cy, cz*sy*sx - sz*cx, cz*sy*cx + sz*sx],
            [sz*cy, sz*sy*sx + cz*cx, sz*sy*cx - cz*sx],
            [-sy,   cy*sx,            cy*cx]]

def mat_vec(m, v):
    return tuple(m[i][0]*v[0] + m[i][1]*v[1] + m[i][2]*v[2] for i in range(3))

def newell(pts):
    nx = ny = nz = 0.0
    for i in range(len(pts)):
        a, b = pts[i], pts[(i+1) % len(pts)]
        nx += (a[1]-b[1])*(a[2]+b[2]); ny += (a[2]-b[2])*(a[0]+b[0]); nz += (a[0]-b[0])*(a[1]+b[1])
    l = math.sqrt(nx*nx+ny*ny+nz*nz) or 1.0
    return nx/l, ny/l, nz/l

# ---------- scene ----------
class Obj:
    def __init__(self, kind, name):
        self.kind, self.name = kind, name
        self.color = "#4a90d9"
        self.keys = {0: [0,0,0, 0,0,0, 1,1,1]}   # frame -> pos, rot, scale

    def state(self, frame):
        ks = sorted(self.keys)
        if frame <= ks[0]: return list(self.keys[ks[0]])
        if frame >= ks[-1]: return list(self.keys[ks[-1]])
        for a, b in zip(ks, ks[1:]):
            if a <= frame <= b:
                t = (frame-a)/(b-a)
                return [p+(q-p)*t for p, q in zip(self.keys[a], self.keys[b])]

    def world(self, frame):
        s = self.state(frame)
        m = rot_matrix(*s[3:6])
        verts, faces = make_mesh(self.kind)
        out = []
        for v in verts:
            sv = (v[0]*s[6], v[1]*s[7], v[2]*s[8])
            r = mat_vec(m, sv)
            out.append((r[0]+s[0], r[1]+s[1], r[2]+s[2]))
        return out, faces

class Scene:
    def __init__(self): self.objs = []
    def add(self, kind):
        o = Obj(kind, f"{kind}{len(self.objs)+1}"); self.objs.append(o); return o
    def to_json(self):
        return json.dumps([{"kind": o.kind, "name": o.name, "color": o.color,
                            "keys": {str(k): v for k, v in o.keys.items()}} for o in self.objs], indent=1)
    def from_json(self, text):
        self.objs = []
        for d in json.loads(text):
            o = Obj(d["kind"], d["name"]); o.color = d["color"]
            o.keys = {int(k): v for k, v in d["keys"].items()}; self.objs.append(o)
    def export_obj(self, path, frame):
        with open(path, "w") as f:
            base = 1
            for o in self.objs:
                v, faces = o.world(frame)
                f.write(f"o {o.name}\n")
                for p in v: f.write("v %.5f %.5f %.5f\n" % p)
                for fc in faces: f.write("f " + " ".join(str(i+base) for i in fc) + "\n")
                base += len(v)

# ---------- camera / render ----------
def shade(hexcol, k):
    r, g, b = (int(hexcol[i:i+2], 16) for i in (1, 3, 5))
    k = 0.25 + 0.75*k
    return "#%02x%02x%02x" % (min(255, int(r*k)), min(255, int(g*k)), min(255, int(b*k)))

def render_faces(scene, frame, yaw, pitch, dist, w, h, selected=None):
    cy, sy, cp, sp = math.cos(yaw), math.sin(yaw), math.cos(pitch), math.sin(pitch)
    def view(p):
        x, y, z = p
        x, z = x*cy - z*sy, x*sy + z*cy
        y, z = y*cp - z*sp, y*sp + z*cp
        return (x, y, z + dist)
    light = (0.4, 0.7, -0.6); ll = math.sqrt(sum(c*c for c in light)); light = tuple(c/ll for c in light)
    f = min(w, h) * 1.1
    out = []
    for o in scene.objs:
        verts, faces = o.world(frame)
        vv = [view(p) for p in verts]
        for fc in faces:
            pts = [vv[i] for i in fc]
            if any(p[2] < 0.1 for p in pts): continue
            n = newell(pts)
            k = abs(n[0]*light[0] + n[1]*light[1] + n[2]*light[2])
            depth = sum(p[2] for p in pts)/len(pts)
            poly = [c for p in pts for c in (w/2 + f*p[0]/p[2], h/2 - f*p[1]/p[2])]
            out.append((depth, poly, shade(o.color, k), "#ffffff" if o is selected else "#102030"))
    out.sort(key=lambda t: -t[0])
    return out

# ---------- GUI ----------
def run_gui():
    import tkinter as tk
    from tkinter import filedialog, colorchooser, messagebox
    root = tk.Tk(); root.title("Lumen3D")
    scene = Scene(); st = {"yaw": 0.6, "pitch": 0.4, "dist": 8.0, "frame": 0, "play": False, "sel": None, "drag": None}
    scene.add("cube")

    canvas = tk.Canvas(root, width=720, height=520, bg="#1e2530", highlightthickness=0)
    canvas.grid(row=0, column=0, sticky="nsew")
    side = tk.Frame(root); side.grid(row=0, column=1, sticky="ns", padx=6, pady=6)
    bottom = tk.Frame(root); bottom.grid(row=1, column=0, columnspan=2, sticky="ew", padx=6, pady=4)
    root.columnconfigure(0, weight=1); root.rowconfigure(0, weight=1)

    lb = tk.Listbox(side, height=10, exportselection=False); lb.pack(fill="x")
    for k in ("cube", "sphere", "cylinder", "cone", "plane"):
        tk.Button(side, text="Add " + k, command=lambda k=k: (scene.add(k), refresh_list(len(scene.objs)-1))).pack(fill="x")
    tk.Button(side, text="Delete object", command=lambda: delete_obj()).pack(fill="x", pady=(6, 0))
    tk.Button(side, text="Colour...", command=lambda: pick_colour()).pack(fill="x")
    grid = tk.Frame(side); grid.pack(pady=6)
    ents = []
    for r, lab in enumerate(("Pos", "Rot", "Scale")):
        tk.Label(grid, text=lab).grid(row=r, column=0)
        for c in range(3):
            e = tk.Entry(grid, width=6); e.grid(row=r, column=c+1); ents.append(e)
    tk.Button(side, text="Apply to current frame", command=lambda: apply_vals()).pack(fill="x")

    tl = tk.Scale(bottom, from_=0, to=100, orient="horizontal", length=420, command=lambda v: set_frame(int(float(v))))
    tl.pack(side="left", fill="x", expand=True)
    tk.Button(bottom, text="Set key", command=lambda: set_key()).pack(side="left")
    tk.Button(bottom, text="Del key", command=lambda: del_key()).pack(side="left")
    play_btn = tk.Button(bottom, text="Play", command=lambda: toggle_play()); play_btn.pack(side="left")

    def cur(): return scene.objs[st["sel"]] if st["sel"] is not None and st["sel"] < len(scene.objs) else None
    def refresh_list(sel=None):
        lb.delete(0, "end")
        for o in scene.objs: lb.insert("end", o.name)
        if sel is not None and scene.objs:
            lb.selection_clear(0, "end"); lb.selection_set(sel); st["sel"] = sel
        fill_entries(); redraw()
    def fill_entries():
        o = cur()
        for e in ents: e.delete(0, "end")
        if o:
            for e, v in zip(ents, o.state(st["frame"])): e.insert(0, "%.3g" % v)
    def apply_vals():
        o = cur()
        if not o: return
        try: vals = [float(e.get()) for e in ents]
        except ValueError: return messagebox.showerror("Lumen3D", "Numbers only")
        if st["frame"] in o.keys or len(o.keys) == 1: o.keys[st["frame"]] = vals
        else: o.keys[st["frame"]] = vals
        redraw()
    def set_key():
        o = cur()
        if o: apply_vals()
    def del_key():
        o = cur()
        if o and len(o.keys) > 1 and st["frame"] in o.keys: del o.keys[st["frame"]]; fill_entries(); redraw()
    def delete_obj():
        if cur(): scene.objs.pop(st["sel"]); st["sel"] = None; refresh_list()
    def pick_colour():
        o = cur()
        if o:
            c = colorchooser.askcolor(o.color)[1]
            if c: o.color = c; redraw()
    def set_frame(f): st["frame"] = f; fill_entries(); redraw()
    def toggle_play():
        st["play"] = not st["play"]; play_btn.config(text="Stop" if st["play"] else "Play")
        if st["play"]: tick()
    def tick():
        if not st["play"]: return
        tl.set((st["frame"]+1) % 101); root.after(40, tick)
    def redraw():
        canvas.delete("all")
        w, h = canvas.winfo_width() or 720, canvas.winfo_height() or 520
        for d, poly, fill, line in render_faces(scene, st["frame"], st["yaw"], st["pitch"], st["dist"], w, h, cur()):
            canvas.create_polygon(poly, fill=fill, outline="#102030")
        canvas.create_text(8, 8, anchor="nw", fill="#9ab", text=f"frame {st['frame']}  |  drag: orbit  wheel: zoom")
    lb.bind("<<ListboxSelect>>", lambda e: (st.update(sel=lb.curselection()[0] if lb.curselection() else None), fill_entries(), redraw()))
    canvas.bind("<Configure>", lambda e: redraw())
    canvas.bind("<ButtonPress-1>", lambda e: st.update(drag=(e.x, e.y)))
    def drag(e):
        if st["drag"]:
            st["yaw"] += (e.x-st["drag"][0])*0.01
            st["pitch"] = max(-1.5, min(1.5, st["pitch"] + (e.y-st["drag"][1])*0.01))
            st["drag"] = (e.x, e.y); redraw()
    canvas.bind("<B1-Motion>", drag)
    def zoom(d): st["dist"] = max(2, min(40, st["dist"] * (0.9 if d > 0 else 1.1))); redraw()
    canvas.bind("<MouseWheel>", lambda e: zoom(e.delta)); canvas.bind("<Button-4>", lambda e: zoom(1)); canvas.bind("<Button-5>", lambda e: zoom(-1))

    def save():
        p = filedialog.asksaveasfilename(defaultextension=".l3d", filetypes=[("Lumen3D scene", "*.l3d")])
        if p: open(p, "w").write(scene.to_json())
    def load():
        p = filedialog.askopenfilename(filetypes=[("Lumen3D scene", "*.l3d")])
        if p: scene.from_json(open(p).read()); st["sel"] = None; refresh_list()
    def export():
        p = filedialog.asksaveasfilename(defaultextension=".obj", filetypes=[("Wavefront OBJ", "*.obj")])
        if p: scene.export_obj(p, st["frame"])
    mb = tk.Menu(root); fm = tk.Menu(mb, tearoff=0)
    fm.add_command(label="Open...", command=load); fm.add_command(label="Save...", command=save)
    fm.add_command(label="Export current frame as OBJ...", command=export); fm.add_separator(); fm.add_command(label="Quit", command=root.destroy)
    mb.add_cascade(label="File", menu=fm); root.config(menu=mb)
    refresh_list(0); root.mainloop()

if __name__ == "__main__":
    run_gui()
