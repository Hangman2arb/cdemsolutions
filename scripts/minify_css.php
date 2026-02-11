<?php
/**
 * Simple CSS minifier — removes comments, whitespace, unnecessary chars.
 * Usage: php scripts/minify_css.php
 * Generates: public/css/style.min.css
 */

$base = dirname(__DIR__);
$input  = $base . '/public/css/style.css';
$output = $base . '/public/css/style.min.css';

if (!file_exists($input)) {
    echo "ERROR: $input not found\n";
    exit(1);
}

$css = file_get_contents($input);
$original_size = strlen($css);

// Remove comments
$css = preg_replace('!/\*.*?\*/!s', '', $css);

// Remove whitespace around selectors/properties
$css = preg_replace('/\s+/', ' ', $css);
$css = preg_replace('/\s*([{}:;,>~+])\s*/', '$1', $css);
$css = preg_replace('/;}/', '}', $css);

// Trim leading/trailing space
$css = trim($css);

file_put_contents($output, $css);
$min_size = strlen($css);
$saved = $original_size - $min_size;

echo "Minified: style.css → style.min.css\n";
echo "Original: " . number_format($original_size) . " bytes\n";
echo "Minified: " . number_format($min_size) . " bytes\n";
echo "Saved:    " . number_format($saved) . " bytes (" . round($saved / $original_size * 100, 1) . "%)\n";
