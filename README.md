# TrackDZ – Global Package Tracker

نظام تتبع طرود متعدد الشركات ببنية PHP خفيفة (MVC)، يدعم العربية/الفرنسية/الإنجليزية، يعمل على استضافة مشتركة.

## المتطلبات
- PHP 8.0+
- MySQL 5.7+/8
- تفعيل cURL و PDO
- صلاحية الكتابة إلى مجلد storage

## التثبيت على استضافة مشتركة
1) ارفع كامل مجلد المشروع إلى `public_html` أو اجعل محتويات `public/` هي الجذر العام للموقع.
2) احمِ المجلدات الحساسة:
   - ملفات `.htaccess` مرفقة في `config/` و `storage/` لمنع الوصول المباشر.
3) أنشئ قاعدة بيانات MySQL ثم استورد ملف `schema.sql`.
4) انسخ `config/config.php.example` إلى `config/config.php` وعدل القيم:
   - بيانات الاتصال بقاعدة البيانات.
   - مفاتيح API لـ AfterShip و 17Track (اختياري). في حال عدم توفرها فعّل `DEMO_MODE=true`.
   - ضع `CRON_SECRET` قوي.
5) اللغة:
   - الافتراضية `ar`. يمكنك تغييرها في `config.php`.
   - زر تبديل اللغة في رأس الموقع.
6) إعداد كرون (كل 10 دقائق):
   - الأمر: `php /path/to/public_html/cron.php 'secret=YOUR_SECRET'` غير ذلك، استخدم رابط: `https://yourdomain.com/cron.php?secret=YOUR_SECRET`
7) اختبار:
   - افتح الصفحة الرئيسية وأدخل: `1Z12345E1512345676` وشاهد النتيجة.
8) Webhooks (اختياري):
   - AfterShip: وجّه إشعارات الويب إلى: `/webhook.php?carrier=aftership`
   - 17Track: إلى: `/webhook.php?carrier=17track
