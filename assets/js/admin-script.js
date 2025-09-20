jQuery(document).ready(function ($) {
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

    $('#submit_transaction').on('click', function (e) {
        var amount = $('#amount').val();
        if (amount <= 0 || amount === '') {
            alert('لطفا مبلغ معتبری وارد کنید.');
            e.preventDefault();
        }
    });

    $('.pay-installment-btn').on('click', function (e) {
        e.preventDefault();
        var button = $(this);
        var installmentId = button.data('id');
        var data = {
            'action': 'my_gym_pay_installment',
            'security': my_gym_security_nonce,
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

    var dashboardData = {
        'action': 'my_gym_get_dashboard_data',
        'security': my_gym_security_nonce
    };

    $.post(ajaxurl, dashboardData, function (response) {
        if (response.success) {
            $('#total-income').text(response.data.income.toLocaleString() + ' تومان');
            $('#total-expense').text(response.data.expense.toLocaleString() + ' تومان');
            $('#overdue-installments').text(response.data.overdue_installments);
            $('#total-members').text(response.data.total_members);

            renderCharts(response.data.monthly_data, response.data.disciplines_data);
        } else {
            console.error('Failed to load dashboard data: ', response.data);
        }
    }).fail(function () {
        console.error('Ajax request failed.');
    });

    function renderCharts(monthlyData, disciplinesData) {
        var monthlyCtx = document.getElementById('monthly-chart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'درآمد',
                    data: monthlyData.income,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'هزینه',
                    data: monthlyData.expense,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {beginAtZero: true}
                }
            }
        });

        var disciplinesCtx = document.getElementById('disciplines-chart').getContext('2d');
        new Chart(disciplinesCtx, {
            type: 'doughnut',
            data: {
                labels: disciplinesData.labels,
                datasets: [{
                    data: disciplinesData.counts,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
                }]
            },
            options: {responsive: true}
        });
    }

    $('#report-filter-form').on('submit', function (e) {
        e.preventDefault();
        var reportData = {
            'action': 'my_gym_get_financial_reports',
            'security': my_gym_security_nonce,
            'start_date': $('#start_date').val(),
            'end_date': $('#end_date').val()
        };

        $.post(ajaxurl, reportData, function (response) {
            if (response.success) {
                renderProfitLossChart(response.data.profit_and_loss);
                renderDisciplineIncomeChart(response.data.discipline_income);
            }
        });
    });
});