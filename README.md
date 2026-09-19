# منصة مُبادِر (Mobadir) — المنصة الموحدة لتنسيق العمل التطوعي بمؤسسات الشباب

> **دليل المطور الشامل ودليل النشر على الاستضافة (InfinityFree / cPanel / Localhost)**  
> تم تصميم وبناء المشروع بهندسة معمارية نظيفة وخفيفة (Vanilla PHP / MySQL) بدون أطر عمل معقدة أو أدوات بناء ثقيلة، لضمان استقرار فائق وسرعة خاطفة وسهولة صيانة من قبل أي مبرمج.

---

## 📁 1. الهيكلة المعمارية للمشروع (Project Structure)

يعتمد المشروع على نمط **Front Controller + Action Dispatcher + Modular Partials**:

```text
mobadir/
├── .htaccess                  # حماية الجذر وحظر الملفات الحساسة وحقن رؤوس الأمان (Security Headers)
├── index.php                  # الموجه الرئيسي (Router) + معالج الإجراءات المسبق (Pre-Shell Action Controller)
├── README.md                  # دليل المشروع وطريقة التثبيت والنشر للمطورين
│
├── config/                    # مجلد الإعدادات والاتصال
│   ├── config.php             # إعدادات النظام، الجلسات، كشف البيئة، وكشف BASE_URL التلقائي
│   └── database.php           # فئة/دالة الاتصال الآمن بقاعدة البيانات (PDO Singleton)
│
├── database/                  # قواعد البيانات وسجلات البذر
│   ├── schema.sql             # هيكل الجداول الكامل المحدث للإنتاج (Production Ready)
│   ├── seed.sql               # بيانات تجريبية وحسابات أولية للاختبار
│   └── update_schema.sql      # التحديثات التراكمية للجداول
│
├── includes/                  # الوحدات البرمجية المساندة (Core Business Logic)
│   ├── algeria_places.php     # مصفوفة الـ 58 ولاية جزائرية ببلدياتها الكاملة
│   ├── auth.php               # إدارة الجلسات، التحقق، الصلاحيات (RBAC)، وحراس الوصول
│   ├── helpers.php            # دوال مساعدة (CSRF, SVG Icons, Escaping, Badges, Dates, Uploads)
│   └── notifications.php      # نظام الإشعارات الداخلية والتنبيهات الحية
│
├── pages/                     # جزئيات صفحات العرض العامة والمتطوعين (View Partials)
│   ├── home.php               # الصفحة الرئيسية والتعريف بالمنصة
│   ├── campaigns.php          # استعراض والبحث في الفرص التطوعية (مع فلترة الولايات والبلديات)
│   ├── campaign_detail.php    # تفاصيل الحملة + معرض الصور التفاعلي + زر الانضمام
│   ├── clubs.php              # دليل المؤسسات والنوادي والجمعيات الشبابية
│   ├── club_detail.php        # صفحة النادي ومبادراته وزر المتابعة
│   ├── passport.php           # جواز التطوع الرقمي الوطني وشارات الرتب وسجل الساعات
│   ├── certificate.php        # نظام استخراج الشهادات الرسمية (شهادة الحملة أو الجواز الشامل)
│   ├── my_volunteering.php    # سجل مشاركات المتطوع وإلغاء التسجيل
│   ├── notifications.php      # مركز الإشعارات
│   ├── profile_edit.php       # تعديل الملف الشخصي والصور والروابط (متطوع ونادي)
│   ├── forgot_password.php    # طلب استعادة كلمة المرور
│   ├── reset_password.php     # تعيين كلمة المرور الجديدة
│   ├── login.php              # تسجيل الدخول
│   ├── register.php           # التسجيل الجديد (متطوع أو مؤسسة/نادي)
│   └── logout.php             # تسجيل الخروج الآمن
│
├── club/                      # لوحة تحكم منسقي المؤسسات والنوادي
│   ├── dashboard.php          # لوحة المؤشرات وإحصائيات النادي
│   ├── new_campaign.php       # نموذج نشر فرصة تطوعية جديدة (مع رفع صور متعددة وتوقيت يومي)
│   ├── edit_campaign.php      # تعديل الحملة الحالية ومعرض صورها
│   └── attendees.php          # كشف الحضور واعتماد وتوثيق ساعات المتطوعين رسمياً
│
├── admin/                     # لوحة قيادة مديرية الشباب والرياضة (DJS)
│   └── index.php              # مؤشرات الأثر الوطني، تغطية الولايات، وتجميد/تفعيل الحسابات
│
├── partials/                  # القوالب المشتركة القابلة لإعادة الاستخدام
│   ├── header.php             # الترويسة وشريط التنقل والوضع الليلي وشريحة المستخدم
│   ├── footer.php             # التذييل والقوائم الجغرافية السريعة
│   └── flash.php              # نظام الرسائل التنبيهية الفورية للمستخدم
│
├── assets/                    # الملفات الثابتة
│   ├── css/
│   │   └── style.css          # التصميم الموحد (Flat Civic Design System + Dark Mode)
│   └── js/
│       └── main.js            # وظائف الجافاسكريبت المساعدة
│
├── docs/                      # التوثيق المعماري وسجلات الدروس المستفادة
│   ├── MOBADIR_LESSONS_LEARNED.md # سجل الأخطاء والحلول القياسية
│   └── DEVELOPER_GUIDE.md     # دليل تفصيلي لكتابة وتوسيع الكود
│
└── uploads/                   # مجلد تخزين المرفقات المرفوعة من المستخدمين
    ├── .htaccess              # حظر تشغيل أي سكربت تنفيذي لحماية الخادم
    ├── avatars/               # صور المتطوعين وشعارات الأندية
    └── campaigns/             # صور أغلفة الحملات ومعارض الأنشطة الميدانية
```

---

## ⚙️ 2. دورة حياة الطلب (Request Lifecycle & Architecture Pattern)

1. **نقطة الدخول الوحيدة (`index.php`):**
   - يبدأ الطلب دائماً عبر `index.php?page={page_name}`.
   - يتم تحميل الإعدادات وقاعدة البيانات والجلسة وحراس الأمان.
2. **معالجة الإجراءات المسبقة (Pre-Shell Action Block):**
   - إذا كان الطلب `POST`:
     - يتم فحص توكن الحماية `csrf_verify()`.
     - يتم توجيه الإجراء في `switch ($page)` مثل: `action_login`، `action_apply_campaign`، `action_update_profile`، إلخ.
     - تتم معالجة البيانات وتحديث قاعدة البيانات.
     - يتم تعيين رسالة للمستخدم عبر `set_flash('success|error', '...')`.
     - يتم التحويل فوراً عبر `header('Location: ...')` مع `exit`.
     - **قاعدة ذهبية:** لا يتم إخراج أي كود HTML قبل أو أثناء تنفيذ إجراءات الـ POST!
3. **عرض الصفحة (View Rendering):**
   - إذا كان الطلب `GET`:
     - يتم فحص مصفوفة المسارات المسجلة `$routes[$page]`.
     - يتم استدعاء الملف المعني الذي يقوم بتضمين `header.php` ثم عرض المحتوى ثم `footer.php`.

---

## 🚀 3. خطوات الرفع والتثبيت على استضافة مجانية (InfinityFree Guide)

تم تجهيز الكود ليعمل بنسبة **100% Plug-and-Play** على خوادم **InfinityFree** أو أي استضافة cPanel مشتركة:

### الخطوة 1: إنشاء قاعدة البيانات على InfinityFree
1. ادخل إلى لوحة تحكم حسابك في InfinityFree وافتح **Control Panel (vPanel)**.
2. انتقل إلى **MySQL Databases**.
3. أنشئ قاعدة بيانات جديدة، مثلاً: `mobadir_db` (سيصبح اسمها الكامل شيء مثل `if0_12345678_mobadir_db`).
4. انسخ بيانات الاتصال التي تظهر لك:
   - **MySQL Hostname:** (مثال: `sql123.infinityfree.com`)
   - **MySQL Database Name:** (مثال: `if0_12345678_mobadir_db`)
   - **MySQL Username:** (مثال: `if0_12345678`)
   - **MySQL Password:** (كلمة المرور الخاصة بحسابك في vPanel)
5. افتح أداة **phpMyAdmin** من نفس الصفحة بجوار قاعدة البيانات.
6. اضغط على زر **Import**، واختر ملف `database/schema.sql` ثم اضغط **Go**.
7. *(اختياري للحسابات التجريبية)*: استورد أيضاً ملف `database/seed.sql` لإضافة حسابات تجريبية جاهزة.

### الخطوة 2: ضبط ملف الإعدادات `config/config.php`
افتح ملف `config/config.php` وعدّل قسم قاعدة البيانات فقط:
```php
define('DB_HOST', 'sql123.infinityfree.com'); // استبدله بالـ Hostname من vPanel
define('DB_PORT', '3306');
define('DB_NAME', 'if0_12345678_mobadir_db');  // استبدله باسم القاعدة
define('DB_USER', 'if0_12345678');             // استبدله باسم المستخدم
define('DB_PASS', 'كلمة_المرور_الخاصة_بك');
```
> **ملاحظة ذكية:** لا تحتاج لتعديل `BASE_URL` إطلاقاً! النظام يتحسس مسار النطاق تلقائياً سواء رفعت الملفات في المجلد الرئيسي `htdocs/` مباشرة أو في مجلد فرعي.

### الخطوة 3: رفع ملفات المشروع عبر FTP أو File Manager
1. افتح **Online File Manager** أو استخدم برنامج **FileZilla**:
   - الـ Host: الـ FTP Hostname المعطى في InfinityFree
   - Username: اسم مستخدم الـ FTP
   - Password: كلمة مرور الاستضافة
2. ادخل إلى مجلد **`htdocs/`**.
3. ارفع جميع ملفات ومجلدات مشروع `mobadir` داخل `htdocs/` مباشرة.
4. تأكد من أن ملفات `.htaccess` تم رفعها في:
   - مجلد الجذر: `htdocs/.htaccess`
   - مجلد المرفقات: `htdocs/uploads/.htaccess`

### الخطوة 4: التجربة والدخول
افتح رابط موقعك الممنوح لك من InfinityFree (مثال: `https://yourname.infinityfreeapp.com/`).
الموقع سيعمل فوراً بالكامل!

---

## 🔑 4. الحسابات التجريبية الافتراضية (Default Demo Accounts)
*كلمة المرور لجميع الحسابات التجريبية هي:* `Test@123`

| الدور (Role) | البريد الإلكتروني | نوع الحساب والوصول |
| :--- | :--- | :--- |
| **مشرف المديرية (Admin)** | `admin@djs-bechar.dz` | لوحة الرصد الوطني DJS، مؤشرات الأثر، وتجميد الحسابات |
| **منسق نادي (Club)** | `club.kenadsa@djs-bechar.dz` | نشر فرص، كشف الحضور، اعتماد الساعات، وإدارة ملف النادي |
| **متطوع (Volunteer)** | `amine@volunteer.dz` | جواز التطوع، سجل المشاركات، واستخراج الشهادات الرسمية |

---

## 🛠️ 5. دليل المطور: كيفية إضافة ميزة أو صفحة جديدة

### لإضافة صفحة عرض جديدة (مثال: صفحة الشروط والأحكام `terms`):
1. أنشئ ملف العرض في `pages/terms.php`:
   ```php
   <?php
   $page_title = "الشروط والأحكام";
   include __DIR__ . '/../partials/header.php';
   ?>
   <div class="container" style="padding:var(--sp-8) 0;">
       <h1>الشروط والأحكام</h1>
       <!-- المحتوى هنا -->
   </div>
   <?php include __DIR__ . '/../partials/footer.php'; ?>
   ```
2. سجّل المسار في مصفوفة `$routes` في نهاية `index.php`:
   ```php
   'terms' => __DIR__ . '/pages/terms.php',
   ```
3. الرابط في أي مكان يصبح: `<?= url('terms') ?>`.

### لإضافة إجراء POST جديد (مثال: حفظ استبيان تقييم `action_submit_feedback`):
1. ضع الفورم في الصفحة مع توكن الأمان:
   ```html
   <form method="POST" action="<?= url('action_submit_feedback') ?>">
       <?= csrf_field() ?>
       <input type="text" name="feedback" required class="form-control">
       <button type="submit" class="btn btn-primary">إرسال</button>
   </form>
   ```
2. أضف معالج الإجراء في `index.php` داخل كتلة `switch ($page)`:
   ```php
   case 'action_submit_feedback':
       require_login();
       $feedback = trim($_POST['feedback'] ?? '');
       // معالجة البيانات وتخزينها في قاعدة البيانات عبر Prepared Statement
       set_flash('success', 'شكراً لمشاركتك رأيك!');
       header('Location: ' . url('home'));
       exit;
       break;
   ```
