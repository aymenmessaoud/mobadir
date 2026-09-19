<?php
/**
 * Master Router & Action Controller: Mobadir (مُبادِر)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';

// Route Resolution
$page = $_GET['page'] ?? '';
if (empty($page)) {
    $page = 'home';
}

// -------------------------------------------------------------
// PRE-SHELL POST ACTION DISPATCHER
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $db = get_db_connection();

    switch ($page) {
        // --- 1. Login Action ---
        case 'action_login':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $redirect = trim($_POST['redirect'] ?? '');

            if (login_user($email, $password)) {
                set_flash('success', 'مرحباً بك! تم تسجيل الدخول بنجاح.');
                if (!empty($redirect) && strpos($redirect, '://') === false) {
                    header('Location: ' . url($redirect));
                } else {
                    $u = current_user();
                    if ($u['role'] === 'club') {
                        header('Location: ' . url('club_dashboard'));
                    } elseif ($u['role'] === 'admin') {
                        header('Location: ' . url('admin_dashboard'));
                    } else {
                        header('Location: ' . url('passport'));
                    }
                }
                exit;
            } else {
                set_flash('error', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
                header('Location: ' . url('login'));
                exit;
            }
            break;

        // --- 2. Register Volunteer ---
        case 'action_register_volunteer':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '08 - بشار');
            $municipality = trim($_POST['municipality'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($municipality)) {
                set_flash('error', 'يرجى ملء كافة الحقول الإلزامية.');
                header('Location: ' . url('register?role=volunteer'));
                exit;
            }

            $res = register_volunteer($name, $email, $password, $phone, $wilaya, $municipality, $bio);
            if ($res['success']) {
                set_flash('success', 'تهانينا! تم إنشاء جواز التطوع الرقمي الخاص بك بنجاح. استكشف الفرص التطوعية الآن!');
                header('Location: ' . url('passport'));
                exit;
            } else {
                set_flash('error', $res['message']);
                header('Location: ' . url('register?role=volunteer'));
                exit;
            }
            break;

        // --- 3. Register Club ---
        case 'action_register_club':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '08 - بشار');
            $municipality = trim($_POST['municipality'] ?? '');
            $org_name = trim($_POST['org_name'] ?? '');
            $institution_type = trim($_POST['institution_type'] ?? 'دار الشباب');
            $address = trim($_POST['address'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($org_name) || empty($municipality)) {
                set_flash('error', 'يرجى ملء كافة الحقول الإلزامية.');
                header('Location: ' . url('register?role=club'));
                exit;
            }

            $res = register_club($name, $email, $password, $phone, $wilaya, $municipality, $org_name, $institution_type, $address, $bio);
            if ($res['success']) {
                set_flash('success', 'تم تسجيل حساب النادي بنجاح! يمكنك الآن البدء في نشر الفرص التطوعية.');
                header('Location: ' . url('club_dashboard'));
                exit;
            } else {
                set_flash('error', $res['message']);
                header('Location: ' . url('register?role=club'));
                exit;
            }
            break;

        // --- 4. 1-Click RSVP Application ---
        case 'action_apply_campaign':
            require_login();
            if (!is_volunteer()) {
                set_flash('error', 'الانضمام كمتطوع متاح فقط لحسابات الأفراد.');
                header('Location: ' . url('campaigns'));
                exit;
            }

            $campaign_id = (int)($_POST['campaign_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $volunteer_id = $_SESSION['user']['id'];

            // Fetch campaign details
            $stmt = $db->prepare("SELECT c.*, cp.organization_name, cp.user_id AS club_owner_id FROM campaigns c JOIN clubs_profile cp ON c.club_id = cp.id WHERE c.id = ?");
            $stmt->execute([$campaign_id]);
            $camp = $stmt->fetch();

            if (!$camp) {
                set_flash('error', 'الحملة غير متوفرة.');
                header('Location: ' . url('campaigns'));
                exit;
            }

            // Check if already applied
            $stmt_check = $db->prepare("SELECT id FROM volunteering_applications WHERE campaign_id = ? AND volunteer_id = ?");
            $stmt_check->execute([$campaign_id, $volunteer_id]);
            if ($stmt_check->fetch()) {
                set_flash('error', 'أنت مسجل بالفعل في هذه الحملة التطوعية.');
                header('Location: ' . url('campaign_detail?id=' . $campaign_id));
                exit;
            }

            // Check vacancies
            $stmt_cnt = $db->prepare("SELECT COUNT(*) FROM volunteering_applications WHERE campaign_id = ? AND status != 'cancelled'");
            $stmt_cnt->execute([$campaign_id]);
            $current_count = (int)$stmt_cnt->fetchColumn();

            // min_volunteers is the minimum required — registration stays open after that
            // No hard cap enforced here (open enrollment platform)


            // Insert application
            $stmt_ins = $db->prepare("INSERT INTO volunteering_applications (campaign_id, volunteer_id, status, notes) VALUES (?, ?, 'confirmed', ?)");
            $stmt_ins->execute([$campaign_id, $volunteer_id, $notes]);

            // Notify Club Organizer
            $vol_name = $_SESSION['user']['name'];
            send_notification(
                $camp['club_owner_id'],
                'متطوع جديد انضم للحملة',
                "انضم المتطوع {$vol_name} إلى حملة {$camp['title']}.",
                'club_attendees?id=' . $campaign_id
            );

            set_flash('success', 'تهانينا! تم حجز مقعدك وتأكيد انضمامك للحملة بنجاح.');
            header('Location: ' . url('campaign_detail?id=' . $campaign_id));
            exit;
            break;

        // --- 5. Cancel Volunteer Application ---
        case 'action_cancel_application':
            require_login();
            $campaign_id = (int)($_POST['campaign_id'] ?? 0);
            $volunteer_id = $_SESSION['user']['id'];

            $stmt_del = $db->prepare("DELETE FROM volunteering_applications WHERE campaign_id = ? AND volunteer_id = ? AND status != 'attended'");
            $stmt_del->execute([$campaign_id, $volunteer_id]);

            set_flash('success', 'تم إلغاء انضمامك للحملة.');
            header('Location: ' . url('my_volunteering'));
            exit;
            break;

        // --- 6. Follow / Unfollow Club ---
        case 'action_toggle_follow':
            require_login();
            $club_id = (int)($_POST['club_id'] ?? 0);
            $return_url = $_POST['return_url'] ?? url('clubs');
            $volunteer_id = $_SESSION['user']['id'];

            $stmt_check = $db->prepare("SELECT id FROM follows WHERE volunteer_id = ? AND club_id = ?");
            $stmt_check->execute([$volunteer_id, $club_id]);
            $existing = $stmt_check->fetch();

            if ($existing) {
                $stmt_del = $db->prepare("DELETE FROM follows WHERE id = ?");
                $stmt_del->execute([$existing['id']]);
                set_flash('success', 'تم إلغاء متابعة النادي.');
            } else {
                $stmt_ins = $db->prepare("INSERT INTO follows (volunteer_id, club_id) VALUES (?, ?)");
                $stmt_ins->execute([$volunteer_id, $club_id]);
                set_flash('success', 'أصبحت تتابع هذا النادي الآن! ستصلك إشعارات بكل فرصة جديدة ينشرها.');
            }

            header('Location: ' . $return_url);
            exit;
            break;

        // --- 7. Credit Hours & Mark Attendance (Key Feature) ---
        case 'action_credit_hours':
            require_login();
            if (!is_club() && !is_admin()) {
                http_response_code(403);
                die("غير مصرح.");
            }

            $campaign_id = (int)($_POST['campaign_id'] ?? 0);
            $volunteer_id = (int)($_POST['volunteer_id'] ?? 0);
            $hours_awarded = (int)($_POST['hours_awarded'] ?? 4);

            // Fetch campaign to check ownership
            $stmt = $db->prepare("SELECT c.*, cp.organization_name FROM campaigns c JOIN clubs_profile cp ON c.club_id = cp.id WHERE c.id = ?");
            $stmt->execute([$campaign_id]);
            $camp = $stmt->fetch();

            if (!$camp || (!is_admin() && $camp['club_id'] != $_SESSION['user']['club_id'])) {
                set_flash('error', 'لا تملك صلاحية تعديل سجلات هذه الحملة.');
                header('Location: ' . url('club_dashboard'));
                exit;
            }

            // Update application status to attended
            $stmt_upd = $db->prepare("
                UPDATE volunteering_applications 
                SET status = 'attended', hours_awarded = ?, attended_at = NOW() 
                WHERE campaign_id = ? AND volunteer_id = ?
            ");
            $stmt_upd->execute([$hours_awarded, $campaign_id, $volunteer_id]);

            // Notify Volunteer
            send_notification(
                $volunteer_id,
                'ساعات تطوع معتمدة رسمياً! 🎖️',
                "تهانينا! اعتمد {$camp['organization_name']} حضورك وتم إيداع {$hours_awarded} ساعات في جواز التطوع الرقمي الخاص بك.",
                'passport'
            );

            set_flash('success', "تم اعتماد حضور المتطوع وإيداع {$hours_awarded} ساعات في جواز التطوع بنجاح!");
            header('Location: ' . url('club_attendees?id=' . $campaign_id));
            exit;
            break;

        // --- 8. Create Campaign ---
        case 'action_create_campaign':
            require_login();
            if (!is_club()) { http_response_code(403); die("غير مصرح."); }

            $club_id          = $_SESSION['user']['club_id'];
            $title            = trim($_POST['title'] ?? '');
            $category         = trim($_POST['category'] ?? 'بيئة وتراث');
            $wilaya           = trim($_POST['wilaya'] ?? '');
            $municipality     = trim($_POST['municipality'] ?? '');
            $location_name    = trim($_POST['location_name'] ?? '');
            $start_date       = $_POST['start_date'] ?? date('Y-m-d');
            $end_date         = $_POST['end_date'] ?? date('Y-m-d');
            $daily_start_time = $_POST['daily_start_time'] ?? '08:00';
            $daily_end_time   = $_POST['daily_end_time']   ?? '12:00';
            $hours_count      = max(1, (int)($_POST['hours_count'] ?? 4));
            $min_volunteers   = max(1, (int)($_POST['min_volunteers'] ?? 15));
            $description      = trim($_POST['description'] ?? '');

            if (empty($title) || empty($location_name) || empty($description) || empty($wilaya)) {
                set_flash('error', 'يرجى ملء جميع الحقول المطلوبة (العنوان، الولاية، المكان، الوصف).');
                header('Location: ' . url('club_new_campaign'));
                exit;
            }

            // Handle optional image upload
            $campaign_image = null;
            if (!empty($_FILES['campaign_image']['name'])) {
                $up = handle_upload($_FILES['campaign_image'], 'campaigns');
                if ($up['ok']) {
                    $campaign_image = $up['filename'];
                } else {
                    set_flash('error', $up['msg']);
                    header('Location: ' . url('club_new_campaign'));
                    exit;
                }
            }

            $stmt_ins = $db->prepare("
                INSERT INTO campaigns
                    (club_id, title, description, category, wilaya, municipality, location_name,
                     start_date, end_date, daily_start_time, daily_end_time, hours_count, min_volunteers, image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
            ");
            $stmt_ins->execute([
                $club_id, $title, $description, $category, $wilaya, $municipality, $location_name,
                $start_date, $end_date, $daily_start_time, $daily_end_time, $hours_count, $min_volunteers, $campaign_image
            ]);
            $campaign_id = $db->lastInsertId();

            // Handle gallery images (multi-upload)
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $gallery_files = $_FILES['gallery_images'];
                $count = count($gallery_files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($gallery_files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $single_file = [
                        'name'     => $gallery_files['name'][$i],
                        'type'     => $gallery_files['type'][$i],
                        'tmp_name' => $gallery_files['tmp_name'][$i],
                        'error'    => $gallery_files['error'][$i],
                        'size'     => $gallery_files['size'][$i],
                    ];
                    $gup = handle_upload($single_file, 'campaigns');
                    if ($gup['ok']) {
                        $stmt_gi = $db->prepare("INSERT INTO campaign_images (campaign_id, image_path) VALUES (?, ?)");
                        $stmt_gi->execute([$campaign_id, $gup['filename']]);
                    }
                }
            }

            $club_name = $_SESSION['user']['club_name'];
            notify_club_followers(
                $club_id,
                'فرصة تطوع جديدة من ' . $club_name,
                "أطلق {$club_name} حملة جديدة: {$title} ({$hours_count} ساعات معتمدة). انضم الآن!",
                'campaign_detail?id=' . $campaign_id
            );

            set_flash('success', 'تم نشر الحملة التطوعية بنجاح وإشعار المتابعين!');
            header('Location: ' . url('club_dashboard'));
            exit;
            break;

        // --- 9. Update Campaign ---
        case 'action_update_campaign':
            require_login();
            $campaign_id      = (int)($_POST['campaign_id'] ?? 0);
            $title            = trim($_POST['title'] ?? '');
            $category         = trim($_POST['category'] ?? 'بيئة وتراث');
            $wilaya           = trim($_POST['wilaya'] ?? '');
            $municipality     = trim($_POST['municipality'] ?? '');
            $location_name    = trim($_POST['location_name'] ?? '');
            $start_date       = $_POST['start_date'] ?? date('Y-m-d');
            $end_date         = $_POST['end_date'] ?? date('Y-m-d');
            $daily_start_time = $_POST['daily_start_time'] ?? '08:00';
            $daily_end_time   = $_POST['daily_end_time']   ?? '12:00';
            $hours_count      = max(1, (int)($_POST['hours_count'] ?? 4));
            $min_volunteers   = max(1, (int)($_POST['min_volunteers'] ?? 15));
            $status           = $_POST['status'] ?? 'published';
            $description      = trim($_POST['description'] ?? '');

            $stmt_camp = $db->prepare("SELECT club_id, image AS old_image FROM campaigns WHERE id = ?");
            $stmt_camp->execute([$campaign_id]);
            $camp = $stmt_camp->fetch();

            if (!$camp || (!is_admin() && $camp['club_id'] != $_SESSION['user']['club_id'])) {
                set_flash('error', 'لا تملك صلاحية تعديل هذه الحملة.');
                header('Location: ' . url('club_dashboard'));
                exit;
            }

            // Handle optional image upload
            $new_image = $camp['old_image'];
            if (!empty($_FILES['campaign_image']['name'])) {
                $up = handle_upload($_FILES['campaign_image'], 'campaigns');
                if ($up['ok']) {
                    delete_upload('campaigns', $camp['old_image']);
                    $new_image = $up['filename'];
                } else {
                    set_flash('error', $up['msg']);
                    header('Location: ' . url('club_edit_campaign?id=' . $campaign_id));
                    exit;
                }
            }

            $stmt_upd = $db->prepare("
                UPDATE campaigns
                SET title=?, description=?, category=?, wilaya=?, municipality=?, location_name=?,
                    start_date=?, end_date=?, daily_start_time=?, daily_end_time=?,
                    hours_count=?, min_volunteers=?, image=?, status=?
                WHERE id=?
            ");
            $stmt_upd->execute([
                $title, $description, $category, $wilaya, $municipality, $location_name,
                $start_date, $end_date, $daily_start_time, $daily_end_time,
                $hours_count, $min_volunteers, $new_image, $status, $campaign_id
            ]);

            // Handle gallery images (multi-upload)
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $gallery_files = $_FILES['gallery_images'];
                $count = count($gallery_files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($gallery_files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $single_file = [
                        'name'     => $gallery_files['name'][$i],
                        'type'     => $gallery_files['type'][$i],
                        'tmp_name' => $gallery_files['tmp_name'][$i],
                        'error'    => $gallery_files['error'][$i],
                        'size'     => $gallery_files['size'][$i],
                    ];
                    $gup = handle_upload($single_file, 'campaigns');
                    if ($gup['ok']) {
                        $stmt_gi = $db->prepare("INSERT INTO campaign_images (campaign_id, image_path) VALUES (?, ?)");
                        $stmt_gi->execute([$campaign_id, $gup['filename']]);
                    }
                }
            }

            set_flash('success', 'تم حفظ تعديلات الحملة بنجاح.');
            header('Location: ' . (is_admin() ? url('admin_dashboard') : url('club_dashboard')));
            exit;
            break;

        // --- 10. Update Profile (Volunteer or Club) ---
        case 'action_update_profile':
            require_login();
            $user_id = $_SESSION['user']['id'];

            $name     = trim($_POST['name'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $wilaya   = trim($_POST['wilaya'] ?? '');
            $municipality = trim($_POST['municipality'] ?? '');
            $address_details = trim($_POST['address_details'] ?? '');
            $bio      = trim(mb_substr($_POST['bio'] ?? '', 0, 500, 'UTF-8'));
            $facebook_url  = trim($_POST['facebook_url'] ?? '');
            $instagram_url = trim($_POST['instagram_url'] ?? '');
            $linkedin_url  = trim($_POST['linkedin_url'] ?? '');

            if (empty($name) || empty($phone)) {
                set_flash('error', 'الاسم ورقم الهاتف حقول إلزامية.');
                header('Location: ' . url('profile_edit'));
                exit;
            }

            // Handle avatar upload
            $avatar_filename = null;
            if (!empty($_FILES['avatar']['name'])) {
                $up = handle_upload($_FILES['avatar'], 'avatars');
                if ($up['ok']) {
                    // Delete old avatar
                    $old = $db->prepare("SELECT avatar FROM users WHERE id = ?");
                    $old->execute([$user_id]);
                    $old_avatar = $old->fetchColumn();
                    if ($old_avatar) delete_upload('avatars', $old_avatar);
                    $avatar_filename = $up['filename'];
                } else {
                    set_flash('error', $up['msg']);
                    header('Location: ' . url('profile_edit'));
                    exit;
                }
            }

            // Update users table
            $sql = "UPDATE users SET name=?, phone=?, wilaya=?, municipality=?, address_details=?, bio=?, facebook_url=?, instagram_url=?, linkedin_url=?";
            $params = [$name, $phone, $wilaya, $municipality, $address_details, $bio, $facebook_url, $instagram_url, $linkedin_url];
            if ($avatar_filename) {
                $sql .= ", avatar=?";
                $params[] = $avatar_filename;
            }
            $sql .= " WHERE id=?";
            $params[] = $user_id;
            $db->prepare($sql)->execute($params);

            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['wilaya'] = $wilaya;
            $_SESSION['user']['municipality'] = $municipality;
            if ($avatar_filename) {
                $_SESSION['user']['avatar'] = $avatar_filename;
            }

            // If club, update clubs_profile too
            if (is_club()) {
                $org_name = trim($_POST['org_name'] ?? '');
                $institution_type = trim($_POST['institution_type'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $website_url = trim($_POST['website_url'] ?? '');
                $club_facebook = trim($_POST['club_facebook_url'] ?? $facebook_url);
                $club_instagram = trim($_POST['club_instagram_url'] ?? $instagram_url);

                $club_id = $_SESSION['user']['club_id'];

                // Handle logo upload
                $logo_filename = null;
                if (!empty($_FILES['logo']['name'])) {
                    $lup = handle_upload($_FILES['logo'], 'avatars');
                    if ($lup['ok']) {
                        $old_logo = $db->prepare("SELECT logo FROM clubs_profile WHERE id = ?");
                        $old_logo->execute([$club_id]);
                        $ol = $old_logo->fetchColumn();
                        if ($ol) delete_upload('avatars', $ol);
                        $logo_filename = $lup['filename'];
                    }
                }

                // Handle cover image upload
                $cover_filename = null;
                if (!empty($_FILES['cover_image']['name'])) {
                    $cup = handle_upload($_FILES['cover_image'], 'campaigns');
                    if ($cup['ok']) {
                        $old_cover = $db->prepare("SELECT cover_image FROM clubs_profile WHERE id = ?");
                        $old_cover->execute([$club_id]);
                        $oc = $old_cover->fetchColumn();
                        if ($oc) delete_upload('campaigns', $oc);
                        $cover_filename = $cup['filename'];
                    }
                }

                $csql = "UPDATE clubs_profile SET organization_name=?, institution_type=?, bio=?, wilaya=?, municipality=?, address=?, address_details=?, facebook_url=?, instagram_url=?, website_url=?";
                $cparams = [$org_name, $institution_type, $bio, $wilaya, $municipality, $address, $address_details, $club_facebook, $club_instagram, $website_url];
                if ($logo_filename) {
                    $csql .= ", logo=?";
                    $cparams[] = $logo_filename;
                }
                if ($cover_filename) {
                    $csql .= ", cover_image=?";
                    $cparams[] = $cover_filename;
                }
                $csql .= " WHERE id=?";
                $cparams[] = $club_id;
                $db->prepare($csql)->execute($cparams);

                $_SESSION['user']['club_name'] = $org_name;
            }

            set_flash('success', 'تم تحديث بيانات الحساب بنجاح!');
            header('Location: ' . url('profile_edit'));
            exit;
            break;

        // --- 11. Admin: Toggle User Suspension ---
        case 'action_toggle_suspension':
            require_login();
            if (!is_admin()) { http_response_code(403); die("غير مصرح."); }

            $target_user_id = (int)($_POST['user_id'] ?? 0);
            if ($target_user_id <= 0) {
                set_flash('error', 'معرف المستخدم غير صالح.');
                header('Location: ' . url('admin_dashboard'));
                exit;
            }

            // Get current status
            $stmt_status = $db->prepare("SELECT is_suspended, name, role FROM users WHERE id = ? AND role != 'admin'");
            $stmt_status->execute([$target_user_id]);
            $target = $stmt_status->fetch();

            if (!$target) {
                set_flash('error', 'المستخدم غير موجود أو لا يمكن تعديل صلاحياته.');
                header('Location: ' . url('admin_dashboard'));
                exit;
            }

            $new_status = $target['is_suspended'] ? 0 : 1;
            $db->prepare("UPDATE users SET is_suspended = ? WHERE id = ?")->execute([$new_status, $target_user_id]);

            $action_word = $new_status ? 'تجميد' : 'تفعيل';
            set_flash('success', "تم {$action_word} حساب «{$target['name']}» بنجاح.");

            // Notify user
            $notif_msg = $new_status
                ? 'تم تجميد حسابك من قِبل إدارة المنصة لدواعي تنظيمية. يرجى التواصل مع الإدارة للاستفسار.'
                : 'تم إعادة تفعيل حسابك. يمكنك الآن استخدام المنصة بشكل طبيعي.';
            send_notification($target_user_id, $new_status ? 'تجميد الحساب' : 'إعادة تفعيل الحساب', $notif_msg, '');

            header('Location: ' . url('admin_dashboard'));
            exit;
            break;

        // --- 12. Request Password Reset ---
        case 'action_request_password_reset':
            $email = trim(filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL));
            if (empty($email)) {
                set_flash('error', 'يرجى إدخال بريد إلكتروني صحيح.');
                header('Location: ' . url('forgot_password'));
                exit;
            }

            // Check if user exists
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $usr = $stmt->fetch();

            if ($usr) {
                $token = bin2hex(random_bytes(32));
                // Delete previous tokens for this email
                $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

                // Insert new token valid for 1 hour
                $stmt_ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $stmt_ins->execute([$email, $token]);

                $reset_link = url('reset_password?token=' . $token);

                // Attempt sending email via PHP mail()
                $subject = "استعادة كلمة المرور — " . APP_NAME;
                $message = "مرحباً {$usr['name']}،\n\nلقد تلقينا طلباً لتعيين كلمة مرور جديدة لحسابك في منصة " . APP_NAME . ".\n\nيمكنك تعيين كلمة المرور عبر الرابط التالي (صالح لمدة ساعة واحدة):\n" . $reset_link . "\n\nإذا لم تكن أنت صاحب هذا الطلب، يمكنك تجاهل هذه الرسالة بأمان.";
                $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'mobadir.dz') . "\r\nReply-To: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'mobadir.dz') . "\r\nContent-Type: text/plain; charset=UTF-8";

                @mail($email, $subject, $message, $headers);

                // Always provide instant access link so reset works on any shared hosting (like InfinityFree) without relying on sendmail
                set_flash('success', 'تم إنشاء طلب استعادة كلمة المرور بنجاح! <br><a href="' . e($reset_link) . '" style="color:var(--c-brand);font-weight:700;text-decoration:underline;">اضغط هنا لتعيين كلمة المرور الجديدة مباشرة</a>');
            } else {
                set_flash('success', 'إذا كان هذا البريد مسجلاً لدينا، فستظهر لك تعليمات استعادة كلمة المرور.');
            }

            header('Location: ' . url('forgot_password'));
            exit;
            break;

        // --- 13. Reset Password Confirmation ---
        case 'action_reset_password':
            $token = trim($_POST['token'] ?? '');
            $new_password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($token) || empty($new_password)) {
                set_flash('error', 'يرجى ملء جميع الحقول المطلوبة.');
                header('Location: ' . url('reset_password?token=' . urlencode($token)));
                exit;
            }

            if (strlen($new_password) < 6) {
                set_flash('error', 'كلمة المرور يجب أن لا تقل عن 6 أحرف.');
                header('Location: ' . url('reset_password?token=' . urlencode($token)));
                exit;
            }

            if ($new_password !== $password_confirm) {
                set_flash('error', 'كلمتا المرور غير متطابقتين.');
                header('Location: ' . url('reset_password?token=' . urlencode($token)));
                exit;
            }

            // Verify token in DB
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$token]);
            $reset_row = $stmt->fetch();

            if (!$reset_row) {
                set_flash('error', 'رابط الاستعادة منتهي الصلاحية أو غير صالح.');
                header('Location: ' . url('forgot_password'));
                exit;
            }

            // Update user password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $upd->execute([$new_hash, $reset_row['email']]);

            // Invalidate all tokens for this email
            $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset_row['email']]);

            set_flash('success', 'تم تعيين كلمة المرور الجديدة بنجاح! يمكنك الآن تسجيل الدخول.');
            header('Location: ' . url('login'));
            exit;
            break;
    }
}

// -------------------------------------------------------------
// PAGE ROUTER DISPATCHER
// -------------------------------------------------------------
$routes = [
    'home'               => __DIR__ . '/pages/home.php',
    'campaigns'          => __DIR__ . '/pages/campaigns.php',
    'campaign_detail'    => __DIR__ . '/pages/campaign_detail.php',
    'clubs'              => __DIR__ . '/pages/clubs.php',
    'club_detail'        => __DIR__ . '/pages/club_detail.php',
    'passport'           => __DIR__ . '/pages/passport.php',
    'certificate'        => __DIR__ . '/pages/certificate.php',
    'my_volunteering'    => __DIR__ . '/pages/my_volunteering.php',
    'notifications'      => __DIR__ . '/pages/notifications.php',
    'profile_edit'       => __DIR__ . '/pages/profile_edit.php',
    'forgot_password'    => __DIR__ . '/pages/forgot_password.php',
    'reset_password'     => __DIR__ . '/pages/reset_password.php',
    'login'              => __DIR__ . '/pages/login.php',
    'register'           => __DIR__ . '/pages/register.php',
    'logout'             => __DIR__ . '/pages/logout.php',
    'club_dashboard'     => __DIR__ . '/club/dashboard.php',
    'club_new_campaign'  => __DIR__ . '/club/new_campaign.php',
    'club_edit_campaign' => __DIR__ . '/club/edit_campaign.php',
    'club_attendees'     => __DIR__ . '/club/attendees.php',
    'admin_dashboard'    => __DIR__ . '/admin/index.php',
];

if (isset($routes[$page]) && file_exists($routes[$page])) {
    require $routes[$page];
} else {
    http_response_code(404);
    $page_title = '404 — الصفحة غير موجودة';
    include __DIR__ . '/partials/header.php';
    echo '<div class="container" style="text-align:center;padding:80px 20px;">
            <div style="width:64px;height:64px;color:var(--c-text-muted);margin:0 auto 20px;">' . svg_icon('search', 64) . '</div>
            <h1 style="font-size:2rem;font-weight:800;margin-bottom:12px;">404 — الصفحة غير موجودة</h1>
            <p style="color:var(--c-text-muted);margin-bottom:24px;">عذراً، الصفحة التي تبحث عنها غير متوفرة أو تم نقلها.</p>
            <a href="' . url() . '" class="btn btn-primary">العودة إلى الرئيسية</a>
          </div>';
    include __DIR__ . '/partials/footer.php';
}
