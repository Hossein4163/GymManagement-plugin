<?php

namespace GymManagement\Controllers;

use GymManagement\Models\Installment;
use DateTime;

class MembershipController
{
    public function __construct()
    {
        add_action('show_user_profile', array($this, 'add_member_fields'));
        add_action('edit_user_profile', array($this, 'add_member_fields'));
        add_action('personal_options_update', array($this, 'save_member_fields'));
        add_action('edit_user_profile_update', array($this, 'save_member_fields'));
    }

    public function add_member_fields($user)
    {
        $installments = $this->get_installments_for_user($user->ID);
        $disciplines = get_posts(array('post_type' => 'sports_discipline', 'numberposts' => -1));

        ?>
        <h3>اطلاعات باشگاهی</h3>
        <table class="form-table">
            <tr>
                <th><label for="national_id">کد ملی</label></th>
                <td><input type="text" name="national_id" id="national_id"
                           value="<?php echo esc_attr(get_the_author_meta('national_id', $user->ID)); ?>"
                           class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="sport_discipline">رشته ورزشی</label></th>
                <td>
                    <select name="sport_discipline" id="sport_discipline">
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
                style="<?php echo (get_the_author_meta('payment_type', $user->ID) == 'installments') ? '' : 'display: none;'; ?>">
                <th><label for="total_amount">مبلغ کل</label></th>
                <td><input type="number" name="total_amount" id="total_amount"
                           value="<?php echo esc_attr(get_the_author_meta('total_amount', $user->ID)); ?>"/></td>
            </tr>
            <tr id="installment_count_field"
                style="<?php echo (get_the_author_meta('payment_type', $user->ID) == 'installments') ? '' : 'display: none;'; ?>">
                <th><label for="installment_count">تعداد اقساط</label></th>
                <td><input type="number" name="installment_count" id="installment_count"
                           value="<?php echo esc_attr(get_the_author_meta('installment_count', $user->ID)); ?>"
                           min="1"/></td>
            </tr>
        </table>

        <h3>مدیریت اقساط</h3>
        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>مبلغ</th>
                <th>تاریخ سررسید</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($installments)): ?>
                <?php foreach ($installments as $installment): ?>
                    <tr>
                        <td><?php echo number_format($installment->amount); ?> تومان</td>
                        <td><?php echo esc_html($installment->due_date); ?></td>
                        <td><span
                                class="status-badge <?php echo esc_attr($installment->status); ?>"><?php echo esc_html($this->get_installment_status_label($installment->status)); ?></span>
                        </td>
                        <td>
                            <?php if ($installment->status !== 'paid'): ?>
                                <button class="button button-primary pay-installment-btn"
                                        data-id="<?php echo esc_attr($installment->id); ?>">ثبت پرداخت
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">هیچ قسطی ثبت نشده است.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    public function save_member_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        update_user_meta($user_id, 'national_id', sanitize_text_field($_POST['national_id']));
        update_user_meta($user_id, 'sport_discipline', sanitize_text_field($_POST['sport_discipline']));
        update_user_meta($user_id, 'payment_type', sanitize_text_field($_POST['payment_type']));

        if (isset($_POST['payment_type']) && $_POST['payment_type'] === 'installments') {
            $total_amount = floatval($_POST['total_amount']);
            $installment_count = intval($_POST['installment_count']);
            update_user_meta($user_id, 'total_amount', $total_amount);
            update_user_meta($user_id, 'installment_count', $installment_count);

            $this->create_or_update_installments($user_id, $total_amount, $installment_count);
        } else {
            delete_user_meta($user_id, 'total_amount');
            delete_user_meta($user_id, 'installment_count');
            $this->delete_all_installments($user_id);
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

    private function get_installments_for_user($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY due_date ASC", $user_id));
    }

    private function get_installment_status_label($status)
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
}