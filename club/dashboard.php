<?php
/**
 * Club Coordinator Dashboard — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (!is_club()) {
    header('Location: ' . url());
    exit;
}

$db = get_db_connection();
$current_user = current_user();
$club_id = $current_user['club_id'];

$stmt_profile = $db->prepare("SELECT * FROM clubs_profile WHERE id = ?");
$stmt_profile->execute([$club_id]);
$club = $stmt_profile->fetch();

$metrics = [
    'campaigns' => (int)$db->query("SELECT COUNT(*) FROM campaigns WHERE club_id = $club_id")->fetchColumn(),
    'volunteers' => (int)$db->query("SELECT COUNT(DISTINCT volunteer_id) FROM volunteering_applications va JOIN campaigns c ON va.campaign_id = c.id WHERE c.club_id = $club_id")->fetchColumn(),
    'hours_awarded' => (int)$db->query("SELECT COALESCE(SUM(va.hours_awarded), 0) FROM volunteering_applications va JOIN campaigns c ON va.campaign_id = c.id WHERE c.club_id = $club_id AND va.status = 'attended'")->fetchColumn(),
    'followers' => (int)$db->query("SELECT COUNT(*) FROM follows WHERE club_id = $club_id")->fetchColumn(),
];

$stmt_camps = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status != 'cancelled') AS applicants_count,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status = 'attended') AS attended_count
    FROM campaigns c
    WHERE c.club_id = ?
    ORDER BY c.created_at DESC
");
$stmt_camps->execute([$club_id]);
$campaigns = $stmt_camps->fetchAll();

$page_title = "لوحة تحكم النادي — " . $club['organization_name'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <!-- Dashboard Header -->
    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div style="display:flex; align-items:center; gap:var(--sp-4);">
                <div class="stat-icon" style="width:54px; height:54px; font-size:1.6rem;">
                    <?= svg_icon('building', 26) ?>
                </div>
                <div>
                    <h1 class="page-title"><?= e($club['organization_name']) ?></h1>
                    <div class="page-sub">
                        <?= e($club['institution_type']) ?> • <?= e($club['wilaya'] ?? 'الجزائر') ?> • <?= e($club['municipality']) ?>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:var(--sp-2); flex-wrap:wrap;">
                <a href="<?= url('club_new_campaign') ?>" class="btn btn-primary">
                    <?= svg_icon('plus-circle', 16) ?> نشر فرصة تطوعية جديدة
                </a>
                <a href="<?= url('profile_edit') ?>" class="btn btn-outline">
                    <?= svg_icon('settings', 16) ?> تعديل بيانات النادي
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Ribbon -->
    <div class="dash-stats">
        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">فرص منشورة</span>
                <div class="dash-stat-icon"><?= svg_icon('flag', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $metrics['campaigns'] ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">متطوع تم استقطابهم</span>
                <div class="dash-stat-icon"><?= svg_icon('users', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $metrics['volunteers'] ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">ساعات معتمدة رسمياً</span>
                <div class="dash-stat-icon"><?= svg_icon('clock', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $metrics['hours_awarded'] ?></div>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-lbl">متابعون للنادي</span>
                <div class="dash-stat-icon"><?= svg_icon('bell', 18) ?></div>
            </div>
            <div class="dash-stat-num"><?= $metrics['followers'] ?></div>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--sp-5);">
            <h2 style="font-family:var(--font-head); font-size:1.25rem; font-weight:800;">إدارة الفرص التطوعية وكشوفات الحضور</h2>
            <span class="tag tag-teal"><?= count($campaigns) ?> فرص</span>
        </div>

        <?php if (empty($campaigns)): ?>
            <div class="empty-state" style="padding:var(--sp-10) var(--sp-4);">
                <div class="empty-icon"><?= svg_icon('flag', 48) ?></div>
                <h3 class="empty-title">لم تقم بنشر أي نشاط تطوعي بعد</h3>
                <p class="empty-sub">انشر أول فرصة لمؤسستك لتبدأ في استقطاب الشباب واعتماد ساعاتهم!</p>
                <a href="<?= url('club_new_campaign') ?>" class="btn btn-primary btn-sm">نشر فرصة الآن</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>عنوان النشاط</th>
                            <th>المجال</th>
                            <th>التاريخ والموقع</th>
                            <th>الساعات</th>
                            <th>المسجلون / الأدنى</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $camp): ?>
                            <tr>
                                <td>
                                    <strong><a href="<?= url('campaign_detail?id=' . $camp['id']) ?>"><?= e($camp['title']) ?></a></strong>
                                </td>
                                <td><span class="tag tag-gray"><?= e($camp['category']) ?></span></td>
                                <td>
                                    <div><?= format_date_ar($camp['start_date']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($camp['location_name']) ?></div>
                                </td>
                                <td><strong><?= $camp['hours_count'] ?> س</strong></td>
                                <td>
                                    <strong><?= $camp['applicants_count'] ?></strong> / <?= $camp['min_volunteers'] ?>
                                    <?php if ($camp['attended_count'] > 0): ?>
                                        <div style="font-size:0.75rem; color:var(--c-success);">✓ اعتُمد لـ <?= $camp['attended_count'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($camp['status'] === 'published'): ?>
                                        <span class="tag tag-green">نشطة</span>
                                    <?php elseif ($camp['status'] === 'completed'): ?>
                                        <span class="tag tag-gray">مكتملة</span>
                                    <?php else: ?>
                                        <span class="tag tag-red">ملغاة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:var(--sp-2);">
                                        <a href="<?= url('club_attendees?id=' . $camp['id']) ?>" class="btn btn-primary btn-sm" title="كشف الحضور واعتماد الساعات">
                                            <?= svg_icon('users', 14) ?> الحضور (<?= $camp['applicants_count'] ?>)
                                        </a>
                                        <a href="<?= url('club_edit_campaign?id=' . $camp['id']) ?>" class="btn btn-outline btn-sm" title="تعديل">
                                            <?= svg_icon('edit', 14) ?>
                                        </a>
                                    </div>
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
