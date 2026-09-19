<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
set_flash('success', 'تم تسجيل الخروج بنجاح. نتمنى رؤيتك قريباً!');
header('Location: ' . url());
exit;
