<?php
/**
 * Helper to generate <picture> elements with WebP sources + fallback.
 *
 * Usage:
 *   picture('/img/hero-bg.jpg', 'Hero', ['class' => 'hero-img', 'loading' => 'lazy'])
 *   picture('/img/service-ai.jpg', 'AI', ['loading' => 'lazy', 'width' => 550])
 */

function picture(string $src, string $alt, array $attrs = []): string
{
    $ext  = pathinfo($src, PATHINFO_EXTENSION);
    $base = substr($src, 0, -strlen($ext) - 1); // e.g. /img/hero-bg
    $webp = $base . '.webp';

    // If a sized variant is requested (e.g. width=550 → look for -550.webp)
    $width = $attrs['width'] ?? null;
    if ($width) {
        $sized_webp = $base . '-' . $width . '.webp';
        $sized_orig = $base . '-' . $width . '.' . $ext;
        // Use sized variant if the file exists on disk
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__) . '/public', '/');
        if (file_exists($doc_root . $sized_webp)) {
            $webp = $sized_webp;
        }
        if (file_exists($doc_root . $sized_orig)) {
            $src = $sized_orig;
        }
    }

    // Build <img> attributes
    $img_attrs = '';
    foreach ($attrs as $key => $val) {
        if ($val === null || $val === false) continue;
        $img_attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars((string)$val) . '"';
    }

    $mime_map = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
    ];
    $fallback_type = $mime_map[$ext] ?? 'image/' . $ext;

    $html  = '<picture>';
    $html .= '<source srcset="' . htmlspecialchars($webp) . '" type="image/webp">';
    $html .= '<source srcset="' . htmlspecialchars($src) . '" type="' . $fallback_type . '">';
    $html .= '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"' . $img_attrs . '>';
    $html .= '</picture>';

    return $html;
}
