<?php
/**
 * Flash alerts partial — SVG icons, alert component
 */
require_once __DIR__ . '/../includes/helpers.php';
$flashes = get_flashes();
if (!empty($flashes)):
    foreach ($flashes as $f):
        $type  = $f['type'] === 'error' ? 'error' : 'success';
        $icon  = $type === 'error' ? 'info' : 'check-circle';
        $cls   = 'alert alert-' . ($type === 'error' ? 'error' : 'success');
?>
    <div class="container" style="padding-top:12px;">
        <div class="<?= $cls ?>" role="alert">
            <?= svg_icon($icon, 18) ?>
            <span><?= e($f['message']) ?></span>
        </div>
    </div>
<?php
    endforeach;
endif;
?>
