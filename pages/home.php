<?php
/**
 * Home Page — Mobadir (مُبادِر)
 * National scope, SVG icons, proper hero + stats + featured campaigns + clubs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$db = get_db_connection();

// Live stats
$stats = [
    'hours'      => (int)$db->query("SELECT COALESCE(SUM(hours_awarded),0) FROM volunteering_applications WHERE status='attended'")->fetchColumn(),
    'volunteers' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='volunteer'")->fetchColumn(),
    'campaigns'  => (int)$db->query("SELECT COUNT(*) FROM campaigns WHERE status!='cancelled'")->fetchColumn(),
    'clubs'      => (int)$db->query("SELECT COUNT(*) FROM clubs_profile")->fetchColumn(),
];

// Featured campaigns
$featured = $db->query("
    SELECT c.*, cp.organization_name, cp.logo,
           (SELECT COUNT(*) FROM volunteering_applications va WHERE va.campaign_id=c.id AND va.status!='cancelled') AS current_applicants
    FROM campaigns c
    JOIN clubs_profile cp ON c.club_id=cp.id
    WHERE c.status='published'
    ORDER BY c.created_at DESC LIMIT 6
")->fetchAll();

// Active clubs
$clubs = $db->query("
    SELECT cp.*, u.email,
           (SELECT COUNT(*) FROM campaigns c WHERE c.club_id=cp.id) AS campaigns_count,
           (SELECT COUNT(*) FROM follows f WHERE f.club_id=cp.id) AS followers_count
    FROM clubs_profile cp
    JOIN users u ON cp.user_id=u.id
    LIMIT 4
")->fetchAll();

$page_title = 'الرئيسية';
include __DIR__ . '/../partials/header.php';
?>

<!-- ─── Hero ────────────────────────────────────────────── -->
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">
                    <?= svg_icon('shield', 13) ?>
                    تحت إشراف المديرية العامة للشباب والرياضة — الجزائر
                </div>
                <h1 class="hero-title">
                    استثمر وقتك، اصنع الأثر،<br>
                    ووثّق إنجازاتك التطوعية
                </h1>
                <p class="hero-desc">
                    المنصة الرقمية الموحدة لربط شباب الجزائر بالنوادي والجمعيات ومؤسسات الشباب.
                    انضم للحملات الميدانية بضغطة زر، واجمع ساعاتك المعتمدة في <strong>جواز التطوع الرقمي</strong>.
                </p>
                <div class="hero-cta">
                    <a href="<?= url('campaigns') ?>" class="btn btn-hero-primary btn-lg">
                        <?= svg_icon('flag', 18) ?> تصفح الفرص التطوعية
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?= url('register') ?>" class="btn btn-hero-outline btn-lg">
                            <?= svg_icon('plus', 18) ?> انضم مجاناً
                        </a>
                    <?php else: ?>
                        <?php
                            $dest = is_volunteer() ? url('passport') : (is_club() ? url('club_dashboard') : url('admin_dashboard'));
                            $label = is_volunteer() ? 'جواز تطوعي' : 'لوحة التحكم';
                        ?>
                        <a href="<?= $dest ?>" class="btn btn-hero-outline btn-lg">
                            <?= svg_icon('award', 18) ?> <?= $label ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Widget (hides on mobile) -->
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <div class="hero-stat-num"><?= number_format($stats['hours']) ?>+</div>
                    <div class="hero-stat-label">ساعة معتمدة</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num"><?= number_format($stats['volunteers']) ?></div>
                    <div class="hero-stat-label">متطوع مسجّل</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num"><?= number_format($stats['campaigns']) ?></div>
                    <div class="hero-stat-label">حملة تطوعية</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num"><?= number_format($stats['clubs']) ?></div>
                    <div class="hero-stat-label">مؤسسة شريكة</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── Stats Ribbon ──────────────────────────────────── -->
<div class="container">
    <div class="stats-ribbon">
        <div class="stat-card">
            <div class="stat-icon"><?= svg_icon('clock', 22) ?></div>
            <div>
                <div class="stat-num"><?= number_format($stats['hours']) ?>+</div>
                <div class="stat-lbl">ساعة تطوع معتمدة وموثقة</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(37,99,235,.1);color:#2563eb"><?= svg_icon('users', 22) ?></div>
            <div>
                <div class="stat-num"><?= number_format($stats['volunteers']) ?></div>
                <div class="stat-lbl">شاب متطوع منخرط بالمنصة</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(124,58,237,.1);color:#7c3aed"><?= svg_icon('flag', 22) ?></div>
            <div>
                <div class="stat-num"><?= number_format($stats['campaigns']) ?></div>
                <div class="stat-lbl">حملة وفرصة تطوعية ميدانية</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(244,161,0,.1);color:#c57f00"><?= svg_icon('building', 22) ?></div>
            <div>
                <div class="stat-num"><?= number_format($stats['clubs']) ?></div>
                <div class="stat-lbl">نادي ومؤسسة شبابية شريكة</div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Featured Campaigns ────────────────────────────── -->
<section class="section">
    <div class="container">
        <div class="section-head section-row">
            <div>
                <p class="section-eyebrow"><?= svg_icon('flag', 13) ?> الفرص التطوعية</p>
                <h2 class="section-title">أحدث الفرص التطوعية المتاحة</h2>
            </div>
            <a href="<?= url('campaigns') ?>" class="btn btn-outline btn-sm">
                عرض كل الفرص <?= svg_icon('chevron-right', 14) ?>
            </a>
        </div>

        <div class="campaigns-grid">
            <?php if (empty($featured)): ?>
                <div style="grid-column:1/-1">
                    <div class="empty-state">
                        <div class="empty-icon"><?= svg_icon('flag', 48) ?></div>
                        <h3 class="empty-title">لا توجد فرص منشورة حالياً</h3>
                        <p class="empty-sub">ارجع لاحقاً أو تابع نادياً لتصلك الإشعارات فور النشر</p>
                        <a href="<?= url('clubs') ?>" class="btn btn-primary">تابع نادياً</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($featured as $camp):
                    $applied  = (int)$camp['current_applicants'];
                    $min_vol  = (int)($camp['min_volunteers'] ?? 10);
                    $progress = $min_vol > 0 ? min(100, round(($applied / $min_vol) * 100)) : 0;
                    $time_from = format_time($camp['daily_start_time'] ?? '');
                    $time_to   = format_time($camp['daily_end_time']   ?? '');
                ?>
                    <article class="campaign-card">
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
                                    <span class="card-tag"><?= svg_icon('map-pin', 11) ?> <?= e($camp['wilaya'] ?? $camp['municipality']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-club">
                                <?= svg_icon('building', 13) ?> <?= e($camp['organization_name']) ?>
                            </div>
                            <h3 class="card-title"><?= e($camp['title']) ?></h3>
                            <p class="card-desc"><?= e($camp['description']) ?></p>
                            <div class="card-meta">
                                <div class="card-meta-row">
                                    <?= svg_icon('calendar', 13) ?>
                                    <?= format_date_ar($camp['start_date']) ?>
                                </div>
                                <?php if ($time_from && $time_to): ?>
                                <div class="card-meta-row">
                                    <?= svg_icon('clock', 13) ?>
                                    من <?= e($time_from) ?> إلى <?= e($time_to) ?>
                                    <span class="tag tag-teal" style="font-size:.72rem;padding:1px 7px;"><?= e($camp['hours_count']) ?> ساعة</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-header">
                                    <span>التسجيل</span>
                                    <span style="font-family:var(--font-num)"><?= $applied ?>/<?= $min_vol ?></span>
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
                                <span class="card-vacancies"><?= svg_icon('users', 12) ?> <?= max(0,$min_vol-$applied) ?> مطلوب</span>
                            <?php endif; ?>
                            <a href="<?= url('campaign_detail?id='.$camp['id']) ?>" class="btn btn-primary btn-sm">
                                انضم <?= svg_icon('chevron-right', 13) ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ─── Digital Passport Promo ─────────────────────────── -->
<section class="section" style="background:var(--c-surface-alt);border-top:1px solid var(--c-border-light);border-bottom:1px solid var(--c-border-light);">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-10);align-items:center;">
            <div>
                <p class="section-eyebrow"><?= svg_icon('award', 13) ?> ابتكار رقمي نوعي</p>
                <h2 class="section-title" style="font-size:1.7rem;margin-bottom:var(--sp-4);">
                    جواز التطوع الرقمي: وثيقة رسمية تثبت مساهمتك المجتمعية
                </h2>
                <p style="color:var(--c-text-muted);line-height:1.85;margin-bottom:var(--sp-5);">
                    مع <strong><?= APP_NAME ?></strong>، تُسجَّل كل ساعة تطوعية آلياً وتُعتمد رسمياً من إدارة مؤسسات الشباب.
                    احصل على شهادة موثقة برمز QR قابلة للرفع في سيرتك الذاتية وملفاتك الجامعية والمهنية.
                </p>
                <div style="display:flex;gap:var(--sp-3);flex-wrap:wrap;">
                    <a href="<?= url('passport') ?>" class="btn btn-primary">
                        <?= svg_icon('award', 16) ?> معاينة الجواز
                    </a>
                    <a href="<?= url('register') ?>" class="btn btn-outline">
                        أنشئ حسابك مجاناً
                    </a>
                </div>
            </div>

            <!-- Passport preview card -->
            <div class="passport-card" style="max-width:360px;margin:0 auto;">
                <div class="passport-head">
                    <span class="passport-badge-label">VOLUNTEER PASSPORT</span>
                    <span class="passport-id">DJS-2026-DEMO</span>
                </div>
                <div class="passport-user">
                    <div class="passport-avatar" style="font-family:var(--font-head);font-size:1.4rem;">م</div>
                    <div>
                        <div class="passport-name">أمين بلقاسم</div>
                        <div class="passport-sub">متطوع معتمد — 🥈 متطوع فاعل</div>
                    </div>
                </div>
                <div class="passport-stats">
                    <div class="p-stat">
                        <div class="p-stat-num">16</div>
                        <div class="p-stat-lbl">ساعة موثقة</div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-num">3</div>
                        <div class="p-stat-lbl">حملات منجزة</div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-num">2</div>
                        <div class="p-stat-lbl">شارات مكتسبة</div>
                    </div>
                </div>
                <div class="passport-qr-row">
                    <?= render_qr_code('DJS-2026-DEMO', 80) ?>
                    <div class="passport-qr-note">وثيقة رسمية معتمدة من DJS — مسح QR يؤكد الصحة</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── Active Clubs ───────────────────────────────────── -->
<section class="section">
    <div class="container">
        <div class="section-head section-row">
            <div>
                <p class="section-eyebrow"><?= svg_icon('building', 13) ?> المؤسسات الشريكة</p>
                <h2 class="section-title">نوادي وجمعيات الشباب المعتمدة</h2>
            </div>
            <a href="<?= url('clubs') ?>" class="btn btn-outline btn-sm">
                عرض الكل <?= svg_icon('chevron-right', 14) ?>
            </a>
        </div>

        <div class="clubs-grid">
            <?php foreach ($clubs as $club): ?>
                <article class="club-card">
                    <div class="club-cover">
                        <?php if (!empty($club['cover_image'])): ?>
                            <img src="<?= e(url('uploads/logos/' . $club['cover_image'])) ?>" alt="غلاف" loading="lazy">
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
                        <h3 class="club-name"><?= e($club['organization_name']) ?></h3>
                        <div class="club-loc">
                            <?= svg_icon('map-pin', 12) ?>
                            <?= e($club['wilaya'] ?? $club['municipality']) ?> — <?= e($club['institution_type']) ?>
                        </div>
                        <?php if (!empty($club['bio'])): ?>
                            <p class="club-desc"><?= e($club['bio']) ?></p>
                        <?php endif; ?>
                        <div class="club-footer">
                            <div class="club-counters">
                                <span><strong><?= $club['campaigns_count'] ?></strong> حملات</span>
                                <span><strong><?= $club['followers_count'] ?></strong> متابع</span>
                            </div>
                            <a href="<?= url('club_detail?id=' . $club['id']) ?>" class="btn btn-outline btn-sm">
                                زيارة <?= svg_icon('chevron-right', 13) ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
