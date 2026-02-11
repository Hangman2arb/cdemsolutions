<?php
/**
 * Simple router — matches URI to route definitions.
 */

function route_match(string $uri, array $routes): ?array {
    $uri = '/' . trim($uri, '/') . '/';
    if ($uri === '//') $uri = '/';

    foreach ($routes as $pattern => $handler) {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[a-zA-Z0-9_-]+)', $pattern);
        $regex = '#^' . rtrim($regex, '/') . '/?$#';

        if (preg_match($regex, rtrim($uri, '/') ?: '/', $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['handler' => $handler, 'params' => $params];
        }
    }

    return null;
}

/**
 * Get clean URI path (without query string).
 */
function get_uri(): string {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $uri ?: '/';
}

/**
 * Generate URL with current language parameter preserved.
 */
function url(string $path): string {
    return rtrim($path, '/') . '/';
}

/**
 * Check if current URI matches a given path (for active nav).
 */
function is_active(string $path): bool {
    $current = get_uri();
    if ($path === '/') {
        return $current === '/';
    }
    return strpos($current, rtrim($path, '/')) === 0;
}
