<?php
/**
 * Clubs and Youth Institutions Directory — Mobadir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$db = get_db_connection();
$current_user = current_user();

$wilaya = trim($_GET['wilaya'] ?? '');

$sql = "
    SELECT cp.*, u.email, u.phone,
           (SELECT COUNT(*) FROM campaigns c WHERE c.club_id = cp.id AND c.status != 'cancelled') AS campaigns_count,
           (SELECT COUNT(*) FROM follows f WHERE f.club_id = cp.id) AS followers_count
";

if (is_volunteer()) {
    $sql .= ", (SELECT COUNT(*) FROM follows f WHERE f.club_id = cp.id AND f.volunteer_id = {$current_user['id']}) AS is_following";
} else {
    $sql .= ", 0 AS is_following";
}

$sql .= " FROM clubs_profile cp JOIN users u ON cp.user_id = u.id";

if (!empty($wilaya)) {
    $sql .= " WHERE cp.wilaya = " . $db->quote($wilaya);
}

$sql .= " ORDER BY followers_count DESC, cp.organization_name ASC";

$clubs = $db->query($sql)->fetchAll();
$wilayas = get_wilayas();

$page_title = "مؤسسات ونوادي الشباب الشريكة";
include __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--sp-6); padding-bottom:var(--sp-12);">

    <div class="page-header" style="padding-top:0;">
        <div class="page-header-inner">
            <div>
                <h1 class="page-title">دليل مؤسسات ونوادي الشباب</h1>
                <p class="page-sub">تابع النوادي المعتمدة لتصلك إشعارات فورية بكل فرصة تطوعية يطلقونها</p>
            </div>

            <form method="GET" action="<?= url('clubs') ?>" style="display:flex; gap:var(--sp-2);">
                <select name="wilaya" class="form-control" onchange="this.form.submit()" style="min-width:200px;">
                    <option value="">كل الولايات (الجزائر)</option>
                    <?php foreach ($wilayas as $w): ?>
                        <option value="<?= e($w) ?>" <?= $wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Clubs Grid -->
    <div class="clubs-grid">
        <?php foreach ($clubs as $club): ?>
            <article class="club-card">
                <div class="club-cover">
                    <?php if (!empty($club['cover_image'])): ?>
                        <img src="<?= e(url('uploads/logos/' . $club['cover_image'])) ?>" alt="غلاف">
                    <?php endif; ?>
                    <div class="club-logo-wrap">
                        <div class="club-logo">
                            <?php if (!empty($club['logo'])): ?>
                                <img src="<?= e(url('uploads/logos/' . $club['logo'])) ?>" alt="شعار">
                            <?php else: ?>
                                <?= svg_icon('building', 22) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="club-body">
                    <h3 class="club-name">
                        <a href="<?= url('club_detail?id=' . $club['id']) ?>"><?= e($club['organization_name']) ?></a>
                    </h3>
                    <div class="club-loc">
                        <?= svg_icon('map-pin', 12) ?>
                        <?= e($club['wilaya'] ?? 'الجزائر') ?> • <?= e($club['institution_type']) ?>
                    </div>

                    <p class="club-desc"><?= e($club['bio']) ?></p>

                    <div class="club-footer">
                        <div class="club-counters">
                            <span><strong><?= $club['campaigns_count'] ?></strong> حملة</span>
                            <span><strong><?= $club['followers_count'] ?></strong> متابع</span>
                        </div>

                        <div style="display:flex; gap:var(--sp-2);">
                            <a href="<?= url('club_detail?id=' . $club['id']) ?>" class="btn btn-outline btn-sm">
                                التفاصيل
                            </a>
                            <?php if (is_volunteer()): ?>
                                <form method="POST" action="<?= url('action_toggle_follow') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                    <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                    <button type="submit" class="btn btn-sm <?= $club['is_following'] ? 'btn-outline' : 'btn-primary' ?>">
                                        <?= $club['is_following'] ? svg_icon('check', 12).' متابَع' : svg_icon('bell', 12).' متابعة' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
