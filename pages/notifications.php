<?php
/**
 * Notifications Center Page — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

require_login();
$current_user = current_user();

mark_notifications_as_read($current_user['id']);
$notifications = get_user_notifications($current_user['id'], 50);

$page_title = "مركز الإشعارات والتنبيهات";
include __DIR__ . '/../partials/header.php';
?>

<div class="container container--narrow" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div style="display:flex; align-items:center; gap:var(--sp-3);">
                <div class="stat-icon" style="background:var(--c-brand-light); color:var(--c-brand-dark);">
                    <?= svg_icon('bell', 22) ?>
                </div>
                <div>
                    <h1 class="page-title">مركز الإشعارات والتنبيهات</h1>
                    <p class="page-sub">تنبيهات فورية باعتماد ساعاتك والحملات الجديدة من النوادي التي تتابعها</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <div class="empty-icon"><?= svg_icon('bell', 48) ?></div>
            <h3 class="empty-title">لا توجد إشعارات جديدة</h3>
            <p class="empty-sub">ستظهر هنا أي تنبيهات تخص نشاطاتك وساعاتك التطوعية فور صدورها.</p>
        </div>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifications as $notif): 
                $is_hours = (str_contains($notif['title'], 'ساعات') || str_contains($notif['title'], 'اعتماد'));
                $icon = $is_hours ? 'award' : 'bell';
            ?>
                <div class="notif-item">
                    <div class="notif-icon">
                        <?= svg_icon($icon, 20) ?>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:2px;">
                            <h4 class="notif-title"><?= e($notif['title']) ?></h4>
                            <span class="notif-time"><?= format_date_ar($notif['created_at']) ?></span>
                        </div>
                        <p class="notif-msg"><?= e($notif['message']) ?></p>
                        <?php if (!empty($notif['link'])): ?>
                            <a href="<?= url($notif['link']) ?>" class="btn btn-outline btn-sm" style="margin-top:var(--sp-2);">
                                عرض التفاصيل <?= svg_icon('chevron-right', 12) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
