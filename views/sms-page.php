<?php
/**
 * View for the Send SMS page.
 */
?>
<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('ارسال پیامک', 'rame-gym'); ?></h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle"><?php esc_html_e('ارسال پیامک جدید', 'rame-gym'); ?></h2>
        <div class="inside">
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="rame_gym_send_sms">
                <?php wp_nonce_field('my_gym_sms_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="recipient_group"><?php esc_html_e('گروه گیرندگان', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="recipient_group" id="recipient_group" required>
                                <option value="all"><?php esc_html_e('همه اعضا', 'rame-gym'); ?></option>
                                <option value="active"><?php esc_html_e('اعضای فعال', 'rame-gym'); ?></option>
                                <option value="inactive"><?php esc_html_e('اعضای غیرفعال', 'rame-gym'); ?></option>
                                <option value="manual"><?php esc_html_e('شماره‌های دستی', 'rame-gym'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="manual_numbers_row" style="display: none;">
                        <th><label for="manual_numbers"><?php esc_html_e('شماره‌ها', 'rame-gym'); ?></label></th>
                        <td>
                            <textarea name="manual_numbers" id="manual_numbers" rows="5" class="large-text ltr"
                                      placeholder="<?php esc_attr_e('Each number on a new line, e.g., 09123456789', 'rame-gym'); ?>"></textarea>
                            <p class="description"><?php esc_html_e('هر شماره موبایل را در یک خط جداگانه وارد کنید.', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="message_text"><?php esc_html_e('متن پیامک', 'rame-gym'); ?></label></th>
                        <td>
                            <textarea name="message_text" id="message_text" rows="5" class="large-text" required
                                      placeholder="<?php esc_attr_e('متن پیامک خود را وارد کنید...', 'rame-gym'); ?>"></textarea>
                            <p class="description"><?php esc_html_e('متغیر قابل استفاده: {name} برای نام عضو.', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_sms" class="button button-primary"
                           value="<?php esc_attr_e('ارسال پیامک', 'rame-gym'); ?>">
                </p>
            </form>
        </div>
    </div>
</div>