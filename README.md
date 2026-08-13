# Soran Login Panel

A polished, mobile-first passwordless login and registration experience for WordPress and WooCommerce. Customers can sign in with a mobile number, username, or email and verify their identity using a one-time password (OTP) delivered by SMS.

> **Current version:** 1.7.3  
> **Default documentation language:** English  
> [راهنمای فارسی](#راهنمای-فارسی)

## Overview

Soran Login Panel replaces the standard customer login journey with a branded, responsive OTP interface while leaving the native WordPress administrator login untouched. It is designed for stores and service websites that want a faster sign-in process, fewer password-related support requests, and a consistent experience across login, My Account, and checkout pages.

The plugin currently includes a dedicated integration for the MeliPayamak SMS service and supports both regular SMS messages and approved pattern/body-ID messages.

## Key Features

- Passwordless sign-in and registration with SMS OTP.
- Sign-in using a mobile number, WordPress username, or email address.
- Dedicated, configurable front-end login URL such as `/login/`.
- Automatic login immediately after successful OTP verification.
- Automatic customer account creation for new verified mobile numbers.
- Guided profile completion for first name, last name, and email.
- Safe linking of a newly created mobile account to an existing customer account.
- Protection against automatic OTP login or account merging for administrative roles.
- WooCommerce My Account login form replacement.
- Checkout authentication guard for guests.
- Configurable post-login redirection with loop protection.
- Mobile-friendly OTP fields with `autocomplete="one-time-code"` and WebOTP support.
- Configurable OTP lifetime, resend delay, code length, request limit, and verification attempts.
- MeliPayamak simple-message and pattern/body-ID sending modes.
- Built-in SMS connection test in the WordPress dashboard.
- Fully editable branding, headings, descriptions, and benefit content.
- Optional full-screen login mode that hides the theme header and footer.
- Optional mobile bottom navigation for account, cart, and support.
- Support contact sheet for WhatsApp, Bale, Eitaa, Telegram, Rubika, and phone calls.
- Optional compatibility with the WC Order Reserve dashboard shortcode or endpoint.
- Two compatible shortcodes for flexible placement.
- Translation-ready structure using the `kadec-mobile-login` text domain.

## Requirements

- WordPress with pretty permalinks enabled.
- PHP 7.4 or later is recommended.
- WooCommerce for checkout, My Account, cart, and order-related integrations.
- An active MeliPayamak account with valid API credentials.
- A working SMS sender line or an approved pattern/body ID.
- HTTPS is strongly recommended for production websites.

The OTP shortcode and dedicated login route can work without WooCommerce, but store-specific integrations naturally require WooCommerce.

## Installation

1. Copy the `soran-login-panel` directory to `wp-content/plugins/`.
2. Activate **Luna Unified Login** from **Plugins** in WordPress. This is the current branded name of Soran Login Panel.
3. Open **Luna Login** in the WordPress admin menu.
4. Enter and save the SMS provider credentials.
5. Configure the OTP rules, login route, branding, and optional mobile tools.
6. Use the built-in SMS test before enabling the flow for customers.
7. Open the configured login URL and complete an end-to-end test.

Activation automatically registers the login route and refreshes WordPress rewrite rules.

## SMS Configuration

Open **WordPress Admin → Luna Login** and configure the following fields.

### Provider credentials

- SMS username
- SMS password
- Sender number or line
- API base URL

The password field keeps the previously saved password when submitted empty.

### Simple message mode

Choose the regular/simple mode and define the message template. The placeholder below is replaced with the generated OTP:

```text
Your login code: {code}
```

### Pattern mode

Choose pattern mode and configure:

- MeliPayamak body ID
- Pattern variable values
- Flash-message option, if supported by the account

Use `{code}` wherever the OTP must appear. Multiple pattern values may be separated by a new line or `|`.

### Test the connection

Save the settings, enter a valid Iranian mobile number in the SMS test section, and click the test button. The plugin translates common MeliPayamak API response codes into readable administration messages.

## OTP and Security Settings

The following rules can be adjusted from the settings page:

- OTP lifetime
- Resend waiting time
- OTP length between 4 and 8 digits
- Per-IP sending limit
- Maximum verification attempts

Security measures include:

- WordPress nonces for AJAX requests.
- Hashed OTP values stored temporarily with WordPress Transients.
- Temporary resend and verification-attempt locks.
- Per-IP request throttling.
- Mobile-number normalization and validation.
- Safe redirect validation through WordPress APIs.
- Short-lived profile-completion tokens.
- Sanitization of all saved settings and customer profile values.
- Restricted OTP access for administrator, editor, shop manager, and other sensitive roles.
- Account merging only when the verified mobile identity is compatible with the existing account.

OTP login is intended for regular customers. Administrators continue to use the native WordPress login page.

## Dedicated Login Page

The dedicated front-end login route is enabled by default:

```text
https://example.com/login/
```

The route slug can be changed in the plugin settings. WordPress login links for regular front-end journeys are redirected to this route, while administrator access remains available through the native WordPress login.

If a customer is already signed in, visiting the standalone login route redirects them to the website homepage instead of displaying an empty login screen. The embedded checkout flow keeps its own checkout destination.

## Shortcodes

Recommended shortcode:

```text
[soran_mobile_login]
```

Legacy-compatible shortcode:

```text
[kadec_mobile_login]
```

Both shortcodes render the same OTP interface. The legacy shortcode is retained to avoid breaking existing pages.

## Customer Journey

1. The customer enters a mobile number, username, or email.
2. The plugin resolves the verified mobile number associated with the account.
3. An OTP is generated and sent by SMS.
4. The customer enters the code.
5. Existing customers are signed in; new mobile customers receive a customer account.
6. Incomplete profiles are prompted for first name, last name, and email.
7. The standalone login page returns the customer to the homepage; embedded forms such as checkout preserve their own safe destination.

## Existing Account Linking

When enabled, a newly created mobile customer can enter the email address of an older customer account. The plugin links the accounts only when the operation is considered safe.

Automatic linking is blocked when:

- The email belongs to an administrative or sensitive account.
- The existing account contains a different verified mobile number.
- WordPress rejects the account update.

Successful linking preserves the established customer account and assigns the verified mobile number to it.

Always back up the database before enabling account linking on a website with an existing customer base.

## WooCommerce Integration

When WooCommerce is active, the plugin can:

- Replace the customer login and registration form in My Account.
- Require OTP authentication before checkout continues.
- Disable WooCommerce's standard checkout registration form.
- Redirect authenticated customers safely after login.
- Display cart count in the optional mobile navigation.
- Provide direct links to My Account and the cart.
- Display reserved-order information when WC Order Reserve is available.

## Mobile Navigation and Support Sheet

The optional bottom navigation is displayed on supported WooCommerce account and checkout views. Its labels can be edited from the settings page.

The support button can either open WhatsApp directly or display a bottom sheet containing any enabled support channels:

- WhatsApp
- Bale
- Eitaa
- Telegram
- Rubika
- Telephone

Only configured and enabled channels are shown to customers.

## Branding and Presentation

The admin settings allow editing of:

- Brand word and side mark
- Security badge text
- Main title and description
- Benefit pill
- Three benefit titles and descriptions
- Checkout title and description
- Mobile navigation labels
- Full-screen header/footer behavior

Front-end styles and behavior are located in:

```text
assets/css/style.css
assets/js/otp.js
```

Admin styles and scripts are located in:

```text
assets/css/admin.css
assets/js/admin.js
```

## Project Structure

```text
soran-login-panel/
├── kadec-mobile-login.php     # Plugin bootstrap and core application logic
├── assets/
│   ├── css/
│   │   ├── admin.css          # Settings-page styling
│   │   └── style.css          # Login and customer-facing styling
│   ├── js/
│   │   ├── admin.js           # Admin SMS test and settings behavior
│   │   └── otp.js             # OTP, profile, and support interactions
│   └── icons/                 # Support-channel icons
├── README.md
└── README.txt
```

## AJAX Actions

The front-end communicates with WordPress through these AJAX actions:

| Action | Purpose |
| --- | --- |
| `kml_send_otp` | Resolve the identifier and send an OTP |
| `kml_verify_otp` | Verify the OTP and sign in or register the customer |
| `kml_complete_profile` | Complete customer details and optionally link an existing account |
| `kml_test_sms` | Test SMS delivery for an authorized administrator |

Do not expose provider credentials in JavaScript or custom templates. All SMS requests must remain server-side.

## Troubleshooting

### The login URL returns 404

Visit **Settings → Permalinks** and click **Save Changes** once. Also verify that the dedicated login route is enabled and its slug does not conflict with an existing page.

### SMS is not delivered

- Run the built-in SMS test.
- Verify the API username and password.
- Verify the sender line or body ID.
- Confirm that the MeliPayamak account is active and funded.
- Check provider IP restrictions and pattern approval status.
- Ensure the server can make outgoing HTTPS requests.

### OTP is immediately rejected

Check the configured OTP lifetime and confirm that server time is correct. Request a fresh code after the resend delay.

### A customer cannot link an older account

Confirm that the old account is not administrative and that its saved mobile number is empty or matches the newly verified number.

### Changes are not visible

Clear page cache, object cache, CDN cache, and browser cache. Minification plugins may also need their generated assets rebuilt.

## Deployment Checklist

- Back up the WordPress database and files.
- Configure production SMS credentials.
- Test both simple and pattern modes if they may be used.
- Test new-customer registration.
- Test login for an existing mobile customer.
- Test username/email identifier login.
- Verify that administrator OTP login is blocked.
- Test profile completion and account linking.
- Test My Account and checkout redirects.
- Test the experience on both mobile and desktop.
- Confirm HTTPS and cache exclusions for the login flow and `admin-ajax.php`.

## Versioning and Releases

The plugin version is defined in both the WordPress plugin header and `KADEC_Mobile_Login::VERSION`. Keep the two values synchronized when preparing a release.

Suggested Git tags follow semantic versioning:

```text
v1.7.3
```

### 1.7.3

- Added a per-mobile database advisory lock around OTP verification.
- Made the attempt read/check/increment/consume sequence concurrency-safe.
- Prevented parallel requests from reusing one successful OTP.
- Prevented simultaneous send requests from generating conflicting OTP values.

## Contributing

1. Create a feature branch from the main development branch.
2. Keep changes focused and backward compatible.
3. Preserve both shortcode names unless a migration path is provided.
4. Sanitize input, escape output, and protect state-changing requests with nonces.
5. Test login, registration, profile completion, redirects, and WooCommerce checkout.
6. Update this README and the plugin version when behavior changes.

## License

No open-source license is currently included in this repository. Unless a `LICENSE` file is added by the copyright owner, the source code should be treated as **all rights reserved**.

---

# راهنمای فارسی

## معرفی

**Soran Login Panel** یک افزونه ورود و ثبت‌نام بدون رمز عبور برای وردپرس و ووکامرس است. کاربر می‌تواند شماره موبایل، نام کاربری یا ایمیل خود را وارد کند و پس از دریافت رمز یک‌بارمصرف پیامکی وارد حساب شود.

این افزونه مسیر ورود مشتریان را با یک رابط اختصاصی، واکنش‌گرا و مناسب موبایل جایگزین می‌کند؛ با این حال ورود مدیران وردپرس دست‌نخورده باقی می‌ماند و حساب‌های مدیریتی اجازه ورود خودکار با OTP را ندارند.

در نسخه فعلی، اتصال پیامکی برای سرویس **ملی‌پیامک** پیاده‌سازی شده و ارسال عادی و ارسال براساس پترن یا Body ID را پشتیبانی می‌کند.

## امکانات اصلی

- ورود و ثبت‌نام با رمز یک‌بارمصرف پیامکی
- پشتیبانی از شماره موبایل، نام کاربری و ایمیل در فرم ورود
- مسیر اختصاصی و قابل تنظیم مانند `/login/`
- ورود خودکار پس از تأیید صحیح کد
- ساخت حساب مشتری برای شماره موبایل جدید
- تکمیل مرحله‌ای نام، نام خانوادگی و ایمیل
- اتصال امن حساب موبایلی جدید به حساب قدیمی مشتری
- جلوگیری از ورود پیامکی و ادغام خودکار حساب‌های مدیریتی
- جایگزینی فرم ورود مشتری ووکامرس
- الزام ورود قبل از ادامه پرداخت
- مدیریت امن آدرس بازگشت پس از ورود
- پشتیبانی از `one-time-code` و WebOTP در موبایل
- تنظیم زمان اعتبار، فاصله ارسال مجدد، طول کد و محدودیت تلاش
- ارسال ساده یا پترن ملی‌پیامک
- تست ارسال پیامک از مدیریت وردپرس
- تنظیم کامل متن‌ها و هویت بصری فرم
- امکان مخفی‌کردن هدر و فوتر در تجربه ورود
- نوار پایین موبایل برای حساب، سبد خرید و پشتیبانی
- پشتیبانی از واتساپ، بله، ایتا، تلگرام، روبیکا و تماس تلفنی
- سازگاری اختیاری با افزونه WC Order Reserve
- دو شورت‌کد سازگار برای استفاده در صفحات مختلف

## پیش‌نیازها

- وردپرس با پیوندهای یکتای فعال
- پیشنهاد می‌شود PHP نسخه 7.4 یا جدیدتر استفاده شود
- ووکامرس برای قابلیت‌های حساب کاربری، سبد خرید و پرداخت
- حساب فعال ملی‌پیامک و اطلاعات معتبر API
- خط ارسال یا پترن تأییدشده
- استفاده از HTTPS در سایت اصلی

شورت‌کد و مسیر ورود اختصاصی بدون ووکامرس نیز قابل استفاده هستند، اما امکانات فروشگاهی به ووکامرس وابسته‌اند.

## نصب

1. پوشه `soran-login-panel` را داخل `wp-content/plugins/` قرار دهید.
2. افزونه **ورود یکپارچه لونا** را از بخش افزونه‌ها فعال کنید.
3. از منوی مدیریت وارد **ورود لونا** شوید.
4. اطلاعات سرویس پیامکی را وارد و ذخیره کنید.
5. تنظیمات OTP، مسیر ورود، متن‌ها و ابزارهای موبایل را مشخص کنید.
6. قبل از انتشار عمومی، ارسال پیامک آزمایشی انجام دهید.
7. مسیر ورود را در موبایل و دسکتاپ به‌صورت کامل تست کنید.

فعال‌سازی افزونه مسیر ورود را ثبت می‌کند و قوانین پیوند یکتا را به‌روزرسانی می‌کند.

## تنظیم ملی‌پیامک

از مسیر **مدیریت وردپرس ← ورود لونا** اطلاعات زیر را تنظیم کنید:

- نام کاربری پنل پیامکی
- رمز عبور
- شماره یا خط فرستنده
- آدرس پایه API

اگر هنگام ذخیره تنظیمات، فیلد رمز عبور خالی باشد، رمز قبلی حفظ می‌شود.

### ارسال عادی

حالت ارسال ساده را انتخاب و متن پیامک را وارد کنید. عبارت `{code}` با کد ورود جایگزین می‌شود:

```text
کد ورود شما: {code}
```

### ارسال پترن

حالت پترن را انتخاب و موارد زیر را وارد کنید:

- Body ID ملی‌پیامک
- مقادیر متغیرهای پترن
- حالت Flash در صورت پشتیبانی پنل

برای قرار دادن کد از `{code}` استفاده کنید. چند مقدار را می‌توان با خط جدید یا `|` از هم جدا کرد.

### تست ارسال

پس از ذخیره تنظیمات، شماره موبایل معتبر را در بخش تست وارد کنید. افزونه پاسخ‌ها و خطاهای رایج ملی‌پیامک را به پیام‌های قابل فهم تبدیل می‌کند.

## تنظیمات امنیتی OTP

موارد زیر قابل تنظیم هستند:

- مدت اعتبار کد
- فاصله مجاز ارسال مجدد
- طول کد بین 4 تا 8 رقم
- سقف ارسال براساس IP
- حداکثر تلاش ناموفق

اقدامات امنیتی افزونه:

- استفاده از nonce وردپرس برای درخواست‌های AJAX
- ذخیره هش‌شده کد در Transient و عدم نگهداری کد خام
- قفل موقت ارسال مجدد و تلاش‌های ناموفق
- محدودیت ارسال براساس IP
- یکسان‌سازی و اعتبارسنجی شماره موبایل
- اعتبارسنجی امن آدرس بازگشت
- توکن کوتاه‌عمر برای تکمیل پروفایل
- پاک‌سازی تمام تنظیمات و اطلاعات ورودی
- مسدودکردن OTP برای نقش‌های حساس و مدیریتی
- کنترل تطابق هویت موبایلی پیش از اتصال حساب‌ها

## صفحه ورود اختصاصی

مسیر پیش‌فرض:

```text
https://example.com/login/
```

نامک مسیر از تنظیمات قابل تغییر است. لینک‌های ورود سمت کاربر به این صفحه هدایت می‌شوند، اما مدیر سایت همچنان از صفحه اصلی ورود وردپرس استفاده می‌کند.

اگر کاربر قبلاً وارد شده باشد، مراجعه به صفحه ورود او را به مقصد امن یا حساب کاربری ووکامرس هدایت می‌کند و صفحه خالی نمایش داده نمی‌شود.

## شورت‌کدها

شورت‌کد پیشنهادی:

```text
[soran_mobile_login]
```

شورت‌کد قدیمی و سازگار:

```text
[kadec_mobile_login]
```

هر دو شورت‌کد یک فرم یکسان نمایش می‌دهند. شورت‌کد قدیمی برای جلوگیری از خراب‌شدن صفحات قبلی حفظ شده است.

## فرایند ورود مشتری

1. کاربر شماره موبایل، نام کاربری یا ایمیل را وارد می‌کند.
2. افزونه شماره موبایل تأییدپذیر حساب را پیدا می‌کند.
3. کد یک‌بارمصرف ساخته و پیامک می‌شود.
4. کاربر کد را وارد می‌کند.
5. کاربر قدیمی وارد می‌شود یا برای شماره جدید حساب مشتری ساخته می‌شود.
6. در صورت ناقص‌بودن پروفایل، نام، نام خانوادگی و ایمیل دریافت می‌شود.
7. کاربر به صفحه موردنظر، پرداخت یا پنل کاربری هدایت می‌شود.

## اتصال حساب قدیمی

اگر این قابلیت فعال باشد، مشتری موبایلی جدید می‌تواند ایمیل حساب قدیمی خود را وارد کند. اتصال فقط در شرایط امن انجام می‌شود.

اتصال خودکار انجام نمی‌شود اگر:

- ایمیل مربوط به حساب مدیریتی یا حساس باشد.
- حساب قدیمی شماره موبایل متفاوتی داشته باشد.
- وردپرس عملیات به‌روزرسانی حساب را نپذیرد.

قبل از فعال‌کردن این قابلیت در سایتی با مشتریان قدیمی، حتماً از دیتابیس نسخه پشتیبان بگیرید.

## یکپارچگی با ووکامرس

در صورت فعال‌بودن ووکامرس، افزونه می‌تواند:

- فرم ورود و ثبت‌نام حساب کاربری را جایگزین کند.
- پیش از ادامه پرداخت، ورود پیامکی را الزامی کند.
- فرم ثبت‌نام پیش‌فرض Checkout را غیرفعال کند.
- کاربر را بعد از ورود به مقصد امن هدایت کند.
- تعداد محصولات سبد خرید را در نوار موبایل نمایش دهد.
- لینک سریع حساب کاربری و سبد خرید ارائه کند.
- اطلاعات سفارش‌های رزروشده را نمایش دهد.

## نوار موبایل و مرکز پشتیبانی

نوار پایین اختیاری در صفحات مرتبط حساب کاربری و پرداخت نمایش داده می‌شود. متن کلیدهای آن از تنظیمات قابل ویرایش است.

کلید پشتیبانی می‌تواند مستقیماً واتساپ را باز کند یا یک Bottom Sheet شامل راه‌های ارتباطی زیر نمایش دهد:

- واتساپ
- بله
- ایتا
- تلگرام
- روبیکا
- تماس تلفنی

فقط گزینه‌های فعال و دارای آدرس معتبر به کاربر نمایش داده می‌شوند.

## شخصی‌سازی ظاهر و متن

موارد زیر از صفحه تنظیمات قابل ویرایش هستند:

- نام برند و نشان کناری
- متن نشان امنیت
- عنوان و توضیحات اصلی
- متن کوتاه مزیت
- عنوان و توضیح سه مزیت
- عنوان و توضیح Checkout
- متن‌های نوار موبایل
- مخفی‌شدن هدر و فوتر

فایل‌های سمت کاربر:

```text
assets/css/style.css
assets/js/otp.js
```

فایل‌های مدیریت:

```text
assets/css/admin.css
assets/js/admin.js
```

## ساختار پروژه

```text
soran-login-panel/
├── kadec-mobile-login.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── style.css
│   ├── js/
│   │   ├── admin.js
│   │   └── otp.js
│   └── icons/
├── README.md
└── README.txt
```

## رفع اشکال

### صفحه ورود خطای 404 دارد

وارد **تنظیمات ← پیوندهای یکتا** شوید و یک‌بار «ذخیره تغییرات» را بزنید. همچنین مطمئن شوید نامک ورود با برگه دیگری تداخل ندارد.

### پیامک ارسال نمی‌شود

- ارسال آزمایشی را اجرا کنید.
- نام کاربری و رمز API را بررسی کنید.
- خط فرستنده یا Body ID را بررسی کنید.
- اعتبار و فعال‌بودن حساب ملی‌پیامک را کنترل کنید.
- محدودیت IP و تأیید پترن را بررسی کنید.
- مطمئن شوید سرور اجازه اتصال HTTPS خروجی دارد.

### کد بلافاصله نامعتبر می‌شود

مدت اعتبار OTP و ساعت سرور را بررسی کنید. پس از پایان محدودیت ارسال مجدد، کد جدید بگیرید.

### اتصال حساب قدیمی انجام نمی‌شود

بررسی کنید حساب قدیمی مدیریتی نباشد و شماره موبایل آن خالی یا با شماره تأییدشده یکسان باشد.

### تغییرات دیده نمی‌شود

کش صفحه، Object Cache، CDN و مرورگر را پاک کنید. در صورت استفاده از افزونه بهینه‌سازی، فایل‌های تجمیع‌شده را دوباره بسازید.

## چک‌لیست انتشار

- پشتیبان‌گیری از فایل‌ها و دیتابیس
- ثبت اطلاعات واقعی سرویس پیامکی
- تست ارسال ساده و پترن
- تست ثبت‌نام مشتری جدید
- تست ورود مشتری قدیمی
- تست ورود با نام کاربری یا ایمیل
- اطمینان از مسدودبودن ورود OTP مدیر
- تست تکمیل پروفایل و اتصال حساب
- تست بازگشت به حساب کاربری و Checkout
- تست کامل در موبایل و دسکتاپ
- بررسی HTTPS و خارج‌کردن مسیر ورود و `admin-ajax.php` از کش نامناسب

## نسخه‌گذاری

نسخه افزونه هم در هدر فایل اصلی و هم در ثابت `KADEC_Mobile_Login::VERSION` تعریف شده است. هنگام انتشار هر دو مقدار باید یکسان تغییر کنند.

نمونه تگ Git:

```text
v1.7.3
```

### تغییرات نسخه 1.7.3

- اضافه‌شدن قفل دیتابیسی اختصاصی برای بررسی OTP هر شماره
- اتمیک‌شدن چرخه خواندن، بررسی و افزایش شمارنده تلاش‌ها
- جلوگیری از استفاده هم‌زمان چند درخواست از یک کد صحیح
- جلوگیری از ساخته‌شدن دو کد متناقض در ارسال‌های هم‌زمان

## مشارکت در توسعه

1. برای هر قابلیت یک branch جدا ایجاد کنید.
2. تغییرات را محدود و سازگار با نسخه‌های قبلی نگه دارید.
3. بدون مسیر مهاجرت، شورت‌کد قدیمی را حذف نکنید.
4. ورودی‌ها را پاک‌سازی، خروجی‌ها را escape و عملیات را با nonce محافظت کنید.
5. ورود، ثبت‌نام، پروفایل، هدایت و Checkout را تست کنید.
6. همراه تغییر رفتار، README و نسخه افزونه را به‌روزرسانی کنید.

## مجوز

در حال حاضر فایل مجوز متن‌باز در پروژه وجود ندارد. تا زمانی که مالک پروژه فایل `LICENSE` اضافه نکرده است، کد به‌صورت **تمام حقوق محفوظ است** در نظر گرفته می‌شود.
