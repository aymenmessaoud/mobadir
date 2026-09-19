<?php
/**
 * Authentication and Session Management: Mobadir
 */

require_once __DIR__ . '/../config/database.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function is_volunteer() {
    return is_logged_in() && $_SESSION['user']['role'] === 'volunteer';
}

function is_club() {
    return is_logged_in() && $_SESSION['user']['role'] === 'club';
}

function is_admin() {
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . url('login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '')));
        exit;
    }
}

function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['user']['role'], $allowed_roles)) {
        http_response_code(403);
        die("غير مصرح لك بالوصول إلى هذه الصفحة.");
    }
}

function login_user($email, $password) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!empty($user['is_suspended'])) {
            set_flash('error', 'تم تجميد هذا الحساب من قِبل إدارة المنصة لدواعي تنظيمية أو أمنية. يرجى مراجعة الإدارة.');
            return false;
        }

        $club_profile = null;
        if ($user['role'] === 'club') {
            $stmt_club = $db->prepare("SELECT * FROM clubs_profile WHERE user_id = ? LIMIT 1");
            $stmt_club->execute([$user['id']]);
            $club_profile = $stmt_club->fetch();
        }

        $_SESSION['user'] = [
            'id'           => $user['id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'phone'        => $user['phone'],
            'role'         => $user['role'],
            'wilaya'       => $user['wilaya'] ?? '08 - بشار',
            'municipality' => $user['municipality'],
            'avatar'       => $user['avatar'],
            'club_id'      => $club_profile ? $club_profile['id'] : null,
            'club_name'    => $club_profile ? $club_profile['organization_name'] : null
        ];
        return true;
    }
    return false;
}

function register_volunteer($name, $email, $password, $phone, $wilaya, $municipality, $bio = '') {
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([trim($email)]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً، يرجى تسجيل الدخول.'];
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, phone, role, wilaya, municipality, bio) VALUES (?, ?, ?, ?, 'volunteer', ?, ?, ?)");
    $stmt->execute([trim($name), trim($email), $password_hash, trim($phone), trim($wilaya), trim($municipality), trim($bio)]);
    $user_id = $db->lastInsertId();

    $_SESSION['user'] = [
        'id'           => $user_id,
        'name'         => $name,
        'email'        => $email,
        'phone'        => $phone,
        'role'         => 'volunteer',
        'wilaya'       => $wilaya,
        'municipality' => $municipality,
        'avatar'       => null,
        'club_id'      => null,
        'club_name'    => null
    ];

    return ['success' => true];
}

function register_club($name, $email, $password, $phone, $wilaya, $municipality, $org_name, $institution_type, $address, $bio = '') {
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([trim($email)]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً، يرجى استخدام بريد آخر.'];
    }

    $db->beginTransaction();
    try {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, phone, role, wilaya, municipality, bio) VALUES (?, ?, ?, ?, 'club', ?, ?, ?)");
        $stmt->execute([trim($name), trim($email), $password_hash, trim($phone), trim($wilaya), trim($municipality), trim($bio)]);
        $user_id = $db->lastInsertId();

        $stmt_profile = $db->prepare("INSERT INTO clubs_profile (user_id, organization_name, institution_type, bio, wilaya, municipality, address, verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt_profile->execute([$user_id, trim($org_name), trim($institution_type), trim($bio), trim($wilaya), trim($municipality), trim($address)]);
        $club_id = $db->lastInsertId();

        $db->commit();

        $_SESSION['user'] = [
            'id'           => $user_id,
            'name'         => $name,
            'email'        => $email,
            'phone'        => $phone,
            'role'         => 'club',
            'wilaya'       => $wilaya,
            'municipality' => $municipality,
            'avatar'       => null,
            'club_id'      => $club_id,
            'club_name'    => $org_name
        ];

        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الحساب: ' . $e->getMessage()];
    }
}

function logout_user() {
    unset($_SESSION['user']);
    session_destroy();
}
