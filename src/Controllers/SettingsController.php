<?php

namespace GymManagement\Controllers;

final class SettingsController
{
    private const OPTION_GROUP = 'rame_gym_settings_group';
    private const OPTION_NAME = 'rame_gym_options';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'rame-gym',
            __('تنظیمات', 'rame-gym'),
            __('تنظیمات', 'rame-gym'),
            'manage_options',
            'rame-gym-settings',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        ?>
        <div class="wrap my-gym-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('rame-gym-settings');
                submit_button(__('ذخیره تنظیمات', 'rame-gym'));
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings()
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [$this, 'sanitize_options']
        );

        add_settings_section(
            'rame_gym_api_settings_section',
            __('تنظیمات API', 'rame-gym'),
            '__return_false', // No description needed for this section
            'rame-gym-settings'
        );

        add_settings_field(
            'zarinpal_merchant_id',
            __('شناسه مرچنت زرین‌پال', 'rame-gym'),
            [$this, 'render_text_field'],
            'rame-gym-settings',
            'rame_gym_api_settings_section',
            ['id' => 'zarinpal_merchant_id', 'description' => 'کد مرچنت دریافت شده از زرین‌پال.']
        );

        add_settings_field(
            'sms_api_key',
            __('کلید API پنل پیامک', 'rame-gym'),
            [$this, 'render_text_field'],
            'rame-gym-settings',
            'rame_gym_api_settings_section',
            ['id' => 'sms_api_key', 'description' => 'کلید API برای ارسال پیامک.']
        );

        add_settings_field(
            'sms_sender_number',
            __('شماره خط ارسال کننده', 'rame-gym'),
            [$this, 'render_text_field'],
            'rame-gym-settings',
            'rame_gym_api_settings_section',
            ['id' => 'sms_sender_number', 'description' => 'شماره‌ای که پیامک‌ها با آن ارسال می‌شوند.']
        );
    }

    public function render_text_field(array $args)
    {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options[$args['id']] ?? '';
        ?>
        <input type="text"
               id="<?php echo esc_attr($args['id']); ?>"
               name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['id'] . ']'); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text ltr">
        <?php if (!empty($args['description'])): ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php endif; ?>
        <?php
    }

    public function sanitize_options(array $input): array
    {
        $sanitized_input = [];
        $options = get_option(self::OPTION_NAME, []);

        $sanitized_input['zarinpal_merchant_id'] = isset($input['zarinpal_merchant_id'])
            ? sanitize_text_field($input['zarinpal_merchant_id'])
            : ($options['zarinpal_merchant_id'] ?? '');

        $sanitized_input['sms_api_key'] = isset($input['sms_api_key'])
            ? sanitize_text_field($input['sms_api_key'])
            : ($options['sms_api_key'] ?? '');

        $sanitized_input['sms_sender_number'] = isset($input['sms_sender_number'])
            ? sanitize_text_field($input['sms_sender_number'])
            : ($options['sms_sender_number'] ?? '');

        return $sanitized_input;
    }
}