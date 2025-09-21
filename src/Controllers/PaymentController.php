<?php

namespace GymManagement\Controllers;

final class PaymentController
{
    private string $zarinpal_url;
    private string $zarinpal_verify_url;
    private string $zarinpal_api_key;

    public function __construct()
    {
        $options = get_option('rame_gym_options');
        $this->zarinpal_api_key = $options['zarinpal_merchant_id'] ?? '';

        $is_sandbox = false; // This can be made a setting later
        $this->zarinpal_url = $is_sandbox
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentRequest.json'
            : 'https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json';

        $this->zarinpal_verify_url = $is_sandbox
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentVerification.json'
            : 'https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json';

        add_action('wp_ajax_my_gym_process_payment', [$this, 'ajax_process_payment']);
        add_action('wp_ajax_nopriv_my_gym_process_payment', [$this, 'ajax_process_payment']);

        add_action('admin_post_nopriv_zarinpal_callback', [$this, 'handle_gateway_callback']);
        add_action('admin_post_zarinpal_callback', [$this, 'handle_gateway_callback']);
    }

    public function ajax_process_payment()
    {
        check_ajax_referer('my_gym_payment_nonce', 'security');
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : 'پرداخت شهریه باشگاه';
        $user_id = get_current_user_id();

        if ($amount > 0 && $user_id) {
            $this->send_to_zarinpal($amount, $user_id, $description);
        } else {
            wp_send_json_error(['message' => 'مبلغ یا اطلاعات کاربر نامعتبر است.']);
        }
    }

    private function send_to_zarinpal(float $amount, int $user_id, string $description)
    {
        if (empty($this->zarinpal_api_key)) {
            wp_send_json_error(['message' => 'درگاه پرداخت پیکربندی نشده است.']);
        }

        $callback_url = add_query_arg(
            [
                'action' => 'zarinpal_callback',
                'user_id' => $user_id,
                'amount' => $amount
            ],
            admin_url('admin-post.php')
        );

        $data = [
            'MerchantID' => $this->zarinpal_api_key,
            'Amount' => $amount, // Zarinpal expects Toman
            'Description' => $description,
            'CallbackURL' => $callback_url,
        ];

        $response = wp_remote_post($this->zarinpal_url, [
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'خطا در اتصال به درگاه پرداخت.']);
        } else {
            $body = json_decode(wp_remote_retrieve_body($response));
            if (isset($body->Status) && $body->Status == 100) {
                $redirect_url = 'https://www.zarinpal.com/pg/StartPay/' . $body->Authority;
                wp_send_json_success(['redirect_url' => $redirect_url]);
            } else {
                $error_code = $body->Status ?? 'Unknown';
                wp_send_json_error(['message' => 'خطا در ایجاد تراکنش: ' . $error_code]);
            }
        }
    }

    public function handle_gateway_callback()
    {
        $authority = isset($_GET['Authority']) ? sanitize_text_field($_GET['Authority']) : '';
        $status = isset($_GET['Status']) ? sanitize_text_field($_GET['Status']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

        if (empty($authority) || empty($status) || $user_id <= 0 || $amount <= 0) {
            wp_redirect(site_url('/payment-failed/?reason=invalid_data'));
            exit;
        }

        if ($status === 'OK') {
            $this->verify_zarinpal_payment($authority, $amount, $user_id);
        } else {
            wp_redirect(site_url('/payment-failed/?reason=canceled'));
            exit;
        }
    }

    private function verify_zarinpal_payment(string $authority, float $amount, int $user_id)
    {
        $data = [
            'MerchantID' => $this->zarinpal_api_key,
            'Authority' => $authority,
            'Amount' => $amount,
        ];

        $response = wp_remote_post($this->zarinpal_verify_url, [
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_redirect(site_url('/payment-failed/?reason=verify_connection_error'));
            exit;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (isset($body->Status) && ($body->Status == 100 || $body->Status == 101)) { // Status 101 means already verified
            (new AccountingController())->create_transaction(
                $user_id,
                $amount,
                'دریافت',
                'آنلاین',
                'پرداخت موفق از طریق درگاه - کد رهگیری: ' . ($body->RefID ?? 'N/A')
            );
            wp_redirect(site_url('/payment-success/'));
        } else {
            wp_redirect(site_url('/payment-failed/?reason=verify_failed'));
        }
        exit;
    }
}