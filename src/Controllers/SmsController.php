<?php

namespace GymManagement\Controllers;

class SmsController
{
    public function __construct()
    {
        // Remove menu registration from constructor
        add_action('admin_init', array($this, 'process_sms'));
    }

    public function render_sms_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }

        require_once MY_GYM_PLUGIN_PATH . 'views/sms-page.php';
    }

    public function process_sms()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sms']) && check_admin_referer('my_gym_sms_nonce')) {
            // Check user permissions
            if (!current_user_can('manage_options')) {
                return;
            }

            $recipient_group = sanitize_text_field($_POST['recipient_group']);
            $message_text = sanitize_textarea_field($_POST['message_text']);
            $manual_numbers = isset($_POST['manual_numbers']) ? sanitize_textarea_field($_POST['manual_numbers']) : '';

            if (empty($message_text)) {
                add_settings_error('my_gym_messages', 'invalid_message', 'متن پیامک نمی‌تواند خالی باشد.', 'error');
                return;
            }

            $recipients = [];
            if ($recipient_group === 'manual') {
                $numbers = array_filter(array_map('trim', explode("\n", $manual_numbers)));
                foreach ($numbers as $number) {
                    if (preg_match('/^09[0-9]{9}$/', $number)) {
                        $recipients[] = ['number' => $number, 'name' => 'ناشناس', 'discipline' => '', 'amount' => 0];
                    }
                }
            } else {
                $args = ['role__in' => ['subscriber']];
                if ($recipient_group === 'active') {
                    $args['meta_query'] = [['key' => 'payment_type', 'value' => 'full']];
                } elseif ($recipient_group === 'inactive') {
                    $args['meta_query'] = [['key' => 'payment_type', 'compare' => 'NOT EXISTS']];
                }
                $users = get_users($args);
                foreach ($users as $user) {
                    $phone = get_user_meta($user->ID, 'phone_number', true);
                    if (!empty($phone)) {
                        $recipients[] = [
                            'number' => $phone,
                            'name' => $user->display_name,
                            'discipline' => get_the_title(get_user_meta($user->ID, 'sport_discipline', true)),
                            'amount' => floatval(get_user_meta($user->ID, 'total_amount', true))
                        ];
                    }
                }
            }

            $success_count = 0;
            foreach ($recipients as $recipient) {
                if (!empty($recipient['number'])) {
                    $message = str_replace(
                        ['{name}', '{discipline}', '{amount}'],
                        [$recipient['name'], $recipient['discipline'], number_format($recipient['amount'])],
                        $message_text
                    );
                    if ($this->send_sms($recipient['number'], $message)) {
                        $success_count++;
                    }
                }
            }

            add_settings_error('my_gym_messages', 'sms_sent', "پیامک به $success_count گیرنده با موفقیت ارسال شد.", 'success');
        }
    }

    private function send_sms($number, $message)
    {
        // نمونه پیاده‌سازی فرضی با API پیامکی
        // برای استفاده واقعی، باید API سرویس پیامکی (مثل کاوه‌نگار) را جایگزین کنید
        $api_url = 'https://api.sms-service.com/send';
        $api_key = 'YOUR_SMS_API_KEY'; // جایگزین کنید

        $data = [
            'api_key' => $api_key,
            'to' => $number,
            'message' => $message
        ];

        $response = wp_remote_post($api_url, [
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);

        // For development/testing purposes, always return true
        // Remove this line when implementing real SMS API
        return true;

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}