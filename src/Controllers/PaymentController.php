<?php

namespace GymManagement\Controllers;

use GymManagement\Models\Transaction;

class PaymentController
{
    private $zarinpal_api_key = 'YOUR_ZARINPAL_MERCHANT_ID'; // شناسه مرچنت شما
    private $zarinpal_url = 'https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json';
    private $zarinpal_verify_url = 'https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json';

    public function __construct()
    {
        add_action('wp_ajax_my_gym_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_my_gym_process_payment', array($this, 'ajax_process_payment'));
        add_action('init', array($this, 'handle_zarinpal_callback'));
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
            wp_send_json_error('مبلغ یا اطلاعات کاربر نامعتبر است.');
        }

        wp_die();
    }

    public function send_to_zarinpal($amount, $user_id, $description)
    {
        $data = array(
            'MerchantID' => $this->zarinpal_api_key,
            'Amount' => $amount,
            'Description' => $description,
            'CallbackURL' => MY_GYM_PLUGIN_URL . 'zarinpal-callback.php?user_id=' . $user_id . '&amount=' . $amount,
        );

        $response = wp_remote_post($this->zarinpal_url, array(
            'body' => json_encode($data),
            'headers' => array('Content-Type' => 'application/json'),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('خطا در اتصال به درگاه پرداخت.');
        } else {
            $body = json_decode(wp_remote_retrieve_body($response));
            if ($body->Status == 100) {
                $redirect_url = 'https://www.zarinpal.com/pg/StartPay/' . $body->Authority;
                wp_send_json_success(array('redirect_url' => $redirect_url));
            } else {
                wp_send_json_error('خطا در ایجاد تراکنش: ' . $body->Status);
            }
        }
    }

    public function handle_zarinpal_callback()
    {
        if (isset($_GET['Authority']) && isset($_GET['Status'])) {
            $authority = sanitize_text_field($_GET['Authority']);
            $status = sanitize_text_field($_GET['Status']);
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

            if ($status == 'OK' && $amount > 0) {
                $this->verify_zarinpal_payment($authority, $amount, $user_id);
            } else {
                wp_redirect(site_url('/payment-failed/?reason=canceled'));
                exit;
            }
        }
    }

    public function verify_zarinpal_payment($authority, $amount, $user_id)
    {
        $data = array(
            'MerchantID' => $this->zarinpal_api_key,
            'Authority' => $authority,
            'Amount' => $amount,
        );

        $response = wp_remote_post($this->zarinpal_verify_url, array(
            'body' => json_encode($data),
            'headers' => array('Content-Type' => 'application/json'),
        ));

        $body = json_decode(wp_remote_retrieve_body($response));

        if ($body->Status == 100) {
            (new AccountingController())->create_transaction($user_id, $amount, 'دریافت', 'آنلاین', 'پرداخت شهریه از طریق درگاه زرین‌پال');
            wp_redirect(site_url('/payment-success/'));
        } else {
            wp_redirect(site_url('/payment-failed/?reason=failed'));
        }
        exit;
    }
}