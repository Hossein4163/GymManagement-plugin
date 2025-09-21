<?php
/**
 * View for the [rame_user_profile] shortcode.
 * This file is included by FrontendController.
 *
 * @var int $user_id The current logged-in user's ID.
 */

use GymManagement\Controllers\MembershipController;
use Morilog\Jalali\Jalalian;

global $wpdb;

// Fetch current active membership
$memberships_table = $wpdb->prefix . 'gym_memberships';
$active_membership = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$memberships_table} WHERE user_id = %d AND status = 'active' ORDER BY start_date DESC LIMIT 1",
    $user_id
));

$user_info = get_userdata($user_id);
?>
<div class="rame-profile-container">
    <h2><?php esc_html_e('پروفایل کاربری', 'rame-gym'); ?></h2>

    <div class="profile-section">
        <h3><?php esc_html_e('اطلاعات شما', 'rame-gym'); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e('نام:', 'rame-gym'); ?></strong> <?php echo esc_html($user_info->display_name); ?>
            </li>
            <li>
                <strong><?php esc_html_e('ایمیل:', 'rame-gym'); ?></strong> <?php echo esc_html($user_info->user_email); ?>
            </li>
            <li>
                <strong><?php esc_html_e('شماره تماس:', 'rame-gym'); ?></strong> <?php echo esc_html(get_user_meta($user_id, 'phone_number', true) ?: 'ثبت نشده'); ?>
            </li>
        </ul>
    </div>

    <?php if ($active_membership) : ?>
        <div class="profile-section">
            <h3><?php esc_html_e('وضعیت عضویت فعال', 'rame-gym'); ?></h3>
            <ul>
                <li>
                    <strong><?php esc_html_e('رشته ورزشی:', 'rame-gym'); ?></strong> <?php echo esc_html(get_the_title($active_membership->discipline_id)); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('تاریخ شروع:', 'rame-gym'); ?></strong> <?php echo esc_html(Jalalian::fromDateTime($active_membership->start_date)->format('Y/m/d')); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('تاریخ انقضا:', 'rame-gym'); ?></strong> <?php echo esc_html(Jalalian::fromDateTime($active_membership->end_date)->format('Y/m/d')); ?>
                </li>
                <li><strong><?php esc_html_e('وضعیت:', 'rame-gym'); ?></strong> <span
                        class="status-badge <?php echo esc_attr($active_membership->status); ?>"><?php echo esc_html(MembershipController::get_status_label($active_membership->status)); ?></span>
                </li>
            </ul>

            <?php
            // If membership is paid by installments, show the installment table
            if ($active_membership->payment_type === 'installments') :
                $installments_table = $wpdb->prefix . 'gym_installments';
                $installments = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$installments_table} WHERE membership_id = %d ORDER BY due_date ASC",
                    $active_membership->id
                ));

                if (!empty($installments)) :
                    ?>
                    <h4 style="margin-top: 20px;"><?php esc_html_e('وضعیت اقساط این دوره', 'rame-gym'); ?></h4>
                    <table class="installment-table">
                        <thead>
                        <tr>
                            <th><?php esc_html_e('مبلغ قسط', 'rame-gym'); ?></th>
                            <th><?php esc_html_e('تاریخ سررسید', 'rame-gym'); ?></th>
                            <th><?php esc_html_e('وضعیت', 'rame-gym'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($installments as $installment): ?>
                            <tr>
                                <td><?php echo number_format($installment->amount); ?> تومان</td>
                                <td><?php echo esc_html(Jalalian::fromDateTime($installment->due_date)->format('Y/m/d')); ?></td>
                                <td>
                                <span class="status-badge <?php echo esc_attr($installment->status); ?>">
                                    <?php echo esc_html(MembershipController::get_status_label($installment->status)); ?>
                                </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php
                endif;
            endif;
            ?>
        </div>
    <?php else : ?>
        <div class="profile-section">
            <h3><?php esc_html_e('وضعیت عضویت', 'rame-gym'); ?></h3>
            <p><?php esc_html_e('شما در حال حاضر عضویت فعالی ندارید.', 'rame-gym'); ?></p>
        </div>
    <?php endif; ?>
</div>