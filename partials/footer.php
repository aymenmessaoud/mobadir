<?php
/**
 * Footer Partial — Mobadir
 */
?>
</main><!-- /main-content -->

<footer class="main-footer no-print">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div class="footer-brand">
                <div class="footer-logo-wrap">
                    <span style="background:var(--c-brand);border-radius:var(--r-xs);width:28px;height:28px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                        <?= svg_icon('flag', 14) ?>
                    </span>
                    <span class="footer-logo-name"><?= APP_NAME ?></span>
                </div>
                <p class="footer-tagline">
                    المنصة الوطنية الموحدة لتنسيق وتوثيق العمل التطوعي بمؤسسات الشباب.
                    نهدف إلى مأسسة التطوع واستقطاب طاقات الشباب الجزائري.
                </p>
            </div>

            <!-- Links -->
            <div class="footer-col">
                <h4>روابط المنصة</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('campaigns') ?>"><?= svg_icon('flag', 13) ?> الفرص التطوعية</a></li>
                    <li><a href="<?= url('clubs') ?>"><?= svg_icon('building', 13) ?> دليل المؤسسات والنوادي</a></li>
                    <li><a href="<?= url('passport') ?>"><?= svg_icon('award', 13) ?> جواز التطوع الرقمي</a></li>
                    <li><a href="<?= url('register') ?>"><?= svg_icon('plus', 13) ?> تسجيل حساب جديد</a></li>
                </ul>
            </div>

            <!-- Quick filters -->
            <div class="footer-col">
                <h4>تصفح حسب المجال</h4>
                <ul class="footer-links">
                    <?php foreach (get_categories() as $cat): ?>
                        <li>
                            <a href="<?= url('campaigns?category=' . rawurlencode($cat)) ?>">
                                <?= svg_icon(category_icon($cat), 12) ?> <?= e($cat) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Supervising authority -->
            <div class="footer-col">
                <h4>الإشراف والرعاية</h4>
                <p style="color:var(--c-text-muted);font-size:.85rem;line-height:1.7;">
                    الجمهورية الجزائرية الديمقراطية الشعبية<br>
                    وزارة الشباب والرياضة<br>
                    <strong>المديرية العامة للشباب</strong>
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© <?= date('Y') ?> منصة <?= APP_NAME ?> — جميع الحقوق محفوظة.</p>
            <p style="font-size:.78rem;">مشروع هاكاثون رقمنة مؤسسات الشباب 2026</p>
        </div>
    </div>
</footer>

<script>
window.ALGERIA_COMMUNES = <?= json_encode(get_algeria_communes_map(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= url('assets/js/main.js?v=2.1') ?>"></script>
</body>
</html>
