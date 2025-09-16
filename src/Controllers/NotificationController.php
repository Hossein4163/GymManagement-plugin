<?php

namespace GymManagement\Controllers;

class NotificationController
{
    public function __construct()
    {
        add_action('admin_notices', array($this, 'display_overdue_installments_notice'));
    }

    public function display_overdue_installments_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        $overdue_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'overdue'");

        if ($overdue_count > 0) {
            $message = sprintf('🔔 توجه: شما %d قسط معوق دارید! لطفاً برای پیگیری به بخش <a href="%s">مدیریت اعضا</a> مراجعه کنید.', $overdue_count, admin_url('users.php'));
            printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message);
        }
    }
}