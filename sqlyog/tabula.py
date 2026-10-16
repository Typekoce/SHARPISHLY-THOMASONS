#!/usr/bin/env python3
"""Tabula - a small original SQL client for MySQL/MariaDB and SQLite.
Pure Python 3 + Tkinter. MySQL support needs PyMySQL (sudo apt install python3-pymysql)."""
import sqlite3, csv, re, sys

KEYWORDS = ("select insert update delete from where join left right inner outer on group by order having limit "
            "create alter drop table index view database values set into as and or not null like in is distinct "
            "union show describe use primary key default auto_increment").split()

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

class DB:
    def __init__(self, kind, **kw):
        self.kind = kind
        if kind == "sqlite":
            self.conn = sqlite3.connect(kw["path"]); self.label = kw["path"]
        else:
            import pymysql
            self.conn = pymysql.connect(host=kw["host"], port=int(kw.get("port") or 3306), user=kw["user"],
                                        password=kw.get("password", ""), autocommit=True, connect_timeout=8)
            self.label = f'{kw["user"]}@{kw["host"]}'
    def q(self, ident): return '"%s"' % ident.replace('"', '""') if self.kind == "sqlite" else "`%s`" % ident.replace("`", "``")
    def run(self, sql):
        cur = self.conn.cursor(); cur.execute(sql)
        if cur.description:
            return [d[0] for d in cur.description], [list(r) for r in cur.fetchall()], None
        if self.kind == "sqlite": self.conn.commit()
        return [], [], cur.rowcount
    def databases(self):
        return ["main"] if self.kind == "sqlite" else [r[0] for r in self.run("SHOW DATABASES")[1]]
    def tables(self, db):
        if self.kind == "sqlite":
            return [r[0] for r in self.run("SELECT name FROM sqlite_master WHERE type IN ('table','view') ORDER BY name")[1]]
        return [r[0] for r in self.run(f"SHOW TABLES FROM {self.q(db)}")[1]]
    def columns(self, db, t):
        if self.kind == "sqlite":
            return [(r[1], r[2]) for r in self.run(f"PRAGMA table_info({self.q(t)})")[1]]
        return [(r[0], r[1]) for r in self.run(f"SHOW COLUMNS FROM {self.q(db)}.{self.q(t)}")[1]]
    def select_all(self, db, t):
        name = self.q(t) if self.kind == "sqlite" else f"{self.q(db)}.{self.q(t)}"
        return f"SELECT * FROM {name} LIMIT 1000;"

def run_gui():
    import tkinter as tk
    from tkinter import ttk, simpledialog, filedialog, messagebox
    root = tk.Tk(); root.title("Tabula"); root.geometry("1100x700")
    S = {"db": None, "history": []}

    paned = ttk.PanedWindow(root, orient="horizontal"); paned.pack(fill="both", expand=True)
    tree = ttk.Treeview(paned, show="tree"); paned.add(tree, weight=1)
    nb = ttk.Notebook(paned); paned.add(nb, weight=4)
    status = tk.StringVar(value="Not connected"); ttk.Label(root, textvariable=status, anchor="w").pack(fill="x")

    tabs = []
    def new_tab(sql=""):
        f = ttk.Frame(nb); pw = ttk.PanedWindow(f, orient="vertical"); pw.pack(fill="both", expand=True)
        ed = tk.Text(f, height=10, undo=True, font=("monospace", 11)); ed.insert("1.0", sql)
        ed.tag_configure("kw", foreground="#1a56c4"); ed.tag_configure("str", foreground="#b5541c")
        grid = ttk.Treeview(f, show="headings"); sb = ttk.Scrollbar(f, command=grid.yview); grid.configure(yscroll=sb.set)
        rf = ttk.Frame(f); grid.pack(in_=rf, side="left", fill="both", expand=True); sb.pack(in_=rf, side="right", fill="y")
        pw.add(ed, weight=1); pw.add(rf, weight=2)
        def highlight(_=None):
            for t in ("kw", "str"): ed.tag_remove(t, "1.0", "end")
            txt = ed.get("1.0", "end")
            for m in re.finditer(r"'[^']*'|\"[^\"]*\"", txt): ed.tag_add("str", f"1.0+{m.start()}c", f"1.0+{m.end()}c")
            for m in re.finditer(r"\b\w+\b", txt):
                if m.group().lower() in KEYWORDS: ed.tag_add("kw", f"1.0+{m.start()}c", f"1.0+{m.end()}c")
        ed.bind("<KeyRelease>", highlight); highlight()
        ed.bind("<F9>", lambda e: execute()); ed.bind("<Control-Return>", lambda e: execute())
        nb.add(f, text=f"Query {len(tabs)+1}"); nb.select(f); tabs.append((ed, grid))
        return ed, grid
    def cur(): return tabs[nb.index(nb.select())]

    def show(grid, cols, rows):
        grid.delete(*grid.get_children()); grid["columns"] = cols
        for c in cols: grid.heading(c, text=c); grid.column(c, width=120, stretch=False)
        for r in rows[:5000]: grid.insert("", "end", values=["NULL" if v is None else v for v in r])

    def execute():
        if not S["db"]: return messagebox.showinfo("Tabula", "Connect first (File > Connect).")
        ed, grid = cur()
        try: sql = ed.get("sel.first", "sel.last")
        except tk.TclError: sql = ed.get("1.0", "end")
        last = None
        try:
            for stmt in split_statements(sql):
                S["history"].append(stmt); cols, rows, n = S["db"].run(stmt)
                last = (cols, rows, n)
        except Exception as e:
            return status.set("Error: " + str(e))
        if last:
            show(grid, last[0], last[1])
            status.set(f"{len(last[1])} row(s)" if last[0] else f"OK, {last[2]} row(s) affected")

    def populate():
        tree.delete(*tree.get_children())
        for d in S["db"].databases():
            n = tree.insert("", "end", text=d, values=("db", d), open=False); tree.insert(n, "end", text="...")
    def expand(_):
        n = tree.focus(); v = tree.item(n, "values")
        if not v or tree.get_children(n) and tree.item(tree.get_children(n)[0], "text") != "...": return
        tree.delete(*tree.get_children(n))
        try:
            if v[0] == "db":
                for t in S["db"].tables(v[1]):
                    c = tree.insert(n, "end", text=t, values=("table", v[1], t)); tree.insert(c, "end", text="...")
            elif v[0] == "table":
                for name, typ in S["db"].columns(v[1], v[2]): tree.insert(n, "end", text=f"{name}  ({typ})", values=("col",))
        except Exception as e: status.set("Error: " + str(e))
    def dbl(_):
        v = tree.item(tree.focus(), "values")
        if v and v[0] == "table":
            ed, _g = new_tab(S["db"].select_all(v[1], v[2])); execute()
    tree.bind("<<TreeviewOpen>>", expand); tree.bind("<Double-1>", dbl)

    def connect():
        d = tk.Toplevel(root); d.title("Connect"); fields = {}
        kind = tk.StringVar(value="mysql"); row = ttk.Frame(d); row.grid(columnspan=2, pady=4)
        for k in ("mysql", "sqlite"): ttk.Radiobutton(row, text=k, value=k, variable=kind).pack(side="left", padx=6)
        for i, (lab, dflt) in enumerate((("host", "127.0.0.1"), ("port", "3306"), ("user", "root"), ("password", ""), ("sqlite file", ""))):
            ttk.Label(d, text=lab).grid(row=i+1, column=0, sticky="e"); e = ttk.Entry(d, show="*" if lab == "password" else "")
            e.insert(0, dflt); e.grid(row=i+1, column=1, padx=4, pady=2); fields[lab] = e
        def go():
            try:
                S["db"] = DB("sqlite", path=fields["sqlite file"].get()) if kind.get() == "sqlite" else \
                    DB("mysql", host=fields["host"].get(), port=fields["port"].get(), user=fields["user"].get(), password=fields["password"].get())
            except Exception as e: return messagebox.showerror("Connection failed", str(e), parent=d)
            status.set("Connected: " + S["db"].label); populate(); d.destroy()
        ttk.Button(d, text="Connect", command=go).grid(columnspan=2, pady=6)
    def export():
        _e, grid = cur(); p = filedialog.asksaveasfilename(defaultextension=".csv")
        if p:
            with open(p, "w", newline="") as f:
                w = csv.writer(f); w.writerow(grid["columns"])
                for i in grid.get_children(): w.writerow(grid.item(i, "values"))
    def history():
        h = tk.Toplevel(root); h.title("History"); lb = tk.Listbox(h, width=90, height=20); lb.pack(fill="both", expand=True)
        for s in reversed(S["history"]): lb.insert("end", s.replace("\n", " "))
        lb.bind("<Double-1>", lambda e: (cur()[0].insert("end", "\n" + S["history"][::-1][lb.curselection()[0]] + ";"), h.destroy()))

    mb = tk.Menu(root); fm = tk.Menu(mb, tearoff=0)
    fm.add_command(label="Connect...", command=connect); fm.add_command(label="New query tab", command=lambda: new_tab(), accelerator="Ctrl+T")
    fm.add_command(label="Execute (F9)", command=execute); fm.add_command(label="Export results to CSV...", command=export)
    fm.add_command(label="History...", command=history); fm.add_separator(); fm.add_command(label="Quit", command=root.destroy)
    mb.add_cascade(label="File", menu=fm); root.config(menu=mb); root.bind("<Control-t>", lambda e: new_tab())
    new_tab("-- F9 or Ctrl+Enter runs the selection (or everything)\n"); root.mainloop()

if __name__ == "__main__":
    run_gui()
