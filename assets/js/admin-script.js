(function ($) {
    'use strict';

    $(document).ready(function () {

        // --- General Helper Functions ---
        function formatNumber(number) {
            if (typeof number === 'undefined' || number === null) return '0';
            return new Intl.NumberFormat('fa-IR').format(number);
        }

        // --- Dashboard Page Logic ---
        if ($('#monthly-chart').length) {
            loadDashboardData();
        }

        function loadDashboardData() {
            $.post(my_gym_vars.ajax_url, {
                action: 'my_gym_get_dashboard_data',
                security: my_gym_vars.security_nonce
            }, function (response) {
                if (response.success) {
                    $('#total-income').text(formatNumber(response.data.income) + ' تومان');
                    $('#total-expense').text(formatNumber(response.data.expense) + ' تومان');
                    $('#overdue-installments').text(formatNumber(response.data.overdue_installments));
                    $('#total-members').text(formatNumber(response.data.active_members));
                    renderMainCharts(response.data.monthly_data, response.data.disciplines_data);
                }
            });
        }

        function renderMainCharts(monthlyData, disciplinesData) {
            // Monthly Chart
            const monthlyCtx = document.getElementById('monthly-chart');
            if (monthlyCtx && monthlyData) {
                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.labels,
                        datasets: [
                            {label: 'درآمد', data: monthlyData.income, backgroundColor: '#2ecc71', borderRadius: 4},
                            {label: 'هزینه', data: monthlyData.expense, backgroundColor: '#e74c3c', borderRadius: 4}
                        ]
                    },
                    options: {responsive: true, scales: {y: {beginAtZero: true}}}
                });
            }
            // Disciplines Chart
            const disciplinesCtx = document.getElementById('disciplines-chart');
            if (disciplinesCtx && disciplinesData) {
                new Chart(disciplinesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: disciplinesData.labels,
                        datasets: [{
                            data: disciplinesData.counts,
                            backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6', '#1abc9c']
                        }]
                    },
                    options: {responsive: true, maintainAspectRatio: false}
                });
            }
        }

        // --- Shared Select2 Logic for User Search ---
        const select2UserSearchOptions = {
            ajax: {
                url: my_gym_vars.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {q: params.term || '', action: 'my_gym_get_users', security: my_gym_vars.security_nonce};
                },
                processResults: function (data) {
                    if (data.success) return data.data;
                    return {results: []};
                }
            },
            minimumInputLength: 2
        };

        // --- Buffet Page Logic ---
        if ($('#buffet-sale-form').length) {
            $('#customer_id_select2').select2({
                ...select2UserSearchOptions,
                placeholder: 'جستجوی مشتری...',
                allowClear: true
            });
            $('#category_filter').on('change', () => loadProductsByCategory($('#category_filter').val())).trigger('change');
            $(document).on('input', '#products-table .quantity-input', updateBuffetTotal);
            $('#customer_id_select2').on('change', updateBuffetTotal);
        }

        function loadProductsByCategory(categoryId) {
            // AJAX logic to load products
        }

        function updateBuffetTotal() {
            // Logic to calculate total price and enable/disable submit button
        }

        // --- Accounting Page Logic ---
        if ($('#user_id_select2').length) {
            $('#user_id_select2').select2({
                ...select2UserSearchOptions,
                placeholder: 'جستجوی عضو...',
                allowClear: true
            });
        }

        // --- Reports Page Logic ---
        if ($('#report-filter-form').length) {
            $('#filter_user_id').select2({...select2UserSearchOptions, placeholder: 'همه اعضا', allowClear: true});
            // Report chart rendering logic here
        }

        // --- SMS Page Logic ---
        $('#recipient_group').on('change', function () {
            $('#manual_numbers_row').toggle($(this).val() === 'manual');
        }).trigger('change');

        // --- User Profile Membership Logic ---
        if ($('#rame-gym-member-details').length) {
            function calculatePrice() {
                const price = $('#discipline_id').find(':selected').data('price') || 0;
                const duration = parseInt($('#membership_duration').val()) || 1;
                $('#total_amount').val(price * duration);
            }

            $('#discipline_id, #membership_duration').on('change', calculatePrice);

            $('#payment_type').on('change', function () {
                $('#installment_count_row').toggle($(this).val() === 'installments');
            }).trigger('change');

            calculatePrice();
        }

        // --- Pay Installment Button (User Profile) ---
        $(document).on('click', '.pay-installment-btn', function (e) {
            e.preventDefault();
            const button = $(this);
            const installmentId = button.data('id');
            button.text('در حال ثبت...').prop('disabled', true);

            $.post(my_gym_vars.ajax_url, {
                action: 'my_gym_pay_installment',
                security: my_gym_vars.security_nonce,
                installment_id: installmentId
            })
                .done(function (response) {
                    if (response.success) {
                        button.closest('td').html('<span class="status-badge paid">پرداخت شده</span>');
                    } else {
                        alert(response.data.message || 'خطا در ثبت پرداخت.');
                        button.text('ثبت پرداخت').prop('disabled', false);
                    }
                })
                .fail(function () {
                    alert('خطای سرور.');
                    button.text('ثبت پرداخت').prop('disabled', false);
                });
        });

    });
})(jQuery);