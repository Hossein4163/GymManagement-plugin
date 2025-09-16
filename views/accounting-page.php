<?php
// این بخش باید از کنترلر دریافت شود
$transactions = [
    (object)['id' => 1, 'user_id' => 10, 'user_name' => 'علی احمدی', 'amount' => 500000, 'type' => 'دریافت', 'payment_type' => 'کارت‌خوان', 'date' => '2023-10-01 10:30:00'],
    (object)['id' => 2, 'user_id' => 15, 'user_name' => 'زهرا کریمی', 'amount' => 600000, 'type' => 'دریافت', 'payment_type' => 'آنلاین', 'date' => '2023-10-02 11:45:00'],
    (object)['id' => 3, 'user_id' => 20, 'user_name' => 'مدیریت', 'amount' => 100000, 'type' => 'هزینه', 'payment_type' => 'نقدی', 'description' => 'خرید لوازم نظافت', 'date' => '2023-10-03 15:00:00'],
];
$total_income = 1100000;
$total_expense = 100000;
?>

<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">حسابداری باشگاه</h1>
    <hr class="wp-header-end">

    <div class="postbox">
        <h2 class="hndle">ثبت تراکنش جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="transaction_type">نوع تراکنش</label></th>
                        <td>
                            <select name="transaction_type" id="transaction_type">
                                <option value="income">درآمد</option>
                                <option value="expense">هزینه</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="amount">مبلغ (تومان)</label></th>
                        <td><input type="number" name="amount" id="amount" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="payment_method">روش پرداخت</label></th>
                        <td>
                            <select name="payment_method" id="payment_method">
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
                    <input type="submit" name="submit_transaction" id="submit" class="button button-primary"
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
        <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?php echo esc_html($transaction->id); ?></td>
                <td><?php echo esc_html($transaction->user_name ?? 'نامشخص'); ?></td>
                <td><?php echo number_format($transaction->amount); ?> تومان</td>
                <td><?php echo esc_html($transaction->type); ?></td>
                <td><?php echo esc_html($transaction->payment_type); ?></td>
                <td><?php echo esc_html($transaction->description ?? '-'); ?></td>
                <td><?php echo esc_html($transaction->date); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>