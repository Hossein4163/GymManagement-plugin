<?php
// src/Controllers/MembershipController.php

namespace GymManagement\Controllers;

use DateTime;
use GymManagement\Models\Installment;

class MembershipController
{
    public function __construct()
    {
        add_action('show_user_profile', array($this, 'add_member_fields'));
        add_action('edit_user_profile', array($this, 'add_member_fields'));
        add_action('personal_options_update', array($this, 'save_member_fields'));
        add_action('edit_user_profile_update', array($this, 'save_member_fields'));
        add_action('wp_ajax_my_gym_get_all_users', array($this, 'ajax_get_all_users'));
    }

    public function add_member_fields($user)
    {
        $installments = $this->get_installments_for_user($user->ID);
        $disciplines = get_posts(array('post_type' => 'sports_discipline', 'numberposts' => -1));
        wp_nonce_field('my_gym_member_nonce', 'my_gym_member_nonce');
        ?>
        <h3>اطلاعات باشگاهی</h3>
        <table class="form-table">
            <tr>
                <th><label for="national_id">کد ملی</label></th>
                <td><input type="text" name="national_id" id="national_id"
                           value="<?php echo esc_attr(get_the_author_meta('national_id', $user->ID)); ?>"
                           class="regular-text" pattern="[0-9]{10}" title="کد ملی باید 10 رقم باشد"/></td>
            </tr>
            <tr>
                <th><label for="phone_number">شماره تماس</label></th>
                <td><input type="text" name="phone_number" id="phone_number"
                           value="<?php echo esc_attr(get_the_author_meta('phone_number', $user->ID)); ?>"
                           class="regular-text" pattern="09[0-9]{9}"
                           title="شماره تماس باید با 09 شروع شود و 11 رقم باشد"/></td>
            </tr>
            <tr>
                <th><label for="sport_discipline">رشته ورزشی</label></th>
                <td>
                    <select name="sport_discipline" id="sport_discipline" class="select2-searchable">
                        <option value="">انتخاب رشته</option>
                        <?php foreach ($disciplines as $discipline) : ?>
                            <option
                                value="<?php echo esc_attr($discipline->ID); ?>" <?php selected(get_the_author_meta('sport_discipline', $user->ID), $discipline->ID); ?>><?php echo esc_html($discipline->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="payment_type">نوع پرداخت</label></th>
                <td>
                    <select name="payment_type" id="payment_type">
                        <option value="full" <?php selected(get_the_author_meta('payment_type', $user->ID), 'full'); ?>>
                            نقدی/کامل
                        </option>
                        <option
                            value="installments" <?php selected(get_the_author_meta('payment_type', $user->ID), 'installments'); ?>>
                            اقساطی
                        </option>
                    </select>
                </td>
            </tr>
            <tr id="total_amount_field"
                style="display: <?php echo get_the_author_meta('payment_type', $user->ID) === 'installments' ? 'table-row' : 'none'; ?>;">
                <th><label for="total_amount">مبلغ کل (تومان)</label></th>
                <td><input type="number" name="total_amount" id="total_amount"
                           value="<?php echo esc_attr(get_the_author_meta('total_amount', $user->ID)); ?>"
                           class="regular-text" min="0"/></td>
            </tr>
            <tr id="installment_count_field"
                style="display: <?php echo get_the_author_meta('payment_type', $user->ID) === 'installments' ? 'table-row' : 'none'; ?>;">
                <th><label for="installment_count">تعداد اقساط</label></th>
                <td><input type="number" name="installment_count" id="installment_count"
                           value="<?php echo esc_attr(get_the_author_meta('installment_count', $user->ID)); ?>"
                           class="regular-text" min="1" max="12"/></td>
            </tr>
            <?php if (!empty($installments)) : ?>
                <tr>
                    <th>وضعیت اقساط</th>
                    <td>
                        <table class="widefat fixed striped">
                            <thead>
                            <tr>
                                <th>مبلغ قسط</th>
                                <th>تاریخ سررسید</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($installments as $installment) : ?>
                                <tr>
                                    <td><?php echo number_format($installment->amount, 2); ?> تومان</td>
                                    <td><?php echo esc_html($installment->due_date); ?></td>
                                    <td><span class="status-badge <?php echo esc_attr($installment->status); ?>">
                                            <?php echo esc_html($this->get_installment_status_label($installment->status)); ?>
                                        </span></td>
                                    <td>
                                        <?php if ($installment->status !== 'paid') : ?>
                                            <button class="pay-installment-btn button button-primary"
                                                    data-id="<?php echo esc_attr($installment->id); ?>">ثبت پرداخت
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public function save_member_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id) || !wp_verify_nonce($_POST['my_gym_member_nonce'], 'my_gym_member_nonce')) {
            return;
        }

        if (isset($_POST['national_id']) && preg_match('/^[0-9]{10}$/', $_POST['national_id'])) {
            update_user_meta($user_id, 'national_id', sanitize_text_field($_POST['national_id']));
        } else {
            add_settings_error('my_gym_messages', 'invalid_national_id', 'کد ملی باید 10 رقم باشد.', 'error');
        }

        if (isset($_POST['phone_number']) && preg_match('/^09[0-9]{9}$/', $_POST['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        } else {
            add_settings_error('my_gym_messages', 'invalid_phone', 'شماره تماس باید با 09 شروع شود و 11 رقم باشد.', 'error');
        }

        if (isset($_POST['sport_discipline'])) {
            update_user_meta($user_id, 'sport_discipline', sanitize_text_field($_POST['sport_discipline']));
        }

        if (isset($_POST['payment_type'])) {
            update_user_meta($user_id, 'payment_type', sanitize_text_field($_POST['payment_type']));
        }

        if ($_POST['payment_type'] === 'installments' && isset($_POST['total_amount']) && isset($_POST['installment_count'])) {
            $total_amount = floatval($_POST['total_amount']);
            $installment_count = intval($_POST['installment_count']);
            if ($total_amount <= 0 || $installment_count < 1 || $installment_count > 12) {
                add_settings_error('my_gym_messages', 'invalid_payment', 'مبلغ کل یا تعداد اقساط نامعتبر است.', 'error');
                return;
            }
            update_user_meta($user_id, 'total_amount', $total_amount);
            update_user_meta($user_id, 'installment_count', $installment_count);
            $this->create_or_update_installments($user_id, $total_amount, $installment_count);
        } else {
            delete_user_meta($user_id, 'total_amount');
            delete_user_meta($user_id, 'installment_count');
            $this->delete_all_installments($user_id);
        }
    }

    public function get_installments_for_user($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY due_date ASC", $user_id));
    }

    public function get_installment_status_label($status)
    {
        switch ($status) {
            case 'paid':
                return 'پرداخت شده';
            case 'pending':
                return 'در انتظار';
            case 'overdue':
                return 'معوق';
            default:
                return 'نامشخص';
        }
    }

    private function create_or_update_installments($user_id, $total_amount, $count)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        $wpdb->delete($table_name, ['user_id' => $user_id]);

        $installment_amount = round($total_amount / $count, 2);
        $current_date = new DateTime();

        for ($i = 1; $i <= $count; $i++) {
            $due_date = (clone $current_date)->modify('+' . $i . ' month')->format('Y-m-d');
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'amount' => $installment_amount,
                    'due_date' => $due_date,
                    'status' => 'pending'
                ]
            );
        }
    }

    private function delete_all_installments($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        $wpdb->delete($table_name, ['user_id' => $user_id]);
    }

    public function ajax_get_all_users()
    {
        check_ajax_referer('my_gym_security_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی.');
        }

        $users = get_users(['role__in' => ['subscriber', 'administrator']]);
        $formatted_users = [];
        foreach ($users as $user) {
            $formatted_users[] = ['id' => $user->ID, 'text' => $user->display_name];
        }

        wp_send_json_success($formatted_users);
    }
}