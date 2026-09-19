<?php
/**
 * Dual Registration Page (Volunteer / Club) — Mobadir
 * Featuring cascading wilaya/commune selects, neighborhood/address detail, and social links
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . url());
    exit;
}

$wilayas = get_wilayas();
$selected_role = $_GET['role'] ?? 'volunteer';

$default_wilaya = '08 - بشار';
$communes = get_communes_for_wilaya($default_wilaya);

$page_title = "إنشاء حساب جديد";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8); padding-bottom:var(--sp-12);">
    <div class="form-card" style="max-width:720px;">
        <div style="text-align:center; margin-bottom:var(--sp-6);">
            <div class="brand-icon" style="margin:0 auto var(--sp-3); width:44px; height:44px;">
                <?= svg_icon('flag', 22) ?>
            </div>
            <h1 class="page-title" style="font-size:1.6rem;">انضم إلى منصة <?= APP_NAME ?></h1>
            <p style="color:var(--c-text-muted); font-size:0.9rem; margin-top:4px;">
                المنظومة الرقمية الوطنية الموحدة لتنسيق وتوثيق العمل التطوعي
            </p>
        </div>

        <!-- Role Selector Tabs -->
        <div style="display:flex; gap:var(--sp-2); margin-bottom:var(--sp-6); background:var(--c-surface-alt); padding:4px; border-radius:var(--r-md); border:1px solid var(--c-border-light);">
            <a href="<?= url('register?role=volunteer') ?>" class="btn <?= $selected_role === 'volunteer' ? 'btn-primary' : 'btn-ghost' ?>" style="flex:1;">
                <?= svg_icon('user', 15) ?> متطوع (فرد)
            </a>
            <a href="<?= url('register?role=club') ?>" class="btn <?= $selected_role === 'club' ? 'btn-primary' : 'btn-ghost' ?>" style="flex:1;">
                <?= svg_icon('building', 15) ?> نادٍ / جمعية شبابية
            </a>
        </div>

        <?php if ($selected_role === 'club'): ?>
            <!-- Club Registration Form -->
            <form method="POST" action="<?= url('action_register_club') ?>">
                <?= csrf_field() ?>

                <h3 style="font-family:var(--font-head); font-size:1.05rem; font-weight:700; color:var(--c-brand); margin-bottom:var(--sp-4);">
                    <?= svg_icon('building', 15) ?> بيانات النادي والمؤسسة الشبابية
                </h3>

                <div class="form-group">
                    <label class="form-label">اسم النادي أو الجمعية <span class="required">*</span></label>
                    <input type="text" name="org_name" required class="form-control" placeholder="مثال: نادي المواطنة والابتكار دار الشباب">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">نوع المؤسسة التابع لها <span class="required">*</span></label>
                        <select name="institution_type" required class="form-control">
                            <option value="دار الشباب">دار الشباب</option>
                            <option value="مركب التسلية العلمية">مركب التسلية العلمية</option>
                            <option value="بيت الشباب">بيت الشباب</option>
                            <option value="مركب رياضي جواري">مركب رياضي جواري</option>
                            <option value="قاعة متعددة النشاطات">قاعة متعددة النشاطات</option>
                            <option value="جمعية شبابية معتمدة">جمعية شبابية معتمدة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الولاية <span class="required">*</span></label>
                        <select id="clubRegWilaya" name="wilaya" required class="form-control" onchange="updateCommunes('clubRegWilaya', 'clubRegCommune')">
                            <?php foreach ($wilayas as $w): ?>
                                <option value="<?= e($w) ?>" <?= $w === $default_wilaya ? 'selected' : '' ?>><?= e($w) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">البلدية (تلقائية حسب الولاية) <span class="required">*</span></label>
                        <select id="clubRegCommune" name="municipality" required class="form-control">
                            <?php foreach ($communes as $c): ?>
                                <option value="<?= e($c) ?>"><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الحي أو المقر بالتفصيل</label>
                        <input type="text" name="address_details" class="form-control" placeholder="مثال: شارع 1 نوفمبر، حي الاستقلال">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">نبذة عن النشاطات والأهداف</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="المجالات، الفئات المستهدفة..."></textarea>
                </div>

                <h3 style="font-family:var(--font-head); font-size:1.05rem; font-weight:700; color:var(--c-brand); margin-top:var(--sp-6); margin-bottom:var(--sp-4);">
                    <?= svg_icon('user', 15) ?> بيانات ممثل النادي وحساب الدخول
                </h3>

                <div class="form-group">
                    <label class="form-label">اسم ولقب المسؤول <span class="required">*</span></label>
                    <input type="text" name="name" required class="form-control" placeholder="الاسم واللقب">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" name="email" required class="form-control" placeholder="club@example.dz">
                    </div>

                    <div class="form-group">
                        <label class="form-label">رقم الهاتف <span class="required">*</span></label>
                        <input type="tel" name="phone" required class="form-control" placeholder="06XXXXXXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="6" class="form-control" placeholder="••••••••">
                </div>

                <!-- روابط التواصل الاجتماعي -->
                <div style="background:var(--c-surface-alt); padding:var(--sp-4); border-radius:var(--r-md); margin-top:var(--sp-4);">
                    <h4 style="font-family:var(--font-head); font-size:0.92rem; font-weight:700; margin-bottom:var(--sp-3);">
                        <?= svg_icon('globe', 14) ?> روابط التواصل الاجتماعي للنادي (اختياري)
                    </h4>
                    <div class="form-row">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.8rem;">صفحة الفيسبوك</label>
                            <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/club">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.8rem;">حساب إنستغرام</label>
                            <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/club">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:var(--sp-6);">
                    <?= svg_icon('check-circle', 18) ?> تسجيل الحساب والانطلاق
                </button>
            </form>

        <?php else: ?>
            <!-- Volunteer Registration Form -->
            <form method="POST" action="<?= url('action_register_volunteer') ?>">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">الاسم واللقب الكامل <span class="required">*</span></label>
                    <input type="text" name="name" required class="form-control" placeholder="مثال: أمين بلقاسم">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" name="email" required class="form-control" placeholder="amine@example.dz">
                    </div>

                    <div class="form-group">
                        <label class="form-label">رقم الهاتف <span class="required">*</span></label>
                        <input type="tel" name="phone" required class="form-control" placeholder="06XXXXXXXX">
                    </div>
                </div>

                <!-- الولاية والبلدية المتسلسلة -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">الولاية <span class="required">*</span></label>
                        <select id="volRegWilaya" name="wilaya" required class="form-control" onchange="updateCommunes('volRegWilaya', 'volRegCommune')">
                            <?php foreach ($wilayas as $w): ?>
                                <option value="<?= e($w) ?>" <?= $w === $default_wilaya ? 'selected' : '' ?>><?= e($w) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">البلدية (تلقائية حسب الولاية) <span class="required">*</span></label>
                        <select id="volRegCommune" name="municipality" required class="form-control">
                            <?php foreach ($communes as $c): ?>
                                <option value="<?= e($c) ?>"><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">الحي أو العنوان السكني (اختياري)</label>
                    <input type="text" name="address_details" class="form-control" placeholder="مثال: حي السلام، عمارة 5">
                </div>

                <div class="form-group">
                    <label class="form-label">اهتماماتك ومهاراتك التطوعية (اختياري)</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="مثال: طالب جامعي مهتم بالبرمجة والبيئة وتنظيم التظاهرات..."></textarea>
                </div>

                <!-- روابط التواصل للمتطوع -->
                <div style="background:var(--c-surface-alt); padding:var(--sp-4); border-radius:var(--r-md); margin-bottom:var(--sp-4);">
                    <h4 style="font-family:var(--font-head); font-size:0.92rem; font-weight:700; margin-bottom:var(--sp-3);">
                        <?= svg_icon('globe', 14) ?> حساباتك على الشبكات المهنية والاجتماعية (اختياري)
                    </h4>
                    <div class="form-row">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.8rem;">رابط LinkedIn</label>
                            <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/username">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.8rem;">صفحة فيسبوك أو إنستغرام</label>
                            <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/username">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="6" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:var(--sp-4);">
                    <?= svg_icon('award', 18) ?> استخراج جواز التطوع وإنشاء الحساب
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align:center; margin-top:var(--sp-6); font-size:0.9rem; color:var(--c-text-muted);">
            لديك حساب مسبقاً؟ <a href="<?= url('login') ?>" style="font-weight:700;">تسجيل الدخول</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
