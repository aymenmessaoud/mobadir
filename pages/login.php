<?php
/**
 * Login Page — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . url());
    exit;
}

$redirect = $_GET['redirect'] ?? '';

$page_title = "تسجيل الدخول";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8); padding-bottom:var(--sp-12);">
    <div class="form-card" style="max-width:480px;">
        <div style="text-align:center; margin-bottom:var(--sp-6);">
            <div class="brand-icon" style="margin:0 auto var(--sp-3); width:48px; height:48px;">
                <?= svg_icon('flag', 24) ?>
            </div>
            <h1 class="page-title" style="font-size:1.6rem;">تسجيل الدخول إلى <?= APP_NAME ?></h1>
            <p style="color:var(--c-text-muted); font-size:0.9rem; margin-top:4px;">
                المنصة الوطنية لتنسيق وتوثيق العمل التطوعي
            </p>
        </div>

        <form method="POST" action="<?= url('action_login') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

            <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" required class="form-control" placeholder="name@example.dz">
            </div>

            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--sp-1);">
                    <label class="form-label" style="margin-bottom:0;">كلمة المرور</label>
                    <a href="<?= url('forgot_password') ?>" style="font-size:0.8rem; color:var(--c-brand);">نسيت كلمة المرور؟</a>
                </div>
                <input type="password" name="password" required class="form-control" placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:var(--sp-4);">
                <?= svg_icon('user', 16) ?> دخول
            </button>
        </form>

        <div style="text-align:center; margin-top:var(--sp-5); font-size:0.9rem; color:var(--c-text-muted);">
            ليس لديك حساب بعد؟ <a href="<?= url('register') ?>" style="font-weight:700;">أنشئ حسابك الآن</a>
        </div>

        <!-- Demo Accounts Box -->
        <div style="margin-top:var(--sp-6); padding:var(--sp-4); background:var(--c-surface-alt); border-radius:var(--r-md); border:1px dashed var(--c-border-light); font-size:0.82rem;">
            <div style="font-weight:700; color:var(--c-brand); margin-bottom:var(--sp-2);">
                <?= svg_icon('shield', 13) ?> حسابات تجريبية سريعة (كلمة المرور: Test@123):
            </div>
            <ul style="list-style:none; display:flex; flex-direction:column; gap:6px; padding:0;">
                <li><strong>إدارة المديرية DJS:</strong> <code style="direction:ltr;display:inline-block;">admin@djs-bechar.dz</code></li>
                <li><strong>نادي شبابي:</strong> <code style="direction:ltr;display:inline-block;">club.kenadsa@djs-bechar.dz</code></li>
                <li><strong>شاب متطوع:</strong> <code style="direction:ltr;display:inline-block;">amine@volunteer.dz</code></li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
