# Tabula 2

A lightweight desktop SQL client for MySQL/MariaDB and SQLite on Linux. Browse databases, run queries in tabs, edit data directly in the results grid, and export results.

Tabula 2 is original software written in Python with Tkinter.

## Features

- **Connections:** MySQL/MariaDB (host, port, user, password) and SQLite files, with saved connection profiles
- **Object browser:** a lazy-loading tree of databases, tables and columns with their types
- **Query editor:** multiple tabs, keyword/string/number highlighting, autocomplete, and a SQL formatter
- **Run selection or all:** runs the selected text, or the whole editor if nothing is selected. Multiple statements are supported, and the last result is shown
- **Editable grid:** double-click a cell to edit it. The change is saved to the database immediately
- **Sort and filter:** click a column header to sort, and use the Filter box to search every column
- **Background execution:** queries run in a separate thread, so the window stays responsive, and run time is shown
- **Explain:** one click to run `EXPLAIN` on the current query
- **Export and copy:** CSV and JSON export, and Ctrl+C to copy selected rows
- **History:** the last 500 statements, click to reinsert
- **Dark and light themes**
- **Session restore:** open tabs and history return at the next start

## Requirements

- Linux (developed for Ubuntu)
- Python 3.8 or newer
- Tkinter
- PyMySQL, only if you want MySQL/MariaDB (SQLite works without it)

On Ubuntu/Debian:

```sh
sudo apt install python3-tk python3-pymysql
```

MySQL 8 servers using `caching_sha2_password` (the default) also need:

```sh
sudo apt install python3-cryptography
```

## Installation

From this folder:

```sh
./install.sh
```

This installs for the current user only (no root needed):

| What | Where |
|---|---|
| Program | `~/.local/share/tabula2/tabula2.py` |
| Command | `~/.local/bin/tabula2` |
| Menu entry | `~/.local/share/applications/tabula2.desktop` |

Make sure `~/.local/bin` is on your `PATH`. You can also run it without installing:

```sh
python3 tabula2.py
```

## Usage

### Connecting

Click **Connect**. Choose `mysql` or `sqlite`, fill in the details and click **Connect**. If **Remember** is ticked, the profile is saved and appears in the drop-down next time. **Passwords are never saved.**

### Browsing

Expand databases and tables in the tree on the left. Double-click a table to open it in a new tab, showing up to 1,000 rows.

### Querying

Type SQL in the editor and press **F9** or **Ctrl+Enter**.

### Editing data

Double-click a cell in the results grid, change the value and press **Enter** (**Esc** cancels). Type `NULL` to set a null value.

Editing is only available when:

- the tab was opened by double-clicking a table in the tree,
- the query is still the unchanged `SELECT * ...` that Tabula generated, and
- the table has a primary key.

Results from any other query are read-only. This prevents an edit from being applied to the wrong row or table.

> **Edits are committed immediately.** There is no undo or pending-changes step, so take care on production databases.

### Keyboard shortcuts

| Shortcut | Action |
|---|---|
| F9 / Ctrl+Enter | Run selection, or everything |
| Ctrl+Space | Autocomplete |
| Ctrl+T | New query tab |
| Ctrl+W | Close tab |
| Ctrl+Shift+F | Format SQL |
| Ctrl+C (in grid) | Copy selected rows |

### Autocomplete

Autocomplete suggests SQL keywords plus the table and column names Tabula has seen. For SQLite, the whole schema is loaded on connect. For MySQL, names are loaded as you expand databases and tables in the tree.

## SSH tunnelling

Built-in SSH tunnelling is not included yet. As a workaround, open a tunnel in a terminal:

```sh
ssh -N -L 3307:127.0.0.1:3306 user@your-server
```

Then connect Tabula 2 to host `127.0.0.1`, port `3307`, with your normal database credentials.

## Data and settings

Settings are stored in `~/.config/tabula/state.json`. It holds the theme, saved connection profiles (without passwords), open tab text and statement history. Delete the file to reset everything. Note that history and tab text can contain sensitive SQL.

## Limits

- At most 5,000 rows are displayed per result (all rows are still exported)
- History keeps the last 500 statements
- When several statements run together, only the last result is shown

## Not included

Visual table designer, SSH tunnelling, schema and data synchronisation, backup and restore, stored procedure editing, and user management.

## Troubleshooting

| Problem | Fix |
|---|---|
| `Missing Tkinter` during install | `sudo apt install python3-tk` |
| `No module named pymysql` | `sudo apt install python3-pymysql` |
| MySQL 8 authentication error | `sudo apt install python3-cryptography` |
| Connection refused or timed out | Check host, port, firewall, and that the server allows remote connections |

## Uninstall

```sh
rm -rf ~/.local/share/tabula2
rm -f ~/.local/bin/tabula2 ~/.local/share/applications/tabula2.desktop
rm -rf ~/.config/tabula   # optional: removes saved settings and history
```

## Status and licence

The database logic (SQL splitting, formatting, autocomplete suggestions, primary-key detection, cell updates, explain, sorting and settings) has been tested against SQLite. The Tkinter window and the MySQL connection path were written without a display or a MySQL server available for testing, so please report any problems you find on first run.

No licence has been chosen yet. Add one before distributing.

Tabula 2 is an independent project and is not affiliated with or derived from any commercial database tool.