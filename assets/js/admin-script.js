jQuery(document).ready(function ($) {
    // افزودن قابلیت تأیید قبل از حذف
    $('.my-gym-delete-btn').on('click', function (e) {
        if (!confirm('آیا از حذف این مورد اطمینان دارید؟')) {
            e.preventDefault();
        }
    });

    // نمایش یا پنهان کردن فیلدهای اضافی بر اساس نوع تراکنش
    var transactionType = $('#transaction_type');
    var userSelectField = $('#user_id_row'); // این فیلد را باید به فرم اضافه کنید

    transactionType.on('change', function () {
        if ($(this).val() === 'income') {
            userSelectField.show();
        } else {
            userSelectField.hide();
        }
    });

    // فعال‌سازی اولیه بر اساس مقدار پیش‌فرض
    transactionType.trigger('change');

    // اعتبارسنجی ساده فرم قبل از ارسال
    $('#submit_transaction').on('click', function (e) {
        var amount = $('#amount').val();
        if (amount <= 0 || amount === '') {
            alert('لطفا مبلغ معتبری وارد کنید.');
            e.preventDefault();
        }
    });

});