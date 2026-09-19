<?php
/**
 * Volunteer Attendance Ledger & Hours Accreditation — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (!is_club() && !is_admin()) {
    header('Location: ' . url());
    exit;
}

$db = get_db_connection();
$campaign_id = (int)($_GET['id'] ?? 0);
$current_user = current_user();

$stmt = $db->prepare("
    SELECT c.*, cp.organization_name, cp.institution_type
    FROM campaigns c
    JOIN clubs_profile cp ON c.club_id = cp.id
    WHERE c.id = ?
");
$stmt->execute([$campaign_id]);
$camp = $stmt->fetch();

if (!$camp || (!is_admin() && $camp['club_id'] != $current_user['club_id'])) {
    set_flash('error', 'الحملة غير متوفرة أو لا تملك صلاحية إدارة متطوعيها.');
    header('Location: ' . url('club_dashboard'));
    exit;
}

$stmt_volunteers = $db->prepare("
    SELECT va.*, u.name AS volunteer_name, u.email AS volunteer_email, u.phone AS volunteer_phone,
           u.wilaya AS volunteer_wilaya, u.municipality AS volunteer_municipality,
           (SELECT COALESCE(SUM(va2.hours_awarded), 0) FROM volunteering_applications va2 WHERE va2.volunteer_id = u.id AND va2.status = 'attended') AS total_volunteer_hours
    FROM volunteering_applications va
    JOIN users u ON va.volunteer_id = u.id
    WHERE va.campaign_id = ?
    ORDER BY va.status ASC, va.applied_at ASC
");
$stmt_volunteers->execute([$campaign_id]);
$volunteers = $stmt_volunteers->fetchAll();

$total_applied = count($volunteers);
$attended_count = 0;
foreach ($volunteers as $v) {
    if ($v['status'] === 'attended') $attended_count++;
}

$page_title = "كشف حضور واعتماد ساعات — " . $camp['title'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div>
                <a href="<?= is_admin() ? url('admin_dashboard') : url('club_dashboard') ?>" class="breadcrumb" style="margin-bottom:var(--sp-2);">
                    <?= svg_icon('chevron-right', 12) ?> العودة للوحة التحكم
                </a>
                <h1 class="page-title">كشف حضور واعتماد الساعات الميدانية</h1>
                <div class="page-sub">
                    نشاط: <strong><?= e($camp['title']) ?></strong> (المقرر: <?= $camp['hours_count'] ?> ساعات معتمدة)
                </div>
            </div>

            <div style="display:flex; gap:var(--sp-2);">
                <button onclick="window.print();" class="btn btn-outline btn-sm no-print">
                    <?= svg_icon('shield', 14) ?> طباعة الكشف
                </button>
                <a href="<?= url('campaign_detail?id=' . $camp['id']) ?>" class="btn btn-primary btn-sm no-print">
                    عرض الفرصة ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Summary ribbon -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:var(--sp-4); margin-bottom:var(--sp-6);">
        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">المتطوعون المسجلون</span>
                <div class="dash-stat-icon"><?= svg_icon('users', 16) ?></div>
            </div>
            <div class="dash-stat-num"><?= $total_applied ?> / <?= $camp['min_volunteers'] ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">الحضور المعتمد رسمياً</span>
                <div class="dash-stat-icon" style="color:var(--c-success);"><?= svg_icon('check-circle', 16) ?></div>
            </div>
            <div class="dash-stat-num" style="color:var(--c-success);"><?= $attended_count ?> متطوع</div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">تاريخ النشاط والموقع</span>
                <div class="dash-stat-icon"><?= svg_icon('calendar', 16) ?></div>
            </div>
            <div style="font-size:0.95rem; font-weight:700; margin-top:var(--sp-1);">
                <?= format_date_ar($camp['start_date']) ?>
            </div>
            <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($camp['location_name']) ?></div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1);">
        <h2 style="font-family:var(--font-head); font-size:1.2rem; font-weight:800; margin-bottom:var(--sp-5);">
            قائمة المتطوعين واعتماد الساعات
        </h2>

        <?php if (empty($volunteers)): ?>
            <div class="empty-state" style="padding:var(--sp-8) var(--sp-4);">
                <div class="empty-icon"><?= svg_icon('users', 44) ?></div>
                <h3 class="empty-title">لا يوجد متطوعون مسجلون في هذه الفرصة بعد</h3>
                <p class="empty-sub">شارك رابط الفرصة لاستقطاب المتطوعين وسيبدأ ظهورهم هنا فوراً.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>المتطوع</th>
                            <th>الاتصال</th>
                            <th>الموقع</th>
                            <th>رصيد ساعاته السابق</th>
                            <th>ملاحظات</th>
                            <th>الحالة</th>
                            <th class="no-print">اعتماد الحضور</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($volunteers as $vol): ?>
                            <tr>
                                <td>
                                    <strong><?= e($vol['volunteer_name']) ?></strong>
                                    <div style="font-size:0.75rem; color:var(--c-text-muted);">
                                        DZ-VOL-<?= sprintf('%04d', $vol['volunteer_id']) ?>
                                    </div>
                                    <?php if (is_admin()): ?>
                                        <a href="<?= url('passport?user_id=' . $vol['volunteer_id']) ?>" target="_blank" style="font-size:0.75rem;">جواز المتطوع ↗</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="direction:ltr; text-align:right; font-weight:600;"><?= e($vol['volunteer_phone']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($vol['volunteer_email']) ?></div>
                                </td>
                                <td><?= e($vol['volunteer_wilaya'] ?? $vol['volunteer_municipality']) ?></td>
                                <td>
                                    <span class="tag tag-teal"><?= $vol['total_volunteer_hours'] ?> ساعة</span>
                                </td>
                                <td style="font-size:0.85rem; color:var(--c-text-muted);">
                                    <?= e($vol['notes'] ?: '—') ?>
                                </td>
                                <td>
                                    <?php if ($vol['status'] === 'attended'): ?>
                                        <span class="tag tag-green">✓ معتمد (<?= $vol['hours_awarded'] ?> س)</span>
                                    <?php elseif ($vol['status'] === 'confirmed'): ?>
                                        <span class="tag tag-teal">مؤكد الحضور</span>
                                    <?php else: ?>
                                        <span class="tag tag-amber">طلب جديد</span>
                                    <?php endif; ?>
                                </td>
                                <td class="no-print">
                                    <?php if ($vol['status'] === 'attended'): ?>
                                        <span style="font-size:0.8rem; color:var(--c-success); font-weight:700;">
                                            ✓ مودعة بالجواز
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="<?= url('action_credit_hours') ?>" style="display:flex; gap:var(--sp-1); align-items:center;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                            <input type="hidden" name="volunteer_id" value="<?= $vol['volunteer_id'] ?>">
                                            <input type="number" name="hours_awarded" value="<?= $camp['hours_count'] ?>" min="1" max="50" style="width:50px; padding:4px 6px; border:1px solid var(--c-border); border-radius:var(--r-xs); font-weight:700; text-align:center;">
                                            <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 10px;">
                                                اعتماد
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
