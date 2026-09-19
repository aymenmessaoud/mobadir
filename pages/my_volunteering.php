<?php
/**
 * Volunteer's Joined Campaigns & Applications List — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (!is_volunteer()) {
    header('Location: ' . url());
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user']['id'];

$stmt = $db->prepare("
    SELECT va.*, c.title AS campaign_title, c.start_date, c.end_date, c.daily_start_time, c.daily_end_time,
           c.hours_count, c.location_name, c.wilaya AS camp_wilaya, c.municipality AS camp_municipality, c.category,
           cp.organization_name, cp.institution_type
    FROM volunteering_applications va
    JOIN campaigns c ON va.campaign_id = c.id
    JOIN clubs_profile cp ON c.club_id = cp.id
    WHERE va.volunteer_id = ?
    ORDER BY va.applied_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

$page_title = "تطوعاتي ومشاركاتي";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div>
                <h1 class="page-title">سجل مشاركاتي في الحملات التطوعية</h1>
                <p class="page-sub">متابعة حالة طلبات الانضمام ومواعيد الأنشطة الميدانية القادمة</p>
            </div>

            <a href="<?= url('passport') ?>" class="btn btn-primary btn-sm">
                <?= svg_icon('award', 15) ?> فتح جواز التطوع الرقمي
            </a>
        </div>
    </div>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <div class="empty-icon"><?= svg_icon('flag', 48) ?></div>
            <h3 class="empty-title">لم تنضم إلى أي نشاط تطوعي بعد</h3>
            <p class="empty-sub">ابدأ رحلتك التطوعية اليوم واستكشف الفرص النشطة في مختلف الولايات!</p>
            <a href="<?= url('campaigns') ?>" class="btn btn-primary">
                <?= svg_icon('search', 15) ?> تصفح الفرص المتاحة
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الحملة التطوعية</th>
                        <th>المؤسسة المنظمة</th>
                        <th>التاريخ والموقع</th>
                        <th>الساعات</th>
                        <th>حالة الانضمام</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= url('campaign_detail?id=' . $app['campaign_id']) ?>"><?= e($app['campaign_title']) ?></a></strong>
                                <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($app['category']) ?></div>
                            </td>
                            <td>
                                <div><?= e($app['organization_name']) ?></div>
                                <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($app['institution_type']) ?></div>
                            </td>
                            <td>
                                <div><?= svg_icon('calendar', 12) ?> <?= format_date_ar($app['start_date']) ?></div>
                                <div style="font-size:0.75rem; color:var(--c-text-muted); margin-top:2px;">
                                    <?= svg_icon('map-pin', 11) ?> <?= e($app['location_name']) ?> (<?= e($app['camp_wilaya'] ?? $app['camp_municipality']) ?>)
                                </div>
                            </td>
                            <td>
                                <strong><?= $app['hours_count'] ?> ساعات</strong>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'attended'): ?>
                                    <span class="tag tag-green">✓ تم الاعتماد (<?= $app['hours_awarded'] ?> س)</span>
                                <?php elseif ($app['status'] === 'confirmed'): ?>
                                    <span class="tag tag-teal">مؤكد الحضور</span>
                                <?php elseif ($app['status'] === 'applied'): ?>
                                    <span class="tag tag-amber">قيد المراجعة</span>
                                <?php else: ?>
                                    <span class="tag tag-gray">ملغى</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($app['status'] !== 'attended' && $app['status'] !== 'cancelled'): ?>
                                    <form method="POST" action="<?= url('action_cancel_application') ?>" onsubmit="return confirm('هل تريد بالتأكيد إلغاء مشاركتك في هذه الحملة؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="campaign_id" value="<?= $app['campaign_id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color:var(--c-danger); border-color:var(--c-danger); padding:4px 8px;">
                                            إلغاء
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:var(--c-text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
