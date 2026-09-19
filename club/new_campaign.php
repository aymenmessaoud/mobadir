<?php
/**
 * New Volunteering Campaign Form — Club Dashboard
 * With: cascading wilaya-commune selector, daily time slots, min_volunteers, multi-photo gallery upload
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (!is_club()) { header('Location: ' . url()); exit; }

$wilayas    = get_wilayas();
$categories = get_categories();
$current_user = current_user();

$selected_wilaya = $current_user['wilaya'] ?? '08 - بشار';
$communes = get_communes_for_wilaya($selected_wilaya);
$selected_commune = $current_user['municipality'] ?? ($communes[0] ?? '');

$page_title = 'نشر فرصة تطوعية جديدة';
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8);padding-bottom:var(--sp-12);">
    <div class="form-card form-card-wide">

        <!-- Breadcrumb -->
        <div class="breadcrumb" style="margin-bottom:var(--sp-5);">
            <?= svg_icon('bar-chart', 13) ?>
            <a href="<?= url('club_dashboard') ?>">لوحة النادي</a>
            <?= svg_icon('chevron-right', 11) ?>
            <span>نشر فرصة جديدة</span>
        </div>

        <div style="margin-bottom:var(--sp-6);">
            <h1 class="page-title"><?= svg_icon('plus-circle', 22) ?> نشر فرصة تطوعية جديدة</h1>
            <p style="color:var(--c-text-muted);font-size:.9rem;margin-top:6px;">
                سيتم إشعار المتطوعين المتابعين لناديكم فور نشر هذه الفرصة في منصة مُبادِر.
            </p>
        </div>

        <form method="POST" action="<?= url('action_create_campaign') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- عنوان النشاط -->
            <div class="form-group">
                <label class="form-label" for="title">
                    <?= svg_icon('flag', 14) ?> عنوان النشاط التطوعي <span class="required">*</span>
                </label>
                <input type="text" id="title" name="title" required class="form-control"
                    placeholder="مثال: حملة تشجير محيط دار الشباب وتهيئة المساحات الخضراء">
            </div>

            <!-- الفئة + الولاية -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category">
                        <?= svg_icon('filter', 14) ?> المجال / الفئة <span class="required">*</span>
                    </label>
                    <select id="category" name="category" required class="form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="campaignWilaya">
                        <?= svg_icon('map-pin', 14) ?> الولاية <span class="required">*</span>
                    </label>
                    <select id="campaignWilaya" name="wilaya" required class="form-control" onchange="updateCommunes('campaignWilaya', 'campaignCommune')">
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= e($w) ?>" <?= $selected_wilaya === $w ? 'selected' : '' ?>>
                                <?= e($w) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- البلدية المنسدلة + الحي والموقع المحدد -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="campaignCommune">
                        <?= svg_icon('map-pin', 14) ?> البلدية (تلقائية حسب الولاية) <span class="required">*</span>
                    </label>
                    <select id="campaignCommune" name="municipality" required class="form-control">
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= e($c) ?>" <?= $selected_commune === $c ? 'selected' : '' ?>>
                                <?= e($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="location_name">
                        الموقع الميداني أو الحي المحدد <span class="required">*</span>
                    </label>
                    <input type="text" id="location_name" name="location_name" required class="form-control"
                        placeholder="مثال: حي الاستقلال، محيط دار الشباب، ساحة الشهداء...">
                </div>
            </div>

            <!-- تواريخ -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">
                        <?= svg_icon('calendar', 14) ?> تاريخ البدء <span class="required">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" required
                        value="<?= date('Y-m-d') ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">
                        <?= svg_icon('calendar', 14) ?> تاريخ الانتهاء <span class="required">*</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" required
                        value="<?= date('Y-m-d') ?>" class="form-control">
                </div>
            </div>

            <!-- التوقيت اليومي (من/إلى) -->
            <div style="background:var(--c-brand-light);border:1px solid rgba(13,122,111,.2);border-radius:var(--r-md);padding:var(--sp-5);margin-bottom:var(--sp-5);">
                <p style="font-weight:700;font-family:var(--font-head);font-size:.9rem;margin-bottom:var(--sp-4);color:var(--c-brand-dark);">
                    <?= svg_icon('clock', 16) ?> التوقيت اليومي للنشاط
                    <span style="font-size:.78rem;color:var(--c-text-muted);font-weight:400;margin-inline-start:8px;">يُطبَّق على جميع أيام المبادرة</span>
                </p>
                <div class="form-row-3">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="daily_start_time">
                            وقت البدء اليومي <span class="required">*</span>
                        </label>
                        <input type="time" id="daily_start_time" name="daily_start_time" required
                            value="08:30" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="daily_end_time">
                            وقت الانتهاء اليومي <span class="required">*</span>
                        </label>
                        <input type="time" id="daily_end_time" name="daily_end_time" required
                            value="12:30" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="hours_count">
                            الساعات المعتمدة/يوم <span class="required">*</span>
                        </label>
                        <input type="number" id="hours_count" name="hours_count" required
                            min="1" max="12" value="4" class="form-control">
                        <p class="form-hint">تُودع في جواز المتطوع</p>
                    </div>
                </div>
            </div>

            <!-- الحد الأدنى للمتطوعين -->
            <div class="form-group">
                <label class="form-label" for="min_volunteers">
                    <?= svg_icon('users', 14) ?> الحد الأدنى للمتطوعين المطلوبين <span class="required">*</span>
                </label>
                <input type="number" id="min_volunteers" name="min_volunteers" required
                    min="1" max="500" value="15" class="form-control" style="max-width:200px;">
                <p class="form-hint">
                    الحد الأدنى لانطلاق النشاط. التسجيل يبقى متاحاً للمتطوعين حتى بعد تجاوز هذا النصاب.
                </p>
            </div>

            <!-- الوصف -->
            <div class="form-group">
                <label class="form-label" for="description">
                    وصف النشاط والمهام المطلوبة <span class="required">*</span>
                </label>
                <textarea id="description" name="description" required class="form-control"
                    rows="5" placeholder="اذكر أهداف الحملة، ما المطلوب من المتطوعين، المعدات والتجهيزات المتوفرة، وبرنامج العمل..."></textarea>
            </div>

            <!-- صورة الغلاف الرئيسية -->
            <div class="form-group">
                <label class="form-label">
                    <?= svg_icon('image', 14) ?> صورة الغلاف الرئيسية / البوستر (Poster)
                    <span style="font-size:.78rem;color:var(--c-text-muted);font-weight:400;">(JPG, PNG, WEBP — حجم أقصى 2MB)</span>
                </label>
                <div class="upload-zone">
                    <input type="file" name="campaign_image" accept="image/*" id="campaignImgInput">
                    <div class="upload-icon"><?= svg_icon('upload', 36) ?></div>
                    <div class="upload-zone-title">اسحب البوستر أو صورة الغلاف هنا أو اضغط للاختيار</div>
                    <div class="upload-zone-sub">JPG · PNG · WEBP — بحد أقصى 2MB</div>
                </div>
                <div id="previewCampaign" style="margin-top:var(--sp-3);display:none;">
                    <img id="previewCampaignImg" src="" alt="معاينة" style="max-height:160px;border-radius:var(--r-sm);border:1px solid var(--c-border);">
                </div>
            </div>

            <!-- ألبوم صور النشاط الميداني والموقع -->
            <div class="form-group" style="background:var(--c-surface-alt); padding:var(--sp-4); border-radius:var(--r-md); border:1px dashed var(--c-border-light);">
                <label class="form-label" style="margin-bottom:var(--sp-2);">
                    <?= svg_icon('image', 14) ?> صور إضافية لمكان العمل والميدان (ألبوم متعدد الصور)
                    <span style="font-size:.78rem;color:var(--c-text-muted);font-weight:400;">(يمكنك اختيار عدة صور معاً: بوستر، خريطة، صور الميدان)</span>
                </label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple class="form-control">
                <p class="form-hint">بإمكانك رفع عدة صور للنشاط ستظهر في ألبوم خاص بصفحة الحملة.</p>
            </div>

            <div style="display:flex;gap:var(--sp-3);margin-top:var(--sp-6);">
                <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
                    <?= svg_icon('plus-circle', 18) ?> نشر الفرصة وإشعار المتابعين
                </button>
                <a href="<?= url('club_dashboard') ?>" class="btn btn-ghost btn-lg">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('campaignImgInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        const wrap = document.getElementById('previewCampaign');
        const img  = document.getElementById('previewCampaignImg');
        img.src = ev.target.result;
        wrap.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
