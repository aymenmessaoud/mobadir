<?php
/**
 * Platform Configuration: Mobadir (مُبادِر)
 * Production & Shared Hosting Ready (InfinityFree / cPanel / Localhost)
 */

// ─── 1. Environment & Error Reporting ───────────────────────
$is_localhost = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'], true);
define('IS_LOCAL', $is_localhost);
define('ENVIRONMENT', $is_localhost ? 'development' : 'production');

if (ENVIRONMENT === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ─── 2. Session Security ────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if ($is_https) {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// ─── 3. Dynamic Base URL Auto-Detection ─────────────────────
// Automatically adapts whether placed in root (htdocs/) on InfinityFree or subfolder (/mobadir)
if (!defined('BASE_URL')) {
    $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $script_dir = str_replace('\\', '/', $script_dir);
    $base = ($script_dir === '/' || $script_dir === '.') ? '' : rtrim($script_dir, '/');
    define('BASE_URL', $base);
}

// ─── 4. System Constants ────────────────────────────────────
define('APP_NAME', 'مُبادِر');
define('APP_TAGLINE', 'المنصة الموحدة لتنسيق العمل التطوعي بمؤسسات الشباب');
define('APP_STATE', 'الجمهورية الجزائرية الديمقراطية الشعبية');

// Timezone
date_default_timezone_set('Africa/Algiers');

// ─── 5. Database Credentials ────────────────────────────────
// [ملاحظة للمستضيف / InfinityFree]:
// قم بتعديل هذه القيم لتطابق بيانات MySQL المعطاة لك في لوحة تحكم الاستضافة (Control Panel):
// - DB_HOST: مثل sql123.infinityfree.com (أو 127.0.0.1 محلياً)
// - DB_NAME: مثل if0_12345678_mobadir_db
// - DB_USER: مثل if0_12345678
// - DB_PASS: كلمة المرور الخاصة بحساب الاستضافة
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mobadir_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ─── 6. Upload Configuration ────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
