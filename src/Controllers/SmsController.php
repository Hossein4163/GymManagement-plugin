<?php

namespace GymManagement\Controllers;

class SmsController
{
    private $api_key = 'YOUR_SMS_API_KEY';
    private $sender_number = 'YOUR_SENDER_NUMBER';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_sms_menu'));
    }

    public function add_sms_menu()
    {
        add_submenu_page(
            'my-gym-accounting',
            'ارسال پیامک',
            'پیامک',
            'manage_options',
            'my-gym-sms',
            array($this, 'render_sms_page')
        );
    }

    public function render_sms_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/sms-page.php';
    }

    public function send_sms($mobile, $message)
    {
        $url = "https://api.sms-service.com/send";
        $args = array(
            'body' => json_encode(array(
                'api_key' => $this->api_key,
                'sender' => $this->sender_number,
                'to' => $mobile,
                'message' => $message,
            )),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 60,
        );
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return false;
        } else {
            return wp_remote_retrieve_body($response);
        }
    }
}