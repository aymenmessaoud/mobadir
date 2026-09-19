<?php
/**
 * Profile Edit Page — Mobadir (مُبادِر)
 * Allows volunteers and clubs to edit their account information.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
if (is_admin()) { header('Location: ' . url('admin_dashboard')); exit; }

$db = get_db_connection();
$user_id = $_SESSION['user']['id'];

// Fetch fresh user data from DB
$stmt_user = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

$club = null;
if (is_club()) {
    $stmt_club = $db->prepare("SELECT * FROM clubs_profile WHERE user_id = ?");
    $stmt_club->execute([$user_id]);
    $club = $stmt_club->fetch();
}

$wilayas = get_wilayas();
$current_wilaya = $user['wilaya'] ?? '08 - بشار';
$current_municipality = $user['municipality'] ?? '';

$page_title = 'تعديل بيانات الحساب';
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12); max-width:780px; margin:0 auto;">

    <!-- Page Header -->
    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div style="display:flex; align-items:center; gap:var(--sp-3);">
                <div class="stat-icon" style="width:44px; height:44px;">
                    <?= svg_icon('settings', 22) ?>
                </div>
                <div>
                    <h1 class="page-title">تعديل بيانات الحساب</h1>
                    <div class="page-sub">تحديث معلوماتك الشخصية وصورتك وروابط التواصل</div>
                </div>
            </div>
            <a href="<?= is_club() ? url('club_dashboard') : url('passport') ?>" class="btn btn-outline btn-sm">
                <?= svg_icon('chevron-right', 14) ?> العودة
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('action_update_profile') ?>" enctype="multipart/form-data"
          style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1);">
        <?= csrf_field() ?>

        <!-- ═══ Avatar / Photo Section ═══ -->
        <div style="text-align:center; margin-bottom:var(--sp-6); padding-bottom:var(--sp-5); border-bottom:1px solid var(--c-border-light);">
            <div style="position:relative; width:80px; height:80px; margin:0 auto var(--sp-3);">
                <?php if (!empty($user['avatar'])): ?>
                    <img id="avatar_preview" src="<?= e(url('uploads/avatars/' . $user['avatar'])) ?>" alt="<?= e($user['name']) ?>"
                         style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--c-brand);">
                <?php else: ?>
                    <div id="avatar_fallback" style="width:80px; height:80px; border-radius:50%; background:var(--c-brand); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; color:#fff; font-family:var(--font-head); border:3px solid var(--c-brand-dark);">
                        <?= e(mb_substr($user['name'], 0, 1, 'UTF-8')) ?>
                    </div>
                    <img id="avatar_preview" src="" alt="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--c-brand); display:none;">
                <?php endif; ?>
            </div>
            <label class="btn btn-outline btn-sm" style="cursor:pointer;">
                <?= svg_icon('upload', 14) ?> تغيير الصورة
                <input type="file" name="avatar" accept="image/*" id="avatar_input" style="display:none;">
            </label>
        </div>

        <!-- ═══ Personal Info Section ═══ -->
        <h3 style="font-family:var(--font-head); font-size:1rem; font-weight:700; margin-bottom:var(--sp-4); display:flex; align-items:center; gap:var(--sp-2);">
            <?= svg_icon('user', 18) ?> المعلومات الشخصية
        </h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); margin-bottom:var(--sp-5);">
            <div>
                <label class="form-label">الاسم الكامل <span style="color:var(--c-error);">*</span></label>
                <input type="text" name="name" value="<?= e($user['name']) ?>" required class="form-control">
            </div>
            <div>
                <label class="form-label">رقم الهاتف <span style="color:var(--c-error);">*</span></label>
                <input type="tel" name="phone" value="<?= e($user['phone']) ?>" required class="form-control" dir="ltr">
            </div>
        </div>

        <!-- ═══ Location Section ═══ -->
        <h3 style="font-family:var(--font-head); font-size:1rem; font-weight:700; margin-bottom:var(--sp-4); display:flex; align-items:center; gap:var(--sp-2);">
            <?= svg_icon('map-pin', 18) ?> الموقع الجغرافي
        </h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); margin-bottom:var(--sp-4);">
            <div>
                <label class="form-label">الولاية <span style="color:var(--c-error);">*</span></label>
                <select name="wilaya" id="wilaya_select" class="form-control" required>
                    <?php foreach ($wilayas as $w): ?>
                        <option value="<?= e($w) ?>" <?= $current_wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">البلدية <span style="color:var(--c-error);">*</span></label>
                <select name="municipality" id="commune_select" class="form-control" required>
                    <option value="">— اختر البلدية —</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:var(--sp-5);">
            <label class="form-label">تفاصيل العنوان (الحي، الشارع)</label>
            <input type="text" name="address_details" value="<?= e($user['address_details'] ?? '') ?>" class="form-control" placeholder="مثال: حي 120 مسكن، شارع الاستقلال">
        </div>

        <!-- ═══ Bio Section ═══ -->
        <div style="margin-bottom:var(--sp-5);">
            <label class="form-label">نبذة تعريفية</label>
            <textarea name="bio" class="form-control" rows="3" maxlength="500" placeholder="تعريف موجز بنفسك أو بمؤسستك..."><?= e($user['bio'] ?? '') ?></textarea>
        </div>

        <?php if (is_club() && $club): ?>
        <!-- ═══ Club-Specific Section ═══ -->
        <h3 style="font-family:var(--font-head); font-size:1rem; font-weight:700; margin-bottom:var(--sp-4); display:flex; align-items:center; gap:var(--sp-2); padding-top:var(--sp-4); border-top:1px solid var(--c-border-light);">
            <?= svg_icon('building', 18) ?> بيانات المؤسسة / النادي
        </h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); margin-bottom:var(--sp-4);">
            <div>
                <label class="form-label">اسم المؤسسة / النادي <span style="color:var(--c-error);">*</span></label>
                <input type="text" name="org_name" value="<?= e($club['organization_name']) ?>" required class="form-control">
            </div>
            <div>
                <label class="form-label">نوع المؤسسة</label>
                <select name="institution_type" class="form-control">
                    <?php foreach (get_institution_types() as $it): ?>
                        <option value="<?= e($it) ?>" <?= ($club['institution_type'] ?? '') === $it ? 'selected' : '' ?>><?= e($it) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-bottom:var(--sp-4);">
            <label class="form-label">العنوان البريدي</label>
            <input type="text" name="address" value="<?= e($club['address'] ?? '') ?>" class="form-control" placeholder="العنوان الرسمي للمؤسسة">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); margin-bottom:var(--sp-5);">
            <div>
                <label class="form-label">شعار المؤسسة (Logo)</label>
                <input type="file" name="logo" accept="image/*" class="form-control">
                <?php if (!empty($club['logo'])): ?>
                    <img src="<?= e(url('uploads/avatars/' . $club['logo'])) ?>" alt="شعار" style="width:48px; height:48px; border-radius:var(--r-md); object-fit:cover; margin-top:8px; border:1px solid var(--c-border-light);">
                <?php endif; ?>
            </div>
            <div>
                <label class="form-label">صورة الغلاف</label>
                <input type="file" name="cover_image" accept="image/*" class="form-control">
                <?php if (!empty($club['cover_image'])): ?>
                    <img src="<?= e(url('uploads/campaigns/' . $club['cover_image'])) ?>" alt="غلاف" style="width:120px; height:48px; border-radius:var(--r-md); object-fit:cover; margin-top:8px; border:1px solid var(--c-border-light);">
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═══ Social Links Section ═══ -->
        <h3 style="font-family:var(--font-head); font-size:1rem; font-weight:700; margin-bottom:var(--sp-4); display:flex; align-items:center; gap:var(--sp-2); padding-top:var(--sp-4); border-top:1px solid var(--c-border-light);">
            <?= svg_icon('link', 18) ?> روابط التواصل الاجتماعي
        </h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); margin-bottom:var(--sp-5);">
            <?php if (is_volunteer()): ?>
                <div>
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="linkedin_url" value="<?= e($user['linkedin_url'] ?? '') ?>" class="form-control" dir="ltr" placeholder="https://linkedin.com/in/...">
                </div>
            <?php endif; ?>
            <div>
                <label class="form-label">Facebook</label>
                <input type="url" name="facebook_url" value="<?= e($user['facebook_url'] ?? ($club['facebook_url'] ?? '')) ?>" class="form-control" dir="ltr" placeholder="https://facebook.com/...">
            </div>
            <div>
                <label class="form-label">Instagram</label>
                <input type="url" name="instagram_url" value="<?= e($user['instagram_url'] ?? ($club['instagram_url'] ?? '')) ?>" class="form-control" dir="ltr" placeholder="https://instagram.com/...">
            </div>
            <?php if (is_club()): ?>
                <div>
                    <label class="form-label">الموقع الإلكتروني</label>
                    <input type="url" name="website_url" value="<?= e($club['website_url'] ?? '') ?>" class="form-control" dir="ltr" placeholder="https://...">
                </div>
            <?php endif; ?>
        </div>

        <!-- ═══ Submit ═══ -->
        <div style="display:flex; gap:var(--sp-3); justify-content:flex-start; padding-top:var(--sp-4); border-top:1px solid var(--c-border-light);">
            <button type="submit" class="btn btn-primary">
                <?= svg_icon('check', 16) ?> حفظ التعديلات
            </button>
            <a href="<?= is_club() ? url('club_dashboard') : url('passport') ?>" class="btn btn-ghost">إلغاء</a>
        </div>
    </form>
</div>

<script>
// Avatar preview on file select
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('avatar_input');
    if (input) {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('avatar_preview');
                    var fallback = document.getElementById('avatar_fallback');
                    if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
                    if (fallback) { fallback.style.display = 'none'; }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Cascading wilaya/commune
    var ws = document.getElementById('wilaya_select');
    if (ws && window.updateCommunes) {
        ws.addEventListener('change', function() {
            window.updateCommunes('wilaya_select', 'commune_select', '');
        });
        // Pre-select current commune
        window.updateCommunes('wilaya_select', 'commune_select', '<?= e($current_municipality) ?>');
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
