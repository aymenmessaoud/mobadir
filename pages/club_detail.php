<?php
/**
 * Club Profile & Hosted Campaigns Page — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$db = get_db_connection();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . url('clubs'));
    exit;
}

$current_user = current_user();

$stmt = $db->prepare("
    SELECT cp.*, u.email, u.phone,
           (SELECT COUNT(*) FROM campaigns c WHERE c.club_id = cp.id AND c.status != 'cancelled') AS campaigns_count,
           (SELECT COUNT(*) FROM follows f WHERE f.club_id = cp.id) AS followers_count,
           (SELECT COUNT(*) FROM follows f WHERE f.club_id = cp.id AND f.volunteer_id = ?) AS is_following
    FROM clubs_profile cp
    JOIN users u ON cp.user_id = u.id
    WHERE cp.id = ?
");
$stmt->execute([$current_user['id'] ?? 0, $id]);
$club = $stmt->fetch();

if (!$club) {
    set_flash('error', 'النادي المطلوب غير موجود.');
    header('Location: ' . url('clubs'));
    exit;
}

$stmt_c = $db->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status != 'cancelled') AS current_applicants
    FROM campaigns c
    WHERE c.club_id = ? AND c.status = 'published'
    ORDER BY c.start_date ASC
");
$stmt_c->execute([$id]);
$campaigns = $stmt_c->fetchAll();

$page_title = $club['organization_name'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <nav class="breadcrumb">
        <a href="<?= url() ?>"><?= svg_icon('home', 14) ?> الرئيسية</a>
        <?= svg_icon('chevron-right', 12) ?>
        <a href="<?= url('clubs') ?>">المؤسسات والنوادي</a>
        <?= svg_icon('chevron-right', 12) ?>
        <span><?= e($club['organization_name']) ?></span>
    </nav>

    <!-- Profile Header Card -->
    <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); overflow:hidden; margin-bottom:var(--sp-8); box-shadow:var(--sh-1);">
        <div class="club-cover" style="height:140px;">
            <?php if (!empty($club['cover_image'])): ?>
                <img src="<?= e(url('uploads/logos/' . $club['cover_image'])) ?>" alt="غلاف">
            <?php endif; ?>
        </div>

        <div style="padding:var(--sp-6); position:relative;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:var(--sp-4);">
                <div style="display:flex; gap:var(--sp-4); align-items:flex-end;">
                    <div class="club-logo" style="width:72px; height:72px; margin-top:-50px; border:4px solid var(--c-surface); box-shadow:var(--sh-2);">
                        <?php if (!empty($club['logo'])): ?>
                            <img src="<?= e(url('uploads/logos/' . $club['logo'])) ?>" alt="شعار">
                        <?php else: ?>
                            <?= svg_icon('building', 32) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 style="font-family:var(--font-head); font-size:1.6rem; font-weight:800;"><?= e($club['organization_name']) ?></h1>
                        <div style="color:var(--c-brand); font-size:0.9rem; font-weight:600; display:flex; align-items:center; gap:var(--sp-1); margin-top:2px;">
                            <?= svg_icon('map-pin', 13) ?> <?= e($club['wilaya'] ?? 'الجزائر') ?> • <?= e($club['institution_type']) ?>
                        </div>
                    </div>
                </div>

                <?php if (is_volunteer()): ?>
                    <form method="POST" action="<?= url('action_toggle_follow') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                        <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                        <button type="submit" class="btn <?= $club['is_following'] ? 'btn-outline' : 'btn-primary' ?>">
                            <?= $club['is_following'] ? svg_icon('check', 14).' متابَع' : svg_icon('bell', 14).' متابعة النادي' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <hr style="border:0; border-top:1px solid var(--c-border-light); margin:var(--sp-6) 0;">

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:var(--sp-8);">
                <div>
                    <h3 style="font-family:var(--font-head); font-size:1.05rem; font-weight:700; margin-bottom:var(--sp-2);">عن النادي / الجمعية</h3>
                    <p style="color:var(--c-text-2); font-size:0.95rem; line-height:1.75;">
                        <?= e($club['bio'] ?: 'لا تتوفر نبذة حالياً.') ?>
                    </p>
                </div>

                <div style="background:var(--c-surface-alt); padding:var(--sp-4); border-radius:var(--r-md); font-size:0.88rem;">
                    <h4 style="font-family:var(--font-head); font-weight:700; margin-bottom:var(--sp-3);">معلومات الاتصال</h4>
                    <div style="display:flex; flex-direction:column; gap:var(--sp-2); color:var(--c-text-muted);">
                        <div><?= svg_icon('map-pin', 14) ?> <?= e($club['address'] ?: 'المقر الولائي') ?></div>
                        <div><?= svg_icon('phone', 14) ?> <?= e($club['phone']) ?></div>
                        <div><?= svg_icon('mail', 14) ?> <?= e($club['email']) ?></div>
                        <div style="margin-top:var(--sp-2); font-weight:700; color:var(--c-text);">
                            <?= svg_icon('users', 14) ?> <strong><?= $club['followers_count'] ?></strong> متابع
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Club Campaigns Grid -->
    <div class="section-head section-row">
        <div>
            <h2 class="section-title">الحملات والفرص التطوعية المنشورة</h2>
            <p style="color:var(--c-text-muted); font-size:0.9rem;">الفرص التي يطلقها ويشرف عليها هذا النادي</p>
        </div>
    </div>

    <div class="campaigns-grid">
        <?php if (empty($campaigns)): ?>
            <div style="grid-column:1/-1;">
                <div class="empty-state">
                    <div class="empty-icon"><?= svg_icon('flag', 44) ?></div>
                    <h3 class="empty-title">لا توجد حملات منشورة حالياً</h3>
                    <p class="empty-sub">تابع النادي لتصلك تنبيهات فور إطلاق نشاطات جديدة</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($campaigns as $camp): 
                $min_vol = (int)($camp['min_volunteers'] ?? 10);
                $applied = (int)$camp['current_applicants'];
                $percent = $min_vol > 0 ? min(100, round(($applied / $min_vol) * 100)) : 0;
            ?>
                <article class="campaign-card">
                    <div class="card-banner">
                        <img src="<?= e(campaign_image_url($camp['image'], $camp['id'])) ?>" alt="<?= e($camp['title']) ?>">
                        <div class="card-banner-tags">
                            <div class="card-banner-top">
                                <span class="card-tag card-tag-cat"><?= e($camp['category']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= e($camp['title']) ?></h3>
                        <p class="card-desc"><?= e($camp['description']) ?></p>

                        <div class="card-meta">
                            <div class="card-meta-row">
                                <?= svg_icon('calendar', 13) ?> <?= format_date_ar($camp['start_date']) ?>
                            </div>
                            <div class="card-meta-row">
                                <?= svg_icon('award', 13) ?> <?= $camp['hours_count'] ?> ساعات معتمدة
                            </div>
                        </div>

                        <div class="progress-wrap">
                            <div class="progress-header">
                                <span>المسجلون</span>
                                <span style="font-family:var(--font-num)"><?= $applied ?> / <?= $min_vol ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= $percent ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-foot">
                        <a href="<?= url('campaign_detail?id=' . $camp['id']) ?>" class="btn btn-primary btn-sm btn-block">
                            تفاصيل والانضمام
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
