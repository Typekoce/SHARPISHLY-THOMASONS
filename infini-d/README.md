# Lumen3D

A small 3D scene editor with keyframe animation for Linux. Add primitives, position them, set keyframes on a timeline, preview the animation, and export to OBJ.

Lumen3D is original software written in pure Python with Tkinter. It has no dependencies beyond Python itself.

## Features

- **Primitives:** cube, sphere, cylinder, cone and plane
- **Transform editor:** position, rotation (degrees) and scale for every object, plus a colour picker
- **Viewport:** flat-shaded rendering with orbit and zoom
- **Keyframe animation:** a 0–100 frame timeline with per-object keyframes and linear interpolation
- **Playback:** preview the animation in the viewport
- **File support:** save and load scenes as `.l3d`, and export the current frame as Wavefront OBJ

## Requirements

- Linux (developed for Ubuntu)
- Python 3.8 or newer
- Tkinter

On Ubuntu/Debian:

```sh
sudo apt install python3-tk
```

Fedora: `sudo dnf install python3-tkinter` · Arch: `sudo pacman -S tk`

## Installation

From this folder:

```sh
./install.sh
```

This installs for the current user only (no root needed):

| What | Where |
|---|---|
| Program | `~/.local/share/lumen3d/lumen3d.py` |
| Command | `~/.local/bin/lumen3d` |
| Menu entry | `~/.local/share/applications/lumen3d.desktop` |

Make sure `~/.local/bin` is on your `PATH`. You can also run the program without installing:

```sh
python3 lumen3d.py
```

## Usage

### Viewport

| Action | Control |
|---|---|
| Orbit the camera | Drag with the left mouse button |
| Zoom | Mouse wheel |

### Building a scene

1. Click one of the **Add** buttons to create an object. It appears in the object list.
2. Select an object in the list. Its values appear in the Pos, Rot and Scale fields.
3. Edit the numbers and click **Apply to current frame**.
4. Use **Colour...** to change its colour, or **Delete object** to remove it.

### Animating

1. Move the timeline slider to a frame.
2. Change an object's values and click **Set key** (or **Apply to current frame**). This stores a keyframe for that object at that frame.
3. Move to another frame, change the values again and set another key.
4. Press **Play** to preview. Between keys, values are interpolated linearly. Before the first key and after the last, the object holds its nearest key.

Use **Del key** to remove the key at the current frame. An object always keeps at least one key.

Each object is animated independently. Position, rotation and scale are all keyed together.

### Files

All file actions are in the **File** menu.

- **Open / Save:** `.l3d` is a plain JSON file containing every object, its colour and its keyframes.
- **Export current frame as OBJ:** writes the geometry as it appears at the current frame. Colours and animation are not included.

## Notes

- Rotation is applied as Euler angles in degrees, in Z·Y·X order.
- Cubes, spheres, cylinders and cones are about one unit in size. The plane is two units across.
- Rendering is a software painter's algorithm, so it suits simple scenes. Intersecting objects may sort incorrectly.

## Limitations

Not included: ray tracing, lights and shadows, textures, a parent/child hierarchy, extrude or lathe tools, and video export. If you want an animation file, export frames as OBJ and render them elsewhere, for example in Blender.

## Uninstall

```sh
rm -rf ~/.local/share/lumen3d
rm -f ~/.local/bin/lumen3d ~/.local/share/applications/lumen3d.desktop
```

## Status and licence

The scene logic (geometry, interpolation, save/load, OBJ export) has been tested. The Tkinter window was written without a display available for testing, so please report any problems you find on first run.

No licence has been chosen yet. Add one before distributing.

Lumen3D is an independent project and is not affiliated with or derived from any commercial 3D software.