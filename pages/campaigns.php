<?php
/**
 * Campaigns Listing — Mobadir
 * National scope: wilaya + category filters, proper SVG icons
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$db = get_db_connection();

$search   = trim($_GET['q']        ?? '');
$wilaya   = trim($_GET['wilaya']   ?? '');
$commune  = trim($_GET['commune']  ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "
    SELECT c.*, cp.organization_name, cp.institution_type, cp.logo,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id = c.id AND va.status != 'cancelled') AS current_applicants
    FROM campaigns c
    JOIN clubs_profile cp ON c.club_id = cp.id
    WHERE c.status = 'published'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.location_name LIKE ? OR cp.organization_name LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
if (!empty($wilaya)) {
    $sql .= " AND c.wilaya = ?";
    $params[] = $wilaya;
}
if (!empty($commune)) {
    $sql .= " AND (c.municipality = ? OR c.location_name LIKE ?)";
    $params[] = $commune;
    $params[] = "%$commune%";
}
if (!empty($category)) {
    $sql .= " AND c.category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY c.start_date ASC, c.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

$wilayas    = get_wilayas();
$categories = get_categories();

$page_title = 'الفرص التطوعية المتاحة';
include __DIR__ . '/../partials/header.php';
?>

<section class="section" style="padding-top: var(--sp-6);">
<div class="container">

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="section-row" style="margin-bottom: var(--sp-4);">
            <div>
                <h1 class="page-title">دليل الفرص التطوعية</h1>
                <p style="color:var(--c-text-muted);font-size:.9rem;margin-top:4px;">ابحث عن فرصة تناسبك في ولايتك وساهم في تنمية مجتمعك</p>
            </div>
            <?php if (!empty($search) || !empty($wilaya) || !empty($category)): ?>
                <a href="<?= url('campaigns') ?>" class="btn btn-ghost btn-sm">
                    <?= svg_icon('x', 14) ?> إلغاء الفلاتر
                </a>
            <?php endif; ?>
        </div>

        <form method="GET" action="<?= url('campaigns') ?>">
            <div class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="fq"><?= svg_icon('search', 13) ?> كلمة البحث أو الحي</label>
                    <input type="text" id="fq" name="q" value="<?= e($search) ?>" class="form-control" placeholder="اسم الحملة، المؤسسة، الحي...">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="filterWilaya"><?= svg_icon('map-pin', 13) ?> الولاية</label>
                    <select id="filterWilaya" name="wilaya" class="form-control" onchange="updateCommunes('filterWilaya', 'filterCommune')">
                        <option value="">كل الولايات</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= e($w) ?>" <?= $wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="filterCommune"><?= svg_icon('map-pin', 13) ?> البلدية</label>
                    <select id="filterCommune" name="commune" class="form-control">
                        <option value="">كل البلديات</option>
                        <?php if (!empty($wilaya)): 
                            $c_list = get_communes_for_wilaya($wilaya);
                            foreach ($c_list as $cl): ?>
                                <option value="<?= e($cl) ?>" <?= $commune === $cl ? 'selected' : '' ?>><?= e($cl) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="fcat"><?= svg_icon('filter', 13) ?> المجال</label>
                    <select id="fcat" name="category" class="form-control">
                        <option value="">كل المجالات</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column: span 1; display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary btn-block">
                        <?= svg_icon('search', 16) ?> تصفية
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results header -->
    <div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
        <span class="tag tag-teal">
            <?= svg_icon('flag', 12) ?>
            <?= count($campaigns) ?> فرصة متاحة
        </span>
        <?php if (!empty($wilaya)): ?>
            <span class="tag tag-blue"><?= svg_icon('map-pin', 12) ?> <?= e($wilaya) ?></span>
        <?php endif; ?>
        <?php if (!empty($category)): ?>
            <span class="tag tag-amber"><?= svg_icon('filter', 12) ?> <?= e($category) ?></span>
        <?php endif; ?>
    </div>

    <!-- Grid -->
    <div class="campaigns-grid">
        <?php if (empty($campaigns)): ?>
            <div style="grid-column:1/-1">
                <div class="empty-state">
                    <div class="empty-icon"><?= svg_icon('search', 56) ?></div>
                    <h3 class="empty-title">لم نجد فرصاً تطوعية تطابق بحثك</h3>
                    <p class="empty-sub">جرّب تعديل معايير البحث أو اختيار ولاية أخرى</p>
                    <a href="<?= url('campaigns') ?>" class="btn btn-primary">عرض كل الفرص</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($campaigns as $camp):
                $applied   = $camp['current_applicants'];
                $min_vol   = (int)($camp['min_volunteers'] ?? 10);
                $progress  = $min_vol > 0 ? min(100, round(($applied / $min_vol) * 100)) : 0;
                $time_from = format_time($camp['daily_start_time'] ?? '');
                $time_to   = format_time($camp['daily_end_time']   ?? '');
            ?>
                <article class="campaign-card">
                    <!-- Banner -->
                    <div class="card-banner">
                        <img src="<?= e(campaign_image_url($camp['image'], $camp['id'])) ?>"
                             alt="<?= e($camp['title']) ?>" loading="lazy">
                        <div class="card-banner-tags">
                            <div class="card-banner-top">
                                <span class="card-tag card-tag-cat">
                                    <?= svg_icon(category_icon($camp['category']), 11) ?>
                                    <?= e($camp['category']) ?>
                                </span>
                            </div>
                            <div class="card-banner-bottom">
                                <span class="card-tag">
                                    <?= svg_icon('map-pin', 11) ?>
                                    <?= e($camp['wilaya']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body">
                        <div class="card-club">
                            <?= svg_icon('building', 13) ?>
                            <span><?= e($camp['organization_name']) ?></span>
                        </div>
                        <h2 class="card-title"><?= e($camp['title']) ?></h2>
                        <p class="card-desc"><?= e($camp['description']) ?></p>

                        <div class="card-meta">
                            <div class="card-meta-row">
                                <?= svg_icon('calendar', 13) ?>
                                <span><?= format_date_ar($camp['start_date']) ?> — <?= format_date_ar($camp['end_date']) ?></span>
                            </div>
                            <?php if ($time_from && $time_to): ?>
                            <div class="card-meta-row">
                                <?= svg_icon('clock', 13) ?>
                                <span>يومياً من <?= e($time_from) ?> إلى <?= e($time_to) ?>
                                    (<?= e($camp['hours_count']) ?> ساعة/يوم معتمدة)
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="card-meta-row">
                                <?= svg_icon('map-pin', 13) ?>
                                <span><?= e($camp['location_name']) ?>، <?= e($camp['municipality']) ?></span>
                            </div>
                        </div>

                        <!-- Progress toward min volunteers -->
                        <div class="progress-wrap">
                            <div class="progress-header">
                                <span>المتطوعون المسجّلون</span>
                                <span style="font-family:var(--font-num)"><?= $applied ?> / <?= $min_vol ?> الحد الأدنى</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= $progress ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-foot">
                        <?php if ($progress >= 100): ?>
                            <span class="tag tag-green"><?= svg_icon('check-circle', 12) ?> اكتمل النصاب</span>
                        <?php else: ?>
                            <span class="card-vacancies">
                                <?= svg_icon('users', 13) ?>
                                <?= max(0, $min_vol - $applied) ?> مطلوب إضافياً
                            </span>
                        <?php endif; ?>
                        <a href="<?= url('campaign_detail?id=' . $camp['id']) ?>" class="btn btn-primary btn-sm">
                            التفاصيل والانضمام <?= svg_icon('chevron-right', 14) ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
