<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">ارسال پیامک</h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle">ارسال پیامک جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <?php wp_nonce_field('my_gym_sms_nonce', 'my_gym_sms_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="recipient_group">گروه گیرندگان</label></th>
                        <td>
                            <select name="recipient_group" id="recipient_group" required>
                                <option value="all">همه اعضا</option>
                                <option value="active">اعضای فعال</option>
                                <option value="inactive">اعضای غیرفعال</option>
                                <option value="manual">شماره‌های دستی</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="manual_numbers_row" style="display: none;">
                        <th><label for="manual_numbers">شماره‌ها</label></th>
                        <td>
                            <textarea name="manual_numbers" id="manual_numbers" rows="5" cols="50"
                                      placeholder="هر شماره را در یک خط جداگانه وارد کنید (مثال: 09123456789)"></textarea>
                            <p class="description">هر شماره موبایل را در یک خط جداگانه وارد کنید. فرمت: 09xxxxxxxxx</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="message_text">متن پیامک</label></th>
                        <td>
                            <textarea name="message_text" id="message_text" rows="5" cols="50" required
                                      placeholder="متن پیامک خود را وارد کنید..."></textarea>
                            <p class="description">متغیرهای قابل استفاده: {name} برای نام، {discipline} برای رشته ورزشی،
                                {amount} برای مبلغ</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_sms" class="button button-primary" value="ارسال پیامک">
                </p>
            </form>
        </div>
    </div>

    <div class="postbox">
        <h2 class="hndle">راهنما</h2>
        <div class="inside">
            <h4>نحوه استفاده از متغیرها:</h4>
            <ul>
                <li><strong>{name}</strong> - نام عضو (مثال: احمد محمدی)</li>
                <li><strong>{discipline}</strong> - رشته ورزشی عضو (مثال: بدنسازی)</li>
                <li><strong>{amount}</strong> - مبلغ مربوط به عضو (مثال: 500,000)</li>
            </ul>
            <h4>مثال:</h4>
            <p><code>سلام {name} عزیز، شهریه {discipline} شما به مبلغ {amount} تومان پرداخت شده است.</code></p>
        </div>
    </div>
</div>