<?php
global $wpdb;
$transactions_table = $wpdb->prefix . 'gym_transactions';
$transactions = $wpdb->get_results("SELECT t.*, u.display_name as user_name FROM $transactions_table t LEFT JOIN $wpdb->users u ON t.user_id = u.ID ORDER BY t.date DESC LIMIT 50");
$total_income = $wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'دریافت' AND MONTH(date) = MONTH(CURRENT_DATE)");
$total_expense = $wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'هزینه' AND MONTH(date) = MONTH(CURRENT_DATE)");
?>

<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">حسابداری باشگاه</h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle">ثبت تراکنش جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <?php wp_nonce_field('my_gym_transaction_nonce', 'my_gym_transaction_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="transaction_type">نوع تراکنش</label></th>
                        <td>
                            <select name="transaction_type" id="transaction_type" required>
                                <option value="income">درآمد</option>
                                <option value="expense">هزینه</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="amount">مبلغ (تومان)</label></th>
                        <td><input type="number" name="amount" id="amount" required class="regular-text" min="0"
                                   step="0.01"></td>
                    </tr>
                    <tr>
                        <th><label for="payment_method">روش پرداخت</label></th>
                        <td>
                            <select name="payment_method" id="payment_method" required>
                                <option value="online">آنلاین</option>
                                <option value="card_reader">کارت‌خوان</option>
                                <option value="cash">نقدی</option>
                                <option value="check">چک</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description">توضیحات</label></th>
                        <td><textarea name="description" id="description" rows="3" cols="50"></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_transaction" id="submit_transaction" class="button button-primary"
                           value="ثبت تراکنش">
                </p>
            </form>
        </div>
    </div>

    <h2>لیست تراکنش‌ها</h2>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th>شناسه</th>
            <th>کاربر</th>
            <th>مبلغ</th>
            <th>نوع</th>
            <th>روش پرداخت</th>
            <th>توضیحات</th>
            <th>تاریخ</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($transactions)) : ?>
            <?php foreach ($transactions as $transaction) : ?>
                <tr>
                    <td><?php echo esc_html($transaction->id); ?></td>
                    <td><?php echo esc_html($transaction->user_name ?: 'نامشخص'); ?></td>
                    <td><?php echo number_format($transaction->amount, 2); ?> تومان</td>
                    <td><?php echo esc_html($transaction->type); ?></td>
                    <td><?php echo esc_html($transaction->payment_type); ?></td>
                    <td><?php echo esc_html($transaction->description ?: '-'); ?></td>
                    <td><?php echo esc_html($transaction->date); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7">هیچ تراکنشی ثبت نشده است.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>