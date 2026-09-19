<?php
/**
 * Edit Volunteering Campaign Form — Club Dashboard
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
$id = (int)($_GET['id'] ?? 0);
$current_user = current_user();

$stmt = $db->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$id]);
$camp = $stmt->fetch();

if (!$camp || (!is_admin() && $camp['club_id'] != $current_user['club_id'])) {
    set_flash('error', 'الحملة غير موجودة أو لا تملك صلاحية تعديلها.');
    header('Location: ' . url('club_dashboard'));
    exit;
}

$wilayas = get_wilayas();
$categories = get_categories();

$selected_wilaya = $camp['wilaya'] ?? '08 - بشار';
$communes = get_communes_for_wilaya($selected_wilaya);
$selected_commune = $camp['municipality'] ?? ($communes[0] ?? '');

// Existing gallery
$stmt_g = $db->prepare("SELECT * FROM campaign_images WHERE campaign_id = ?");
$stmt_g->execute([$id]);
$existing_gallery = $stmt_g->fetchAll();

$page_title = "تعديل حملة — " . $camp['title'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8); padding-bottom:var(--sp-12);">
    <div class="form-card form-card-wide">

        <div class="breadcrumb" style="margin-bottom:var(--sp-5);">
            <?= svg_icon('bar-chart', 13) ?>
            <a href="<?= url('club_dashboard') ?>">لوحة النادي</a>
            <?= svg_icon('chevron-right', 11) ?>
            <span>تعديل فرصة</span>
        </div>

        <div style="margin-bottom:var(--sp-6);">
            <h1 class="page-title"><?= svg_icon('edit', 22) ?> تعديل بيانات النشاط التطوعي</h1>
        </div>

        <form method="POST" action="<?= url('action_update_campaign') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">

            <div class="form-group">
                <label class="form-label" for="title">
                    <?= svg_icon('flag', 14) ?> عنوان النشاط التطوعي <span class="required">*</span>
                </label>
                <input type="text" id="title" name="title" required value="<?= e($camp['title']) ?>" class="form-control">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category">
                        <?= svg_icon('filter', 14) ?> المجال / الفئة <span class="required">*</span>
                    </label>
                    <select id="category" name="category" required class="form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $camp['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editWilaya">
                        <?= svg_icon('map-pin', 14) ?> الولاية <span class="required">*</span>
                    </label>
                    <select id="editWilaya" name="wilaya" required class="form-control" onchange="updateCommunes('editWilaya', 'editCommune')">
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= e($w) ?>" <?= $selected_wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="editCommune">البلدية <span class="required">*</span></label>
                    <select id="editCommune" name="municipality" required class="form-control">
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= e($c) ?>" <?= $selected_commune === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location_name">
                        <?= svg_icon('map-pin', 14) ?> المكان الميداني أو الحي المحدد <span class="required">*</span>
                    </label>
                    <input type="text" id="location_name" name="location_name" required value="<?= e($camp['location_name']) ?>" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">
                        <?= svg_icon('calendar', 14) ?> تاريخ البدء <span class="required">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" required value="<?= $camp['start_date'] ?>" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="end_date">
                        <?= svg_icon('calendar', 14) ?> تاريخ الانتهاء <span class="required">*</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" required value="<?= $camp['end_date'] ?>" class="form-control">
                </div>
            </div>

            <!-- التوقيت اليومي -->
            <div style="background:var(--c-brand-light);border:1px solid rgba(13,122,111,.2);border-radius:var(--r-md);padding:var(--sp-5);margin-bottom:var(--sp-5);">
                <p style="font-weight:700;font-family:var(--font-head);font-size:.9rem;margin-bottom:var(--sp-4);color:var(--c-brand-dark);">
                    <?= svg_icon('clock', 16) ?> التوقيت اليومي للنشاط
                </p>
                <div class="form-row-3">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="daily_start_time">وقت البدء <span class="required">*</span></label>
                        <input type="time" id="daily_start_time" name="daily_start_time" required value="<?= e(format_time($camp['daily_start_time'] ?? '08:30')) ?>" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="daily_end_time">وقت الانتهاء <span class="required">*</span></label>
                        <input type="time" id="daily_end_time" name="daily_end_time" required value="<?= e(format_time($camp['daily_end_time'] ?? '12:30')) ?>" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="hours_count">الساعات المعتمدة/يوم <span class="required">*</span></label>
                        <input type="number" id="hours_count" name="hours_count" required min="1" max="12" value="<?= $camp['hours_count'] ?>" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="min_volunteers">
                        <?= svg_icon('users', 14) ?> الحد الأدنى للمتطوعين <span class="required">*</span>
                    </label>
                    <input type="number" id="min_volunteers" name="min_volunteers" required min="1" max="500" value="<?= $camp['min_volunteers'] ?>" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">حالة النشاط <span class="required">*</span></label>
                    <select id="status" name="status" class="form-control">
                        <option value="published" <?= $camp['status'] === 'published' ? 'selected' : '' ?>>نشطة (منشورة)</option>
                        <option value="completed" <?= $camp['status'] === 'completed' ? 'selected' : '' ?>>مكتملة</option>
                        <option value="cancelled" <?= $camp['status'] === 'cancelled' ? 'selected' : '' ?>>ملغاة</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">وصف النشاط والمهام المطلوبة <span class="required">*</span></label>
                <textarea id="description" name="description" required class="form-control" rows="5"><?= e($camp['description']) ?></textarea>
            </div>

            <!-- تحديث صورة الغلاف -->
            <div class="form-group">
                <label class="form-label">
                    <?= svg_icon('image', 14) ?> تعديل صورة الغلاف الرئيسية (اختياري)
                </label>
                <?php if (!empty($camp['image'])): ?>
                    <div style="margin-bottom:var(--sp-3);">
                        <img src="<?= e(url('uploads/campaigns/' . $camp['image'])) ?>" alt="غلاف حالي" style="max-height:120px;border-radius:var(--r-sm);border:1px solid var(--c-border);">
                    </div>
                <?php endif; ?>
                <input type="file" name="campaign_image" accept="image/*" class="form-control">
            </div>

            <!-- رفع صور إضافية للألبوم -->
            <div class="form-group" style="background:var(--c-surface-alt); padding:var(--sp-4); border-radius:var(--r-md); border:1px dashed var(--c-border-light);">
                <label class="form-label">
                    <?= svg_icon('image', 14) ?> إضافة صور جديدة لألبوم النشاط والميدان
                </label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple class="form-control">
                <?php if (!empty($existing_gallery)): ?>
                    <div style="display:flex; gap:var(--sp-2); margin-top:var(--sp-3); flex-wrap:wrap;">
                        <?php foreach ($existing_gallery as $eg): ?>
                            <img src="<?= e(url('uploads/campaigns/' . $eg['image_path'])) ?>" alt="صورة" style="width:60px; height:60px; object-fit:cover; border-radius:var(--r-xs); border:1px solid var(--c-border);">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:var(--sp-3);margin-top:var(--sp-6);">
                <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
                    <?= svg_icon('edit', 18) ?> حفظ التعديلات
                </button>
                <a href="<?= url('club_dashboard') ?>" class="btn btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
