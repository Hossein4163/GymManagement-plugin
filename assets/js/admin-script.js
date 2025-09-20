jQuery(document).ready(function ($) {
    // تابع کمکی برای فرمت مبلغ به صورت سه رقم سه رقم
    function formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    }

    // مدیریت فرمت مبلغ برای فیلد حسابداری
    $('#amount').on('input', function () {
        let value = $(this).val();
        let cleanValue = value.replace(/[^\d]/g, '');

        if (cleanValue) {
            let formattedValue = formatNumber(cleanValue);
            $('#formatted-amount-text').text(formattedValue + ' تومان');
        } else {
            $('#formatted-amount-text').text('');
        }
    });

    // مدیریت فرمت مبلغ برای فیلد بوفه
    $('#sale_price').on('input', function () {
        let value = $(this).val();
        let cleanValue = value.replace(/[^\d]/g, '');

        if (cleanValue) {
            let formattedValue = formatNumber(cleanValue);
            $('#formatted-sale-price-text').text(formattedValue + ' تومان');
        } else {
            $('#formatted-sale-price-text').text('');
        }
    });

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
            'security': my_gym_vars.security_nonce,
            'installment_id': installmentId
        };

        button.text('در حال ثبت...');

        $.post(my_gym_vars.ajax_url, data, function (response) {
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

    // تابع اصلی برای بارگذاری داشبورد
    function loadDashboardData() {
        var dashboardData = {
            'action': 'my_gym_get_dashboard_data',
            'security': my_gym_vars.security_nonce
        };

        $.post(my_gym_vars.ajax_url, dashboardData, function (response) {
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
    }

    if ($('#monthly-chart').length) {
        loadDashboardData();
    }

    // تابع اصلی برای بارگذاری گزارشات
    function loadReportData(startDate, endDate) {
        var reportData = {
            'action': 'my_gym_get_financial_reports',
            'security': my_gym_vars.security_nonce,
            'start_date': startDate,
            'end_date': endDate
        };

        $.post(my_gym_vars.ajax_url, reportData, function (response) {
            if (response.success) {
                // پاک کردن نمودارهای قبلی
                if (window.profitLossChart) {
                    window.profitLossChart.destroy();
                }
                if (window.disciplineIncomeChart) {
                    window.disciplineIncomeChart.destroy();
                }

                renderProfitLossChart(response.data.profit_and_loss);
                renderDisciplineIncomeChart(response.data.discipline_income);
            }
        });
    }

    // هندلر فرم فیلتر گزارش
    $('#report-filter-form').on('submit', function (e) {
        e.preventDefault();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        loadReportData(startDate, endDate);
    });

    // تابع رندر نمودار سود و زیان
    function renderProfitLossChart(data) {
        var labels = data.map(item => item.year + '-' + item.month);
        var income = data.map(item => item.income);
        var expense = data.map(item => item.expense);

        var ctx = document.getElementById('profit-loss-chart').getContext('2d');
        window.profitLossChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {label: 'درآمد', data: income, borderColor: '#28a745', fill: false},
                    {label: 'هزینه', data: expense, borderColor: '#dc3545', fill: false}
                ]
            },
            options: {responsive: true, scales: {y: {beginAtZero: true}}}
        });
    }

    // تابع رندر نمودار درآمد بر اساس رشته
    function renderDisciplineIncomeChart(data) {
        var labels = data.map(item => item.label);
        var incomes = data.map(item => item.income);

        var ctx = document.getElementById('discipline-income-chart').getContext('2d');
        window.disciplineIncomeChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: incomes,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
                }]
            },
            options: {responsive: true}
        });
    }
});