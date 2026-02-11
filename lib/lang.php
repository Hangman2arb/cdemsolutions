<?php
/**
 * Language detection and translation helper.
 * Language is set via session/cookie only (no ?lang= query param for SEO).
 * Use /set-lang/{code}/ endpoint to switch language.
 */

$supported_languages = ['en', 'es'];
$default_language = 'en';

if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $supported_languages)) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $supported_languages)) {
    $lang = $_COOKIE['lang'];
} else {
    $lang = $default_language;
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, $supported_languages)) {
            $lang = $browserLang;
        }
    }
}

$t = require __DIR__ . '/../lang/' . $lang . '.php';
$otherLang = $lang === 'en' ? 'es' : 'en';

/**
 * Get a translated string by dot-notation key.
 */
function t($key) {
    global $t;
    $keys = explode('.', $key);
    $value = $t;
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key;
        }
    }
    return $value;
}
