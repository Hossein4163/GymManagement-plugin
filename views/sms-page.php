<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">ارسال پیامک</h1>
    <hr class="wp-header-end">

    <div class="postbox">
        <h2 class="hndle">ارسال پیامک جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="recipient_group">گروه گیرندگان</label></th>
                        <td>
                            <select name="recipient_group" id="recipient_group">
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
                                      placeholder="هر شماره را در یک خط جداگانه وارد کنید"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="message_text">متن پیامک</label></th>
                        <td>
                            <textarea name="message_text" id="message_text" rows="5" cols="50" required></textarea>
                            <p class="description">متغیرهای قابل استفاده: {name}, {discipline}, {amount}</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_sms" class="button button-primary" value="ارسال پیامک">
                </p>
            </form>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        var recipientGroup = $('#recipient_group');
        var manualNumbersRow = $('#manual_numbers_row');

        recipientGroup.on('change', function () {
            if ($(this).val() === 'manual') {
                manualNumbersRow.show();
            } else {
                manualNumbersRow.hide();
            }
        });
        recipientGroup.trigger('change'); // فعال‌سازی اولیه
    });
</script>