<?php
/**
 * Admin router — handles all /admin/* routes.
 */

$base = dirname(__DIR__);
require $base . '/admin/bootstrap.php';
require_once $base . '/lib/blog.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/admin#', '', $uri);
$uri = '/' . trim($uri, '/');
if ($uri === '/') $uri = '/dashboard';

$method = $_SERVER['REQUEST_METHOD'];

// --- Public routes (no auth) ---
if ($uri === '/login') {
    if ($method === 'POST') {
        if (!verify_csrf()) {
            flash('error', 'Invalid security token. Please try again.');
            header('Location: /admin/login/');
            exit;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (admin_login($username, $password)) {
            flash('success', 'Welcome back!');
            header('Location: /admin/dashboard/');
            exit;
        } else {
            flash('error', 'Invalid credentials or account locked.');
            header('Location: /admin/login/');
            exit;
        }
    }

    if (is_admin_logged_in()) {
        header('Location: /admin/dashboard/');
        exit;
    }

    $admin_page = 'login';
    require $base . '/admin-templates/login.php';
    exit;
}

if ($uri === '/logout') {
    admin_logout();
    header('Location: /admin/login/');
    exit;
}

// --- Require auth for everything below ---
require_admin_auth();
cleanup_login_attempts();

// --- POST handlers ---
if ($method === 'POST') {
    require_csrf();

    // Lead status update
    if ($uri === '/leads/status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['new', 'contacted', 'qualified', 'closed', 'archived'])) {
            lead_update_status($id, $status);
            flash('success', 'Lead status updated.');
        }
        header('Location: ' . ($_POST['redirect'] ?? '/admin/leads/'));
        exit;
    }

    // Lead notes update
    if ($uri === '/leads/notes') {
        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($id) {
            lead_update_notes($id, $notes);
            flash('success', 'Notes updated.');
        }
        header('Location: /admin/leads/' . $id . '/');
        exit;
    }

    // Lead delete
    if ($uri === '/leads/delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            lead_delete($id);
            flash('success', 'Lead deleted.');
        }
        header('Location: /admin/leads/');
        exit;
    }

    // Blog post save
    if ($uri === '/blog/save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'content_html' => $_POST['content_html'] ?? '',
            'featured_image' => trim($_POST['featured_image'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'author' => trim($_POST['author'] ?? 'CDEM Solutions'),
        ];

        $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));

        if (empty($data['title'])) {
            flash('error', 'Title is required.');
            header('Location: /admin/blog/edit/' . ($id ?: 'new') . '/');
            exit;
        }

        if (empty($data['slug'])) {
            $data['slug'] = blog_slugify($data['title']);
        }

        $data['reading_time'] = blog_reading_time($data['content_html']);

        if ($id) {
            blog_update($id, $data);
            blog_sync_tags($id, $tags);
            flash('success', 'Post updated.');
        } else {
            $id = blog_create($data);
            blog_sync_tags($id, $tags);
            flash('success', 'Post created.');
        }

        header('Location: /admin/blog/edit/' . $id . '/');
        exit;
    }

    // Blog translation save
    if (preg_match('#^/blog/translate/(\d+)$#', $uri, $m)) {
        $postId = (int)$m[1];
        $post = blog_get($postId);
        if (!$post) {
            flash('error', 'Post not found.');
            header('Location: /admin/blog/');
            exit;
        }

        $lang = 'es';

        // Delete translation
        if (!empty($_POST['delete'])) {
            blog_translation_delete($postId, $lang);
            flash('success', 'Translation deleted.');
            header('Location: /admin/blog/');
            exit;
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'content_html' => $_POST['content_html'] ?? '',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        ];

        if (empty($data['title'])) {
            flash('error', 'Title is required.');
            header('Location: /admin/blog/translate/' . $postId . '/');
            exit;
        }

        if (empty($data['slug'])) {
            $data['slug'] = blog_slugify($data['title']);
        }

        $data['reading_time'] = $data['content_html'] ? blog_reading_time($data['content_html']) : 5;

        blog_translation_save($postId, $lang, $data);
        flash('success', 'Translation saved.');
        header('Location: /admin/blog/translate/' . $postId . '/');
        exit;
    }

    // Blog post delete
    if ($uri === '/blog/delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            blog_delete($id);
            flash('success', 'Post deleted.');
        }
        header('Location: /admin/blog/');
        exit;
    }

    // Blog tag save
    if ($uri === '/blog/tags/save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            if ($id) {
                blog_tag_update($id, $name);
                flash('success', 'Tag updated.');
            } else {
                blog_tag_create($name);
                flash('success', 'Tag created.');
            }
        }
        header('Location: /admin/blog/tags/');
        exit;
    }

    // Blog tag delete
    if ($uri === '/blog/tags/delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            blog_tag_delete($id);
            flash('success', 'Tag deleted.');
        }
        header('Location: /admin/blog/tags/');
        exit;
    }

    // Settings save
    if ($uri === '/settings/save') {
        $group = $_POST['group'] ?? 'general';
        $fields = $_POST['settings'] ?? [];
        foreach ($fields as $key => $value) {
            set_setting($key, $value, 'string', $group);
        }
        flash('success', 'Settings saved.');
        header('Location: /admin/settings/?group=' . $group);
        exit;
    }

    // Password change
    if ($uri === '/settings/password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $pdo = db();
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($newPass !== $confirm) {
            flash('error', 'Passwords do not match.');
        } else {
            admin_change_password($_SESSION['admin_id'], $newPass);
            flash('success', 'Password changed successfully.');
        }
        header('Location: /admin/settings/?group=security');
        exit;
    }

    // SEO settings save
    if ($uri === '/seo/save') {
        $tab = $_POST['tab'] ?? 'general';
        $fields = $_POST['settings'] ?? [];
        foreach ($fields as $key => $value) {
            set_setting($key, $value, 'string', 'seo');
        }
        flash('success', 'SEO settings saved.');
        header('Location: /admin/seo/?tab=' . $tab);
        exit;
    }

    // SEO redirect save
    if ($uri === '/seo/redirects/save') {
        $from = trim($_POST['from_path'] ?? '');
        $to = trim($_POST['to_url'] ?? '');
        $type = (int)($_POST['redirect_type'] ?? 301);
        if ($from && $to) {
            $from = '/' . trim($from, '/');
            $pdo = db();
            $stmt = $pdo->prepare('INSERT OR REPLACE INTO seo_redirects (from_path, to_url, redirect_type) VALUES (:from, :to, :type)');
            $stmt->execute([':from' => $from, ':to' => $to, ':type' => $type]);
            flash('success', 'Redirect added.');
        }
        header('Location: /admin/seo/?tab=redirects');
        exit;
    }

    // SEO redirect delete
    if ($uri === '/seo/redirects/delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo = db();
            $pdo->prepare('DELETE FROM seo_redirects WHERE id = :id')->execute([':id' => $id]);
            flash('success', 'Redirect deleted.');
        }
        header('Location: /admin/seo/?tab=redirects');
        exit;
    }

    // Image upload
    if ($uri === '/upload/image') {
        header('Content-Type: application/json');
        if (empty($_FILES['image'])) {
            echo json_encode(['error' => 'No file uploaded']);
            exit;
        }

        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['error' => 'Invalid file type']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['error' => 'File too large (max 5MB)']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;
        $uploadDir = $base . '/public/img/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['url' => '/img/uploads/' . $filename]);
        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
        exit;
    }
}

// --- GET routes ---

// Dashboard
if ($uri === '/dashboard') {
    $stats = admin_dashboard_stats();
    $admin_page = 'dashboard';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/dashboard.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Leads list
if ($uri === '/leads') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $status = $_GET['status'] ?? '';
    $search = trim($_GET['search'] ?? '');
    $result = leads_list($page, 20, $status, $search);
    $leads = $result['data'];
    $pagination = $result['pagination'];
    $statusCounts = leads_count_by_status();

    $admin_page = 'leads';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/leads.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Lead detail
if (preg_match('#^/leads/(\d+)$#', $uri, $m)) {
    $lead = lead_get((int)$m[1]);
    if (!$lead) {
        flash('error', 'Lead not found.');
        header('Location: /admin/leads/');
        exit;
    }

    $admin_page = 'lead-detail';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/lead-detail.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Blog list
if ($uri === '/blog') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $status = $_GET['status'] ?? '';
    $result = blog_list_admin($page, 20, $status);
    $posts = $result['data'];
    $pagination = $result['pagination'];

    $admin_page = 'blog-list';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/blog-list.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Blog edit/create
if (preg_match('#^/blog/edit/(\w+)$#', $uri, $m)) {
    $postId = $m[1];
    if ($postId === 'new') {
        $post = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content_html' => '',
                 'featured_image' => '', 'status' => 'draft', 'meta_title' => '',
                 'meta_description' => '', 'meta_keywords' => '', 'author' => 'CDEM Solutions', 'tags' => ''];
    } else {
        $post = blog_get((int)$postId);
        if (!$post) {
            flash('error', 'Post not found.');
            header('Location: /admin/blog/');
            exit;
        }
        $post['tags'] = blog_get_post_tags_string((int)$postId);
    }

    $allTags = blog_all_tags();

    $admin_page = 'blog-edit';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/blog-edit.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Blog translate
if (preg_match('#^/blog/translate/(\d+)$#', $uri, $m)) {
    $postId = (int)$m[1];
    $post = blog_get($postId);
    if (!$post) {
        flash('error', 'Post not found.');
        header('Location: /admin/blog/');
        exit;
    }

    $translation = blog_translation_get($postId, 'es');

    $admin_page = 'blog-translate';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/blog-translate.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Blog tags management
if ($uri === '/blog/tags') {
    $tags = blog_all_tags();

    $admin_page = 'blog-tags';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/blog-tags.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// SEO Manager
if ($uri === '/seo') {
    $seo_tab = $_GET['tab'] ?? 'general';
    $redirects = [];
    if ($seo_tab === 'redirects') {
        $pdo = db();
        $redirects = $pdo->query('SELECT * FROM seo_redirects ORDER BY from_path')->fetchAll();
    }

    $admin_page = 'seo';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/seo.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Settings
if ($uri === '/settings') {
    $group = $_GET['group'] ?? 'smtp';
    $settings = get_settings_by_group($group);

    $admin_page = 'settings';
    require $base . '/admin-partials/head.php';
    require $base . '/admin-partials/sidebar.php';
    require $base . '/admin-partials/header.php';
    require $base . '/admin-templates/settings.php';
    require $base . '/admin-partials/footer.php';
    exit;
}

// Fallback — 404
http_response_code(404);
flash('error', 'Page not found.');
header('Location: /admin/dashboard/');
exit;
