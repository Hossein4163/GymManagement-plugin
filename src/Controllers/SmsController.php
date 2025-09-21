<?php

namespace GymManagement\Controllers;

final class SmsController
{
    private string $api_key;
    private string $sender_number;

    public function __construct()
    {
        $options = get_option('rame_gym_options', []);
        $this->api_key = $options['sms_api_key'] ?? '';
        $this->sender_number = $options['sms_sender_number'] ?? '';

        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_init', [$this, 'process_sms_form']);
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'rame-gym',
            __('ارسال پیامک', 'rame-gym'),
            __('ارسال پیامک', 'rame-gym'),
            'manage_options',
            'rame-gym-sms',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/sms-page.php';
    }

    public function process_sms_form()
    {
        if (!isset($_POST['submit_sms']) || !check_admin_referer('my_gym_sms_nonce')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_die('Access Denied.');
        }

        $recipient_group = sanitize_text_field($_POST['recipient_group']);
        $message_text = sanitize_textarea_field($_POST['message_text']);
        $manual_numbers_raw = sanitize_textarea_field($_POST['manual_numbers'] ?? '');

        if (empty($message_text)) {
            add_settings_error('my_gym_messages', 'empty_message', 'متن پیامک نمی‌تواند خالی باشد.', 'error');
            return;
        }

        $recipients = $this->get_recipients($recipient_group, $manual_numbers_raw);
        $success_count = 0;

        foreach ($recipients as $recipient) {
            $message = $this->replace_placeholders($message_text, $recipient);
            if ($this->send_sms($recipient['phone'], $message)) {
                $success_count++;
            }
        }
        add_settings_error('my_gym_messages', 'sms_sent', "پیامک به {$success_count} گیرنده ارسال شد.", 'success');
    }

    private function get_recipients(string $group, string $manual_numbers_raw): array
    {
        $recipients = [];
        if ($group === 'manual') {
            $numbers = array_filter(array_map('trim', explode("\n", $manual_numbers_raw)));
            foreach ($numbers as $number) {
                if (preg_match('/^09[0-9]{9}$/', $number)) {
                    $recipients[] = ['phone' => $number, 'name' => '', 'discipline' => ''];
                }
            }
            return $recipients;
        }

        global $wpdb;
        $memberships_table = $wpdb->prefix . 'gym_memberships';
        $status_filter = '';
        if ($group === 'active') {
            $status_filter = $wpdb->prepare("AND m.status = %s", 'active');
        } elseif ($group === 'inactive') {
            $status_filter = $wpdb->prepare("AND m.status != %s", 'active');
        }

        $users_with_phone = $wpdb->get_results("
            SELECT u.ID, u.display_name, um.meta_value as phone
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'phone_number' AND um.meta_value != ''
        ");

        foreach ($users_with_phone as $user) {
            $recipients[] = [
                'phone' => $user->phone,
                'name' => $user->display_name,
                'discipline' => '' // Can be enhanced to get current discipline
            ];
        }
        return $recipients;
    }

    private function replace_placeholders(string $message, array $recipient): string
    {
        return str_replace(
            ['{name}', '{discipline}'],
            [$recipient['name'], $recipient['discipline']],
            $message
        );
    }

    public function send_sms(string $number, string $message): bool
    {
        if (empty($this->api_key) || empty($this->sender_number)) {
            return false;
        }

        // This URL and body structure is a generic example.
        // You MUST adapt this to your actual SMS provider's API documentation.
        $api_url = 'https://api.sms.ir/v1/send/bulk';

        $body = json_encode([
            'lineNumber' => $this->sender_number,
            'messageText' => $message,
            'mobiles' => [$number],
        ]);

        $response = wp_remote_post($api_url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-KEY' => $this->api_key
            ],
            'timeout' => 20,
        ]);

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function send_automated_sms(int $user_id, string $message)
    {
        $phone_number = get_user_meta($user_id, 'phone_number', true);
        if ($phone_number && preg_match('/^09[0-9]{9}$/', $phone_number)) {
            $this->send_sms($phone_number, $message);
        }
    }
}