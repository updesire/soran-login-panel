<?php
/**
 * Plugin Name: لاگین سوران
 * Plugin URI: https://rocketzi.com
 * Description: تنظیمات ورود و ثبت‌نام موبایلی مخصوص ووکامرس و قالب Woodmart.
 * Version: 1.6.4
 * Author: Soran
 * Text Domain: kadec-mobile-login
 */

if (!defined('ABSPATH')) exit;

final class KADEC_Mobile_Login {
    const OPTION = 'kml_options';
    const NONCE  = 'kml_nonce';
    const VERSION = '1.6.4';

    /** @var array|null Runtime cache for plugin options to avoid repeated wp_parse_args/defaults calls. */
    private $options_cache = null;

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'front_assets']);
        add_filter('body_class', [$this, 'filter_body_class']);

        add_shortcode('soran_mobile_login', [$this, 'shortcode']);
        add_shortcode('kadec_mobile_login', [$this, 'shortcode']);

        add_action('wp_ajax_nopriv_kml_send_otp', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_kml_send_otp', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_nopriv_kml_verify_otp', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_kml_verify_otp', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_kml_complete_profile', [$this, 'ajax_complete_profile']);
        add_action('wp_ajax_nopriv_kml_complete_profile', [$this, 'ajax_complete_profile']);
        add_action('wp_ajax_kml_test_sms', [$this, 'ajax_test_sms']);

        add_action('woocommerce_before_checkout_form', [$this, 'guard_checkout'], 1);
        add_filter('woocommerce_checkout_registration_enabled', '__return_false');
        add_filter('woocommerce_checkout_registration_required', '__return_false');

        // Replace WooCommerce customer login/register page for normal customers only.
        // Admin /wp-login.php remains untouched.
        add_action('woocommerce_before_customer_login_form', [$this, 'replace_woocommerce_login_form'], 1);

        // Show WooCommerce order reserve information on My Account dashboard when WC Order Reserve plugin is active.
        add_action('woocommerce_account_dashboard', [$this, 'render_reserved_orders_on_account_dashboard'], 25);
        add_action('wp_footer', [$this, 'render_mobile_bottom_nav'], 50);
    }



    public function render_mobile_bottom_nav() {
        if (is_admin()) return;
        $o = $this->opt();
        if (($o['mobile_nav_enabled'] ?? 'yes') !== 'yes') return;
        if (!(function_exists('is_account_page') && is_account_page()) && !(function_exists('is_checkout') && is_checkout())) return;
        $this->enqueue_front_assets_once();

        $account_url = $this->myaccount_url();
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $wa = preg_replace('/[^0-9]/', '', (string)($o['whatsapp_number'] ?? '989914164362'));
        if (strpos($wa, '0') === 0) $wa = '98' . substr($wa, 1);
        if (strpos($wa, '98') !== 0) $wa = '98' . $wa;
        $whatsapp_url = 'https://wa.me/' . $wa;
        $cart_count = 0;
        if (function_exists('WC') && WC()->cart) {
            $cart_count = (int) WC()->cart->get_cart_contents_count();
        }
        $is_account = function_exists('is_account_page') && is_account_page();
        $is_cart_like = function_exists('is_cart') && is_cart();
        ?>
        <nav class="kml-mobile-bottom-nav" dir="rtl" aria-label="میانبرهای سوران">
            <a class="kml-mobile-nav-item <?php echo $is_account ? 'is-active' : ''; ?>" href="<?php echo esc_url($account_url); ?>">
                <span class="kml-mobile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg></span>
                <span class="kml-mobile-nav-text"><?php echo esc_html($o['mobile_nav_account_text']); ?></span>
            </a>
            <a class="kml-mobile-nav-item <?php echo $is_cart_like ? 'is-active' : ''; ?>" href="<?php echo esc_url($cart_url); ?>">
                <span class="kml-mobile-nav-icon kml-cart-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2 11h10l3-8H6"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg><?php if ($cart_count > 0): ?><em><?php echo esc_html($cart_count); ?></em><?php endif; ?></span>
                <span class="kml-mobile-nav-text"><?php echo esc_html($o['mobile_nav_cart_text']); ?></span>
            </a>
            <?php if (($o['support_sheet_enabled'] ?? 'yes') === 'yes'): ?>
            <button type="button" class="kml-mobile-nav-item kml-open-support-sheet">
                <span class="kml-mobile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 5h14v11H9l-4 3V5Z"/></svg></span>
                <span class="kml-mobile-nav-text"><?php echo esc_html($o['mobile_nav_support_text']); ?></span>
            </button>
            <?php else: ?>
            <a class="kml-mobile-nav-item" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener nofollow">
                <span class="kml-mobile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 5h14v11H9l-4 3V5Z"/></svg></span>
                <span class="kml-mobile-nav-text"><?php echo esc_html($o['mobile_nav_support_text']); ?></span>
            </a>
            <?php endif; ?>
        </nav>
        <?php if (($o['support_sheet_enabled'] ?? 'yes') === 'yes') $this->render_support_sheet($whatsapp_url); ?>
        <?php
    }

    private function allowed_icon_html() {
        return [
            'img' => [
                'src' => true,
                'alt' => true,
                'class' => true,
                'width' => true,
                'height' => true,
                'loading' => true,
                'decoding' => true,
            ],
            'span' => [
                'class' => true,
                'aria-hidden' => true,
            ],
            'svg' => [
                'class' => true,
                'viewBox' => true,
                'fill' => true,
                'xmlns' => true,
                'aria-hidden' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
            ],
        ];
    }

    private function support_icon_html($service, $fallback = '') {
        $icons = [
            'whatsapp' => 'whatsapp.png',
            'telegram' => 'telegram.png',
            'bale' => 'bale.png',
        ];

        if (isset($icons[$service])) {
            $file = plugin_dir_path(__FILE__) . 'assets/icons/' . $icons[$service];
            if (file_exists($file)) {
                return '<img class="kml-support-logo kml-support-logo-' . esc_attr($service) . '" src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/icons/' . $icons[$service]) . '" alt="" width="32" height="32" loading="lazy" decoding="async">';
            }
        }

        if ($service === 'phone') {
            return '<svg class="kml-support-logo kml-support-logo-phone" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.3-.3.74-.4 1.13-.27 1.24.41 2.57.63 3.96.63.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.85 21 3 13.15 3 3.5c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.39.22 2.72.63 3.96.13.39.03.83-.27 1.13l-2.24 2.2Z"/></svg>';
        }

        return '<span class="kml-support-fallback-icon" aria-hidden="true">' . esc_html($fallback) . '</span>';
    }

    private function render_support_sheet($whatsapp_url) {
        $o = $this->opt();
        $phone = preg_replace('/[^0-9+]/', '', (string)($o['support_phone_number'] ?: $o['whatsapp_number']));
        $items = [];
        if (($o['support_whatsapp_enabled'] ?? 'yes') === 'yes') $items[] = ['wa', 'واتساپ', $this->support_icon_html('whatsapp', '💬'), $whatsapp_url, true];
        if (($o['support_bale_enabled'] ?? 'no') === 'yes' && !empty($o['support_bale_url'])) $items[] = ['bale', 'بله', $this->support_icon_html('bale', '🟦'), $o['support_bale_url'], true];
        if (($o['support_eitaa_enabled'] ?? 'no') === 'yes' && !empty($o['support_eitaa_url'])) $items[] = ['eitaa', 'ایتا', $this->support_icon_html('eitaa', '🟧'), $o['support_eitaa_url'], true];
        if (($o['support_telegram_enabled'] ?? 'no') === 'yes' && !empty($o['support_telegram_url'])) $items[] = ['telegram', 'تلگرام', $this->support_icon_html('telegram', '✈️'), $o['support_telegram_url'], true];
        if (($o['support_rubika_enabled'] ?? 'no') === 'yes' && !empty($o['support_rubika_url'])) $items[] = ['rubika', 'روبیکا', $this->support_icon_html('rubika', '🌈'), $o['support_rubika_url'], true];
        if (($o['support_phone_enabled'] ?? 'yes') === 'yes' && !empty($phone)) $items[] = ['phone', 'تماس', $this->support_icon_html('phone', '☎️'), 'tel:' . $phone, false];
        if (empty($items)) return;
        ?>
        <div class="kml-support-sheet" aria-hidden="true" dir="rtl">
            <div class="kml-support-backdrop kml-close-support-sheet"></div>
            <div class="kml-support-panel" role="dialog" aria-modal="true" aria-label="راه‌های ارتباط با پشتیبانی">
                <button type="button" class="kml-support-close kml-close-support-sheet" aria-label="بستن">×</button>
                <h3>ارتباط با پشتیبانی سوران</h3>
                <p>یکی از راه‌های زیر را انتخاب کنید.</p>
                <div class="kml-support-grid">
                    <?php foreach ($items as $item): ?>
                        <a class="kml-support-item kml-support-<?php echo esc_attr($item[0]); ?>" href="<?php echo esc_url($item[3]); ?>" <?php echo $item[4] ? 'target="_blank" rel="noopener nofollow"' : ''; ?>>
                            <span class="kml-support-icon-wrap"><?php echo wp_kses($item[2], $this->allowed_icon_html()); ?></span>
                            <strong><?php echo esc_html($item[1]); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_reserved_orders_on_account_dashboard() {
        if (($this->opt('reserved_orders_enabled') ?? 'yes') !== 'yes') return;
        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) return;

        // Do not render inside admin or AJAX calls.
        if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) return;

        // Preferred: use the official shortcode from WC Order Reserve plugin.
        if (shortcode_exists('wcor_reserved_orders')) {
            echo '<div class="kml-reserved-orders-dashboard" dir="rtl">';
            echo do_shortcode('[wcor_reserved_orders]');
            echo '</div>';
            return;
        }

        // Fallback: if the reserve endpoint exists but shortcode is not registered, show a safe link only.
        if (function_exists('wc_get_account_endpoint_url')) {
            $has_reservations = function_exists('WC') && WC()->query && isset(WC()->query->get_query_vars()['reserved-orders']);
            $orders_url = wc_get_account_endpoint_url($has_reservations ? 'reserved-orders' : 'orders');
            echo '<section class="kml-reserved-orders-dashboard" dir="rtl" aria-labelledby="kml-reserved-orders-title">';
            echo '<div class="kml-reserved-orders-dashboard__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg></div>';
            echo '<div class="kml-reserved-orders-dashboard__content">';
            echo '<span class="kml-reserved-orders-dashboard__eyebrow">پیگیری خرید</span>';
            echo '<h3 id="kml-reserved-orders-title">' . ($has_reservations ? 'سفارشات در انتظار ارسال' : 'پیگیری سفارش‌های شما') . '</h3>';
            echo '<p>' . ($has_reservations ? 'سفارش‌های رزرو شده و لینک‌شده را در یک نمای اختصاصی بررسی و پیگیری کنید.' : 'جزئیات خرید و وضعیت سفارش‌های خود را یک‌جا ببینید.') . '</p>';
            echo '</div>';
            echo '<a class="button kml-reserved-orders-dashboard__action" href="' . esc_url($orders_url) . '">' . ($has_reservations ? 'مشاهده سفارشات رزرو' : 'مشاهده سفارش‌ها') . ' <span aria-hidden="true">←</span></a>';
            echo '</div>';
        }
    }

    public function activate() {
        $defaults = $this->defaults();
        $current = get_option(self::OPTION, []);
        
        $merged = wp_parse_args($current, $defaults);
        $merged['api_url_template'] = $this->normalize_api_url_template($merged['api_url_template'] ?? $defaults['api_url_template']);
        update_option(self::OPTION, $merged);
        $this->options_cache = $merged;
    }

    private function defaults() {
        return [
            'sms_username' => '',
            'sms_password' => '',
            'sms_from' => '',
            'sms_template' => 'کد ورود شما: {code}',
            'sms_send_mode' => 'simple',
            'melipayamak_body_id' => '',
            'melipayamak_pattern_vars' => '{code}',
            'melipayamak_is_flash' => 'no',
            'otp_ttl' => 120,
            'resend_wait' => 120,
            'code_length' => 5,
            'ip_send_limit' => 10,
            'otp_max_attempts' => 5,
            'api_url_template' => 'https://rest.payamak-panel.com/api/SendSMS',
            'checkout_title' => 'ورود / ثبت‌نام با شماره موبایل',
            'checkout_desc' => 'برای ادامه خرید، شماره موبایل خود را وارد کنید.',
            'brand_word' => 'SORAN',
            'badge_text' => 'ورود سریع و امن',
            'side_mark' => 'SORAN',
            'benefit_pill' => 'ورود آسان بدون دردسر',
            'benefit_title' => 'با شماره موبایل وارد شوید و خریدتان را سریع‌تر ادامه دهید',
            'benefit_desc' => 'در سوران نیازی به حفظ رمز عبور نیست. کد ورود برای شما پیامک می‌شود و حساب کاربری‌تان امن و مرتب در دسترس می‌ماند.',
            'benefit_1_title' => 'ورود سریع',
            'benefit_1_desc' => 'بدون رمز عبور و فقط با شماره موبایل',
            'benefit_2_title' => 'پیگیری سفارش',
            'benefit_2_desc' => 'مشاهده وضعیت سفارش و خریدهای قبلی',
            'benefit_3_title' => 'خرید امن',
            'benefit_3_desc' => 'هماهنگ با ووکامرس و مناسب قالب Woodmart',
            'mobile_nav_enabled' => 'yes',
            'mobile_nav_account_text' => 'حساب کاربری',
            'mobile_nav_cart_text' => 'سبد خرید',
            'mobile_nav_support_text' => 'پشتیبانی',
            'whatsapp_number' => '989914164362',
            'hide_header_footer' => 'yes',
            'account_merge_enabled' => 'yes',
            'reserved_orders_enabled' => 'yes',
            'support_sheet_enabled' => 'yes',
            'support_whatsapp_enabled' => 'yes',
            'support_bale_enabled' => 'no',
            'support_eitaa_enabled' => 'no',
            'support_telegram_enabled' => 'no',
            'support_rubika_enabled' => 'no',
            'support_phone_enabled' => 'yes',
            'support_bale_url' => '',
            'support_eitaa_url' => '',
            'support_telegram_url' => '',
            'support_rubika_url' => '',
            'support_phone_number' => '09914164362',
        ];
    }

    private function normalize_api_url_template($url) {
        $default = $this->defaults()['api_url_template'];
        $url = trim((string) $url);
        if (
            empty($url) ||
            strpos($url, 'https://sunwaysms.com/smsws/') !== false ||
            strpos($url, 'https://sms.sunwaysms.com/smsws/') !== false
        ) {
            $url = $default;
        }
        return untrailingslashit($url);
    }

    private function normalize_send_mode($mode) {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, ['simple', 'pattern'], true) ? $mode : 'simple';
    }

    private function normalize_melipayamak_scalar($value) {
        if (is_array($value)) {
            if (isset($value['Value'])) return $this->normalize_melipayamak_scalar($value['Value']);
            if (isset($value['value'])) return $this->normalize_melipayamak_scalar($value['value']);
            return '';
        }
        if (is_object($value)) return '';
        $value = trim((string) $value);
        if ($value === '') return '';
        if ($value[0] === '<') {
            $xml = @simplexml_load_string($value);
            if ($xml !== false) return trim((string) $xml);
            if (preg_match('/>([^<]+)</', $value, $m)) return trim($m[1]);
        }
        return $value;
    }

    private function melipayamak_error_message($code, $fallback = '') {
        $map = [
            '-110' => 'این متد در حساب شما به ApiKey نیاز دارد و با رمز عبور فعلی قابل استفاده نیست.',
            '-109' => 'برای استفاده از API باید IP مجاز در ملی‌پیامک تنظیم شود.',
            '-108' => 'IP شما به‌دلیل تلاش ناموفق در استفاده از API موقتاً مسدود شده است.',
            '-10' => 'در متغیرهای ارسالی لینک وجود دارد و توسط ملی‌پیامک رد شد.',
            '-7' => 'در شماره فرستنده خطا وجود دارد. خط ارسال را در ملی‌پیامک بررسی کنید.',
            '-6' => 'خطای داخلی در سرویس ملی‌پیامک رخ داده است.',
            '-5' => 'متغیرهای ارسالی با پترن تاییدشده همخوانی ندارند.',
            '-4' => 'کد پترن معتبر نیست یا هنوز تایید نشده است.',
            '-3' => 'خط ارسالی در سامانه ملی‌پیامک تعریف نشده است.',
            '-2' => 'برای این متد، تعداد شماره گیرنده نامعتبر است.',
            '-1' => 'دسترسی حساب شما به این وب‌سرویس غیرفعال است.',
            '0' => 'نام کاربری یا رمز عبور ملی‌پیامک صحیح نیست.',
            '2' => 'اعتبار حساب ملی‌پیامک کافی نیست.',
            '3' => 'محدودیت ارسال روزانه ملی‌پیامک پر شده است.',
            '4' => 'محدودیت در حجم ارسال یا تنظیمات حساب ملی‌پیامک وجود دارد.',
            '5' => 'متن پیامک توسط ملی‌پیامک نامعتبر تشخیص داده شد.',
            '6' => 'سامانه ملی‌پیامک در حال بروزرسانی است.',
            '7' => 'متن ارسالی شامل کلمه فیلترشده است.',
            '10' => 'حساب ملی‌پیامک فعال نیست.',
            '11' => 'پیامک توسط ملی‌پیامک ارسال نشد.',
            '12' => 'مدارک حساب ملی‌پیامک کامل نیست.',
            '13' => 'شماره فرستنده برای ارسال مجاز نیست.',
            '14' => 'شماره فرستنده در دسترس نیست.',
            '15' => 'متن پیامک خالی است.',
            '16' => 'شماره گیرنده‌ای یافت نشد.',
            '17' => 'متن پیامک خالی است.',
            '18' => 'شماره گیرنده نامعتبر است.',
            '19' => 'از محدودیت ساعتی ملی‌پیامک فراتر رفته‌اید.',
            '20' => 'دسترسی ارسال برای این سرویس محدود شده است.',
        ];

        $code = (string) $code;
        if (isset($map[$code])) return $map[$code];
        return $fallback !== '' ? $fallback : ('خطای ملی‌پیامک با کد ' . $code);
    }

    private function melipayamak_pattern_values($code) {
        $template = trim((string) $this->opt('melipayamak_pattern_vars'));
        if ($template === '') $template = '{code}';
        $expanded = str_replace('{code}', $code, $template);
        $parts = preg_split('/(?:\r\n|\r|\n|\|)+/', $expanded);
        $parts = array_values(array_filter(array_map('trim', (array) $parts), static function($item) {
            return $item !== '';
        }));
        return !empty($parts) ? $parts : [$code];
    }

    private function melipayamak_request($endpoint, $payload) {
        $o = $this->opt();
        $url = trailingslashit($o['api_url_template']) . ltrim((string) $endpoint, '/');
        $body = array_merge([
            'username' => $o['sms_username'],
            'password' => $o['sms_password'],
        ], $payload);

        $res = wp_remote_post($url, [
            'timeout' => 20,
            'sslverify' => true,
            'headers' => ['Accept' => 'application/json'],
            'body' => $body,
        ]);

        if (is_wp_error($res)) return $res;

        $http_code = (int) wp_remote_retrieve_response_code($res);
        $raw_body = trim((string) wp_remote_retrieve_body($res));
        if ($http_code < 200 || $http_code >= 300) {
            return new WP_Error('sms_http', 'خطای ارتباط با ملی‌پیامک: ' . $http_code . ' - ' . wp_strip_all_tags($raw_body));
        }

        $json = json_decode($raw_body, true);
        if (is_array($json)) {
            $value = $this->normalize_melipayamak_scalar($json['Value'] ?? $json['value'] ?? '');
            $ret_status = isset($json['RetStatus']) ? (int) $json['RetStatus'] : null;
            $ret_message = trim((string) ($json['StrRetStatus'] ?? $json['strRetStatus'] ?? ''));

            if (is_numeric($value) && (float) $value > 1000) {
                return ['http_code' => $http_code, 'body' => $raw_body, 'data' => $json, 'rec_id' => $value];
            }

            if ($ret_status !== null && $ret_status > 0 && $value !== '' && !is_numeric($value)) {
                return ['http_code' => $http_code, 'body' => $raw_body, 'data' => $json, 'rec_id' => $value];
            }

            $code = is_numeric($value) ? (string) (int) $value : (string) $ret_status;
            return new WP_Error('sms_api', $this->melipayamak_error_message($code, $ret_message !== '' ? $ret_message : $raw_body));
        }

        $value = $this->normalize_melipayamak_scalar($raw_body);
        if (is_numeric($value) && (float) $value > 1000) {
            return ['http_code' => $http_code, 'body' => $raw_body, 'rec_id' => $value];
        }

        if ($value !== '') {
            $code = is_numeric($value) ? (string) (int) $value : $value;
            return new WP_Error('sms_api', $this->melipayamak_error_message($code, $raw_body));
        }

        return new WP_Error('sms_api', 'پاسخ ملی‌پیامک قابل تشخیص نبود.');
    }

    private function myaccount_url() {
        return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    }

    private function checkout_or_account_url() {
        if (function_exists('wc_get_checkout_url') && function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
            return wc_get_checkout_url();
        }
        return $this->myaccount_url();
    }

    private function opt($key = null) {
        if ($this->options_cache === null) {
            $opts = wp_parse_args(get_option(self::OPTION, []), $this->defaults());
            $opts['api_url_template'] = $this->normalize_api_url_template($opts['api_url_template'] ?? '');
            $this->options_cache = $opts;
        }
        return $key !== null ? ($this->options_cache[$key] ?? null) : $this->options_cache;
    }

    public function admin_menu() {
        add_menu_page(
            'لاگین سوران',
            'لاگین سوران',
            'manage_options',
            'kadec-mobile-login',
            [$this, 'settings_page'],
            'dashicons-smartphone',
            56
        );
    }

    public function register_settings() {
        register_setting('kml_settings_group', self::OPTION, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        $old = $this->opt();
        $out = [];
        $out['sms_username'] = sanitize_text_field($input['sms_username'] ?? '');
        $out['sms_password'] = isset($input['sms_password']) && $input['sms_password'] !== '' ? sanitize_text_field($input['sms_password']) : ($old['sms_password'] ?? '');
        $out['sms_from'] = sanitize_text_field($input['sms_from'] ?? '');
        $out['sms_template'] = sanitize_textarea_field($input['sms_template'] ?? 'کد ورود شما: {code}');
        $out['sms_send_mode'] = $this->normalize_send_mode($input['sms_send_mode'] ?? ($old['sms_send_mode'] ?? 'simple'));
        $out['melipayamak_body_id'] = absint($input['melipayamak_body_id'] ?? 0);
        $out['melipayamak_pattern_vars'] = sanitize_textarea_field($input['melipayamak_pattern_vars'] ?? ($old['melipayamak_pattern_vars'] ?? '{code}'));
        $out['melipayamak_is_flash'] = !empty($input['melipayamak_is_flash']) ? 'yes' : 'no';
        $out['api_url_template'] = sanitize_text_field($this->normalize_api_url_template($input['api_url_template'] ?? $old['api_url_template']));
        $out['otp_ttl'] = max(60, absint($input['otp_ttl'] ?? 120));
        $out['resend_wait'] = max(60, absint($input['resend_wait'] ?? 120));
        $out['code_length'] = min(8, max(4, absint($input['code_length'] ?? 5)));
        $out['ip_send_limit'] = min(100, max(3, absint($input['ip_send_limit'] ?? 10)));
        $out['otp_max_attempts'] = min(10, max(3, absint($input['otp_max_attempts'] ?? 5))); 
        $out['checkout_title'] = sanitize_text_field($input['checkout_title'] ?? 'ورود / ثبت‌نام با شماره موبایل');
        $out['checkout_desc'] = sanitize_textarea_field($input['checkout_desc'] ?? 'برای ادامه خرید، شماره موبایل خود را وارد کنید.');
        $out['brand_word'] = sanitize_text_field($input['brand_word'] ?? 'SORAN');
        $out['badge_text'] = sanitize_text_field($input['badge_text'] ?? 'ورود سریع و امن');
        $out['side_mark'] = sanitize_text_field($input['side_mark'] ?? 'SORAN');
        $out['benefit_pill'] = sanitize_text_field($input['benefit_pill'] ?? 'ورود آسان بدون دردسر');
        $out['benefit_title'] = sanitize_text_field($input['benefit_title'] ?? 'با شماره موبایل وارد شوید و خریدتان را سریع‌تر ادامه دهید');
        $out['benefit_desc'] = sanitize_textarea_field($input['benefit_desc'] ?? 'در سوران نیازی به حفظ رمز عبور نیست.');
        $out['benefit_1_title'] = sanitize_text_field($input['benefit_1_title'] ?? 'ورود سریع');
        $out['benefit_1_desc'] = sanitize_text_field($input['benefit_1_desc'] ?? 'بدون رمز عبور و فقط با شماره موبایل');
        $out['benefit_2_title'] = sanitize_text_field($input['benefit_2_title'] ?? 'پیگیری سفارش');
        $out['benefit_2_desc'] = sanitize_text_field($input['benefit_2_desc'] ?? 'مشاهده وضعیت سفارش و خریدهای قبلی');
        $out['benefit_3_title'] = sanitize_text_field($input['benefit_3_title'] ?? 'خرید امن');
        $out['benefit_3_desc'] = sanitize_text_field($input['benefit_3_desc'] ?? 'هماهنگ با ووکامرس و مناسب قالب Woodmart');
        $out['mobile_nav_enabled'] = !empty($input['mobile_nav_enabled']) ? 'yes' : 'no';
        $out['mobile_nav_account_text'] = sanitize_text_field($input['mobile_nav_account_text'] ?? 'حساب کاربری');
        $out['mobile_nav_cart_text'] = sanitize_text_field($input['mobile_nav_cart_text'] ?? 'سبد خرید');
        $out['mobile_nav_support_text'] = sanitize_text_field($input['mobile_nav_support_text'] ?? 'پشتیبانی');
        $out['whatsapp_number'] = preg_replace('/[^0-9+]/', '', (string)($input['whatsapp_number'] ?? '989914164362'));
        $out['hide_header_footer'] = !empty($input['hide_header_footer']) ? 'yes' : 'no';
        $out['account_merge_enabled'] = !empty($input['account_merge_enabled']) ? 'yes' : 'no';
        $out['reserved_orders_enabled'] = !empty($input['reserved_orders_enabled']) ? 'yes' : 'no';
        $out['support_sheet_enabled'] = !empty($input['support_sheet_enabled']) ? 'yes' : 'no';
        $out['support_whatsapp_enabled'] = !empty($input['support_whatsapp_enabled']) ? 'yes' : 'no';
        $out['support_bale_enabled'] = !empty($input['support_bale_enabled']) ? 'yes' : 'no';
        $out['support_eitaa_enabled'] = !empty($input['support_eitaa_enabled']) ? 'yes' : 'no';
        $out['support_telegram_enabled'] = !empty($input['support_telegram_enabled']) ? 'yes' : 'no';
        $out['support_rubika_enabled'] = !empty($input['support_rubika_enabled']) ? 'yes' : 'no';
        $out['support_phone_enabled'] = !empty($input['support_phone_enabled']) ? 'yes' : 'no';
        $out['support_bale_url'] = esc_url_raw($input['support_bale_url'] ?? '');
        $out['support_eitaa_url'] = esc_url_raw($input['support_eitaa_url'] ?? '');
        $out['support_telegram_url'] = esc_url_raw($input['support_telegram_url'] ?? '');
        $out['support_rubika_url'] = esc_url_raw($input['support_rubika_url'] ?? '');
        $out['support_phone_number'] = preg_replace('/[^0-9+]/', '', (string)($input['support_phone_number'] ?? ''));
        $out = wp_parse_args($out, $this->defaults());
        $this->options_cache = $out;
        return $out;
    }

    private function should_load_front_assets() {
        if (is_admin()) return false;
        if (function_exists('is_checkout') && is_checkout()) return true;
        if (function_exists('is_account_page') && is_account_page()) return true;
        global $post;
        return $post && isset($post->post_content) && (
            has_shortcode($post->post_content, 'soran_mobile_login') ||
            has_shortcode($post->post_content, 'kadec_mobile_login')
        );
    }

    /** Return whether the current page already renders the dedicated login shortcode. */
    private function current_page_has_login_shortcode() {
        global $post;
        return $post instanceof WP_Post && isset($post->post_content) && (
            has_shortcode($post->post_content, 'soran_mobile_login') ||
            has_shortcode($post->post_content, 'kadec_mobile_login')
        );
    }

    private function enqueue_front_assets_once() {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        wp_enqueue_style('kml-front', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], self::VERSION);
        wp_enqueue_script('kml-front', plugin_dir_url(__FILE__) . 'assets/js/otp.js', ['jquery'], self::VERSION, true);
        wp_localize_script('kml-front', 'KML', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE),
            'resend_wait' => (int) $this->opt('resend_wait'),
            'code_length' => (int) $this->opt('code_length'),
        ]);
    }

    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_kadec-mobile-login') return;
        wp_enqueue_style('kml-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], self::VERSION);
        wp_enqueue_script('kml-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], self::VERSION, true);
        wp_localize_script('kml-admin', 'KML_ADMIN', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE),
        ]);
    }

    public function front_assets() {
        // Load assets only on pages where the login UI/nav may be rendered.
        // This keeps product/category/home pages lighter.
        if (!$this->should_load_front_assets()) return;
        $this->enqueue_front_assets_once();
    }

    public function filter_body_class($classes) {
        if (is_admin()) return $classes;

        if (!is_user_logged_in() && $this->current_page_has_login_shortcode()) {
            $classes[] = 'kml-has-login-shortcode';
        }

        if (($this->opt('hide_header_footer') ?? 'yes') !== 'yes') return $classes;

        $is_hidden_login_shell =
            !is_user_logged_in() &&
            (
                (function_exists('is_account_page') && is_account_page()) ||
                (function_exists('is_checkout') && is_checkout())
            );

        if ($is_hidden_login_shell) $classes[] = 'kml-hide-header-footer';
        return $classes;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $o = $this->opt();
        ?>
        <div class="wrap kml-admin-wrap" dir="rtl">
            <h1>لاگین سوران</h1>
            <p>پلاگین اختصاصی ورود/ثبت نام تهران ولت</p>
            <?php settings_errors(); ?>
            <form method="post" action="options.php" novalidate>
                <?php settings_fields('kml_settings_group'); ?>
                <input type="hidden" name="kml_active_tab" value="">
                <table class="form-table" role="presentation">
                    <tr><th>نام کاربری ملی‌پیامک</th><td><input type="text" name="<?php echo self::OPTION; ?>[sms_username]" value="<?php echo esc_attr($o['sms_username']); ?>" class="regular-text"></td></tr>
                    <tr><th>رمز عبور API</th><td><input type="password" name="<?php echo self::OPTION; ?>[sms_password]" value="" placeholder="<?php echo !empty($o['sms_password']) ? 'رمز ذخیره شده است؛ برای تغییر، رمز جدید را وارد کنید' : 'رمز API یا رمز وب‌سرویس را وارد کنید'; ?>" class="regular-text"><p class="description">برای امنیت، رمز در فرم نمایش داده نمی‌شود. اگر خالی بماند، رمز قبلی حفظ می‌شود.</p></td></tr>
                    <tr><th>نوع ارسال OTP</th><td><select name="<?php echo self::OPTION; ?>[sms_send_mode]"><option value="simple" <?php selected($o['sms_send_mode'] ?? 'simple', 'simple'); ?>>ارسال عادی</option><option value="pattern" <?php selected($o['sms_send_mode'] ?? 'simple', 'pattern'); ?>>پترن / خدماتی</option></select><p class="description">اگر از خط خدماتی اشتراکی ملی‌پیامک استفاده می‌کنید، حالت پترن را انتخاب کنید.</p></td></tr>
                    <tr><th>شماره / خط ارسال‌کننده</th><td><input type="text" name="<?php echo self::OPTION; ?>[sms_from]" value="<?php echo esc_attr($o['sms_from']); ?>" class="regular-text ltr"><p class="description">این فیلد فقط در حالت ارسال عادی استفاده می‌شود.</p></td></tr>
                    <tr><th>متن پیامک عادی</th><td><textarea name="<?php echo self::OPTION; ?>[sms_template]" rows="4" class="large-text"><?php echo esc_textarea($o['sms_template']); ?></textarea><p class="description">در حالت عادی حتماً از <code>{code}</code> استفاده کن. برای پیشنهاد/خواندن خودکار در موبایل، پیشنهاد: <code>کد ورود سوران: {code}

@<?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?> #{code}</code></p></td></tr>
                    <tr><th>REST API ملی‌پیامک</th><td><input type="text" name="<?php echo self::OPTION; ?>[api_url_template]" value="<?php echo esc_attr($o['api_url_template']); ?>" class="large-text ltr"><p class="description">پیش‌فرض: <code>https://rest.payamak-panel.com/api/SendSMS</code>. افزونه برای ارسال عادی از endpoint <code>SendSMS</code> و برای پترن از <code>BaseServiceNumber</code> استفاده می‌کند.</p></td></tr>
                    <tr><th>کد پترن</th><td><input type="number" name="<?php echo self::OPTION; ?>[melipayamak_body_id]" value="<?php echo esc_attr(($body_id = absint($o['melipayamak_body_id'] ?? 0)) ? $body_id : ''); ?>" class="regular-text ltr" min="1"><p class="description">فقط در حالت پترن لازم است. همان <code>bodyId</code> تاییدشده در ملی‌پیامک.</p></td></tr>
                    <tr><th>متغیرهای پترن</th><td><input type="text" name="<?php echo self::OPTION; ?>[melipayamak_pattern_vars]" value="<?php echo esc_attr($o['melipayamak_pattern_vars'] ?? '{code}'); ?>" class="large-text ltr"><p class="description">مقادیر را با <code>|</code> جدا کن. مثال: <code>{code}</code> یا <code>{code}|سوران</code>. مقدار <code>{code}</code> با کد OTP جایگزین می‌شود.</p></td></tr>
                    <tr><th>ارسال فلش</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[melipayamak_is_flash]" value="1" <?php checked($o['melipayamak_is_flash'] ?? 'no', 'yes'); ?>> فقط برای حالت عادی به‌صورت Flash SMS ارسال شود</label></td></tr>
                    <tr><th>اعتبار کد</th><td><input type="number" name="<?php echo self::OPTION; ?>[otp_ttl]" value="<?php echo esc_attr($o['otp_ttl']); ?>" min="60"> ثانیه</td></tr>
                    <tr><th>فاصله ارسال مجدد</th><td><input type="number" name="<?php echo self::OPTION; ?>[resend_wait]" value="<?php echo esc_attr($o['resend_wait']); ?>" min="60"> ثانیه</td></tr>
                    <tr><th>تعداد رقم کد</th><td><input type="number" name="<?php echo self::OPTION; ?>[code_length]" value="<?php echo esc_attr($o['code_length']); ?>" min="4" max="8"></td></tr>
                    <tr><th>حداکثر ارسال از هر IP در ساعت</th><td><input type="number" name="<?php echo self::OPTION; ?>[ip_send_limit]" value="<?php echo esc_attr($o['ip_send_limit']); ?>" min="3" max="100"></td></tr>
                    <tr><th>حداکثر تلاش اشتباه کد</th><td><input type="number" name="<?php echo self::OPTION; ?>[otp_max_attempts]" value="<?php echo esc_attr($o['otp_max_attempts']); ?>" min="3" max="10"></td></tr>
                    <tr><th>عنوان فرم checkout</th><td><input type="text" name="<?php echo self::OPTION; ?>[checkout_title]" value="<?php echo esc_attr($o['checkout_title']); ?>" class="regular-text"></td></tr>
                    <tr><th>توضیح فرم checkout</th><td><textarea name="<?php echo self::OPTION; ?>[checkout_desc]" rows="2" class="large-text"><?php echo esc_textarea($o['checkout_desc']); ?></textarea></td></tr>
                    <tr><th colspan="2"><h2>🎨 متن‌ها و ظاهر فرم</h2></th></tr>
                    <tr><th>کلمه برند روی فرم</th><td><input type="text" name="<?php echo self::OPTION; ?>[brand_word]" value="<?php echo esc_attr($o['brand_word']); ?>" class="regular-text"></td></tr>
                    <tr><th>متن بج ورود</th><td><input type="text" name="<?php echo self::OPTION; ?>[badge_text]" value="<?php echo esc_attr($o['badge_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>متن کناری انگلیسی</th><td><input type="text" name="<?php echo self::OPTION; ?>[side_mark]" value="<?php echo esc_attr($o['side_mark']); ?>" class="regular-text ltr"></td></tr>
                    <tr><th>برچسب مزایا</th><td><input type="text" name="<?php echo self::OPTION; ?>[benefit_pill]" value="<?php echo esc_attr($o['benefit_pill']); ?>" class="regular-text"></td></tr>
                    <tr><th>عنوان کارت مزایا</th><td><input type="text" name="<?php echo self::OPTION; ?>[benefit_title]" value="<?php echo esc_attr($o['benefit_title']); ?>" class="large-text"></td></tr>
                    <tr><th>توضیح کارت مزایا</th><td><textarea name="<?php echo self::OPTION; ?>[benefit_desc]" rows="3" class="large-text"><?php echo esc_textarea($o['benefit_desc']); ?></textarea></td></tr>
                    <tr><th>مزیت ۱</th><td><input type="text" name="<?php echo self::OPTION; ?>[benefit_1_title]" value="<?php echo esc_attr($o['benefit_1_title']); ?>" class="regular-text" placeholder="عنوان"><br><input type="text" name="<?php echo self::OPTION; ?>[benefit_1_desc]" value="<?php echo esc_attr($o['benefit_1_desc']); ?>" class="large-text" placeholder="توضیح"></td></tr>
                    <tr><th>مزیت ۲</th><td><input type="text" name="<?php echo self::OPTION; ?>[benefit_2_title]" value="<?php echo esc_attr($o['benefit_2_title']); ?>" class="regular-text" placeholder="عنوان"><br><input type="text" name="<?php echo self::OPTION; ?>[benefit_2_desc]" value="<?php echo esc_attr($o['benefit_2_desc']); ?>" class="large-text" placeholder="توضیح"></td></tr>
                    <tr><th>مزیت ۳</th><td><input type="text" name="<?php echo self::OPTION; ?>[benefit_3_title]" value="<?php echo esc_attr($o['benefit_3_title']); ?>" class="regular-text" placeholder="عنوان"><br><input type="text" name="<?php echo self::OPTION; ?>[benefit_3_desc]" value="<?php echo esc_attr($o['benefit_3_desc']); ?>" class="large-text" placeholder="توضیح"></td></tr>
                    <tr><th colspan="2"><h2>📱 نوار پایین موبایل</h2></th></tr>
                    <tr><th>نمایش نوار پایین</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[mobile_nav_enabled]" value="1" <?php checked($o['mobile_nav_enabled'], 'yes'); ?>> فعال باشد</label></td></tr>
                    <tr><th>متن حساب کاربری</th><td><input type="text" name="<?php echo self::OPTION; ?>[mobile_nav_account_text]" value="<?php echo esc_attr($o['mobile_nav_account_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>متن سبد خرید</th><td><input type="text" name="<?php echo self::OPTION; ?>[mobile_nav_cart_text]" value="<?php echo esc_attr($o['mobile_nav_cart_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>متن پشتیبانی</th><td><input type="text" name="<?php echo self::OPTION; ?>[mobile_nav_support_text]" value="<?php echo esc_attr($o['mobile_nav_support_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>شماره واتساپ</th><td><input type="text" name="<?php echo self::OPTION; ?>[whatsapp_number]" value="<?php echo esc_attr($o['whatsapp_number']); ?>" class="regular-text ltr"><p class="description">مثال: 09914164362 یا 989914164362</p></td></tr>
                    <tr><th>تمام‌صفحه کردن ورود</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[hide_header_footer]" value="1" <?php checked($o['hide_header_footer'], 'yes'); ?>> هدر/فوتر در صفحات ورود، حساب کاربری و checkout مخفی شود</label></td></tr>
                    <tr><th colspan="2"><h2>🔗 حساب‌های قدیمی و رزرو سفارش</h2></th></tr>
                    <tr><th>یکی‌سازی حساب قدیمی</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[account_merge_enabled]" value="1" <?php checked($o['account_merge_enabled'], 'yes'); ?>> اگر کاربر موبایلی جدید، ایمیل حساب قدیمی خود را وارد کرد، در صورت امن بودن حساب‌ها یکی شوند</label><p class="description">برای نقش‌های مدیریتی انجام نمی‌شود.</p></td></tr>
                    <tr><th>نمایش سفارش‌های رزرو</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[reserved_orders_enabled]" value="1" <?php checked($o['reserved_orders_enabled'], 'yes'); ?>> اطلاعات افزونه رزرو سفارش در داشبورد حساب کاربری نمایش داده شود</label></td></tr>
                    <tr><th colspan="2"><h2>💬 راه‌های ارتباط با پشتیبانی</h2></th></tr>
                    <tr><th>Bottom Sheet پشتیبانی</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_sheet_enabled]" value="1" <?php checked($o['support_sheet_enabled'], 'yes'); ?>> دکمه پشتیبانی نوار موبایل، لیست پیام‌رسان‌ها را باز کند</label></td></tr>
                    <tr><th>واتساپ</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_whatsapp_enabled]" value="1" <?php checked($o['support_whatsapp_enabled'], 'yes'); ?>> فعال</label></td></tr>
                    <tr><th>بله</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_bale_enabled]" value="1" <?php checked($o['support_bale_enabled'], 'yes'); ?>> فعال</label><br><input type="url" name="<?php echo self::OPTION; ?>[support_bale_url]" value="<?php echo esc_attr($o['support_bale_url']); ?>" class="large-text ltr" placeholder="https://ble.ir/..."></td></tr>
                    <tr><th>ایتا</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_eitaa_enabled]" value="1" <?php checked($o['support_eitaa_enabled'], 'yes'); ?>> فعال</label><br><input type="url" name="<?php echo self::OPTION; ?>[support_eitaa_url]" value="<?php echo esc_attr($o['support_eitaa_url']); ?>" class="large-text ltr" placeholder="https://eitaa.com/..."></td></tr>
                    <tr><th>تلگرام</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_telegram_enabled]" value="1" <?php checked($o['support_telegram_enabled'], 'yes'); ?>> فعال</label><br><input type="url" name="<?php echo self::OPTION; ?>[support_telegram_url]" value="<?php echo esc_attr($o['support_telegram_url']); ?>" class="large-text ltr" placeholder="https://t.me/..."></td></tr>
                    <tr><th>روبیکا</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_rubika_enabled]" value="1" <?php checked($o['support_rubika_enabled'], 'yes'); ?>> فعال</label><br><input type="url" name="<?php echo self::OPTION; ?>[support_rubika_url]" value="<?php echo esc_attr($o['support_rubika_url']); ?>" class="large-text ltr" placeholder="https://rubika.ir/..."></td></tr>
                    <tr><th>تماس تلفنی</th><td><label><input type="checkbox" name="<?php echo self::OPTION; ?>[support_phone_enabled]" value="1" <?php checked($o['support_phone_enabled'], 'yes'); ?>> فعال</label><br><input type="text" name="<?php echo self::OPTION; ?>[support_phone_number]" value="<?php echo esc_attr($o['support_phone_number']); ?>" class="regular-text ltr" placeholder="09914164362"></td></tr>
                </table>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
            <hr>
            <h2>تست ارسال پیامک</h2>
            <p>بعد از ذخیره تنظیمات، یک شماره موبایل وارد کن و ارسال تست را بزن.</p>
            <input type="text" id="kml-test-mobile" class="regular-text" placeholder="09392908600">
            <button class="button button-primary" id="kml-test-send">ارسال تست</button>
            <div id="kml-test-result"></div>
            <hr>
            <h2>شورت‌کد</h2>
            <code>[soran_mobile_login]</code>
        </div>
        <?php
    }

    private function normalize_mobile($mobile) {
        $mobile = preg_replace('/[^0-9+]/', '', trim((string)$mobile));
        if (strpos($mobile, '+98') === 0) $mobile = '0' . substr($mobile, 3);
        if (strpos($mobile, '98') === 0 && strlen($mobile) === 12) $mobile = '0' . substr($mobile, 2);
        return $mobile;
    }

    private function valid_mobile($mobile) {
        return (bool) preg_match('/^09[0-9]{9}$/', $mobile);
    }


    private function normalize_identifier($identifier) {
        return trim(sanitize_text_field((string) $identifier));
    }

    private function is_email_identifier($identifier) {
        return is_email($identifier);
    }

    private function mask_mobile($mobile) {
        $mobile = $this->normalize_mobile($mobile);
        if (!$this->valid_mobile($mobile)) return 'شماره ثبت‌شده';
        return substr($mobile, 0, 4) . '***' . substr($mobile, -4);
    }

    private function find_user_by_login_or_email($identifier) {
        $identifier = $this->normalize_identifier($identifier);
        if ($identifier === '') return false;

        if ($this->is_email_identifier($identifier)) {
            $user = get_user_by('email', $identifier);
            if ($user) return $user;
        }

        $user = get_user_by('login', $identifier);
        if ($user) return $user;

        return false;
    }

    private function get_user_mobile($user_id) {
        $candidates = [
            get_user_meta($user_id, 'kadec_mobile', true),
            get_user_meta($user_id, 'billing_phone', true),
            get_user_meta($user_id, 'shipping_phone', true),
        ];

        $user = get_user_by('id', $user_id);
        if ($user) {
            $candidates[] = $user->user_login;
        }

        foreach ($candidates as $candidate) {
            $mobile = $this->normalize_mobile($candidate);
            if ($this->valid_mobile($mobile)) return $mobile;
        }

        return '';
    }

    private function resolve_login_identifier($identifier) {
        $identifier = $this->normalize_identifier($identifier);
        $mobile = $this->normalize_mobile($identifier);

        // Normal mobile login: current behavior.
        if ($this->valid_mobile($mobile)) {
            return [
                'ok' => true,
                'mobile' => $mobile,
                'user' => $this->find_user_by_mobile($mobile),
                'create_allowed' => true,
                'identifier_type' => 'mobile',
                'message_suffix' => '',
            ];
        }

        // Legacy login: email or username. We do NOT create a new user in this path.
        // We only send OTP to the mobile already stored on that old account.
        $user = $this->find_user_by_login_or_email($identifier);
        if (!$user) {
            return ['ok' => false, 'code' => 'need_mobile_new', 'message' => 'برای ساخت حساب جدید و ادامه خرید، لطفاً شماره موبایل خود را وارد کنید.'];
        }
        if ($this->is_sensitive_user($user)) {
            return ['ok' => false, 'message' => 'ورود پیامکی برای حساب‌های مدیریتی غیرفعال است. لطفاً از ورود اصلی وردپرس استفاده کنید.'];
        }

        $user_mobile = $this->get_user_mobile($user->ID);
        if (!$this->valid_mobile($user_mobile)) {
            return ['ok' => false, 'code' => 'need_mobile_new', 'message' => 'این حساب قدیمی شماره موبایل ثبت‌شده ندارد. برای ادامه خرید، لطفاً شماره موبایل خود را وارد کنید تا حساب جدید موبایلی ساخته شود.'];
        }

        return [
            'ok' => true,
            'mobile' => $user_mobile,
            'user' => $user,
            'create_allowed' => false,
            'identifier_type' => $this->is_email_identifier($identifier) ? 'email' : 'username',
            'message_suffix' => ' کد به شماره ' . $this->mask_mobile($user_mobile) . ' ارسال شد.',
        ];
    }

    private function generate_code() {
        $len = (int) $this->opt('code_length');
        $min = (int) pow(10, $len - 1);
        $max = (int) pow(10, $len) - 1;
        return (string) wp_rand($min, $max);
    }

    private function transient_key($mobile) { return 'kml_otp_' . md5($mobile); }
    private function wait_key($mobile) { return 'kml_wait_' . md5($mobile); }
    private function ip_key($ip) { return 'kml_ip_' . md5($ip); }
    private function attempt_key($mobile) { return 'kml_try_' . md5($mobile); }
    private function profile_token_key($user_id) { return 'kml_profile_token_' . absint($user_id); }

    private function client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return preg_replace('/[^0-9a-fA-F:\.:]/', '', $ip);
    }

    private function is_sensitive_user($user) {
        if (!$user || empty($user->roles)) return false;
        $blocked = ['administrator', 'editor', 'shop_manager'];
        return (bool) array_intersect($blocked, (array) $user->roles);
    }

    private function profile_incomplete($user_id, $created_now = false) {
        if ($created_now) return true;
        $first = trim((string) get_user_meta($user_id, 'billing_first_name', true));
        $last  = trim((string) get_user_meta($user_id, 'billing_last_name', true));
        if ($first === '' || $last === '') return true;
        if (get_user_meta($user_id, 'kml_profile_completed', true) !== 'yes') return true;
        return false;
    }

    private function send_sms($mobile, $code) {
        $o = $this->opt();
        if (empty($o['sms_username']) || empty($o['sms_password']) || empty($o['api_url_template'])) {
            return new WP_Error('sms_settings_missing', 'تنظیمات ملی‌پیامک کامل نیست. نام کاربری، رمز API و آدرس REST API را ذخیره کنید.');
        }

        $mode = $this->normalize_send_mode($o['sms_send_mode'] ?? 'simple');
        if ($mode === 'pattern') {
            if (empty($o['melipayamak_body_id'])) {
                return new WP_Error('sms_settings_missing', 'برای حالت پترن باید `bodyId` ملی‌پیامک را ثبت کنید.');
            }

            return $this->melipayamak_request('BaseServiceNumber', [
                'to' => $mobile,
                'bodyId' => (int) $o['melipayamak_body_id'],
                'text' => $this->melipayamak_pattern_values($code),
            ]);
        }

        if (empty($o['sms_from'])) {
            return new WP_Error('sms_settings_missing', 'در حالت ارسال عادی باید شماره فرستنده ملی‌پیامک را ثبت کنید.');
        }

        $message = str_replace('{code}', $code, $o['sms_template']);
        return $this->melipayamak_request('SendSMS', [
            'to' => $mobile,
            'from' => $o['sms_from'],
            'text' => $message,
            'isFlash' => ($o['melipayamak_is_flash'] ?? 'no') === 'yes' ? 'true' : 'false',
        ]);
    }

    public function ajax_send_otp() {
        check_ajax_referer(self::NONCE, 'nonce');
        $identifier = $this->normalize_identifier($_POST['mobile'] ?? $_POST['identifier'] ?? '');
        $resolved = $this->resolve_login_identifier($identifier);
        if (empty($resolved['ok'])) {
            wp_send_json_error(['message' => $resolved['message'] ?? 'اطلاعات وارد شده معتبر نیست.', 'code' => $resolved['code'] ?? '']);
        }
        $mobile = $resolved['mobile'];

        $ip = $this->client_ip();
        $ip_key = $this->ip_key($ip);
        $ip_count = (int) get_transient($ip_key);
        if ($ip_count >= (int) $this->opt('ip_send_limit')) {
            wp_send_json_error(['message' => 'تعداد درخواست‌های ارسال کد زیاد است. لطفاً کمی بعد دوباره تلاش کنید.']);
        }

        if (get_transient($this->wait_key($mobile))) wp_send_json_error(['message' => 'ارسال مجدد هر ۲ دقیقه یک‌بار مجاز است.']);
        $code = $this->generate_code();
        set_transient($this->transient_key($mobile), wp_hash_password($code), (int)$this->opt('otp_ttl'));
        set_transient($this->wait_key($mobile), 1, (int)$this->opt('resend_wait'));
        set_transient($this->attempt_key($mobile), 0, (int)$this->opt('otp_ttl'));
        set_transient($ip_key, $ip_count + 1, HOUR_IN_SECONDS);

        $sent = $this->send_sms($mobile, $code);
        if (is_wp_error($sent)) {
            delete_transient($this->wait_key($mobile));
            wp_send_json_error(['message' => $sent->get_error_message()]);
        }
        wp_send_json_success(['message' => 'کد تایید ارسال شد.' . ($resolved['message_suffix'] ?? '')]);
    }

    private function find_user_by_mobile($mobile) {
        $user = get_user_by('login', $mobile);
        if ($user) return $user;

        $users = get_users([
            'number' => 1,
            'fields' => 'all',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'billing_phone',
                    'value' => $mobile,
                    'compare' => '='
                ],
                [
                    'key' => 'kadec_mobile',
                    'value' => $mobile,
                    'compare' => '='
                ]
            ]
        ]);

        return !empty($users) ? $users[0] : false;
    }

    public function ajax_verify_otp() {
        check_ajax_referer(self::NONCE, 'nonce');
        $identifier = $this->normalize_identifier($_POST['mobile'] ?? $_POST['identifier'] ?? '');
        $resolved = $this->resolve_login_identifier($identifier);
        if (empty($resolved['ok'])) {
            wp_send_json_error(['message' => $resolved['message'] ?? 'اطلاعات وارد شده معتبر نیست.', 'code' => $resolved['code'] ?? '']);
        }
        $mobile = $resolved['mobile'];
        $code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');
        if (!$this->valid_mobile($mobile) || empty($code)) wp_send_json_error(['message' => 'اطلاعات وارد شده معتبر نیست.']);

        $attempt_key = $this->attempt_key($mobile);
        $attempts = (int) get_transient($attempt_key);
        if ($attempts >= (int) $this->opt('otp_max_attempts')) {
            delete_transient($this->transient_key($mobile));
            wp_send_json_error(['message' => 'تعداد تلاش ناموفق زیاد است. لطفاً کد جدید دریافت کنید.']);
        }

        $hash = get_transient($this->transient_key($mobile));
        if (!$hash || !wp_check_password($code, $hash)) {
            set_transient($attempt_key, $attempts + 1, (int)$this->opt('otp_ttl'));
            wp_send_json_error(['message' => 'کد تایید اشتباه یا منقضی شده است.']);
        }
        delete_transient($this->transient_key($mobile));
        delete_transient($attempt_key);

        $created_now = false;
        $user = !empty($resolved['user']) ? $resolved['user'] : $this->find_user_by_mobile($mobile);
        if ($user && $this->is_sensitive_user($user)) {
            wp_send_json_error(['message' => 'ورود پیامکی برای حساب‌های مدیریتی غیرفعال است. لطفاً از ورود اصلی وردپرس استفاده کنید.']);
        }

        if (!$user) {
            if (empty($resolved['create_allowed'])) {
                wp_send_json_error(['message' => 'برای ساخت حساب جدید، لطفاً شماره موبایل خود را وارد کنید و کد پیامکی را تایید کنید.', 'code' => 'need_mobile_new']);
            }
            $user_id = wp_create_user($mobile, wp_generate_password(24, true), $mobile . '@mobile.local');
            if (is_wp_error($user_id)) wp_send_json_error(['message' => $user_id->get_error_message()]);
            update_user_meta($user_id, 'billing_phone', $mobile);
            update_user_meta($user_id, 'kadec_mobile', $mobile);
            update_user_meta($user_id, 'nickname', $mobile);
            $user = get_user_by('id', $user_id);
            $created_now = true;
        } else {
            update_user_meta($user->ID, 'billing_phone', $mobile);
            update_user_meta($user->ID, 'kadec_mobile', $mobile);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        $fallback = $this->checkout_or_account_url();
        $redirect = wp_validate_redirect($_POST['redirect'] ?? '', $fallback);

        $needs_profile = $this->profile_incomplete($user->ID, $created_now);
        $profile_token = '';
        if ($needs_profile) {
            $profile_token = wp_generate_password(32, false, false);
            set_transient($this->profile_token_key($user->ID), wp_hash_password($profile_token), 10 * MINUTE_IN_SECONDS);
        }
        wp_send_json_success([
            'message' => $needs_profile ? 'ورود انجام شد. لطفاً مشخصات اولیه را تکمیل کنید.' : 'ورود با موفقیت انجام شد.',
            'redirect' => $redirect,
            'needs_profile' => $needs_profile,
            // After wp_set_auth_cookie(), the original guest nonce can become invalid on cached pages.
            // Return both a fresh nonce and a one-time profile token for the authenticated customer.
            'profile_nonce' => wp_create_nonce(self::NONCE),
            'profile_token' => $profile_token,
        ]);
    }


    private function user_has_orders($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) return false;
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders([
                'customer_id' => $user_id,
                'limit' => 1,
                'return' => 'ids',
                'status' => array_keys(wc_get_order_statuses()),
            ]);
            return !empty($orders);
        }
        return false;
    }

    private function maybe_merge_current_mobile_user_with_email($current_user_id, $email, $first, $last) {
        if (($this->opt('account_merge_enabled') ?? 'yes') !== 'yes') return ['merged' => false, 'blocked' => false, 'user_id' => $current_user_id, 'message' => ''];
        $email = sanitize_email($email);
        if (!$email || !is_email($email)) return ['merged' => false, 'blocked' => false, 'user_id' => $current_user_id, 'message' => ''];
        $legacy = get_user_by('email', $email);
        if (!$legacy || (int)$legacy->ID === (int)$current_user_id) return ['merged' => false, 'blocked' => false, 'user_id' => $current_user_id, 'message' => ''];
        if ($this->is_sensitive_user($legacy)) return ['merged' => false, 'blocked' => true, 'user_id' => $current_user_id, 'message' => 'این ایمیل مربوط به حساب مدیریتی است و قابل اتصال خودکار نیست.'];

        $mobile = $this->get_user_mobile($current_user_id);
        if (!$this->valid_mobile($mobile)) return ['merged' => false, 'blocked' => false, 'user_id' => $current_user_id, 'message' => ''];

        $legacy_mobile = $this->get_user_mobile($legacy->ID);
        if (!$this->valid_mobile($legacy_mobile) || $legacy_mobile !== $mobile) {
            return [
                'merged' => false,
                'blocked' => true,
                'user_id' => $current_user_id,
                'message' => 'این ایمیل روی حساب دیگری ثبت شده است و چون شماره موبایل آن حساب با شماره تاییدشده شما یکی نیست، اتصال خودکار مجاز نیست.',
            ];
        }

        update_user_meta($legacy->ID, 'billing_phone', $mobile);
        update_user_meta($legacy->ID, 'kadec_mobile', $mobile);
        update_user_meta($legacy->ID, 'billing_first_name', $first);
        update_user_meta($legacy->ID, 'billing_last_name', $last);
        update_user_meta($legacy->ID, 'shipping_first_name', $first);
        update_user_meta($legacy->ID, 'shipping_last_name', $last);
        update_user_meta($legacy->ID, 'kml_profile_completed', 'yes');
        $updated = wp_update_user([
            'ID' => $legacy->ID,
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => trim($first . ' ' . $last),
        ]);
        if (is_wp_error($updated)) {
            return ['merged' => false, 'blocked' => true, 'user_id' => $current_user_id, 'message' => $updated->get_error_message()];
        }

        update_user_meta($current_user_id, 'kml_merged_to_user', $legacy->ID);
        delete_user_meta($current_user_id, 'billing_phone');
        delete_user_meta($current_user_id, 'kadec_mobile');

        if (!$this->user_has_orders($current_user_id) && get_user_meta($current_user_id, 'kml_profile_completed', true) !== 'yes') {
            if (!function_exists('wp_delete_user')) require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($current_user_id);
        }

        wp_set_current_user($legacy->ID);
        wp_set_auth_cookie($legacy->ID, true);
        do_action('wp_login', $legacy->user_login, $legacy);
        return ['merged' => true, 'user_id' => $legacy->ID, 'message' => 'حساب موبایلی شما به حساب قدیمی‌تان متصل شد.'];
    }

    public function ajax_complete_profile() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'ابتدا وارد شوید.']);

        $user_id = get_current_user_id();
        // Cached checkout pages can keep a guest nonce after OTP login. Accept either a valid nonce
        // or the short-lived one-time profile token returned immediately after successful OTP login.
        $nonce_ok = check_ajax_referer(self::NONCE, 'nonce', false);
        $token_ok = false;
        $profile_token = sanitize_text_field($_POST['profile_token'] ?? '');
        if (!$nonce_ok && $profile_token !== '') {
            $stored_hash = get_transient($this->profile_token_key($user_id));
            $token_ok = $stored_hash && wp_check_password($profile_token, $stored_hash);
        }
        if (!$nonce_ok && !$token_ok) {
            wp_send_json_error(['message' => 'اعتبار فرم منقضی شده است. لطفاً دوباره کد ورود را دریافت کنید.']);
        }

        $first = sanitize_text_field($_POST['first_name'] ?? '');
        $last  = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if ($first === '' || $last === '') {
            wp_send_json_error(['message' => 'نام و نام خانوادگی الزامی است.']);
        }
        if ($email !== '' && !is_email($email)) {
            wp_send_json_error(['message' => 'ایمیل وارد شده معتبر نیست.']);
        }

        $merge = $this->maybe_merge_current_mobile_user_with_email($user_id, $email, $first, $last);
        if (!empty($merge['merged'])) {
            $fallback = $this->checkout_or_account_url();
            $redirect = wp_validate_redirect($_POST['redirect'] ?? '', $fallback);
            wp_send_json_success(['message' => $merge['message'], 'redirect' => $redirect]);
        }
        if (!empty($merge['blocked'])) {
            wp_send_json_error(['message' => $merge['message'] ?: 'امکان اتصال خودکار این حساب وجود ندارد.']);
        }

        $updated = wp_update_user([
            'ID' => $user_id,
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => trim($first . ' ' . $last),
        ]);
        if (is_wp_error($updated)) {
            wp_send_json_error(['message' => $updated->get_error_message()]);
        }
        update_user_meta($user_id, 'billing_first_name', $first);
        update_user_meta($user_id, 'billing_last_name', $last);
        update_user_meta($user_id, 'shipping_first_name', $first);
        update_user_meta($user_id, 'shipping_last_name', $last);
        if ($email !== '') {
            $email_updated = wp_update_user(['ID' => $user_id, 'user_email' => $email]);
            if (is_wp_error($email_updated)) {
                wp_send_json_error(['message' => $email_updated->get_error_message()]);
            }
            update_user_meta($user_id, 'billing_email', $email);
        }
        update_user_meta($user_id, 'kml_profile_completed', 'yes');
        delete_transient($this->profile_token_key($user_id));

        $fallback = $this->checkout_or_account_url();
        $redirect = wp_validate_redirect($_POST['redirect'] ?? '', $fallback);
        wp_send_json_success(['message' => 'مشخصات با موفقیت ذخیره شد.', 'redirect' => $redirect]);
    }

    public function ajax_test_sms() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        check_ajax_referer(self::NONCE, 'nonce');
        $mobile = $this->normalize_mobile($_POST['mobile'] ?? '');
        if (!$this->valid_mobile($mobile)) wp_send_json_error(['message' => 'شماره موبایل معتبر نیست.']);
        $code = $this->generate_code();
        $sent = $this->send_sms($mobile, $code);
        if (is_wp_error($sent)) wp_send_json_error(['message' => $sent->get_error_message()]);
        wp_send_json_success(['message' => 'پیامک تست ارسال شد. پاسخ پنل: ' . esc_html($sent['body'])]);
    }

    public function replace_woocommerce_login_form() {
        if (is_user_logged_in()) return;
        if (is_admin()) return;

        // The page shortcode is the single source of the login UI. Avoid a
        // second copy being injected by WooCommerce's login-form hook.
        if ($this->current_page_has_login_shortcode()) return;

        $this->enqueue_front_assets_once();

        echo '<div class="kml-woo-login-replacer">';
        echo $this->form_html(false);
        echo '</div>';
        echo '<style>body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set, body.woocommerce-account:not(.logged-in) .woocommerce form.woocommerce-form-login, body.woocommerce-account:not(.logged-in) .woocommerce form.register, body.woocommerce-account:not(.logged-in) .woocommerce h2{display:none!important}.kml-woo-login-replacer .kml-box{margin-top:10px}</style>';
    }

    public function shortcode($atts = []) {
        $this->enqueue_front_assets_once();
        // Logged-in customers already have the WooCommerce account shell.
        // Rendering a second status box below it is redundant and disrupts
        // the shared account layout, so the shortcode becomes a no-op here.
        if (is_user_logged_in()) return '';
        return $this->form_html(false);
    }

    public function guard_checkout() {
        if (is_user_logged_in()) return;
        if (!function_exists('is_checkout') || !is_checkout()) return;
        $this->enqueue_front_assets_once();
        echo $this->form_html(true);
        echo '<style>form.checkout{display:none!important}.woocommerce-form-coupon-toggle,.woocommerce-form-login-toggle{display:none!important}</style>';
    }

    private function form_html($checkout = false) {
        $o = $this->opt();
        $mobile_id = wp_unique_id('kml-mobile-');
        $code_id = wp_unique_id('kml-code-');
        $first_name_id = wp_unique_id('kml-first-name-');
        $last_name_id = wp_unique_id('kml-last-name-');
        $email_id = wp_unique_id('kml-email-');
        ob_start();
        ?>
        <?php
        $redirect = $checkout && function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        ?>
        <div class="kml-auth-shell <?php echo $checkout ? 'kml-auth-shell-checkout' : ''; ?>" dir="rtl">
            <div class="kml-benefit-card">
                <span class="kml-side-mark"><?php echo esc_html($o['side_mark']); ?></span>
                <div class="kml-benefit-pill"><?php echo esc_html($o['benefit_pill']); ?></div>
                <h2><?php echo esc_html($o['benefit_title']); ?></h2>
                <p><?php echo esc_html($o['benefit_desc']); ?></p>
                <ul>
                    <li><span>۱</span><strong><?php echo esc_html($o['benefit_1_title']); ?></strong><em><?php echo esc_html($o['benefit_1_desc']); ?></em></li>
                    <li><span>۲</span><strong><?php echo esc_html($o['benefit_2_title']); ?></strong><em><?php echo esc_html($o['benefit_2_desc']); ?></em></li>
                    <li><span>۳</span><strong><?php echo esc_html($o['benefit_3_title']); ?></strong><em><?php echo esc_html($o['benefit_3_desc']); ?></em></li>
                </ul>
            </div>
            <div class="kml-box <?php echo $checkout ? 'kml-checkout-lock' : ''; ?>" data-redirect="<?php echo esc_url($redirect); ?>">
                <div class="kml-brand-word"><?php echo esc_html($o['brand_word']); ?></div>
                <div class="kml-badge">
                    <span class="kml-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3v3m0 12v3M3 12h3m12 0h3m-3.64-6.36-2.12 2.12M8.76 15.24l-2.12 2.12m0-11.72 2.12 2.12m6.48 7.48 2.12 2.12"/></svg></span>
                    <span><?php echo esc_html($o['badge_text']); ?></span>
                </div>
                <div class="kml-icon-wrap" aria-hidden="true"><span class="kml-phone-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M9.5 5h5M11 18.5h2"/></svg></span></div>
                <h1 class="kml-login-title"><?php echo esc_html($o['checkout_title']); ?></h1>
                <p><?php echo esc_html($o['checkout_desc']); ?></p>
                <div class="kml-step kml-step-mobile">
                    <label for="<?php echo esc_attr($mobile_id); ?>">شماره موبایل، ایمیل یا نام کاربری</label>
                    <input id="<?php echo esc_attr($mobile_id); ?>" type="text" class="kml-mobile" placeholder="09123456789 یا ایمیل / نام کاربری" inputmode="text" autocomplete="username">
                    <button type="button" class="button kml-send">ارسال کد تایید</button>
                    <button type="button" class="kml-forgot-link">رمز عبور را فراموش کرده‌اید؟ ورود با کد پیامکی</button>
                </div>
                <div class="kml-step kml-step-code" style="display:none">
                    <div class="kml-mobile-confirm" style="display:none">
                        <span class="kml-mobile-confirm-check">✓</span>
                        <div>
                            <strong>کد تایید ارسال شد</strong>
                            <small>به <b class="kml-masked-mobile"></b></small>
                        </div>
                        <button type="button" class="kml-change-mobile">تغییر شماره</button>
                    </div>
                    <label for="<?php echo esc_attr($code_id); ?>">کد تایید</label>
                    <div class="kml-otp-wrap" dir="ltr" role="group" aria-label="کد تایید پیامکی">
                        <input id="<?php echo esc_attr($code_id); ?>" type="text" class="kml-code kml-otp-real" placeholder="کد پیامک شده" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" maxlength="<?php echo esc_attr((int) $this->opt('code_length')); ?>">
                        <div class="kml-otp-boxes" aria-hidden="true"></div>
                    </div>
                    <small class="kml-otp-hint">کد پیامک‌شده را وارد کنید؛ بعد از تکمیل، ورود خودکار انجام می‌شود.</small>
                    <button type="button" class="button kml-verify">ورود / ادامه خرید</button>
                    <button type="button" class="button kml-resend" disabled>ارسال مجدد</button>
                    <span class="kml-countdown"></span>
                </div>
                <div class="kml-step kml-profile-step" style="display:none">
                    <label for="<?php echo esc_attr($first_name_id); ?>">نام</label>
                    <input id="<?php echo esc_attr($first_name_id); ?>" type="text" class="kml-first-name" placeholder="نام" autocomplete="given-name">
                    <label for="<?php echo esc_attr($last_name_id); ?>">نام خانوادگی</label>
                    <input id="<?php echo esc_attr($last_name_id); ?>" type="text" class="kml-last-name" placeholder="نام خانوادگی" autocomplete="family-name">
                    <label for="<?php echo esc_attr($email_id); ?>">ایمیل <small>(اختیاری)</small></label>
                    <input id="<?php echo esc_attr($email_id); ?>" type="email" class="kml-email" placeholder="example@email.com" autocomplete="email">
                    <button type="button" class="button kml-save-profile">ذخیره و ادامه</button>
                </div>
                <div class="kml-message" role="status" aria-live="polite"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new KADEC_Mobile_Login();
