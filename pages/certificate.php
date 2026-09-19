<?php
/**
 * Official Verified Volunteering Certificate (Printable / PDF) — Mobadir
 * Supports:
 * 1) Specific Campaign Activity Certificate (شهادة إتمام نشاط تطوعي ميداني برعاية النادي والمديرية)
 * 2) Comprehensive Cumulative Passport Certificate (شهادة إثبات رصيد ساعات العمل التطوعي الوطني)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$db = get_db_connection();
$current_user = current_user();
$volunteer_id = $current_user['id'];
$campaign_id = (int)($_GET['campaign_id'] ?? 0);

if (is_admin() && isset($_GET['user_id'])) {
    $volunteer_id = (int)$_GET['user_id'];
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$volunteer_id]);
$volunteer = $stmt->fetch();

if (!$volunteer) {
    die("المتطوع غير موجود.");
}

$is_single_campaign = false;
$single_camp = null;

if ($campaign_id > 0) {
    $stmt_single = $db->prepare("
        SELECT va.*, c.title AS campaign_title, c.category, c.start_date, c.end_date,
               c.daily_start_time, c.daily_end_time, c.location_name, c.wilaya AS camp_wilaya, c.municipality AS camp_municipality,
               cp.organization_name, cp.institution_type, cp.wilaya AS club_wilaya, cp.logo AS club_logo
        FROM volunteering_applications va
        JOIN campaigns c ON va.campaign_id = c.id
        JOIN clubs_profile cp ON c.club_id = cp.id
        WHERE va.volunteer_id = ? AND va.campaign_id = ? AND va.status = 'attended'
    ");
    $stmt_single->execute([$volunteer_id, $campaign_id]);
    $single_camp = $stmt_single->fetch();

    if ($single_camp) {
        $is_single_campaign = true;
    }
}

// Compute total accredited hours
$stmt_hours = $db->prepare("
    SELECT COALESCE(SUM(hours_awarded), 0) 
    FROM volunteering_applications 
    WHERE volunteer_id = ? AND status = 'attended'
");
$stmt_hours->execute([$volunteer_id]);
$total_hours = (int)$stmt_hours->fetchColumn();

// Fetch activities summary for comprehensive certificate
$stmt_history = $db->prepare("
    SELECT va.*, c.title AS campaign_title, cp.organization_name
    FROM volunteering_applications va
    JOIN campaigns c ON va.campaign_id = c.id
    JOIN clubs_profile cp ON c.club_id = cp.id
    WHERE va.volunteer_id = ? AND va.status = 'attended'
    ORDER BY va.attended_at DESC
");
$stmt_history->execute([$volunteer_id]);
$activities = $stmt_history->fetchAll();

if ($is_single_campaign) {
    $certificate_no = sprintf("DJS-ACT-%04d-%04d-%04d", $volunteer['id'], $campaign_id, date('Y'));
    $verification_hash = hash('sha256', "MOBADIR-ACT-{$volunteer['id']}-{$campaign_id}-{$single_camp['hours_awarded']}-{$certificate_no}");
    $page_title = "شهادة مشاركة ميدانية — " . $single_camp['campaign_title'];
} else {
    $certificate_no = sprintf("DJS-DZ-VOL-%04d-%04d", $volunteer['id'], date('Y'));
    $verification_hash = hash('sha256', "MOBADIR-{$volunteer['id']}-{$total_hours}-{$certificate_no}");
    $page_title = "شهادة إثبات العمل التطوعي — " . $volunteer['name'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=2.0') ?>">
    <style>
        body { background-color: #f1f5f9; padding: 24px; font-family: 'Noto Naskh Arabic', sans-serif; }
        .cert-paper {
            max-width: 860px; margin: 0 auto; background: #fff; color: #0f172a;
            padding: 48px 56px; border: 8px double #0d7a6f; box-shadow: var(--sh-3);
            border-radius: 6px; text-align: center; position: relative;
        }
        .cert-republic { font-family: 'Noto Kufi Arabic', sans-serif; font-weight: 800; font-size: 1.15rem; color: #0d7a6f; }
        .cert-ministry { font-family: 'Noto Kufi Arabic', sans-serif; font-weight: 700; font-size: 1rem; margin-top: 4px; color: #1e293b; }
        .cert-title { font-family: 'Noto Kufi Arabic', sans-serif; font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 24px 0 16px; border-bottom: 2px solid #0d7a6f; display: inline-block; padding-bottom: 8px; }
        .cert-user-name { font-family: 'Noto Kufi Arabic', sans-serif; font-size: 2.1rem; font-weight: 800; color: #0d7a6f; margin: 14px 0; }
        .cert-hours-box { display: inline-block; background: #f0fdf4; border: 2px dashed #059669; padding: 12px 32px; border-radius: 8px; margin: 18px 0; }
        .cert-hours-number { font-family: 'Inter', sans-serif; font-size: 2.2rem; font-weight: 900; color: #059669; }
        .seals-grid { display: flex; justify-content: space-around; align-items: center; margin-top: 32px; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
        .seal-box { border: 2px solid #0d7a6f; border-radius: 50%; width: 105px; height: 105px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.72rem; color: #0d7a6f; font-weight: 700; line-height: 1.3; }
        @media print {
            body { background: transparent; padding: 0; }
            .no-print { display: none !important; }
            .cert-paper { border-width: 6px; box-shadow: none; max-width: 100%; padding: 40px; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 860px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= url('passport') ?>" class="btn btn-outline">
        <?= svg_icon('chevron-right', 14) ?> العودة إلى جواز التطوع
    </a>
    <button onclick="window.print();" class="btn btn-primary">
        <?= svg_icon('shield', 16) ?> طباعة الشهادة الرسمية / حفظ كملف PDF
    </button>
</div>

<!-- Certificate Sheet -->
<div class="cert-paper">
    <div style="margin-bottom: 24px;">
        <div class="cert-republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
        <div class="cert-ministry">وزارة الشباب والرياضة</div>
        <div style="font-size: 0.92rem; color: #475569; margin-top: 2px; font-weight: 600;">
            المديرية العامة للشباب — قطاع مؤسسات الشباب والعمل التطوعي
        </div>
        <div style="width: 100px; height: 3px; background: #0d7a6f; margin: 14px auto 0;"></div>
    </div>

    <?php if ($is_single_campaign): ?>
        <!-- Single Campaign Certificate -->
        <div class="cert-title">شهادة مشاركة وتقدير في عمل تطوعي ميداني</div>

        <p style="font-size: 1.05rem; color: #334155; margin-top: 10px;">
            تشهد إدارة <strong><?= e($single_camp['organization_name']) ?></strong> (<?= e($single_camp['institution_type']) ?> — ولاية <?= e($single_camp['camp_wilaya'] ?? 'الجزائر') ?>) بالتنسيق مع مديرية الشباب والرياضة، بأن المتطوع(ة):
        </p>

        <div class="cert-user-name"><?= e($volunteer['name']) ?></div>

        <div style="font-size: 0.95rem; color: #64748b;">
            الولاية: <strong><?= e($volunteer['wilaya'] ?? 'الجزائر') ?></strong> | البلدية: <strong><?= e($volunteer['municipality']) ?></strong> | المعرّف: <strong>DZ-VOL-<?= sprintf('%04d', $volunteer['id']) ?></strong>
        </div>

        <p style="font-size: 1.02rem; color: #334155; line-height: 1.85; margin-top: 16px;">
            قد شارك(ت) بفعالية وانضباط وتفانٍ في إنجاح المبادرة التطوعية الميدانية:
            <br>
            <strong style="font-size: 1.15rem; color: #0f172a;">« <?= e($single_camp['campaign_title']) ?> »</strong>
            <br>
            <span style="font-size: 0.9rem; color: #64748b;">
                المقامة بتاريخ <?= format_date_ar($single_camp['start_date']) ?> بالموقع الميداني: <?= e($single_camp['location_name']) ?> (<?= e($single_camp['camp_municipality']) ?>).
            </span>
        </p>

        <div class="cert-hours-box">
            <div class="cert-hours-number"><?= $single_camp['hours_awarded'] ?> ساعات معتمدة</div>
            <div style="font-size: 0.85rem; font-weight: 700; color: #065f46; margin-top: 4px;">
                مودعة رسمياً في جواز التطوع الرقمي الموحد (منصة <?= APP_NAME ?>)
            </div>
        </div>

        <p style="font-size: 0.88rem; color: #64748b; margin-top: 8px;">
            سُلمت له(ا) هذه الشهادة تقديراً لجهوده(ا) وإسهامه(ا) في خدمة المجتمع وتأطير النشاط الشبابي.
        </p>

        <!-- Multi-party Seals (Club & DJS) -->
        <div class="seals-grid">
            <div style="text-align: right;">
                <div style="font-size: 0.82rem; color: #64748b;">تاريخ الإصدار: <?= format_date_ar(date('Y-m-d')) ?></div>
                <div style="font-size: 0.75rem; color: #94a3b8; font-family: monospace; margin-top: 4px;">الرقم التسلسلي: <?= $certificate_no ?></div>
                <div style="margin-top: 8px;">
                    <?= render_qr_code("VERIFY-MOBADIR-ACT-" . substr($verification_hash, 0, 16), 80) ?>
                </div>
            </div>

            <div class="seal-box">
                <span>ختم النادي المؤطر</span>
                <span style="font-size:0.65rem;"><?= e(mb_substr($single_camp['organization_name'], 0, 18)) ?></span>
                <span>★ معتمد ★</span>
            </div>

            <div class="seal-box">
                <span>الجمهورية الجزائرية</span>
                <span>الختم الرقمي</span>
                <span>★ DJS ★</span>
            </div>

            <div style="text-align: left;">
                <div style="font-size: 0.88rem; font-weight: 700; color: #0f172a;">مسؤول المؤسسة الشابة</div>
                <div style="font-size: 0.8rem; color: #64748b;"><?= e($single_camp['organization_name']) ?></div>
                <div style="margin-top: 24px; color: #94a3b8; font-size: 0.75rem; font-style: italic;">[التوقيع الرقمي للمؤسسة والمديرية]</div>
            </div>
        </div>

    <?php else: ?>
        <!-- Comprehensive Cumulative Certificate -->
        <div class="cert-title">شهادة إثبات رصيد ساعات العمل التطوعي الوطني</div>

        <p style="font-size: 1.05rem; color: #334155; margin-top: 10px;">
            تشهد مؤسسات ونوادي الشباب الجزائرية المعتمدة رسمياً لدى قطاع الشباب والرياضة بأن المتطوع(ة):
        </p>

        <div class="cert-user-name"><?= e($volunteer['name']) ?></div>

        <div style="font-size: 0.95rem; color: #64748b;">
            الولاية: <strong><?= e($volunteer['wilaya'] ?? 'الجزائر') ?></strong> | البلدية: <strong><?= e($volunteer['municipality']) ?></strong> | المعرّف الوطني: <strong>DZ-VOL-<?= sprintf('%04d', $volunteer['id']) ?></strong>
        </div>

        <p style="font-size: 1rem; color: #334155; line-height: 1.8; margin-top: 18px;">
            قد شارك(ت) بفعالية وانضباط ميداني في المبادرات والأنشطة التطوعية الموجهة لخدمة المجتمع، وقد بلغ إجمالي الساعات المنجزة والمعتمدة رسمياً في سجله الرقمي:
        </p>

        <div class="cert-hours-box">
            <div class="cert-hours-number"><?= $total_hours ?> ساعة تطوعية</div>
            <div style="font-size: 0.85rem; font-weight: 700; color: #065f46; margin-top: 4px;">
                موثقة في السجل الرقمي الوطني (منصة <?= APP_NAME ?>)
            </div>
        </div>

        <?php if (!empty($activities)): ?>
            <div style="max-width: 680px; margin: 0 auto 20px; font-size: 0.85rem; text-align: right; background: #f8fafc; padding: 14px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong style="display: block; margin-bottom: 6px; color: #0f172a;">أبرز المبادرات المنجزة والموثقة:</strong>
                <ul style="padding-right: 20px; color: #475569; line-height: 1.6;">
                    <?php foreach (array_slice($activities, 0, 4) as $act): ?>
                        <li><?= e($act['campaign_title']) ?> (<?= e($act['organization_name']) ?>) — <strong><?= $act['hours_awarded'] ?> ساعات</strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <p style="font-size: 0.85rem; color: #64748b; margin-top: 12px;">
            سُلمت هذه الوثيقة الرسمية للمعني(ة) لتقديمها واستعمالها في الملفات الأكاديمية والمهنية وتدعيم السيرة الذاتية.
        </p>

        <div class="seals-grid">
            <div style="text-align: right;">
                <div style="font-size: 0.8rem; color: #64748b;">تاريخ التحرير: <?= format_date_ar(date('Y-m-d')) ?></div>
                <div style="font-size: 0.75rem; color: #94a3b8; font-family: monospace; margin-top: 4px;">كود الشهادة: <?= $certificate_no ?></div>
                <div style="margin-top: 8px;">
                    <?= render_qr_code("VERIFY-MOBADIR-" . substr($verification_hash, 0, 16), 80) ?>
                </div>
            </div>

            <div class="seal-box">
                <span>الجمهورية الجزائرية</span>
                <span>الختم الرقمي</span>
                <span>★ DJS ★</span>
            </div>

            <div style="text-align: left;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a;">ع/ مدير الشباب والرياضة</div>
                <div style="font-size: 0.8rem; color: #64748b;">مصلحة الترقية والأنشطة الشبابية</div>
                <div style="margin-top: 26px; color: #94a3b8; font-size: 0.75rem;">[التوقيع والاعتماد الرقمي]</div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
