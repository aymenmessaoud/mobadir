<?php
/**
 * Forgot Password Page — Mobadir (مُبادِر)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . url());
    exit;
}

$page_title = "استعادة كلمة المرور";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8); padding-bottom:var(--sp-12);">
    <div class="form-card" style="max-width:480px; margin:0 auto;">
        <div style="text-align:center; margin-bottom:var(--sp-6);">
            <div class="brand-icon" style="margin:0 auto var(--sp-3); width:48px; height:48px; background:var(--c-brand-light); color:var(--c-brand);">
                <?= svg_icon('shield', 24) ?>
            </div>
            <h1 class="page-title" style="font-size:1.5rem;">استعادة كلمة المرور</h1>
            <p style="color:var(--c-text-muted); font-size:0.9rem; margin-top:6px;">
                أدخل بريدك الإلكتروني المسجل وسنساعدك في تعيين كلمة مرور جديدة لحسابك.
            </p>
        </div>

        <form method="POST" action="<?= url('action_request_password_reset') ?>">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom:var(--sp-5);">
                <label class="form-label">البريد الإلكتروني المسجل</label>
                <input type="email" name="email" required class="form-control" placeholder="name@example.dz" dir="ltr" autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <?= svg_icon('check-circle', 16) ?> إرسال رابط الاستعادة
            </button>
        </form>

        <div style="text-align:center; margin-top:var(--sp-5); font-size:0.9rem; color:var(--c-text-muted);">
            تذكرت كلمة المرور؟ <a href="<?= url('login') ?>" style="font-weight:700;">العودة لتسجيل الدخول</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
