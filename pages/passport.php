<?php
/**
 * Digital Volunteer Passport (جواز التطوع الرقمي) — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if (!is_volunteer() && !is_admin()) {
    set_flash('error', 'جواز التطوع الرقمي مخصص لحسابات المتطوعين فقط.');
    header('Location: ' . url());
    exit;
}

$db = get_db_connection();
$current_user = current_user();
$volunteer_id = $current_user['id'];

if (is_admin() && isset($_GET['user_id'])) {
    $volunteer_id = (int)$_GET['user_id'];
}

$stmt_user = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$volunteer_id]);
$volunteer = $stmt_user->fetch();

if (!$volunteer) {
    set_flash('error', 'المتطوع غير موجود.');
    header('Location: ' . url());
    exit;
}

$stmt_hours = $db->prepare("
    SELECT COALESCE(SUM(hours_awarded), 0) 
    FROM volunteering_applications 
    WHERE volunteer_id = ? AND status = 'attended'
");
$stmt_hours->execute([$volunteer_id]);
$total_hours = (int)$stmt_hours->fetchColumn();

$badge = calculate_volunteer_badge($total_hours);

$stmt_history = $db->prepare("
    SELECT va.*, c.title AS campaign_title, c.wilaya AS camp_wilaya, c.municipality AS camp_municipality, c.category, cp.organization_name, cp.institution_type
    FROM volunteering_applications va
    JOIN campaigns c ON va.campaign_id = c.id
    JOIN clubs_profile cp ON c.club_id = cp.id
    WHERE va.volunteer_id = ? AND va.status = 'attended'
    ORDER BY va.attended_at DESC, va.applied_at DESC
");
$stmt_history->execute([$volunteer_id]);
$attended_history = $stmt_history->fetchAll();

$volunteer_serial = sprintf("DZ-VOL-%04d", $volunteer['id']);

$page_title = "جواز التطوع الرقمي — " . $volunteer['name'];
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <!-- Header Section -->
    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div>
                <div style="display:flex; align-items:center; gap:var(--sp-3); margin-bottom:var(--sp-2);">
                    <div class="stat-icon" style="background:var(--c-brand-light); color:var(--c-brand-dark);">
                        <?= svg_icon('award', 24) ?>
                    </div>
                    <div>
                        <h1 class="page-title">جواز التطوع الرقمي الوطني</h1>
                        <p class="page-sub">الوثيقة الرقمية المعتمدة لاحتساب وتوثيق ساعات العمل التطوعي رسمياً</p>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:var(--sp-2); flex-wrap:wrap;">
                <a href="<?= url('certificate' . ($volunteer_id !== $current_user['id'] ? '?user_id=' . $volunteer_id : '')) ?>" class="btn btn-primary">
                    <?= svg_icon('shield', 16) ?> استخراج الشهادة الرسمية للطباعة
                </a>
                <?php if ($volunteer_id === $current_user['id']): ?>
                <a href="<?= url('profile_edit') ?>" class="btn btn-outline">
                    <?= svg_icon('edit', 16) ?> تعديل بيانات الحساب
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Passport Layout -->
    <div class="passport-layout">

        <!-- Front Card -->
        <div>
            <div class="passport-card">
                <div class="passport-head">
                    <div>
                        <div class="passport-badge-label">مُبادِر • MOBADIR</div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.7);">بطاقة متطوع مؤسسات الشباب</div>
                    </div>
                    <div class="passport-id"><?= $volunteer_serial ?></div>
                </div>

                <div class="passport-user">
                    <div class="passport-avatar">
                        <?php if (!empty($volunteer['avatar'])): ?>
                            <img src="<?= e(url('uploads/avatars/' . $volunteer['avatar'])) ?>" alt="<?= e($volunteer['name']) ?>">
                        <?php else: ?>
                            <?= mb_substr($volunteer['name'], 0, 1, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="passport-name"><?= e($volunteer['name']) ?></div>
                        <div class="passport-sub"><?= svg_icon('map-pin', 12) ?> <?= e($volunteer['wilaya'] ?? 'الجزائر') ?> • <?= e($volunteer['municipality']) ?></div>
                        <div class="passport-sub"><?= svg_icon('phone', 12) ?> <?= e($volunteer['phone']) ?></div>
                    </div>
                </div>

                <div class="passport-stats">
                    <div class="p-stat">
                        <div class="p-stat-num"><?= $total_hours ?></div>
                        <div class="p-stat-lbl">ساعة معتمدة</div>
                    </div>
                    <div class="p-stat">
                        <div style="font-size:1.4rem; font-weight:800; color:var(--c-accent);"><?= $badge['emoji'] ?></div>
                        <div class="p-stat-lbl"><?= $badge['title'] ?></div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-num"><?= count($attended_history) ?></div>
                        <div class="p-stat-lbl">مشاريع منجزة</div>
                    </div>
                </div>

                <?php if ($badge['next_target']): ?>
                    <div class="p-progress">
                        <div class="p-progress-header">
                            <span>الترقية إلى الشارة القادمة</span>
                            <span style="font-family:var(--font-num);"><?= $total_hours ?> / <?= $badge['next_target'] ?> ساعة</span>
                        </div>
                        <div class="p-progress-track">
                            <div class="p-progress-fill" style="width:<?= $badge['progress'] ?>%;"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="passport-qr-row">
                    <div class="passport-qr-note">
                        رمز QR للتحقق السريع من الساعات المعتمدة في قاعدة البيانات المركزية.
                    </div>
                    <div>
                        <?= render_qr_code("MOBADIR-VOL-{$volunteer['id']}-HOURS-{$total_hours}", 76) ?>
                    </div>
                </div>
            </div>

            <!-- Badge Box & System Explanation -->
            <div class="badge-row" style="margin-top:var(--sp-4); border-inline-start:4px solid <?= e($badge['badge_color'] ?? 'var(--c-brand)') ?>;">
                <div class="badge-icon" style="font-size:2rem;"><?= $badge['emoji'] ?></div>
                <div class="badge-info">
                    <div class="badge-title" style="color:<?= e($badge['badge_color'] ?? 'var(--c-brand-dark)') ?>;">الرتبة الشرفية: <?= $badge['title'] ?></div>
                    <div class="badge-desc"><?= $badge['description'] ?></div>
                    <div style="font-size:0.75rem; color:var(--c-text-muted); margin-top:4px;">
                        <?= svg_icon('shield', 11) ?> نظام آلي موحد: تُمنح وتترقى الشارات تلقائياً برمجياً فور اعتماد ساعات كل نشاط من إدارة النادي/المديرية.
                    </div>
                </div>
            </div>
        </div>

        <!-- History Ledger -->
        <div style="background:var(--c-surface); border:1px solid var(--c-border-light); border-radius:var(--r-lg); padding:var(--sp-6); box-shadow:var(--sh-1);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--sp-5);">
                <h2 style="font-family:var(--font-head); font-size:1.25rem; font-weight:800;">سجل الأنشطة التطوعية المعتمدة</h2>
                <span class="tag tag-teal"><?= count($attended_history) ?> نشاط موثق</span>
            </div>

            <?php if (empty($attended_history)): ?>
                <div class="empty-state" style="padding:var(--sp-10) var(--sp-4);">
                    <div class="empty-icon"><?= svg_icon('clock', 48) ?></div>
                    <h3 class="empty-title">لا توجد ساعات معتمدة حتى الآن</h3>
                    <p class="empty-sub">شارك في الفرص التطوعية الميدانية، وسيتم اعتماد حضورك وإيداع ساعاتك آلياً!</p>
                    <a href="<?= url('campaigns') ?>" class="btn btn-primary btn-sm">استكشف الفرص الآن</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الحملة</th>
                                <th>المؤسسة المشرفة</th>
                                <th>الموقع</th>
                                <th>الساعات</th>
                                <th>تاريخ الاعتماد</th>
                                <th>الشهادة الميدانية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attended_history as $row): ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?= url('campaign_detail?id='.$row['campaign_id']) ?>"><?= e($row['campaign_title']) ?></a></strong>
                                        <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($row['category']) ?></div>
                                    </td>
                                    <td>
                                        <div><?= e($row['organization_name']) ?></div>
                                        <div style="font-size:0.75rem; color:var(--c-text-muted);"><?= e($row['institution_type']) ?></div>
                                    </td>
                                    <td><?= e($row['camp_wilaya'] ?? $row['camp_municipality']) ?></td>
                                    <td>
                                        <span class="tag tag-green">
                                            <?= svg_icon('clock', 12) ?> <?= $row['hours_awarded'] ?> ساعات
                                        </span>
                                    </td>
                                    <td style="font-size:0.85rem; color:var(--c-text-muted);">
                                        <?= format_date_ar($row['attended_at'] ?: $row['applied_at']) ?>
                                    </td>
                                    <td>
                                        <a href="<?= url('certificate?campaign_id='.$row['campaign_id'].($volunteer_id !== $current_user['id'] ? '&user_id='.$volunteer_id : '')) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding:3px 8px; font-size:0.75rem;">
                                            <?= svg_icon('shield', 12) ?> شهادة النشاط
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:var(--sp-6); padding:var(--sp-4); background:var(--c-brand-light); border-radius:var(--r-md); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:var(--sp-3);">
                    <div>
                        <strong style="color:var(--c-brand-dark);">إجمالي الساعات المعتمدة في الجواز:</strong>
                        <span style="font-family:var(--font-num); font-size:1.3rem; font-weight:900; color:var(--c-brand-dark); margin-inline-start:var(--sp-2);">
                            <?= $total_hours ?> ساعة
                        </span>
                    </div>
                    <a href="<?= url('certificate' . ($volunteer_id !== $current_user['id'] ? '?user_id=' . $volunteer_id : '')) ?>" class="btn btn-primary btn-sm">
                        <?= svg_icon('shield', 14) ?> طباعة الشهادة
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
