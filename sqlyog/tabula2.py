#!/usr/bin/env python3
"""Tabula 2 - an original SQL client for MySQL/MariaDB and SQLite (Python 3 + Tkinter).
MySQL support needs PyMySQL: sudo apt install python3-pymysql"""
import sqlite3, csv, re, os, json, time, threading, queue

CFG = os.path.join(os.environ.get("XDG_CONFIG_HOME", os.path.expanduser("~/.config")), "tabula")
STATE = os.path.join(CFG, "state.json")
KEYWORDS = ("select insert update delete from where join left right inner outer on group by order having limit offset "
            "create alter drop table index view database values set into as and or not null like in is distinct union "
            "show describe use primary key default explain between exists case when then else end asc desc count sum avg min max").split()
CLAUSES = r"\b(FROM|WHERE|GROUP BY|ORDER BY|HAVING|LIMIT|LEFT JOIN|RIGHT JOIN|INNER JOIN|JOIN|UNION|VALUES|SET)\b"

def load_state():
    try: return json.load(open(STATE))
    except Exception: return {}
def save_state(s):
    os.makedirs(CFG, exist_ok=True); json.dump(s, open(STATE, "w"), indent=1)

def split_statements(sql):
    out, buf, q = [], [], None
    for ch in sql:
        if q:
            if ch == q: q = None
        elif ch in "'\"`": q = ch
        elif ch == ";":
            s = "".join(buf).strip()
            if s: out.append(s)
            buf = []; continue
        buf.append(ch)
    s = "".join(buf).strip()
    if s: out.append(s)
    return out

def format_sql(sql):
    parts = re.split(r"('[^']*'|\"[^\"]*\"|`[^`]*`)", sql); out = []
    for i, p in enumerate(parts):
        if i % 2: out.append(p); continue
        p = re.sub(r"\b[A-Za-z_]+\b", lambda m: m.group().upper() if m.group().lower() in KEYWORDS else m.group(), p)
        out.append(re.sub(CLAUSES, r"\n\1", p))
    return re.sub(r"[ \t]*\n[ \t]*", "\n", "".join(out)).strip()

def suggest(prefix, names):
    p, seen, out = prefix.lower(), set(), []
    for w in sorted(set(names)) + [k.upper() for k in KEYWORDS]:
        if w.lower().startswith(p) and w.lower() != p and w not in seen: seen.add(w); out.append(w)
    return out[:200]

class DB:
    def __init__(self, kind, **kw):
        self.kind = kind
        if kind == "sqlite":
            self.conn = sqlite3.connect(kw["path"], check_same_thread=False); self.label = kw["path"]; self.ph = "?"
        else:
            import pymysql
            self.conn = pymysql.connect(host=kw["host"], port=int(kw.get("port") or 3306), user=kw["user"],
                                        password=kw.get("password", ""), autocommit=True, connect_timeout=8)
            self.label = f'{kw["user"]}@{kw["host"]}'; self.ph = "%s"
    def q(self, i): return '"%s"' % i.replace('"', '""') if self.kind == "sqlite" else "`%s`" % i.replace("`", "``")
    def full(self, db, t): return self.q(t) if self.kind == "sqlite" else f"{self.q(db)}.{self.q(t)}"
    def run(self, sql, params=()):
        cur = self.conn.cursor()
        cur.execute(sql, params) if params else cur.execute(sql)
        if cur.description: return [d[0] for d in cur.description], [list(r) for r in cur.fetchall()], None
        if self.kind == "sqlite": self.conn.commit()
        return [], [], cur.rowcount
    def databases(self): return ["main"] if self.kind == "sqlite" else [r[0] for r in self.run("SHOW DATABASES")[1]]
    def tables(self, db):
        if self.kind == "sqlite":
            return [r[0] for r in self.run("SELECT name FROM sqlite_master WHERE type IN ('table','view') ORDER BY name")[1]]
        return [r[0] for r in self.run(f"SHOW TABLES FROM {self.q(db)}")[1]]
    def columns(self, db, t):
        if self.kind == "sqlite": return [(r[1], r[2]) for r in self.run(f"PRAGMA table_info({self.q(t)})")[1]]
        return [(r[0], r[1]) for r in self.run(f"SHOW COLUMNS FROM {self.full(db, t)}")[1]]
    def pk(self, db, t):
        if self.kind == "sqlite":
            return [r[1] for r in sorted((r for r in self.run(f"PRAGMA table_info({self.q(t)})")[1] if r[5]), key=lambda r: r[5])]
        rows = self.run(f"SHOW KEYS FROM {self.full(db, t)} WHERE Key_name = 'PRIMARY'")[1]
        return [r[4] for r in sorted(rows, key=lambda r: r[3])]
    def select_all(self, db, t): return f"SELECT * FROM {self.full(db, t)} LIMIT 1000;"
    def update_cell(self, db, t, pk, row, col, val):
        sql = f"UPDATE {self.full(db, t)} SET {self.q(col)} = {self.ph} WHERE " + " AND ".join(f"{self.q(c)} = {self.ph}" for c in pk)
        return self.run(sql, [val] + [row[c] for c in pk])[2]

def sortkey(v): return (2, 0) if v is None else (0, v) if isinstance(v, (int, float)) else (1, str(v))

def run_gui():
    import tkinter as tk
    from tkinter import ttk, filedialog, messagebox
    root = tk.Tk(); root.title("Tabula 2"); root.geometry("1200x740")
    st = load_state()
    for k, d in (("connections", []), ("history", []), ("theme", "light"), ("tabs", [])): st.setdefault(k, d)
    S = {"db": None, "names": set()}; tabs = []; results = queue.Queue(); status = tk.StringVar(value="Not connected")
    T = {"light": dict(bg="#f4f5f7", fg="#1b1f24", panel="#ffffff", sel="#cfe3ff", kw="#1a56c4", str="#b5541c", num="#1a7f37"),
         "dark": dict(bg="#22262d", fg="#d7dae0", panel="#1b1e24", sel="#34507a", kw="#6cb2ff", str="#e0a36b", num="#7fd18b")}

    tool = ttk.Frame(root); tool.pack(fill="x", padx=4, pady=3)
    ttk.Label(root, textvariable=status, anchor="w").pack(side="bottom", fill="x", padx=6)
    paned = ttk.PanedWindow(root, orient="horizontal"); paned.pack(fill="both", expand=True)
    tree = ttk.Treeview(paned, show="tree"); paned.add(tree, weight=1)
    nb = ttk.Notebook(paned); paned.add(nb, weight=4)

    def style_tab(tab):
        t = T[st["theme"]]; ed = tab["ed"]
        ed.configure(bg=t["panel"], fg=t["fg"], insertbackground=t["fg"], selectbackground=t["sel"])
        for n in ("num", "kw", "str"): ed.tag_configure(n, foreground=t[n])
        tab["grid"].tag_configure("odd", background=t["bg"])
    def apply_theme():
        t = T[st["theme"]]; s = ttk.Style(); s.theme_use("clam")
        s.configure(".", background=t["bg"], foreground=t["fg"], fieldbackground=t["panel"])
        s.configure("Treeview", background=t["panel"], fieldbackground=t["panel"], foreground=t["fg"], rowheight=22)
        s.configure("Treeview.Heading", background=t["bg"], foreground=t["fg"])
        s.map("Treeview", background=[("selected", t["sel"])], foreground=[("selected", t["fg"])])
        root.configure(bg=t["bg"]); [style_tab(x) for x in tabs]
    def toggle_theme(): st["theme"] = "dark" if st["theme"] == "light" else "light"; apply_theme()

    def highlight(ed):
        for n in ("kw", "str", "num"): ed.tag_remove(n, "1.0", "end")
        txt = ed.get("1.0", "end")
        for pat, tag in ((r"\b\d+(\.\d+)?\b", "num"), (r"\b[A-Za-z_]+\b", "kw"), (r"'[^']*'|\"[^\"]*\"", "str")):
            for m in re.finditer(pat, txt):
                if tag != "kw" or m.group().lower() in KEYWORDS: ed.tag_add(tag, f"1.0+{m.start()}c", f"1.0+{m.end()}c")

    def cur(): return tabs[nb.index(nb.select())]

    def render(tab):
        g = tab["grid"]; g.delete(*g.get_children()); rows, needle = tab["rows"], tab["fv"].get().lower()
        idx = [i for i in range(len(rows)) if not needle or any(needle in str(v).lower() for v in rows[i])]
        if tab["sort"]: c, rev = tab["sort"]; idx.sort(key=lambda i: sortkey(rows[i][c]), reverse=rev)
        for ci, name in enumerate(tab["cols"]):
            arrow = (" ▼" if tab["sort"][1] else " ▲") if tab["sort"] and tab["sort"][0] == ci else ""
            g.heading(f"c{ci}", text=name + arrow, command=lambda ci=ci: sort_by(tab, ci))
        for n, i in enumerate(idx[:5000]):
            g.insert("", "end", iid=str(i), values=["NULL" if v is None else v for v in rows[i]], tags=("odd",) if n % 2 else ())
        tab["info"].config(text=f'{tab["msg"]}   showing {min(len(idx), 5000)} of {len(rows)}' if tab["cols"] else tab["msg"])
    def sort_by(tab, ci):
        tab["sort"] = (ci, not tab["sort"][1]) if tab["sort"] and tab["sort"][0] == ci else (ci, False); render(tab)
    def set_result(tab, cols, rows, edit, msg):
        g = tab["grid"]; tab.update(cols=cols, rows=rows, edit=edit, sort=None, msg=msg)
        g["columns"] = [f"c{i}" for i in range(len(cols))]
        for i in range(len(cols)): g.column(f"c{i}", width=130, stretch=False)
        render(tab)

    def new_tab(sql="", ctx=None):
        f = ttk.Frame(nb); pw = ttk.PanedWindow(f, orient="vertical"); pw.pack(fill="both", expand=True)
        ed = tk.Text(f, height=9, undo=True, font=("DejaVu Sans Mono", 11), relief="flat", padx=6, pady=4); ed.insert("1.0", sql)
        res = ttk.Frame(f); bar = ttk.Frame(res); bar.pack(fill="x"); fv = tk.StringVar()
        ttk.Label(bar, text="Filter").pack(side="left", padx=4); ttk.Entry(bar, textvariable=fv, width=28).pack(side="left")
        info = ttk.Label(bar, text=""); info.pack(side="right", padx=6)
        g = ttk.Treeview(res, show="headings", selectmode="extended")
        ys = ttk.Scrollbar(res, command=g.yview); xs = ttk.Scrollbar(res, orient="horizontal", command=g.xview)
        g.configure(yscrollcommand=ys.set, xscrollcommand=xs.set)
        xs.pack(side="bottom", fill="x"); ys.pack(side="right", fill="y"); g.pack(fill="both", expand=True)
        pw.add(ed, weight=1); pw.add(res, weight=2)
        tab = dict(f=f, ed=ed, grid=g, info=info, fv=fv, cols=[], rows=[], ctx=ctx, gen=sql if ctx else None, edit=None, sort=None, msg="")
        fv.trace_add("write", lambda *a: render(tab))
        ed.bind("<KeyRelease>", lambda e: highlight(ed)); ed.bind("<F9>", lambda e: execute()); ed.bind("<Control-Return>", lambda e: execute())
        ed.bind("<Control-space>", lambda e: (complete(ed), "break")[1])
        g.bind("<Double-1>", lambda e: edit_cell(tab, e)); g.bind("<Control-c>", lambda e: copy_rows())
        nb.add(f, text=f"Query {len(tabs)+1}"); tabs.append(tab); nb.select(f); style_tab(tab); highlight(ed); return tab
    def close_tab(_=None):
        if len(tabs) > 1: i = nb.index(nb.select()); nb.forget(i); tabs.pop(i)

    def complete(ed):
        pre = re.search(r"[\w$]*$", ed.get("insert linestart", "insert")).group(); c = suggest(pre, S["names"])
        if not c: return
        def put(ch): ed.delete(f"insert-{len(pre)}c", "insert"); ed.insert("insert", ch); ed.focus_set()
        if len(c) == 1: return put(c[0])
        lb = tk.Listbox(ed, height=min(8, len(c)), exportselection=False); [lb.insert("end", w) for w in c]
        x, y, _w, h = ed.bbox("insert"); lb.place(x=x, y=y + h); lb.selection_set(0); lb.activate(0); lb.focus_set()
        def accept(_=None): ch = lb.get(lb.curselection()[0]); lb.destroy(); put(ch)
        lb.bind("<Return>", accept); lb.bind("<Double-1>", accept)
        lb.bind("<Escape>", lambda e: (lb.destroy(), ed.focus_set())); lb.bind("<FocusOut>", lambda e: lb.destroy())

    def edit_cell(tab, ev):
        g = tab["grid"]
        if g.identify_region(ev.x, ev.y) != "cell": return
        item, col = g.identify_row(ev.y), g.identify_column(ev.x)
        if not tab["edit"]: return status.set("Read-only result. Double-click a table in the tree to open it editable.")
        db, t, pk = tab["edit"]; ci = int(col[1:]) - 1
        if not pk or any(p not in tab["cols"] for p in pk): return status.set("Cannot edit: no primary key available.")
        x, y, w, h = g.bbox(item, col); row = tab["rows"][int(item)]
        e = ttk.Entry(g); e.insert(0, "NULL" if row[ci] is None else str(row[ci])); e.place(x=x, y=y, width=w, height=h)
        e.focus_set(); e.select_range(0, "end")
        def commit(_=None):
            val = e.get(); val = None if val == "NULL" else val
            try:
                n = S["db"].update_cell(db, t, pk, dict(zip(tab["cols"], row)), tab["cols"][ci], val)
                row[ci] = val; g.set(item, f"c{ci}", "NULL" if val is None else val); status.set(f"Updated {n} row(s)")
            except Exception as ex: status.set("Error: " + str(ex))
            e.destroy()
        e.bind("<Return>", commit); e.bind("<Escape>", lambda _: e.destroy())
        e.bind("<FocusOut>", lambda _: e.winfo_exists() and e.destroy())

    def execute(explain=False):
        if not S["db"]: return status.set("Connect first.")
        tab = cur(); ed = tab["ed"]
        try: sql = ed.get("sel.first", "sel.last")
        except tk.TclError: sql = ed.get("1.0", "end")
        stmts = split_statements(sql)
        if not stmts: return
        edit = tab["ctx"] if tab["ctx"] and sql.strip() == (tab["gen"] or "").strip() else None
        if explain: stmts[-1] = ("EXPLAIN QUERY PLAN " if S["db"].kind == "sqlite" else "EXPLAIN ") + stmts[-1]; edit = None
        run_btn.config(state="disabled"); status.set("Running...")
        def work():
            t0 = time.time()
            try:
                last = None
                for s in stmts: last = S["db"].run(s)
                results.put((tab, "ok", last, time.time() - t0, stmts, edit))
            except Exception as ex: results.put((tab, "err", str(ex), 0, stmts, None))
        threading.Thread(target=work, daemon=True).start(); root.after(40, poll)
    def poll():
        try: tab, kind, data, dt, stmts, edit = results.get_nowait()
        except queue.Empty: return root.after(40, poll)
        run_btn.config(state="normal")
        if kind == "err": return status.set("Error: " + data)
        cols, rows, n = data; msg = f"{len(rows)} row(s)" if cols else f"OK, {n} row(s) affected"
        set_result(tab, cols, rows, edit, msg); status.set(f"{msg} in {dt*1000:.0f} ms")
        st["history"] = (st["history"] + stmts)[-500:]
        if not cols and re.match(r"\s*(create|drop|alter)\b", stmts[-1], re.I): populate()

    def learn(db):
        for t in S["db"].tables(db): S["names"].add(t); S["names"].update(c for c, _ in S["db"].columns(db, t))
    def populate():
        tree.delete(*tree.get_children())
        for d in S["db"].databases():
            n = tree.insert("", "end", text=d, values=("db", d)); tree.insert(n, "end", text="...")
            if S["db"].kind == "sqlite": S["names"].add(d); learn(d)
    def expand(_):
        n = tree.focus(); v = tree.item(n, "values"); kids = tree.get_children(n)
        if not v or not kids or tree.item(kids[0], "text") != "...": return
        tree.delete(*kids)
        try:
            if v[0] == "db":
                for t in S["db"].tables(v[1]): S["names"].add(t); tree.insert(tree.insert(n, "end", text=t, values=("table", v[1], t)), "end", text="...")
            elif v[0] == "table":
                for c, typ in S["db"].columns(v[1], v[2]): S["names"].add(c); tree.insert(n, "end", text=f"{c}  ({typ})", values=("col",))
        except Exception as ex: status.set("Error: " + str(ex))
    def open_table(_):
        v = tree.item(tree.focus(), "values")
        if v and v[0] == "table":
            try: pk = S["db"].pk(v[1], v[2])
            except Exception: pk = []
            new_tab(S["db"].select_all(v[1], v[2]), (v[1], v[2], pk)); execute()
    tree.bind("<<TreeviewOpen>>", expand); tree.bind("<Double-1>", open_table)

    def connect():
        d = tk.Toplevel(root); d.title("Connect"); F = {}; kind = tk.StringVar(value="mysql"); save = tk.BooleanVar(value=True)
        saved = ttk.Combobox(d, values=[c["name"] for c in st["connections"]], state="readonly"); saved.grid(row=0, column=0, columnspan=2, padx=6, pady=4, sticky="ew")
        row = ttk.Frame(d); row.grid(row=1, column=0, columnspan=2)
        for k in ("mysql", "sqlite"): ttk.Radiobutton(row, text=k, value=k, variable=kind).pack(side="left", padx=6)
        for i, (lab, df) in enumerate((("host", "127.0.0.1"), ("port", "3306"), ("user", "root"), ("password", ""), ("file", ""))):
            ttk.Label(d, text=lab).grid(row=i+2, column=0, sticky="e"); e = ttk.Entry(d, show="*" if lab == "password" else "")
            e.insert(0, df); e.grid(row=i+2, column=1, padx=6, pady=2); F[lab] = e
        def pick(_):
            c = next(x for x in st["connections"] if x["name"] == saved.get()); kind.set(c["kind"])
            for k in ("host", "port", "user", "file"): F[k].delete(0, "end"); F[k].insert(0, c.get(k, ""))
        saved.bind("<<ComboboxSelected>>", pick)
        ttk.Checkbutton(d, text="Remember (without password)", variable=save).grid(row=7, columnspan=2)
        def go():
            try:
                S["db"] = DB("sqlite", path=F["file"].get()) if kind.get() == "sqlite" else DB("mysql", host=F["host"].get(), port=F["port"].get(), user=F["user"].get(), password=F["password"].get())
            except Exception as ex: return messagebox.showerror("Connection failed", str(ex), parent=d)
            if save.get():
                name = F["file"].get() if kind.get() == "sqlite" else f'{F["user"].get()}@{F["host"].get()}'
                st["connections"] = [c for c in st["connections"] if c["name"] != name] + [dict(name=name, kind=kind.get(), host=F["host"].get(), port=F["port"].get(), user=F["user"].get(), file=F["file"].get())]
            status.set("Connected: " + S["db"].label); populate(); d.destroy()
        ttk.Button(d, text="Connect", command=go).grid(row=8, columnspan=2, pady=6)

    def export(kind):
        tab = cur(); p = filedialog.asksaveasfilename(defaultextension="." + kind)
        if not p: return
        with open(p, "w", newline="") as f:
            if kind == "csv": w = csv.writer(f); w.writerow(tab["cols"]); w.writerows(tab["rows"])
            else: json.dump([dict(zip(tab["cols"], r)) for r in tab["rows"]], f, indent=1, default=str)
    def copy_rows():
        tab = cur(); sel = tab["grid"].selection()
        root.clipboard_clear(); root.clipboard_append("\n".join("\t".join("NULL" if v is None else str(v) for v in tab["rows"][int(i)]) for i in sel))
        status.set(f"Copied {len(sel)} row(s)")
    def fmt(): ed = cur()["ed"]; s = format_sql(ed.get("1.0", "end")); ed.delete("1.0", "end"); ed.insert("1.0", s); highlight(ed)
    def history():
        h = tk.Toplevel(root); h.title("History"); lb = tk.Listbox(h, width=100, height=20); lb.pack(fill="both", expand=True)
        items = st["history"][::-1]; [lb.insert("end", s.replace("\n", " ")) for s in items]
        lb.bind("<Double-1>", lambda e: (cur()["ed"].insert("end", "\n" + items[lb.curselection()[0]] + ";"), h.destroy()))
    def quit_app():
        st["tabs"] = [t["ed"].get("1.0", "end").rstrip() for t in tabs]
        try: save_state(st)
        except OSError: pass
        root.destroy()

    run_btn = ttk.Button(tool, text="▶ Run (F9)", command=execute)
    for b in (ttk.Button(tool, text="Connect", command=connect), run_btn, ttk.Button(tool, text="Explain", command=lambda: execute(True)),
              ttk.Button(tool, text="Format", command=fmt), ttk.Button(tool, text="New tab", command=lambda: new_tab()),
              ttk.Button(tool, text="History", command=history), ttk.Button(tool, text="Dark/Light", command=toggle_theme)):
        b.pack(side="left", padx=2)
    mb = tk.Menu(root); fm = tk.Menu(mb, tearoff=0)
    for lab, fn in (("Connect...", connect), ("Export results as CSV...", lambda: export("csv")), ("Export results as JSON...", lambda: export("json")), ("Quit", quit_app)):
        fm.add_command(label=lab, command=fn)
    mb.add_cascade(label="File", menu=fm); root.config(menu=mb)
    root.bind("<Control-t>", lambda e: new_tab()); root.bind("<Control-w>", close_tab); root.bind("<Control-Shift-F>", lambda e: fmt())
    root.protocol("WM_DELETE_WINDOW", quit_app); apply_theme()
    for s in st["tabs"] or ["-- F9 / Ctrl+Enter: run | Ctrl+Space: autocomplete | double-click a table in the tree to browse and edit\n"]: new_tab(s)
    root.mainloop()

if __name__ == "__main__":
    run_gui()
