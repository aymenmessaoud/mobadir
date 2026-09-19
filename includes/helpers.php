<?php
/**
 * Helper functions for Mobadir (مُبادِر)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/algeria_places.php';

// ─── Flash Messaging ────────────────────────────────────────
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    if (!isset($_SESSION['flash'])) return [];
    $flashes = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flashes;
}

// ─── CSRF Protection ─────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('خطأ في التحقق الأمني. يرجى إعادة المحاولة.');
        }
    }
}

// ─── URL Helper ───────────────────────────────────────────────
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    if (empty($path)) return BASE_URL . '/';
    if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
        return BASE_URL . '/' . $path;
    }
    if (str_starts_with($path, '?')) return BASE_URL . '/' . $path;
    if (str_contains($path, '?')) {
        [$route, $query] = explode('?', $path, 2);
        return BASE_URL . '/?page=' . $route . '&' . $query;
    }
    return BASE_URL . '/?page=' . $path;
}

// ─── Safe escaping ────────────────────────────────────────────
function e($string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

// ─── SVG Icon System (Feather-style) ─────────────────────────
function svg_icon(string $name, int $size = 20, string $class = ''): string {
    $paths = [
        'home'          => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'search'        => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'bell'          => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'         => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'building'      => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="8" y1="6" x2="8" y2="6.01"/><line x1="12" y1="6" x2="12" y2="6.01"/><line x1="16" y1="6" x2="16" y2="6.01"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/>',
        'map-pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'plus'          => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'plus-circle'   => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'edit'          => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'award'         => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
        'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'eye'           => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'chevron-down'  => '<polyline points="6 9 12 15 18 9"/>',
        'globe'         => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'activity'      => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'trending-up'   => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'bar-chart'     => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'image'         => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'upload'        => '<polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>',
        'link'          => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'menu'          => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'x'             => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'check'         => '<polyline points="20 6 9 17 4 12"/>',
        'sun'           => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        'moon'          => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.7 12.91 19.79 19.79 0 0 1 1.17 4.29 2 2 0 0 1 3.14 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21 16z"/>',
        'mail'          => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'flag'          => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        'copy'          => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    ];

    $path_data = $paths[$name] ?? '<circle cx="12" cy="12" r="10"/>'; // fallback circle
    $cls = $class ? ' class="' . e($class) . '"' : '';
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $cls . ' aria-hidden="true">' . $path_data . '</svg>';
}

// ─── Date Formatting ─────────────────────────────────────────
function format_date_ar(string $date_str): string {
    if (empty($date_str)) return '';
    $ts = strtotime($date_str);
    $months = [1=>'جانفي',2=>'فيفري',3=>'مارس',4=>'أفريل',5=>'ماي',6=>'جوان',7=>'جويلية',8=>'أوت',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

// Format time like "08:30" → "08:30"
function format_time(string $time_str): string {
    if (empty($time_str)) return '';
    return substr($time_str, 0, 5);
}

// ─── 58 Algerian Wilayas ─────────────────────────────────────
function get_wilayas(): array {
    return [
        '01 - أدرار', '02 - الشلف', '03 - الأغواط', '04 - أم البواقي',
        '05 - باتنة', '06 - بجاية', '07 - بسكرة', '08 - بشار',
        '09 - البليدة', '10 - البويرة', '11 - تمنراست', '12 - تبسة',
        '13 - تلمسان', '14 - تيارت', '15 - تيزي وزو', '16 - الجزائر',
        '17 - الجلفة', '18 - جيجل', '19 - سطيف', '20 - سعيدة',
        '21 - سكيكدة', '22 - سيدي بلعباس', '23 - عنابة', '24 - قالمة',
        '25 - قسنطينة', '26 - المدية', '27 - مستغانم', '28 - المسيلة',
        '29 - معسكر', '30 - ورقلة', '31 - وهران', '32 - البيض',
        '33 - إليزي', '34 - برج بوعريريج', '35 - بومرداس', '36 - الطارف',
        '37 - تندوف', '38 - تيسمسيلت', '39 - الوادي', '40 - خنشلة',
        '41 - سوق أهراس', '42 - تيبازة', '43 - ميلة', '44 - عين الدفلى',
        '45 - النعامة', '46 - عين تيموشنت', '47 - غرداية', '48 - غليزان',
        '49 - تيميمون', '50 - برج باجي مختار', '51 - أولاد جلال', '52 - بني عباس',
        '53 - عين صالح', '54 - عين قزام', '55 - تقرت', '56 - جانت',
        '57 - المغير', '58 - المنيعة',
    ];
}

// Institution types
function get_institution_types(): array {
    return ['دار الشباب', 'دار الثقافة', 'مؤسسة ثقافية', 'نادي شبابي', 'جمعية خيرية', 'جمعية بيئية', 'جمعية رياضية', 'جمعية تنموية', 'ملحقة إدارية', 'أخرى'];
}

// Volunteering Categories — NO emoji keys
function get_categories(): array {
    return [
        'بيئة وتراث',
        'رقمنة وتدريب',
        'تنظيم فعاليات',
        'ثقافة ومواطنة',
        'رياضة وصحة مجتمعية',
        'إغاثة وتضامن',
    ];
}

// Category icon name (svg_icon key)
function category_icon(string $cat): string {
    return match($cat) {
        'بيئة وتراث'          => 'globe',
        'رقمنة وتدريب'         => 'activity',
        'تنظيم فعاليات'        => 'star',
        'ثقافة ومواطنة'        => 'flag',
        'رياضة وصحة مجتمعية'  => 'heart',
        'إغاثة وتضامن'         => 'users',
        default                => 'check-circle',
    };
}

// ─── Automated Volunteer Badge System (نظام الترقية الآلي للرتب والشارات) ────
function calculate_volunteer_badge(int $hours): array {
    if ($hours >= 100) {
        return [
            'level'       => 'diamond',
            'title'       => 'سفير العمل التطوعي الوطني',
            'emoji'       => '💎',
            'badge_color' => '#0284c7',
            'description' => 'أعلى رتبة شرفية وطنية: إنجاز أكثر من 100 ساعة عمل تطوعي معتمد.',
            'next_target' => null,
            'progress'    => 100
        ];
    } elseif ($hours >= 50) {
        return [
            'level'       => 'platinum',
            'title'       => 'قائد مبادرات متميز',
            'emoji'       => '🌟',
            'badge_color' => '#7c3aed',
            'description' => 'أتم أكثر من 50 ساعة تطوعية موثقة بإشراف مؤسسات الشباب.',
            'next_target' => 100,
            'progress'    => min(100, round(($hours / 100) * 100))
        ];
    } elseif ($hours >= 25) {
        return [
            'level'       => 'gold',
            'title'       => 'رائد العمل التطوعي',
            'emoji'       => '🥇',
            'badge_color' => '#d97706',
            'description' => 'أتم أكثر من 25 ساعة تطوعية موثقة.',
            'next_target' => 50,
            'progress'    => min(100, round(($hours / 50) * 100))
        ];
    } elseif ($hours >= 10) {
        return [
            'level'       => 'silver',
            'title'       => 'متطوع فاعل',
            'emoji'       => '🥈',
            'badge_color' => '#64748b',
            'description' => 'أتم أكثر من 10 ساعات تطوعية منتظمة.',
            'next_target' => 25,
            'progress'    => min(100, round(($hours / 25) * 100))
        ];
    } else {
        return [
            'level'       => 'bronze',
            'title'       => 'متطوع ناشئ',
            'emoji'       => '🥉',
            'badge_color' => '#b45309',
            'description' => 'انطلاقة واعدة في ميدان التطوع الشبابي.',
            'next_target' => 10,
            'progress'    => min(100, round(($hours / 10) * 100))
        ];
    }
}

// ─── Avatar Helper ───────────────────────────────────────────
function render_avatar(array $user, int $size = 36): string {
    $initial = mb_substr($user['name'] ?? 'م', 0, 1, 'UTF-8');
    $colors = ['#0d7a6f','#2563eb','#7c3aed','#db2777','#ea580c','#16a34a'];
    $color  = $colors[abs(crc32($user['email'] ?? '')) % count($colors)];

    if (!empty($user['avatar'])) {
        return '<img src="' . e(url('uploads/avatars/' . $user['avatar'])) . '" alt="' . e($user['name']) . '" width="' . $size . '" height="' . $size . '" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;object-fit:cover;">';
    }
    return '<span style="width:' . $size . 'px;height:' . $size . 'px;background:' . $color . ';border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:' . round($size * .45) . 'px;font-weight:700;color:#fff;flex-shrink:0;font-family:\'Noto Kufi Arabic\',sans-serif;">' . e($initial) . '</span>';
}

// ─── Campaign Image ──────────────────────────────────────────
function campaign_image_url(?string $img_field, int $campaign_id): string {
    if (!empty($img_field)) {
        return url('uploads/campaigns/' . $img_field);
    }
    // Fallback gradient SVG
    $svgs = ['camp1.svg','camp2.svg','camp3.svg','camp4.svg','camp5.svg'];
    return url('uploads/campaigns/' . $svgs[($campaign_id - 1) % count($svgs)]);
}

// ─── Safe File Upload ────────────────────────────────────────
function handle_upload(array $file, string $dir, int $max_bytes = 2097152): array {
    $allowed_mime = ['image/jpeg','image/png','image/webp','image/gif'];
    $ext_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok'=>false,'msg'=>'فشل رفع الملف (كود: '.$file['error'].')'];
    }
    if ($file['size'] > $max_bytes) {
        return ['ok'=>false,'msg'=>'حجم الملف كبير جداً (الحد الأقصى 2 ميغابايت).'];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_mime, true)) {
        return ['ok'=>false,'msg'=>'نوع الملف غير مسموح به. يُقبل: JPG, PNG, WEBP, GIF فقط.'];
    }
    $ext      = $ext_map[$mime];
    $filename = uniqid('img_', true) . '.' . $ext;
    $dest     = __DIR__ . '/../uploads/' . $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok'=>false,'msg'=>'تعذّر حفظ الملف على الخادم.'];
    }
    return ['ok'=>true,'filename'=>$filename];
}

// Delete old upload safely (prevents path traversal)
function delete_upload(string $dir, ?string $filename): void {
    if (empty($filename)) return;
    $base    = realpath(__DIR__ . '/../uploads/' . $dir);
    $target  = realpath(__DIR__ . '/../uploads/' . $dir . '/' . $filename);
    if ($target && $base && str_starts_with($target, $base)) {
        @unlink($target);
    }
}

// ─── QR Code Mock ────────────────────────────────────────────
function render_qr_code(string $data, int $size = 120): string {
    $hash = md5($data);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" width="'.$size.'" height="'.$size.'" style="background:#fff;padding:4px;border-radius:8px;">';
    // Finder patterns
    foreach([[1,1],[17,1],[1,17]] as [$ox,$oy]) {
        $svg .= '<rect x="'.$ox.'" y="'.$oy.'" width="7" height="7" fill="#1e293b"/>';
        $svg .= '<rect x="'.($ox+1).'" y="'.($oy+1).'" width="5" height="5" fill="#fff"/>';
        $svg .= '<rect x="'.($ox+2).'" y="'.($oy+2).'" width="3" height="3" fill="#1e293b"/>';
    }
    for ($y = 1; $y < 24; $y++) {
        for ($x = 1; $x < 24; $x++) {
            if (($x<=8&&$y<=8)||($x>=16&&$y<=8)||($x<=8&&$y>=16)) continue;
            $ci  = ($x * $y + $y) % 32;
            $val = hexdec($hash[$ci]);
            if (($val + $x + $y) % 2 === 0) {
                $svg .= '<rect x="'.$x.'" y="'.$y.'" width="0.9" height="0.9" fill="#1e293b"/>';
            }
        }
    }
    return $svg . '</svg>';
}
