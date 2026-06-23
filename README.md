# 🏛️ AmunGuide API

> محرك ذكي متكامل لإدارة السياحة وتخطيط الرحلات

[![Laravel Version](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![Database](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Deployment](https://img.shields.io/badge/Railway-Deployed-131415?style=for-the-badge&logo=railway&logoColor=white)](https://railway.app)

**AmunGuide API** هو نظام خلفي متطور ومستقل (Decoupled RESTful API) مبني باستخدام إطار العمل **Laravel 10** ليكون النواة البرمجية لمنصة سياحية متكاملة في مصر. يدعم النظام تطبيقات الويب والهواتف الذكية عبر توفير بيئة مرنة وآمنة وقابلة للتوسع لإدارة المزارات السياحية، الجولات، حجوزات الأفواج، أنظمة الدفع الرقمية، ونظام متكامل مدعوم بالذكاء الاصطناعي (AI) لتخطيط الرحلات تلقائياً وتحليل سلوك المستخدمين.

---

## 🚀 الميزات الرئيسية

### 1. إدارة الهوية وصلاحيات المستخدمين (Multi-Role Authentication)
- **دعم الأدوار المتقدمة:** تصنيف صارم للمستخدمين عبر صيغ `Tourist / Guide / Admin` مع تطبيق جدار حماية مخصص لكل دور عبر ميكانيزم الـ Middleware.
- **توثيق آمن:** اعتماد حزمة **Laravel Sanctum** لإصدار وإدارة رموز الوصول الآمنة (Bearer Tokens).
- **إدارة الحسابات:** ميزات متكاملة لتحديث الملف الشخصي، رفع الصور، واستعادة كلمات المرور عبر الإشعارات المخصصة.

### 2. محرك إدارة المزارات السياحية والجولات (Places & Tours Hub)
- **المزارات (Places):** دليل سياحي متكامل يدعم تصنيف الأماكن، إدارة البيانات الوصفية، والوسائط المتعددة (Images & Media).
- **الجولات السياحية (Tours):** نظام مخصص للمرشدين لإنشاء جولات متكاملة، تحديد الأسعار، المواعيد، وربط الجولة بخطة سير زمنية محددة.
- **البحث والفلترة:** محرك بحث ذكي يسمح بفلترة النتائج بناءً على معايير مركبة مثل الأسعار، التقييمات، والنوع.

### 3. التخطيط الذكي لرحلات السفر (Custom Travel Itineraries)
- **صانع الخطط (Plans Engine):** تمكين السياح من إنشاء خطط سفر مخصصة وجداول زمنية يومية (`Plan Items`) وربطها بأماكن سياحية معينة.

### 4. المساعد الذكي والـ AI Chatbot (Advanced RAG Integration)
- **محادثات مخصصة السياق:** تتبع ذكي للمحادثات عبر نموذج بيانات مرن يدعم سياقات متعددة مثل `image_generation`, `travel_plan`, `info_request`, `place_inquiry`, `tour_inquiry`.
- **تغذية نموذج الـ RAG:** تجميع وتشكيل فوري لبيانات المستخدم وتفضيلاته السياحية وعرضها على محرك الذكاء الاصطناعي لبناء استجابات دقيقة ومخصصة.
- **الصور المولدة بالذكاء الاصطناعي:** تتبع وحفظ الصور المولدة آلياً للمستخدم خلال المحادثة بروابط آمنة.

### 5. التفاعلات متعددة الأشكال (Polymorphic Social Interaction)
- **التعليقات (Polymorphic Comments):** نظام تعليقات موحد يخدم الجولات، الأماكن، والخطط السياحية في جدول واحد عبر بنية `morphs`.
- **الإعجابات (Polymorphic Likes):** ميزة Toggle سريعة محمية من تكرار الإعجاب (`409 Conflict`).

### 6. لوحة التحليلات وتتبع السلوكيات (User Behavior Analytics)
- **تتبع الأنشطة:** رصد فوري للزيارات، عمليات البحث، الإعجابات، التعليقات، وإنشاء الخطط.
- **معالجة البيانات المعقدة:** محرك داخلي ذكي للتعامل مع البيانات المخزنة بصيغ JSON المركبة وتوفير خط زمني نظيف لسلوكيات المستخدم.
- **إحصائيات إدارية (Global Trends):** رؤية شاملة حول المستخدمين النشطين، المزارات الأكثر رواجاً، وتحليل معدلات التفاعل.

### 7. المدفوعات الآمنة والحجوزات (Secure Bookings & Payments)
- **إدارة الحجوزات:** دورة حياة متكاملة تبدأ من إنشاء الطلب، التحقق من التوافر، وتحديث الحالة `Pending / Confirmed / Cancelled`.
- **سجل المدفوعات:** توثيق آمن لعمليات الدفع الرقمي مع الاحتفاظ ببيانات المعاملات والأرقام المرجعية وحالات السداد.

---

## 🛠️ التقنيات المستخدمة (Tech Stack)

| التقنية | التفاصيل |
|---|---|
| **Backend Framework** | Laravel 10 (PHP 8.2) |
| **Database** | MySQL 8.0 مع Polymorphic Relations وIndexes محسّنة |
| **Authentication** | Laravel Sanctum |
| **API Standardization** | Laravel API Resources |
| **Data Validation** | Form Requests مخصصة لكل Endpoint |
| **Deployment** | Nixpacks + Docker Ready (Railway) |

---

## 📂 بنية المجلدات

```
peter1girgis/AmunGuide/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Admin/                     # لوحة تحكم الإدارة وصلاحيات المستخدمين
│   │   │   ├── AnalysisController.php     # محرك التحليلات وتتبع الأنشطة
│   │   │   ├── CommentsController.php     # نظام التعليقات متعدد الأشكال
│   │   │   ├── ConversationController.php # محادثات الـ Chatbot وتوليد الصور
│   │   │   ├── LikesController.php        # الإعجابات التفاعلية الموحدة
│   │   │   ├── PaymentController.php      # معالجة وتوثيق المدفوعات
│   │   │   ├── PlaceController.php        # إدارة المزارات السياحية والوسائط
│   │   │   ├── PlanController.php         # محرك تخطيط الرحلات
│   │   │   ├── RagMessageController.php   # معالجة رسائل الـ AI وتغذية الـ RAG
│   │   │   └── TourBookingController.php  # إدارة حجز الجولات السياحية
│   │   ├── Middleware/                    # جدران الحماية (Admin, Tourist, Guide)
│   │   ├── Requests/                      # Form Requests لكل Endpoint
│   │   └── Resources/                     # طبقة توحيد مخرجات الـ API
│   ├── Models/                            # النماذج البرمجية وعلاقات Eloquent
│   └── Notifications/                     # نظام الإشعارات واستعادة كلمة المرور
├── config/                                # إعدادات CORS, Database, Cache, Sanctum
├── database/
│   ├── migrations/                        # 19 Migration لبناء جداول قاعدة البيانات
│   └── seeders/                           # البيانات الأولية للتجريب
├── routes/
│   ├── api.php                            # 87 مسار برمجي منظم وموزع حسب الصلاحيات
│   └── web.php
├── nixpacks.toml                          # إعدادات بيئة التشغيل السحابية
├── Procfile                               # أوامر التشغيل لمنصات الاستضافة
└── railway-entrypoint.sh                  # سكربت التهيئة التلقائية عند كل Deployment
```

---

## 🗺️ API Endpoints

### 🔐 Auth Module

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/register` | POST | Public | إنشاء حساب جديد (Tourist / Guide) |
| `/api/v1/login` | POST | Public | تسجيل الدخول وإصدار Bearer Token |
| `/api/v1/logout` | POST | Authenticated | إتلاف رمز التحقق وتسجيل الخروج |
| `/api/v1/forgot-password` | POST | Public | إرسال رابط استعادة كلمة المرور |

### 🏛️ Places Module

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/places` | GET | Public | جلب المزارات بنظام Pagination |
| `/api/v1/places/{id}` | GET | Public | عرض التفاصيل الكاملة للمزار |
| `/api/v1/places/filter` | GET | Public | فلترة الأماكن بالنوع والتصنيف الجغرافي |
| `/api/v1/places` | POST | Admin Only | إضافة مزار سياحي جديد |

### 🗺️ Tours & Bookings Module

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/tours` | GET | Public | استعراض كافة الجولات المتاحة |
| `/api/v1/tours/popular` | GET | Public | جلب الجولات الأكثر طلباً |
| `/api/v1/tours` | POST | Guide Only | إنشاء جولة وربطها بخطة سير |
| `/api/v1/bookings` | POST | Tourist Only | طلب حجز جولة سياحية |
| `/api/v1/bookings/{id}` | PUT | Authenticated | تحديث حالة الحجز |

### 🤖 AI Chatbot & RAG Module

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/conversations` | POST | Authenticated | بدء جلسة محادثة جديدة مع البوت |
| `/api/v1/conversations` | GET | Authenticated | جلب أرشيف محادثات المستخدم |
| `/api/v1/conversations/{id}/messages` | POST | Authenticated | إرسال رسالة واستقبال رد الـ AI |
| `/api/v1/rag/data` | GET | Authenticated | تجهيز الملف السلوكي للمستخدم للـ RAG |

### 📈 Analytics Module

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/analysis/my-data` | GET | Authenticated | الخط الزمني لجميع تفاعلات المستخدم |
| `/api/v1/analysis/global-trends` | GET | Admin Only | لوحة الإحصائيات العامة للمنصة |

### 💬 Polymorphic Likes & Comments

| Endpoint | Method | Access | الوصف |
|---|---|---|---|
| `/api/v1/likes/toggle` | POST | Authenticated | إضافة أو إزالة إعجاب من Tour / Place / Plan |
| `/api/v1/comments` | POST | Authenticated | إضافة تعليق على جولة أو مكان |
| `/api/v1/{type}/{id}/comments` | POST | Authenticated | مسار مباشر لإضافة تعليق |

---

## ⚙️ التشغيل المحلي (Local Setup)

```bash
# 1. إعداد ملف البيئة
cp .env.example .env

# 2. تثبيت الحزم
composer install

# 3. توليد المفتاح التشفيري
php artisan key:generate

# 4. ضبط إعدادات قاعدة البيانات في .env ثم تشغيل الـ Migrations
php artisan migrate --seed

# 5. تشغيل الخادم المحلي
php artisan serve
```

---

## ☁️ الاستضافة السحابية (Deployment)

المشروع مهيأ للرفع المباشر على **Railway** بفضل:

- **`nixpacks.toml`** — يبني البيئة البرمجية تلقائياً ويثبت `php82` وامتداداتها مثل `pdo_mysql` مع تحسين كاش الأداء في بيئة الإنتاج.
- **`railway-entrypoint.sh`** — يُنشئ الكاش، يحسّن المسارات عبر `route:cache`، ويحمي مجلدات التخزين تلقائياً عند كل Deployment دون أي تدخل يدوي.
