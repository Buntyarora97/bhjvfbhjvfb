#!/usr/bin/env bash
# Convert all jpg/jpeg/png images in given folders to .webp
# Move originals to unused_images_backup/ preserving folder structure.
# Skips creating .webp if a matching .webp already exists.
#
# Usage: tools/convert_to_webp.sh folder1 folder2 ...

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="$ROOT_DIR/unused_images_backup"
QUALITY="${WEBP_QUALITY:-80}"
JOBS="${PARALLEL_JOBS:-4}"
LOG_FILE="$ROOT_DIR/tools/convert_to_webp.log"

mkdir -p "$BACKUP_DIR"
: > "$LOG_FILE"

if [ "$#" -eq 0 ]; then
    echo "Usage: $0 <folder> [folder ...]"
    exit 1
fi

process_one() {
    local img="$1"
    local quality="$2"
    local root_dir="$3"
    local backup_dir="$4"
    local log_file="$5"

    [ -f "$img" ] || { echo "SKIP_NOFILE $img" >>"$log_file"; return 0; }

    local webp="${img%.*}.webp"
    local rel="${img#${root_dir}/}"
    local backup_path="${backup_dir}/${rel}"
    local backup_dirname
    backup_dirname="$(dirname "$backup_path")"
    mkdir -p "$backup_dirname"

    if [ ! -f "$webp" ]; then
        if magick "$img" -strip -quality "$quality" -define webp:method=4 "$webp" >/dev/null 2>&1; then
            echo "CONVERTED $img -> $webp" >>"$log_file"
        else
            echo "FAILED    $img" >>"$log_file"
            return 0
        fi
    else
        echo "EXISTS    $webp" >>"$log_file"
    fi

    if mv -f "$img" "$backup_path" 2>/dev/null; then
        echo "MOVED     $img -> $backup_path" >>"$log_file"
    else
        echo "MV_FAIL   $img" >>"$log_file"
    fi
    return 0
}

export -f process_one

TMP_LIST="$(mktemp)"
for d in "$@"; do
    if [ -d "$d" ]; then
        find "$d" -type f \
            \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" \) \
            -not -path "${BACKUP_DIR}/*" \
            -print0 >> "$TMP_LIST"
    fi
done

TOTAL=$(tr -cd '\0' < "$TMP_LIST" | wc -c)
echo "Found $TOTAL image(s) to process"
echo "Backup folder: $BACKUP_DIR"
echo "WebP quality: $QUALITY  Jobs: $JOBS"

if [ "$TOTAL" -eq 0 ]; then
    rm -f "$TMP_LIST"
    echo "Nothing to do"
    exit 0
fi

# Process via xargs in parallel; subshell uses bash -c with positional params
xargs -0 -P "$JOBS" -I{} bash -c 'process_one "$1" "$2" "$3" "$4" "$5"' _ {} "$QUALITY" "$ROOT_DIR" "$BACKUP_DIR" "$LOG_FILE" \
    < "$TMP_LIST"

rm -f "$TMP_LIST"

echo ""
echo "=== Summary ==="
echo "Converted:           $(grep -c '^CONVERTED' "$LOG_FILE")"
echo "WebP existed (skip): $(grep -c '^EXISTS'    "$LOG_FILE")"
echo "Moved to backup:     $(grep -c '^MOVED'     "$LOG_FILE")"
echo "Failed conversions:  $(grep -c '^FAILED'    "$LOG_FILE")"
echo "Failed moves:        $(grep -c '^MV_FAIL'   "$LOG_FILE")"
echo "Full log: $LOG_FILE"
