#!/usr/bin/env bash
# ============================================
# Optimize images: convert to WebP + create resized variants
# Requires: ImageMagick (convert/magick) + cwebp (optional, fallback to magick)
# Usage: bash scripts/optimize_images.sh
# ============================================

set -euo pipefail

IMG_DIR="$(cd "$(dirname "$0")/../public/img" && pwd)"
BLOG_DIR="$IMG_DIR/blog"
QUALITY=80

echo "=== Image Optimization ==="
echo "Directory: $IMG_DIR"

# Check for ImageMagick
if ! command -v magick &>/dev/null && ! command -v convert &>/dev/null; then
    echo "ERROR: ImageMagick not found. Install with: sudo apt install imagemagick"
    exit 1
fi

# Use magick if available, else convert
CONVERT="convert"
command -v magick &>/dev/null && CONVERT="magick"

# --- Helper: convert to WebP ---
to_webp() {
    local src="$1"
    local dst="${src%.*}.webp"
    if [[ ! -f "$dst" ]] || [[ "$src" -nt "$dst" ]]; then
        $CONVERT "$src" -quality $QUALITY "$dst"
        echo "  WebP: $(basename "$dst")"
    fi
}

# --- Helper: resize + WebP ---
resize_webp() {
    local src="$1"
    local width="$2"
    local suffix="$3"
    local ext="${src##*.}"
    local base="${src%.*}"
    local dst_orig="${base}-${suffix}.${ext}"
    local dst_webp="${base}-${suffix}.webp"

    if [[ ! -f "$dst_webp" ]] || [[ "$src" -nt "$dst_webp" ]]; then
        $CONVERT "$src" -resize "${width}x>" -quality $QUALITY "$dst_orig"
        $CONVERT "$dst_orig" -quality $QUALITY "$dst_webp"
        echo "  Resized: $(basename "$dst_webp") (${width}px)"
    fi
}

# --- Main images ---
echo ""
echo "--- Main images ---"
for img in "$IMG_DIR"/*.{jpg,png}; do
    [[ -f "$img" ]] || continue
    basename="$(basename "$img")"

    # Skip OG image (already optimized for social)
    [[ "$basename" == "og-default.jpg" ]] && continue

    to_webp "$img"

    # Service card images: create 550px variant
    if [[ "$basename" == service-*.jpg ]]; then
        resize_webp "$img" 550 "550"
    fi

    # Logo: create 88px (2x of 44px display) variant
    if [[ "$basename" == "logo.png" ]]; then
        resize_webp "$img" 88 "88"
    fi

    # About team: create 600px variant
    if [[ "$basename" == "about-team.jpg" ]]; then
        resize_webp "$img" 600 "600"
    fi
done

# --- Blog images ---
if [[ -d "$BLOG_DIR" ]]; then
    echo ""
    echo "--- Blog images ---"
    for img in "$BLOG_DIR"/*.{jpg,png,jpeg}; do
        [[ -f "$img" ]] || continue
        to_webp "$img"
        resize_webp "$img" 600 "600"
    done
fi

echo ""
echo "=== Done! ==="
# Show savings
ORIG_SIZE=$(find "$IMG_DIR" -maxdepth 1 \( -name "*.jpg" -o -name "*.png" \) -exec du -cb {} + 2>/dev/null | tail -1 | cut -f1)
WEBP_SIZE=$(find "$IMG_DIR" -maxdepth 1 -name "*.webp" -exec du -cb {} + 2>/dev/null | tail -1 | cut -f1)
if [[ -n "$ORIG_SIZE" ]] && [[ -n "$WEBP_SIZE" ]] && [[ "$ORIG_SIZE" -gt 0 ]]; then
    SAVED=$(( ORIG_SIZE - WEBP_SIZE ))
    echo "Original: $(( ORIG_SIZE / 1024 )) KiB | WebP: $(( WEBP_SIZE / 1024 )) KiB | Saved: $(( SAVED / 1024 )) KiB"
fi
