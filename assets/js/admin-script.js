jQuery(document).ready(function ($) {
    // نمایش یا پنهان کردن فیلدهای اقساطی
    var paymentTypeSelect = $('#payment_type');
    var totalAmountField = $('#total_amount_field');
    var installmentCountField = $('#installment_count_field');

    paymentTypeSelect.on('change', function () {
        if ($(this).val() === 'installments') {
            totalAmountField.show();
            installmentCountField.show();
        } else {
            totalAmountField.hide();
            installmentCountField.hide();
        }
    });

    paymentTypeSelect.trigger('change');

    // اعتبارسنجی ساده فرم قبل از ارسال
    $('#submit_transaction').on('click', function (e) {
        var amount = $('#amount').val();
        if (amount <= 0 || amount === '') {
            alert('لطفا مبلغ معتبری وارد کنید.');
            e.preventDefault();
        }
    });

    // مدیریت AJAX برای ثبت پرداخت قسط
    $('.pay-installment-btn').on('click', function (e) {
        e.preventDefault();
        var button = $(this);
        var installmentId = button.data('id');
        var data = {
            'action': 'my_gym_pay_installment',
            'security': '<?php echo wp_create_nonce('my-gym - security - nonce'); ?>',
            'installment_id': installmentId
        };

        button.text('در حال ثبت...');

        $.post(ajaxurl, data, function (response) {
            if (response.success) {
                alert('پرداخت قسط با موفقیت ثبت شد!');
                location.reload();
            } else {
                alert('خطا در ثبت پرداخت: ' + response.data);
                button.text('ثبت پرداخت');
            }
        }).fail(function () {
            alert('خطای ارتباطی رخ داد.');
            button.text('ثبت پرداخت');
        });
    });

    // نمایش/پنهان کردن فیلدهای شماره دستی در صفحه پیامک
    var recipientGroup = $('#recipient_group');
    var manualNumbersRow = $('#manual_numbers_row');

    recipientGroup.on('change', function () {
        if ($(this).val() === 'manual') {
            manualNumbersRow.show();
        } else {
            manualNumbersRow.hide();
        }
    });
    recipientGroup.trigger('change');
});