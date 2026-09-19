<?php
/**
 * Campaign Detail & 1-Click RSVP — Mobadir (مُبادِر)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$db = get_db_connection();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    set_flash('error', 'الحملة التطوعية غير موجودة.');
    header('Location: ' . url('campaigns'));
    exit;
}

$stmt = $db->prepare("
    SELECT c.*, cp.id AS club_profile_id, cp.organization_name, cp.institution_type, cp.bio AS club_bio,
           cp.wilaya AS club_wilaya, cp.municipality AS club_municipality, cp.address AS club_address,
           cp.logo AS club_logo, u.phone AS club_phone, u.email AS club_email,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status != 'cancelled') AS current_applicants
    FROM campaigns c
    JOIN clubs_profile cp ON c.club_id = cp.id
    JOIN users u ON cp.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$camp = $stmt->fetch();

if (!$camp) {
    set_flash('error', 'الحملة المطلوبة غير متوفرة أو تم حذفها.');
    header('Location: ' . url('campaigns'));
    exit;
}

$current_user = current_user();
$existing_app = null;

if (is_volunteer()) {
    $stmt_app = $db->prepare("SELECT * FROM volunteering_applications WHERE campaign_id = ? AND volunteer_id = ? LIMIT 1");
    $stmt_app->execute([$id, $current_user['id']]);
    $existing_app = $stmt_app->fetch();
}

// Fetch additional gallery photos for this campaign
$stmt_gallery = $db->prepare("SELECT * FROM campaign_images WHERE campaign_id = ? ORDER BY id ASC");
$stmt_gallery->execute([$id]);
$gallery_images = $stmt_gallery->fetchAll();

$min_vol = (int)($camp['min_volunteers'] ?? 10);
$applied = (int)$camp['current_applicants'];
$percent = $min_vol > 0 ? min(100, round(($applied / $min_vol) * 100)) : 0;
$time_from = format_time($camp['daily_start_time'] ?? '');
$time_to   = format_time($camp['daily_end_time']   ?? '');

$page_title = $camp['title'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top: var(--sp-6); padding-bottom: var(--sp-12);">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="<?= url() ?>"><?= svg_icon('home', 14) ?> الرئيسية</a>
        <?= svg_icon('chevron-right', 12) ?>
        <a href="<?= url('campaigns') ?>">الفرص التطوعية</a>
        <?= svg_icon('chevron-right', 12) ?>
        <span><?= e($camp['title']) ?></span>
    </nav>

    <div class="detail-layout">

        <!-- Main Column -->
        <div class="detail-main">
            <!-- Cover image / banner -->
            <div class="detail-cover">
                <img src="<?= e(campaign_image_url($camp['image'], $camp['id'])) ?>" alt="<?= e($camp['title']) ?>">
            </div>

            <div class="detail-body">
                <div style="display:flex; gap:var(--sp-2); margin-bottom:var(--sp-4); flex-wrap:wrap;">
                    <span class="tag tag-teal">
                        <?= svg_icon(category_icon($camp['category']), 12) ?> <?= e($camp['category']) ?>
                    </span>
                    <span class="tag tag-blue">
                        <?= svg_icon('map-pin', 12) ?> <?= e($camp['wilaya'] ?? $camp['municipality']) ?>
                    </span>
                    <span class="tag tag-gray">
                        <?= e($camp['municipality']) ?>
                    </span>
                </div>

                <h1 style="font-family:var(--font-head); font-size:1.8rem; font-weight:800; color:var(--c-text); margin-bottom:var(--sp-4); line-height:1.35;">
                    <?= e($camp['title']) ?>
                </h1>

                <div style="display:flex; align-items:center; gap:var(--sp-2); font-weight:700; color:var(--c-brand); margin-bottom:var(--sp-6);">
                    <?= svg_icon('building', 16) ?>
                    <span>تنظيم: </span>
                    <a href="<?= url('club_detail?id=' . $camp['club_profile_id']) ?>"><?= e($camp['organization_name']) ?></a>
                    <span style="color:var(--c-text-muted); font-size:0.85rem;">(<?= e($camp['institution_type']) ?>)</span>
                </div>

                <div style="border-top:1px solid var(--c-border-light); border-bottom:1px solid var(--c-border-light); padding:var(--sp-6) 0; margin-bottom:var(--sp-6);">
                    <h2 style="font-family:var(--font-head); font-size:1.15rem; font-weight:700; margin-bottom:var(--sp-3);">وصف النشاط التطوعي وأهدافه</h2>
                    <p style="color:var(--c-text); font-size:0.98rem; line-height:1.8; white-space:pre-line;">
                        <?= e($camp['description']) ?>
                    </p>
                </div>

                <div>
                    <h2 style="font-family:var(--font-head); font-size:1.15rem; font-weight:700; margin-bottom:var(--sp-4);">تفاصيل الميدان والتوقيت</h2>
                    <div class="detail-meta-grid">
                        <div class="meta-box">
                            <div class="meta-box-lbl"><?= svg_icon('calendar', 12) ?> التواريخ</div>
                            <div class="meta-box-val"><?= format_date_ar($camp['start_date']) ?> إلى <?= format_date_ar($camp['end_date']) ?></div>
                        </div>

                        <?php if ($time_from && $time_to): ?>
                        <div class="meta-box">
                            <div class="meta-box-lbl"><?= svg_icon('clock', 12) ?> التوقيت اليومي</div>
                            <div class="meta-box-val">من <?= e($time_from) ?> إلى <?= e($time_to) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="meta-box">
                            <div class="meta-box-lbl"><?= svg_icon('award', 12) ?> الساعات المعتمدة في الجواز</div>
                            <div class="meta-box-val" style="color:var(--c-brand);"><?= $camp['hours_count'] ?> ساعات موثقة</div>
                        </div>

                        <div class="meta-box">
                            <div class="meta-box-lbl"><?= svg_icon('map-pin', 12) ?> الموقع الميداني المحدد</div>
                            <div class="meta-box-val"><?= e($camp['location_name']) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($gallery_images)): ?>
                    <div style="margin-top:var(--sp-6); border-top:1px solid var(--c-border-light); padding-top:var(--sp-5);">
                        <h2 style="font-family:var(--font-head); font-size:1.15rem; font-weight:700; margin-bottom:var(--sp-3);">
                            <?= svg_icon('image', 16) ?> ألبوم صور النشاط الميداني
                        </h2>
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:var(--sp-3);">
                            <?php foreach ($gallery_images as $gimg): ?>
                                <div style="border-radius:var(--r-md); overflow:hidden; border:1px solid var(--c-border-light); background:var(--c-surface-alt);">
                                    <a href="<?= e(url('uploads/campaigns/' . $gimg['image_path'])) ?>" target="_blank">
                                        <img src="<?= e(url('uploads/campaigns/' . $gimg['image_path'])) ?>" alt="<?= e($gimg['caption'] ?: 'صورة ميدانية') ?>" style="width:100%; height:130px; object-fit:cover; display:block;">
                                    </a>
                                    <?php if (!empty($gimg['caption'])): ?>
                                        <div style="padding:4px 8px; font-size:0.75rem; color:var(--c-text-muted); text-align:center;">
                                            <?= e($gimg['caption']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar / Action Box -->
        <aside class="detail-sidebar">
            <h3 style="font-family:var(--font-head); font-size:1.15rem; font-weight:800; margin-bottom:var(--sp-4);">حالة التسجيل</h3>

            <div class="progress-wrap">
                <div class="progress-header">
                    <span>المتطوعون المسجلون</span>
                    <span style="font-family:var(--font-num); font-weight:700;"><?= $applied ?> / <?= $min_vol ?> (الحد الأدنى)</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width:<?= $percent ?>%;"></div>
                </div>
                <div style="font-size:0.8rem; color:var(--c-text-muted); margin-top:var(--sp-2);">
                    <?php if ($applied >= $min_vol): ?>
                        <span style="color:var(--c-success); font-weight:700;">✓ اكتمل النصاب الأدنى والفرصة مؤكدة الانطلاق</span>
                    <?php else: ?>
                        متبقي <?= ($min_vol - $applied) ?> متطوع للوصول للحد الأدنى
                    <?php endif; ?>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--c-border-light); margin:var(--sp-5) 0;">

            <!-- Registration Logic -->
            <?php if (!is_logged_in()): ?>
                <div style="text-align:center;">
                    <p style="font-size:0.88rem; color:var(--c-text-muted); margin-bottom:var(--sp-4);">
                        سجل الدخول بحساب متطوع لحجز مقعدك وتوثيق ساعاتك رسمياً.
                    </p>
                    <a href="<?= url('login?redirect=' . urlencode('campaign_detail?id=' . $camp['id'])) ?>" class="btn btn-primary btn-block">
                        <?= svg_icon('user', 15) ?> تسجيل الدخول للانضمام
                    </a>
                </div>
            <?php elseif (is_volunteer()): ?>
                <?php if ($existing_app): ?>
                    <div style="background:var(--c-surface-alt); border-radius:var(--r-md); padding:var(--sp-4); text-align:center;">
                        <?php if ($existing_app['status'] === 'attended'): ?>
                            <div style="color:var(--c-success); margin-bottom:var(--sp-2);"><?= svg_icon('check-circle', 36) ?></div>
                            <div style="font-weight:800; color:var(--c-success); font-size:1.05rem;">تم اعتماد حضورك وساعاتك!</div>
                            <p style="font-size:0.85rem; color:var(--c-text-muted); margin:var(--sp-2) 0 var(--sp-4);">
                                أُضيفت <strong><?= $existing_app['hours_awarded'] ?> ساعات</strong> إلى جوازك الرقمي.
                            </p>
                            <a href="<?= url('passport') ?>" class="btn btn-primary btn-block btn-sm">
                                <?= svg_icon('award', 15) ?> عرض جواز التطوع
                            </a>
                        <?php else: ?>
                            <div style="color:var(--c-brand); margin-bottom:var(--sp-2);"><?= svg_icon('check-circle', 36) ?></div>
                            <div style="font-weight:800; font-size:1.05rem; margin-bottom:var(--sp-1);">أنت مسجل في هذه الحملة</div>
                            <p style="font-size:0.85rem; color:var(--c-text-muted); margin-bottom:var(--sp-4);">
                                حالتك: <strong><?= $existing_app['status'] === 'confirmed' ? 'مؤكد الحضور' : 'تم استلام طلبك' ?></strong>.
                            </p>
                            <form method="POST" action="<?= url('action_cancel_application') ?>" onsubmit="return confirm('هل أنت متأكد من إلغاء انضمامك؟');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-block btn-sm" style="color:var(--c-danger); border-color:var(--c-danger);">
                                    <?= svg_icon('x', 14) ?> إلغاء الانضمام
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= url('action_apply_campaign') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                        <div class="form-group">
                            <label class="form-label" style="font-size:0.85rem;">ملاحظة أو مهارة إضافية (اختياري)</label>
                            <input type="text" name="notes" class="form-control" placeholder="مثال: خبرة سابقة، تنظيم، إسعافات...">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <?= svg_icon('check-circle', 18) ?> انضمام فوري للحملة
                        </button>
                    </form>
                <?php endif; ?>
            <?php elseif (is_club()): ?>
                <?php if ($current_user['club_id'] == $camp['club_profile_id']): ?>
                    <div style="display:flex; flex-direction:column; gap:var(--sp-2);">
                        <div style="font-size:0.88rem; font-weight:700; color:var(--c-brand); margin-bottom:4px; text-align:center;">
                            أنت المشرف على هذا النشاط
                        </div>
                        <a href="<?= url('club_attendees?id=' . $camp['id']) ?>" class="btn btn-primary btn-block">
                            <?= svg_icon('users', 16) ?> كشف الحضور (<?= $applied ?>)
                        </a>
                        <a href="<?= url('club_edit_campaign?id=' . $camp['id']) ?>" class="btn btn-outline btn-block">
                            <?= svg_icon('edit', 16) ?> تعديل بيانات النشاط
                        </a>
                    </div>
                <?php else: ?>
                    <p style="font-size:0.85rem; color:var(--c-text-muted); text-align:center;">
                        أنت مسجل بحساب مؤسسة/نادي آخر.
                    </p>
                <?php endif; ?>
            <?php elseif (is_admin()): ?>
                <div style="text-align:center;">
                    <span class="tag tag-amber" style="margin-bottom:var(--sp-3);"><?= svg_icon('shield', 13) ?> رقابة وإشراف DJS</span>
                    <a href="<?= url('club_attendees?id=' . $camp['id']) ?>" class="btn btn-outline btn-block btn-sm">
                        <?= svg_icon('users', 15) ?> مراجعة سجل الحضور
                    </a>
                </div>
            <?php endif; ?>

            <div style="margin-top:var(--sp-6); padding-top:var(--sp-4); border-top:1px solid var(--c-border-light); font-size:0.8rem; color:var(--c-text-muted); display:flex; align-items:flex-start; gap:var(--sp-2);">
                <?= svg_icon('shield', 16) ?>
                <span>ساعات معتمدة وموثقة رسمياً تحت إشراف قطاع الشباب والرياضة.</span>
            </div>
        </aside>

    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
