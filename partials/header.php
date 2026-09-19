<?php
/**
 * Header Partial — Mobadir (مُبادِر)
 * Clean RTL nav, SVG icons, no emoji, proper user chip
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

$current_user  = current_user();
$unread_count  = $current_user ? get_unread_notifications_count($current_user['id']) : 0;
$current_page  = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?><?= APP_NAME ?></title>
    <!-- Fonts: Noto Kufi Arabic (headings/UI) + Noto Naskh Arabic (body) + Inter (numbers) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;600;700;800&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=2.0') ?>">
    <!-- No-FOUC theme inline script -->
    <script>
        (function(){
            var t = localStorage.getItem('mobadir_theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>

<header class="site-header no-print">
    <div class="container header-inner">

        <!-- Brand -->
        <a href="<?= url() ?>" class="brand" aria-label="<?= APP_NAME ?>">
            <span class="brand-icon" aria-hidden="true">
                <?= svg_icon('flag', 18, '') ?>
            </span>
            <span class="brand-name"><?= APP_NAME ?></span>
        </a>

        <!-- Main Nav -->
        <nav class="nav-main" aria-label="التنقل الرئيسي">
            <ul class="nav-list" role="list">
                <li>
                    <a href="<?= url() ?>" class="<?= $current_page === 'home' ? 'active' : '' ?>">
                        <?= svg_icon('home', 15) ?> الرئيسية
                    </a>
                </li>
                <li>
                    <a href="<?= url('campaigns') ?>" class="<?= $current_page === 'campaigns' ? 'active' : '' ?>">
                        <?= svg_icon('flag', 15) ?> الفرص التطوعية
                    </a>
                </li>
                <li>
                    <a href="<?= url('clubs') ?>" class="<?= $current_page === 'clubs' ? 'active' : '' ?>">
                        <?= svg_icon('building', 15) ?> المؤسسات والنوادي
                    </a>
                </li>
                <?php if (is_volunteer()): ?>
                    <li>
                        <a href="<?= url('passport') ?>" class="<?= $current_page === 'passport' ? 'active' : '' ?>">
                            <?= svg_icon('award', 15) ?> جواز التطوع
                        </a>
                    </li>
                    <li>
                        <a href="<?= url('my_volunteering') ?>" class="<?= $current_page === 'my_volunteering' ? 'active' : '' ?>">
                            <?= svg_icon('check-circle', 15) ?> تطوعاتي
                        </a>
                    </li>
                <?php elseif (is_club()): ?>
                    <li>
                        <a href="<?= url('club_dashboard') ?>" class="<?= $current_page === 'club_dashboard' ? 'active' : '' ?>">
                            <?= svg_icon('bar-chart', 15) ?> لوحة النادي
                        </a>
                    </li>
                    <li>
                        <a href="<?= url('club_new_campaign') ?>" class="<?= $current_page === 'club_new_campaign' ? 'active' : '' ?>">
                            <?= svg_icon('plus-circle', 15) ?> نشر فرصة
                        </a>
                    </li>
                <?php elseif (is_admin()): ?>
                    <li>
                        <a href="<?= url('admin_dashboard') ?>" class="<?= $current_page === 'admin_dashboard' ? 'active' : '' ?>">
                            <?= svg_icon('shield', 15) ?> لوحة DJS
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">

            <!-- Theme toggle -->
            <button type="button" class="icon-btn" id="themeToggleBtn" aria-label="تبديل المظهر الليلي/النهاري">
                <span id="themeIconLight"><?= svg_icon('sun', 18) ?></span>
                <span id="themeIconDark"  style="display:none"><?= svg_icon('moon', 18) ?></span>
            </button>

            <?php if ($current_user): ?>

                <!-- Notifications -->
                <a href="<?= url('notifications') ?>" class="icon-btn" title="الإشعارات" aria-label="الإشعارات">
                    <?= svg_icon('bell', 18) ?>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-count"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
                    <?php endif; ?>
                </a>

                <!-- User chip — role-based destination -->
                <?php
                    $chip_href = match($current_user['role']) {
                        'admin' => url('admin_dashboard'),
                        'club'  => url('club_dashboard'),
                        default => url('passport'),
                    };
                ?>
                <a href="<?= $chip_href ?>" class="user-chip" title="حسابي">
                    <span class="avatar-ring">
                        <?php if (!empty($current_user['avatar'])): ?>
                            <img src="<?= e(url('uploads/avatars/' . $current_user['avatar'])) ?>" alt="<?= e($current_user['name']) ?>">
                        <?php else: ?>
                            <?= e(mb_substr($current_user['name'], 0, 1, 'UTF-8')) ?>
                        <?php endif; ?>
                    </span>
                    <span><?= e(mb_substr($current_user['name'], 0, 15, 'UTF-8') . (mb_strlen($current_user['name'], 'UTF-8') > 15 ? '…' : '')) ?></span>
                </a>

                <!-- Profile Edit -->
                <?php if (!is_admin()): ?>
                <a href="<?= url('profile_edit') ?>" class="icon-btn" title="تعديل الحساب" aria-label="تعديل الحساب">
                    <?= svg_icon('settings', 16) ?>
                </a>
                <?php endif; ?>

                <!-- Logout -->
                <a href="<?= url('logout') ?>" class="btn btn-ghost btn-sm" title="خروج" aria-label="تسجيل خروج">
                    <?= svg_icon('logout', 15) ?>
                </a>

            <?php else: ?>
                <a href="<?= url('login') ?>" class="btn btn-outline btn-sm">دخول</a>
                <a href="<?= url('register') ?>" class="btn btn-primary btn-sm">
                    <?= svg_icon('plus', 15) ?> انضم الآن
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="main-content">
    <div style="padding: 0 0 8px;">
        <?php include __DIR__ . '/flash.php'; ?>
    </div>
