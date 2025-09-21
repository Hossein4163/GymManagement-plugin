(function ($) {
    'use strict';

    $(document).ready(function () {
        // تابع کمکی برای فرمت مبلغ به صورت سه رقم سه رقم
        function formatNumber(number) {
            if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
                return new Intl.NumberFormat('fa-IR').format(number);
            } else {
                // Fallback for older browsers
                return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
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

        if (paymentTypeSelect.length) {
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
        }

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

            if (typeof my_gym_vars === 'undefined') {
                alert('خطا در بارگیری اطلاعات');
                return;
            }

            var data = {
                'action': 'my_gym_pay_installment',
                'security': my_gym_vars.security_nonce,
                'installment_id': installmentId
            };

            button.text('در حال ثبت...');

            $.post(my_gym_vars.ajax_url, data, function (response) {
                if (response.success) {
                    button.text('پرداخت شده');
                    button.closest('tr').find('.status-badge').removeClass('pending overdue').addClass('paid').text('پرداخت شده');
                } else {
                    button.text('پرداخت');
                    alert(response.data.message || 'خطا در پردازش');
                }
            }).fail(function () {
                button.text('پرداخت');
                alert('خطا در ارتباط با سرور');
            });
        });

        // Dashboard Data Loading
        function loadDashboardData() {
            if (typeof my_gym_vars === 'undefined') {
                console.warn('my_gym_vars not defined');
                return;
            }

            var dashboardData = {
                'action': 'my_gym_get_dashboard_data',
                'security': my_gym_vars.security_nonce
            };

            $.post(my_gym_vars.ajax_url, dashboardData, function (response) {
                if (response.success) {
                    var data = response.data;

                    // Update dashboard stats
                    $('#total-income').text((data.income || 0).toLocaleString('fa-IR') + ' تومان');
                    $('#total-expense').text((data.expense || 0).toLocaleString('fa-IR') + ' تومان');
                    $('#overdue-installments').text(data.overdue_installments || 0);
                    $('#total-members').text(data.total_members || 0);

                    // Render charts if data exists
                    if (data.monthly_data && data.disciplines_data) {
                        renderCharts(data.monthly_data, data.disciplines_data);
                    }
                } else {
                    console.error('Failed to load dashboard data:', response.data);
                }
            }).fail(function () {
                console.error('Ajax request failed for dashboard data');
            });
        }

        // Render charts function
        function renderCharts(monthlyData, disciplinesData) {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            // Monthly chart
            var monthlyCtx = document.getElementById('monthly-chart');
            if (monthlyCtx) {
                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.labels || [],
                        datasets: [
                            {
                                label: 'درآمد',
                                data: monthlyData.income || [],
                                backgroundColor: '#28a745'
                            },
                            {
                                label: 'هزینه',
                                data: monthlyData.expense || [],
                                backgroundColor: '#dc3545'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'درآمد و هزینه ماهانه'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Disciplines chart
            var disciplinesCtx = document.getElementById('disciplines-chart');
            if (disciplinesCtx && disciplinesData.labels && disciplinesData.labels.length > 0) {
                new Chart(disciplinesCtx, {
                    type: 'pie',
                    data: {
                        labels: disciplinesData.labels,
                        datasets: [{
                            data: disciplinesData.counts || [],
                            backgroundColor: [
                                '#007bff', '#28a745', '#ffc107', '#dc3545',
                                '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'توزیع رشته‌های ورزشی'
                            }
                        }
                    }
                });
            }
        }

        // Load dashboard data if on dashboard page
        if ($('#monthly-chart').length || $('#total-income').length) {
            loadDashboardData();
        }

        // هندلر فرم فیلتر گزارش
        $('#report-filter-form').on('submit', function (e) {
            e.preventDefault();
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            loadReportData(startDate, endDate);
        });

        // تابع بارگیری گزارشات
        function loadReportData(startDate, endDate) {
            if (typeof my_gym_vars === 'undefined') {
                alert('خطا در بارگیری اطلاعات');
                return;
            }

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
            }).fail(function () {
                alert('خطا در بارگیری گزارشات');
            });
        }

        // تابع رندر نمودار سود و زیان
        function renderProfitLossChart(data) {
            if (typeof Chart === 'undefined') return;

            var labels = data.map(item => item.year + '-' + item.month);
            var income = data.map(item => item.income);
            var expense = data.map(item => item.expense);

            var ctx = document.getElementById('profit-loss-chart');
            if (ctx) {
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
        }

        // تابع رندر نمودار درآمد بر اساس رشته
        function renderDisciplineIncomeChart(data) {
            if (typeof Chart === 'undefined') return;

            var labels = data.map(item => item.label);
            var incomes = data.map(item => item.income);

            var ctx = document.getElementById('discipline-income-chart');
            if (ctx) {
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
        }

        // Initialize Select2 for customer selection (Buffet Page)
        if ($('#customer_id').length && typeof $.fn.select2 !== 'undefined') {
            $('#customer_id').select2({
                ajax: {
                    url: typeof my_gym_vars !== 'undefined' ? my_gym_vars.ajax_url : ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            action: 'my_gym_get_users',
                            security: typeof my_gym_vars !== 'undefined' ? my_gym_vars.security_nonce : ''
                        };
                    },
                    processResults: function (data) {
                        if (data.success && data.data && data.data.results) {
                            return {
                                results: data.data.results,
                                pagination: data.data.pagination
                            };
                        }
                        return {results: []};
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'جستجوی مشتری...',
                allowClear: true,
                language: {
                    inputTooShort: function () {
                        return 'حداقل 2 کاراکتر تایپ کنید';
                    },
                    searching: function () {
                        return 'در حال جستجو...';
                    },
                    noResults: function () {
                        return 'کاربری یافت نشد';
                    }
                }
            });
        }

        // Buffet: Category filter change handler
        $('#category_filter').on('change', function () {
            var categoryId = $(this).val();
            if (typeof my_gym_vars !== 'undefined') {
                loadProductsByCategory(categoryId);
            } else {
                var url = new URL(window.location);
                if (categoryId) {
                    url.searchParams.set('category', categoryId);
                } else {
                    url.searchParams.delete('category');
                }
                window.location = url;
            }
        });

        // Load products by category (AJAX)
        function loadProductsByCategory(categoryId) {
            $('#products-loading').show();
            $('#products-container').hide();

            $.post(my_gym_vars.ajax_url, {
                action: 'my_gym_load_products_by_category',
                security: my_gym_vars.security_nonce,
                category_id: categoryId || 0
            }, function (response) {
                if (response.success) {
                    updateProductsTable(response.data);
                } else {
                    alert('خطا در بارگیری محصولات');
                }
            }).always(function () {
                $('#products-loading').hide();
                $('#products-container').show();
            }).fail(function () {
                alert('خطا در ارتباط با سرور');
            });
        }

        // Update products table with new data
        function updateProductsTable(products) {
            var tbody = $('#products-table tbody');
            tbody.empty();

            if (products.length === 0) {
                tbody.append('<tr><td colspan="5">هیچ محصولی وجود ندارد.</td></tr>');
                return;
            }

            products.forEach(function (product) {
                var row = '<tr class="product-row" data-product-id="' + product.id + '" ' +
                    'data-price="' + product.price + '" ' +
                    'data-stock="' + (product.stock || 999999) + '">' +
                    '<td><strong>' + product.title + '</strong></td>' +
                    '<td>' + (product.categories || '-') + '</td>' +
                    '<td>' + product.stock_display + '</td>' +
                    '<td>' + formatNumber(product.price) + '</td>' +
                    '<td>' +
                    '<input type="number" name="quantities[' + product.id + ']" ' +
                    'class="quantity-input regular-text" min="0" max="' + (product.stock || 999999) + '" ' +
                    'value="0" style="width: 80px;">' +
                    '</td></tr>';
                tbody.append(row);
            });

            // Re-initialize quantity change handlers
            initQuantityHandlers();
        }

        // Initialize quantity change handlers
        function initQuantityHandlers() {
            $(document).off('input change', '.quantity-input').on('input change', '.quantity-input', function () {
                updateTotalPrice();
                validateStock($(this));
            });
        }

        // Validate stock levels
        function validateStock($input) {
            var quantity = parseInt($input.val()) || 0;
            var maxStock = parseInt($input.attr('max'));

            if (maxStock < 999999 && quantity > maxStock) {
                $input.val(maxStock);
                alert('تعداد وارد شده بیشتر از موجودی است');
                updateTotalPrice();
            }
        }

        // Update total price calculation
        function updateTotalPrice() {
            let total = 0;
            let hasItems = false;

            $('.quantity-input').each(function () {
                const price = parseFloat($(this).closest('tr').data('price') || 0);
                const quantity = parseInt($(this).val() || 0);

                if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
                    total += price * quantity;
                    hasItems = true;
                }
            });

            $('#total-price, #total_amount').text(formatNumber(total));

            // Enable/disable submit button based on selection
            var hasCustomer = $('#customer_id').val() !== '';
            var $submitBtn = $('#submit_sale, input[name="submit_sale"]');

            if (hasItems && hasCustomer) {
                $submitBtn.prop('disabled', false);
                $('#submit-help').text('آماده ثبت فروش');
            } else {
                $submitBtn.prop('disabled', true);
                if (!hasCustomer && !hasItems) {
                    $('#submit-help').text('ابتدا مشتری و محصولات را انتخاب کنید');
                } else if (!hasCustomer) {
                    $('#submit-help').text('ابتدا مشتری را انتخاب کنید');
                } else {
                    $('#submit-help').text('حداقل یک محصول انتخاب کنید');
                }
            }

            return total;
        }

        // Handle customer selection change
        $('#customer_id').on('change', function () {
            updateTotalPrice();
        });

        // Initialize quantity handlers on page load
        initQuantityHandlers();

        // Initial calculation
        if ($('.quantity-input').length) {
            updateTotalPrice();
        }

        // Form validation for buffet sale
        $('form').on('submit', function (e) {
            if ($(this).find('input[name="submit_sale"]').length) {
                var customerId = $('#customer_id').val();
                var total = updateTotalPrice();

                if (!customerId) {
                    alert('لطفا مشتری را انتخاب کنید');
                    e.preventDefault();
                    return false;
                }

                if (total <= 0) {
                    alert('لطفا حداقل یک محصول انتخاب کنید');
                    e.preventDefault();
                    return false;
                }

                return confirm('آیا از ثبت این فروش اطمینان دارید؟\nجمع کل: ' + formatNumber(total) + ' تومان');
            }
        });

        console.log('Rame Gym Admin Script Loaded Successfully');
    });
})(jQuery);