<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">گزارشات مالی</h1>
    <hr class="wp-header-end">

    <div class="dashboard-charts">
        <div class="chart-box">
            <h2>گزارش سود و زیان</h2>
            <canvas id="profit-loss-chart"></canvas>
        </div>
        <div class="chart-box">
            <h2>درآمد بر اساس رشته ورزشی</h2>
            <canvas id="discipline-income-chart"></canvas>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function ($) {
        var reportData = {
            'action': 'my_gym_get_financial_reports',
            'security': '<?php echo wp_create_nonce('my-gym-security-nonce'); ?>'
        };

        $.post(ajaxurl, reportData, function (response) {
            if (response.success) {
                renderProfitLossChart(response.data.profit_and_loss);
                renderDisciplineIncomeChart(response.data.discipline_income);
            }
        });

        function renderProfitLossChart(data) {
            var labels = data.map(item => item.year + '-' + item.month);
            var income = data.map(item => item.income);
            var expense = data.map(item => item.expense);

            var ctx = document.getElementById('profit-loss-chart').getContext('2d');
            new Chart(ctx, {
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

        function renderDisciplineIncomeChart(data) {
            var labels = data.map(item => item.label);
            var incomes = data.map(item => item.income);

            var ctx = document.getElementById('discipline-income-chart').getContext('2d');
            new Chart(ctx, {
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
</script>