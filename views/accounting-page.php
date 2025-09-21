<?php
/**
 * View for the main accounting page.
 * Allows adding new transactions and lists recent ones.
 */
global $wpdb;
$transactions_table = $wpdb->prefix . 'gym_transactions';
$transactions = $wpdb->get_results(
    "SELECT t.*, u.display_name
     FROM {$transactions_table} t
     LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
     ORDER BY t.date DESC
     LIMIT 100"
);
?>

<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('مدیریت حسابداری', 'rame-gym'); ?></h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle"><?php esc_html_e('ثبت تراکنش جدید', 'rame-gym'); ?></h2>
        <div class="inside">
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="rame_gym_add_transaction">
                <?php wp_nonce_field('my_gym_transaction_nonce', 'my_gym_transaction_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="user_id"><?php esc_html_e('کاربر (عضو)', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="user_id" id="user_id_select2" style="width: 300px;"></select>
                            <p class="description"><?php esc_html_e('برای هزینه های عمومی، این فیلد را خالی بگذارید.', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="transaction_type"><?php esc_html_e('نوع تراکنش', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="transaction_type" id="transaction_type" required>
                                <option value="income"><?php esc_html_e('درآمد', 'rame-gym'); ?></option>
                                <option value="expense"><?php esc_html_e('هزینه', 'rame-gym'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="amount"><?php esc_html_e('مبلغ (تومان)', 'rame-gym'); ?></label></th>
                        <td>
                            <input type="number" name="amount" id="amount" required class="regular-text" min="0"
                                   step="1000">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="payment_method"><?php esc_html_e('روش پرداخت', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="payment_method" id="payment_method" required>
                                <option value="card_reader"><?php esc_html_e('کارت‌خوان', 'rame-gym'); ?></option>
                                <option value="cash"><?php esc_html_e('نقدی', 'rame-gym'); ?></option>
                                <option value="online"><?php esc_html_e('آنلاین', 'rame-gym'); ?></option>
                                <option value="transfer"><?php esc_html_e('انتقال بانکی', 'rame-gym'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php esc_html_e('توضیحات', 'rame-gym'); ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="3" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_transaction" id="submit_transaction" class="button button-primary"
                           value="<?php esc_attr_e('ثبت تراکنش', 'rame-gym'); ?>">
                </p>
            </form>
        </div>
    </div>

    <h2 style="margin-top: 30px;"><?php esc_html_e('لیست آخرین تراکنش‌ها', 'rame-gym'); ?></h2>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th><?php esc_html_e('کاربر', 'rame-gym'); ?></th>
            <th><?php esc_html_e('مبلغ', 'rame-gym'); ?></th>
            <th><?php esc_html_e('نوع', 'rame-gym'); ?></th>
            <th><?php esc_html_e('روش پرداخت', 'rame-gym'); ?></th>
            <th><?php esc_html_e('توضیحات', 'rame-gym'); ?></th>
            <th><?php esc_html_e('تاریخ', 'rame-gym'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($transactions)) : ?>
            <?php foreach ($transactions as $transaction) : ?>
                <tr class="<?php echo $transaction->type === 'دریافت' ? 'income-row' : 'expense-row'; ?>">
                    <td><?php echo esc_html($transaction->display_name ?: __('عمومی', 'rame-gym')); ?></td>
                    <td><strong><?php echo number_format($transaction->amount); ?> تومان</strong></td>
                    <td>
                        <span
                            class="transaction-type-badge type-<?php echo sanitize_html_class($transaction->type === 'دریافت' ? 'income' : 'expense'); ?>">
                            <?php echo esc_html($transaction->type); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($transaction->payment_type); ?></td>
                    <td><?php echo esc_html($transaction->description ?: '—'); ?></td>
                    <td><?php echo esc_html(Morilog\Jalali\Jalalian::fromDateTime($transaction->date)->format('Y/m/d H:i')); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6"><?php esc_html_e('هیچ تراکنشی ثبت نشده است.', 'rame-gym'); ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>