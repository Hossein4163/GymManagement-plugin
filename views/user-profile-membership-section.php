<?php
/**
 * View file for the membership management section on the user's admin profile page.
 * This is included by MembershipController.
 *
 * @var WP_User $user The user object being edited.
 */

use Morilog\Jalali\Jalalian;

?>
<h2><?php esc_html_e('مدیریت باشگاه', 'rame-gym'); ?></h2>

<table class="form-table" id="rame-gym-member-details">
    <tr>
        <th><label for="national_id"><?php esc_html_e('کد ملی', 'rame-gym'); ?></label></th>
        <td><input type="text" name="national_id" id="national_id"
                   value="<?php echo esc_attr(get_the_author_meta('national_id', $user->ID)); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="phone_number"><?php esc_html_e('شماره تماس', 'rame-gym'); ?></label></th>
        <td><input type="text" name="phone_number" id="phone_number"
                   value="<?php echo esc_attr(get_the_author_meta('phone_number', $user->ID)); ?>"
                   class="regular-text ltr"></td>
    </tr>
</table>

<div class="postbox">
    <h3 class="hndle"><?php esc_html_e('ثبت / تمدید عضویت', 'rame-gym'); ?></h3>
    <div class="inside">
        <p class="description"><?php esc_html_e('برای ثبت یک دوره عضویت جدید برای این کاربر، فیلدهای زیر را پر کرده و پروفایل را ذخیره کنید.', 'rame-gym'); ?></p>
        <table class="form-table">
            <tr>
                <th><label for="discipline_id"><?php esc_html_e('انتخاب رشته ورزشی', 'rame-gym'); ?></label></th>
                <td>
                    <select name="discipline_id" id="discipline_id">
                        <option value=""><?php esc_html_e('انتخاب کنید...', 'rame-gym'); ?></option>
                        <?php
                        $disciplines = get_posts(['post_type' => 'sports_discipline', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                        foreach ($disciplines as $discipline) {
                            $price = get_post_meta($discipline->ID, '_price', true);
                            echo '<option value="' . esc_attr($discipline->ID) . '" data-price="' . esc_attr($price) . '">' . esc_html($discipline->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="membership_duration"><?php esc_html_e('مدت عضویت (ماه)', 'rame-gym'); ?></label></th>
                <td>
                    <select name="membership_duration" id="membership_duration">
                        <option value="1">۱ ماهه</option>
                        <option value="3" selected>۳ ماهه</option>
                        <option value="6">۶ ماهه</option>
                        <option value="12">۱۲ ماهه</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="total_amount"><?php esc_html_e('مبلغ کل (تومان)', 'rame-gym'); ?></label></th>
                <td>
                    <input type="number" name="total_amount" id="total_amount" class="regular-text"
                           placeholder="<?php esc_attr_e('مبلغ با انتخاب رشته تکمیل می‌شود', 'rame-gym'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="payment_type"><?php esc_html_e('نوع پرداخت', 'rame-gym'); ?></label></th>
                <td>
                    <select name="payment_type" id="payment_type">
                        <option value="full"><?php esc_html_e('نقدی / کامل', 'rame-gym'); ?></option>
                        <option value="installments"><?php esc_html_e('اقساطی', 'rame-gym'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="installment_count_row" style="display: none;">
                <th><label for="installment_count"><?php esc_html_e('تعداد اقساط', 'rame-gym'); ?></label></th>
                <td>
                    <input type="number" name="installment_count" id="installment_count" class="regular-text" min="2"
                           max="12" value="2">
                </td>
            </tr>
        </table>
        <?php wp_nonce_field('save_rame_gym_membership', 'rame_gym_membership_nonce'); ?>
    </div>
</div>

<?php
global $wpdb;
$memberships_table = $wpdb->prefix . 'gym_memberships';
$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $memberships_table WHERE user_id = %d ORDER BY start_date DESC",
    $user->ID
));

if (!empty($history)):
    ?>
    <div class="postbox">
        <h3 class="hndle"><?php esc_html_e('تاریخچه عضویت‌ها', 'rame-gym'); ?></h3>
        <div class="inside">
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php esc_html_e('رشته ورزشی', 'rame-gym'); ?></th>
                    <th><?php esc_html_e('تاریخ شروع', 'rame-gym'); ?></th>
                    <th><?php esc_html_e('تاریخ انقضا', 'rame-gym'); ?></th>
                    <th><?php esc_html_e('مبلغ', 'rame-gym'); ?></th>
                    <th><?php esc_html_e('وضعیت', 'rame-gym'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $membership): ?>
                    <tr>
                        <td><?php echo esc_html(get_the_title($membership->discipline_id)); ?></td>
                        <td><?php echo esc_html(Jalalian::fromDateTime($membership->start_date)->format('Y/m/d')); ?></td>
                        <td><?php echo esc_html(Jalalian::fromDateTime($membership->end_date)->format('Y/m/d')); ?></td>
                        <td><?php echo number_format($membership->total_amount); ?> تومان</td>
                        <td>
                            <span class="status-badge <?php echo esc_attr($membership->status); ?>">
                                <?php echo esc_html(\GymManagement\Controllers\MembershipController::get_status_label($membership->status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
    // This small, view-specific script is best kept here for maintainability.
    jQuery(document).ready(function ($) {
        function calculatePrice() {
            var price = $('#discipline_id').find(':selected').data('price') || 0;
            var duration = parseInt($('#membership_duration').val()) || 1;
            $('#total_amount').val(price * duration);
        }

        $('#discipline_id, #membership_duration').on('change', function () {
            calculatePrice();
        });

        $('#payment_type').on('change', function () {
            if ($(this).val() === 'installments') {
                $('#installment_count_row').show();
            } else {
                $('#installment_count_row').hide();
            }
        }).trigger('change');
    });
</script>