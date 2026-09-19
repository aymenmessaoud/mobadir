<?php
/**
 * Reset Password Page — Mobadir (مُبادِر)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . url());
    exit;
}

$token = trim($_GET['token'] ?? '');
$db = get_db_connection();

$reset_record = null;
if (!empty($token)) {
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $reset_record = $stmt->fetch();
}

$page_title = "تعيين كلمة مرور جديدة";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-8); padding-bottom:var(--sp-12);">
    <div class="form-card" style="max-width:480px; margin:0 auto;">
        
        <?php if (!$reset_record): ?>
            <!-- Invalid / Expired Token State -->
            <div style="text-align:center; padding:var(--sp-4) 0;">
                <div style="width:54px; height:54px; border-radius:50%; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; margin:0 auto var(--sp-4);">
                    <?= svg_icon('x', 28) ?>
                </div>
                <h1 class="page-title" style="font-size:1.4rem; color:var(--c-error);">رابط الاستعادة غير صالح أو منتهي</h1>
                <p style="color:var(--c-text-muted); font-size:0.9rem; margin:var(--sp-3) 0 var(--sp-6);">
                    انتهت صلاحية رابط استعادة كلمة المرور (مدته ساعة واحدة)، أو أنه تم استخدامه مسبقاً.
                </p>
                <a href="<?= url('forgot_password') ?>" class="btn btn-primary">
                    <?= svg_icon('shield', 16) ?> طلب رابط جديد
                </a>
            </div>
        <?php else: ?>
            <!-- Reset Password Form -->
            <div style="text-align:center; margin-bottom:var(--sp-6);">
                <div class="brand-icon" style="margin:0 auto var(--sp-3); width:48px; height:48px; background:var(--c-brand-light); color:var(--c-brand);">
                    <?= svg_icon('shield', 24) ?>
                </div>
                <h1 class="page-title" style="font-size:1.5rem;">تعيين كلمة مرور جديدة</h1>
                <p style="color:var(--c-text-muted); font-size:0.9rem; margin-top:6px;">
                    لحساب: <strong><?= e($reset_record['email']) ?></strong>
                </p>
            </div>

            <form method="POST" action="<?= url('action_reset_password') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group" style="margin-bottom:var(--sp-4);">
                    <label class="form-label">كلمة المرور الجديدة (6 أحرف على الأقل)</label>
                    <input type="password" name="password" required minlength="6" class="form-control" placeholder="••••••••" autofocus>
                </div>

                <div class="form-group" style="margin-bottom:var(--sp-5);">
                    <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" name="password_confirm" required minlength="6" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= svg_icon('check-circle', 16) ?> حفظ كلمة المرور وتسجيل الدخول
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align:center; margin-top:var(--sp-5); font-size:0.9rem; color:var(--c-text-muted);">
            <a href="<?= url('login') ?>">العودة إلى صفحة الدخول</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
