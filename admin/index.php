<?php
/**
 * DJS State Impact Dashboard (مديرية الشباب والرياضة) — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (!is_admin()) {
    header('Location: ' . url());
    exit;
}

$db = get_db_connection();

$total_hours = (int)$db->query("SELECT COALESCE(SUM(hours_awarded), 0) FROM volunteering_applications WHERE status = 'attended'")->fetchColumn();
$total_volunteers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer'")->fetchColumn();
$total_clubs = (int)$db->query("SELECT COUNT(*) FROM clubs_profile")->fetchColumn();
$total_campaigns = (int)$db->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();

// All users for management
$stmt_users = $db->query("
    SELECT u.*, 
           cp.organization_name, cp.institution_type,
           COALESCE((SELECT SUM(va.hours_awarded) FROM volunteering_applications va WHERE va.volunteer_id = u.id AND va.status = 'attended'), 0) AS total_hours
    FROM users u
    LEFT JOIN clubs_profile cp ON u.id = cp.user_id
    WHERE u.role != 'admin'
    ORDER BY u.created_at DESC
");
$all_users = $stmt_users->fetchAll();
$suspended_count = count(array_filter($all_users, fn($u) => $u['is_suspended']));

// Impact by Wilaya
$stmt_wilayas = $db->query("
    SELECT c.wilaya,
           COUNT(DISTINCT c.id) AS campaigns_count,
           COALESCE(SUM(va.hours_awarded), 0) AS hours_count,
           COUNT(DISTINCT va.volunteer_id) AS volunteers_engaged
    FROM campaigns c
    LEFT JOIN volunteering_applications va ON c.id = va.campaign_id AND va.status = 'attended'
    GROUP BY c.wilaya
    ORDER BY hours_count DESC, campaigns_count DESC
");
$wilaya_stats = $stmt_wilayas->fetchAll();

// Top Active Clubs
$stmt_top_clubs = $db->query("
    SELECT cp.*,
           COUNT(DISTINCT c.id) AS total_campaigns,
           COALESCE(SUM(va.hours_awarded), 0) AS total_hours_generated,
           COUNT(DISTINCT va.volunteer_id) AS mobilized_volunteers
    FROM clubs_profile cp
    LEFT JOIN campaigns c ON cp.id = c.club_id
    LEFT JOIN volunteering_applications va ON c.id = va.campaign_id AND va.status = 'attended'
    GROUP BY cp.id
    ORDER BY total_hours_generated DESC, total_campaigns DESC
");
$top_clubs = $stmt_top_clubs->fetchAll();

// Recent Campaigns Audit
$stmt_recent = $db->query("
    SELECT c.*, cp.organization_name,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id) AS applicants_count,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status = 'attended') AS attended_count
    FROM campaigns c
    JOIN clubs_profile cp ON c.club_id = cp.id
    ORDER BY c.created_at DESC
    LIMIT 10
");
$recent_campaigns = $stmt_recent->fetchAll();

$page_title = "لوحة قيادة مديرية الشباب والرياضة";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <!-- DJS Banner Header -->
    <div class="hero" style="padding:var(--sp-8) 0; border-radius:var(--r-lg); margin-bottom:var(--sp-8);">
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:var(--sp-4);">
            <div>
                <div class="hero-eyebrow">
                    <?= svg_icon('shield', 13) ?> وزارة الشباب والرياضة — لوحة الرصد الوطني
                </div>
                <h1 class="hero-title" style="font-size:1.8rem; margin-bottom:var(--sp-2);">
                    لوحة مؤشرات الأثر واستقطاب الشباب
                </h1>
                <p class="hero-desc" style="font-size:0.95rem; margin-bottom:0;">
                    نظام المتابعة المركزي للنشاطات والفرص التطوعية بمؤسسات الشباب عبر القطر الوطني
                </p>
            </div>

            <button onclick="window.print();" class="btn btn-hero-outline btn-sm no-print">
                <?= svg_icon('shield', 14) ?> طباعة تقرير الأثر
            </button>
        </div>
    </div>

    <!-- Macro Metrics Ribbon -->
    <div class="dash-stats">
        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">إجمالي الساعات المعتمدة</span>
                <div class="dash-stat-icon"><?= svg_icon('clock', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= number_format($total_hours) ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">شباب متطوعون مسجلون</span>
                <div class="dash-stat-icon"><?= svg_icon('users', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= number_format($total_volunteers) ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">مؤسسات ونوادي مفعلة</span>
                <div class="dash-stat-icon"><?= svg_icon('building', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $total_clubs ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">مبادرات تطوعية منسقة</span>
                <div class="dash-stat-icon"><?= svg_icon('flag', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $total_campaigns ?></div>
        </div>
    </div>

    <!-- Two Columns: Wilayas Impact & Top Clubs -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-6); margin-bottom:var(--sp-8);">
        <!-- Wilayas Table -->
        <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-5); box-shadow:var(--sh-1);">
            <h3 style="font-family:var(--font-head); font-size:1.1rem; font-weight:800; margin-bottom:var(--sp-4);">
                <?= svg_icon('map-pin', 16) ?> الأثر والتغطية حسب الولايات
            </h3>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الولاية</th>
                            <th>الفرص</th>
                            <th>الساعات</th>
                            <th>المتطوعون</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wilaya_stats as $w): ?>
                            <tr>
                                <td><strong><?= e($w['wilaya'] ?? 'أخرى') ?></strong></td>
                                <td><?= $w['campaigns_count'] ?></td>
                                <td><strong style="color:var(--c-brand);"><?= $w['hours_count'] ?> س</strong></td>
                                <td><?= $w['volunteers_engaged'] ?> شاب</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Active Clubs -->
        <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-5); box-shadow:var(--sh-1);">
            <h3 style="font-family:var(--font-head); font-size:1.1rem; font-weight:800; margin-bottom:var(--sp-4);">
                <?= svg_icon('award', 16) ?> أنشط المؤسسات والنوادي
            </h3>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>المؤسسة</th>
                            <th>الولاية</th>
                            <th>الفرص</th>
                            <th>الساعات المنجزة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_clubs as $tc): ?>
                            <tr>
                                <td>
                                    <strong><?= e($tc['organization_name']) ?></strong>
                                    <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($tc['institution_type']) ?></div>
                                </td>
                                <td><?= e($tc['wilaya'] ?? $tc['municipality']) ?></td>
                                <td><?= $tc['total_campaigns'] ?></td>
                                <td>
                                    <strong style="color:var(--c-accent-dark);"><?= $tc['total_hours_generated'] ?> س</strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Campaigns Audit and Management -->
    <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--sp-4);">
            <h3 style="font-family:var(--font-head); font-size:1.15rem; font-weight:800;">سجل الحملات والمبادرات</h3>
            <span style="font-size:0.85rem; color:var(--c-text-muted);">تدقيق واعتماد كشوفات الحضور</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>عنوان الحملة</th>
                        <th>المؤسسة</th>
                        <th>الولاية</th>
                        <th>المسجلين</th>
                        <th>الحضور المعتمد</th>
                        <th>الحالة</th>
                        <th class="no-print">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_campaigns as $rc): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= url('campaign_detail?id=' . $rc['id']) ?>"><?= e($rc['title']) ?></a></strong>
                                <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($rc['category']) ?></div>
                            </td>
                            <td><?= e($rc['organization_name']) ?></td>
                            <td><?= e($rc['wilaya'] ?? $rc['municipality']) ?></td>
                            <td><?= $rc['applicants_count'] ?> / <?= $rc['min_volunteers'] ?></td>
                            <td>
                                <strong style="color:var(--c-success);"><?= $rc['attended_count'] ?></strong> معتمد
                            </td>
                            <td>
                                <?php if ($rc['status'] === 'published'): ?>
                                    <span class="tag tag-green">منشورة</span>
                                <?php elseif ($rc['status'] === 'completed'): ?>
                                    <span class="tag tag-gray">مكتملة</span>
                                <?php else: ?>
                                    <span class="tag tag-red">ملغاة</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-print">
                                <a href="<?= url('club_attendees?id=' . $rc['id']) ?>" class="btn btn-outline btn-sm">
                                    تدقيق الحضور
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Accounts Management -->
    <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1); margin-top:var(--sp-6);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--sp-4);">
            <h3 style="font-family:var(--font-head); font-size:1.15rem; font-weight:800;">
                <?= svg_icon('users', 16) ?> إدارة حسابات المستخدمين
            </h3>
            <div style="display:flex; gap:var(--sp-2);">
                <span class="tag tag-teal"><?= count($all_users) ?> حساب</span>
                <?php if ($suspended_count > 0): ?>
                    <span class="tag tag-red"><?= $suspended_count ?> مجمّد</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الدور</th>
                        <th>الولاية</th>
                        <th>ساعات التطوع</th>
                        <th>تاريخ التسجيل</th>
                        <th>الحالة</th>
                        <th class="no-print">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $u): ?>
                        <tr<?= $u['is_suspended'] ? ' style="opacity:0.6;"' : '' ?>>
                            <td>
                                <div style="display:flex; align-items:center; gap:var(--sp-2);">
                                    <?= render_avatar($u, 32) ?>
                                    <div>
                                        <strong><?= e($u['name']) ?></strong>
                                        <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'volunteer'): ?>
                                    <span class="tag tag-teal">متطوع</span>
                                <?php else: ?>
                                    <span class="tag tag-gray"><?= e($u['organization_name'] ?? 'نادي') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($u['wilaya'] ?? '—') ?></td>
                            <td>
                                <?php if ($u['role'] === 'volunteer'): ?>
                                    <strong style="color:var(--c-brand);"><?= (int)$u['total_hours'] ?> س</strong>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;"><?= format_date_ar($u['created_at']) ?></td>
                            <td>
                                <?php if ($u['is_suspended']): ?>
                                    <span class="tag tag-red">مجمّد</span>
                                <?php else: ?>
                                    <span class="tag tag-green">نشط</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-print">
                                <form method="POST" action="<?= url('action_toggle_suspension') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['is_suspended']): ?>
                                        <button type="submit" class="btn btn-outline btn-sm" title="إلغاء التجميد">
                                            <?= svg_icon('check-circle', 14) ?> تفعيل
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--c-error);" title="تجميد الحساب" onclick="return confirm('هل أنت متأكد من تجميد هذا الحساب؟');">
                                            <?= svg_icon('x', 14) ?> تجميد
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
