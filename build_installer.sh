#!/usr/bin/env bash
set -euo pipefail

OUTPUT_FILE="./final_installation.sh"
INSTALL_DIR="./installation"

if [[ ! -d "$INSTALL_DIR" ]]; then
    echo "Error: Directory '$INSTALL_DIR' not found." >&2
    exit 1
fi

echo "Building $OUTPUT_FILE from $INSTALL_DIR/..."

# Initialize header block safely
{
    printf '#!/usr/bin/env bash\n'
    printf '# Auto-generated consolidated installation script\n'
    printf '# Generated on %s\n\n' "$(date)"
    printf 'set -euo pipefail\n\n'
} > "$OUTPUT_FILE"

# Safely capture sorted file list without whitespace splitting
mapfile -t files < <(find "$INSTALL_DIR" -maxdepth 1 -type f -name '*.sh' | sort -V)

if [[ ${#files[@]} -eq 0 ]]; then
    echo "Warning: No .sh files found in '$INSTALL_DIR'." >&2
    exit 0
fi

for file in "${files[@]}"; do
    echo "Appending: $file"
    
    printf '\n# --- Start of %s ---\n' "$(basename "$file")" >> "$OUTPUT_FILE"
    
    # Strip redundant shebangs and set options from child scripts
    sed -E \
        -e '/^#! *\/usr\/bin\/env bash$/d' \
        -e '/^#! *\/bin\/bash$/d' \
        -e '/^set -[a-zA-Z]+/d' \
        "$file" >> "$OUTPUT_FILE"
        
    printf '\n# --- End of %s ---\n' "$(basename "$file")" >> "$OUTPUT_FILE"
done

chmod +x "$OUTPUT_FILE"

echo "Done! Consolidated installer created at: $OUTPUT_FILE"
