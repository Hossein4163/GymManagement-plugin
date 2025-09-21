<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php _e('مدیریت بوفه', 'rame-gym'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=buffet_product'); ?>" class="page-title-action"><?php _e('افزودن محصول جدید', 'rame-gym'); ?></a>
    <a href="<?php echo admin_url('edit-tags.php?taxonomy=buffet_category&post_type=buffet_product'); ?>" class="page-title-action"><?php _e('مدیریت دسته‌بندی‌ها', 'rame-gym'); ?></a>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle"><?php _e('ثبت فروش جدید', 'rame-gym'); ?></h2>
        <div class="inside">
            <form action="" method="post" id="buffet-sale-form">
                <?php wp_nonce_field('my_gym_buffet_sale_nonce', 'my_gym_buffet_sale_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="customer_id"><?php _e('مشتری', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="customer_id" id="customer_id" required style="width: 300px;">
                                <option value=""><?php _e('انتخاب مشتری...', 'rame-gym'); ?></option>
                            </select>
                            <p class="description"><?php _e('نام یا ایمیل مشتری را تایپ کنید', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="category_filter"><?php _e('فیلتر بر اساس دسته‌بندی', 'rame-gym'); ?></label></th>
                        <td>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'buffet_category',
                                'hide_empty' => false,
                            ));
                            ?>
                            <select id="category_filter" style="width: 300px;">
                                <option value=""><?php _e('همه محصولات', 'rame-gym'); ?></option>
                                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>">
                                            <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="description"><?php _e('برای یافتن سریع‌تر محصولات، دسته‌بندی را انتخاب کنید', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                </table>

                <div id="products-loading" style="display: none; text-align: center; padding: 20px;">
                    <p><?php _e('در حال بارگیری محصولات...', 'rame-gym'); ?></p>
                </div>

                <div id="products-container">
                    <?php
                    $products = get_posts(array(
                        'post_type' => 'buffet_product',
                        'numberposts' => -1,
                        'post_status' => 'publish'
                    ));
                    ?>
                    <table class="widefat fixed striped" id="products-table">
                        <thead>
                        <tr>
                            <th style="width: 25%;"><?php _e('محصول', 'rame-gym'); ?></th>
                            <th style="width: 20%;"><?php _e('دسته‌بندی', 'rame-gym'); ?></th>
                            <th style="width: 15%;"><?php _e('موجودی', 'rame-gym'); ?></th>
                            <th style="width: 15%;"><?php _e('قیمت (تومان)', 'rame-gym'); ?></th>
                            <th style="width: 25%;"><?php _e('تعداد خرید', 'rame-gym'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($products)) : ?>
                            <?php foreach ($products as $product) : ?>
                                <?php 
                                $stock = get_post_meta($product->ID, 'stock', true);
                                $price = get_post_meta($product->ID, 'price', true);
                                $categories = wp_get_post_terms($product->ID, 'buffet_category');
                                $category_names = array();
                                foreach ($categories as $cat) {
                                    $category_names[] = $cat->name;
                                }
                                ?>
                                <tr class="product-row" data-product-id="<?php echo esc_attr($product->ID); ?>" 
                                    data-price="<?php echo esc_attr($price); ?>" 
                                    data-stock="<?php echo esc_attr($stock !== '' ? $stock : '999999'); ?>"
                                    data-product-name="<?php echo esc_attr($product->post_title); ?>">
                                    <td class="column-title">
                                        <strong><?php echo esc_html($product->post_title); ?></strong>
                                    </td>
                                    <td class="column-category">
                                        <?php echo esc_html(implode(', ', $category_names) ?: '-'); ?>
                                    </td>
                                    <td class="column-stock">
                                        <span class="stock-display"><?php echo $stock !== '' ? intval($stock) : '-'; ?></span>
                                    </td>
                                    <td class="column-price">
                                        <?php echo number_format(floatval($price), 0); ?>
                                    </td>
                                    <td class="column-quantity">
                                        <input type="number" 
                                               name="quantities[<?php echo esc_attr($product->ID); ?>]" 
                                               class="quantity-input regular-text" 
                                               min="0" 
                                               max="<?php echo esc_attr($stock !== '' ? $stock : '999999'); ?>" 
                                               value="0"
                                               data-product-id="<?php echo esc_attr($product->ID); ?>"
                                               style="width: 80px;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5"><?php _e('هیچ محصولی وجود ندارد.', 'rame-gym'); ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="sale-summary" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; display: none;">
                    <h4><?php _e('خلاصه فروش', 'rame-gym'); ?></h4>
                    <div id="selected-items"></div>
                    <div style="margin-top: 10px; font-size: 16px; font-weight: bold;">
                        <?php _e('جمع کل:', 'rame-gym'); ?> <span id="total-price">0</span> <?php _e('تومان', 'rame-gym'); ?>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="submit_sale" id="submit_sale" class="button button-primary" value="<?php _e('ثبت فروش', 'rame-gym'); ?>" disabled>
                    <span id="submit-help" style="margin-left: 10px; color: #666;"><?php _e('ابتدا مشتری و محصولات را انتخاب کنید', 'rame-gym'); ?></span>
                </p>
            </form>
        </div>
    </div>

    <h2><?php _e('گزارش فروش بوفه (10 مورد اخیر)', 'rame-gym'); ?></h2>
    <?php
    $sales = get_posts(array(
        'post_type' => 'buffet_sale',
        'numberposts' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    ?>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th><?php _e('محصول', 'rame-gym'); ?></th>
            <th><?php _e('تعداد', 'rame-gym'); ?></th>
            <th><?php _e('مبلغ', 'rame-gym'); ?></th>
            <th><?php _e('مشتری', 'rame-gym'); ?></th>
            <th><?php _e('تاریخ', 'rame-gym'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($sales)) : ?>
            <?php foreach ($sales as $sale): ?>
                <?php
                $product_id = get_post_meta($sale->ID, 'product_id', true);
                $quantity = get_post_meta($sale->ID, 'quantity', true);
                $price = get_post_meta($sale->ID, 'price', true);
                $customer_name = get_post_meta($sale->ID, 'customer_name', true);
                $product_title = get_the_title($product_id);
                ?>
                <tr>
                    <td><?php echo esc_html($product_title ?: __('نامشخص', 'rame-gym')); ?></td>
                    <td><?php echo esc_html($quantity); ?></td>
                    <td><?php echo number_format(floatval($price), 0); ?> <?php _e('تومان', 'rame-gym'); ?></td>
                    <td><?php echo esc_html($customer_name ?: __('نامشخص', 'rame-gym')); ?></td>
                    <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($sale->post_date))); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="5"><?php _e('هیچ فروشی ثبت نشده است.', 'rame-gym'); ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var totalPrice = 0;
    var selectedItems = {};

    // Initialize Select2 for customer selection
    $('#customer_id').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    action: 'my_gym_get_users',
                    security: my_gym_vars.security_nonce
                };
            },
            processResults: function(data) {
                if (data.success) {
                    return {
                        results: data.data.results,
                        pagination: data.data.pagination
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        placeholder: '<?php _e('انتخاب مشتری...', 'rame-gym'); ?>',
        minimumInputLength: 2,
        language: {
            inputTooShort: function() {
                return '<?php _e('حداقل 2 کاراکتر تایپ کنید', 'rame-gym'); ?>';
            },
            searching: function() {
                return '<?php _e('در حال جستجو...', 'rame-gym'); ?>';
            },
            noResults: function() {
                return '<?php _e('کاربری یافت نشد', 'rame-gym'); ?>';
            }
        }
    });

    // Handle category filter change
    $('#category_filter').on('change', function() {
        var categoryId = $(this).val();
        loadProductsByCategory(categoryId);
    });

    function loadProductsByCategory(categoryId) {
        $('#products-loading').show();
        $('#products-container').hide();

        $.post(ajaxurl, {
            action: 'my_gym_load_products_by_category',
            security: my_gym_vars.security_nonce,
            category_id: categoryId || 0
        }, function(response) {
            if (response.success) {
                updateProductsTable(response.data);
            } else {
                alert('خطا در بارگیری محصولات');
            }
        }).always(function() {
            $('#products-loading').hide();
            $('#products-container').show();
        });
    }

    function updateProductsTable(products) {
        var tbody = $('#products-table tbody');
        tbody.empty();

        if (products.length === 0) {
            tbody.append('<tr><td colspan="5"><?php _e('هیچ محصولی وجود ندارد.', 'rame-gym'); ?></td></tr>');
            return;
        }

        products.forEach(function(product) {
            var row = '<tr class="product-row" data-product-id="' + product.id + '" ' +
                     'data-price="' + product.price + '" ' +
                     'data-stock="' + (product.stock || 999999) + '" ' +
                     'data-product-name="' + product.title + '">' +
                     '<td class="column-title"><strong>' + product.title + '</strong></td>' +
                     '<td class="column-category">-</td>' +
                     '<td class="column-stock"><span class="stock-display">' + product.stock_display + '</span></td>' +
                     '<td class="column-price">' + formatNumber(product.price) + '</td>' +
                     '<td class="column-quantity">' +
                     '<input type="number" name="quantities[' + product.id + ']" ' +
                     'class="quantity-input regular-text" min="0" max="' + (product.stock || 999999) + '" ' +
                     'value="0" data-product-id="' + product.id + '" style="width: 80px;">' +
                     '</td></tr>';
            tbody.append(row);
        });

        // Reset calculations
        selectedItems = {};
        totalPrice = 0;
        updateSummary();
    }

    // Handle quantity changes
    $(document).on('input', '.quantity-input', function() {
        var productId = $(this).data('product-id');
        var quantity = parseInt($(this).val()) || 0;
        var price = parseFloat($(this).closest('tr').data('price')) || 0;
        var productName = $(this).closest('tr').data('product-name');
        var maxStock = parseInt($(this).attr('max'));

        // Validate stock
        if (quantity > maxStock) {
            $(this).val(maxStock);
            quantity = maxStock;
            alert('تعداد وارد شده بیشتر از موجودی است');
        }

        // Update selected items
        if (quantity > 0) {
            selectedItems[productId] = {
                name: productName,
                quantity: quantity,
                price: price,
                total: price * quantity
            };
        } else {
            delete selectedItems[productId];
        }

        updateSummary();
    });

    function updateSummary() {
        var itemsHtml = '';
        totalPrice = 0;

        Object.keys(selectedItems).forEach(function(productId) {
            var item = selectedItems[productId];
            totalPrice += item.total;
            itemsHtml += '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                        '<span>' + item.name + ' × ' + item.quantity + '</span>' +
                        '<span>' + formatNumber(item.total) + ' تومان</span>' +
                        '</div>';
        });

        $('#selected-items').html(itemsHtml);
        $('#total-price').text(formatNumber(totalPrice));

        // Show/hide summary and enable/disable submit button
        var hasItems = Object.keys(selectedItems).length > 0;
        var hasCustomer = $('#customer_id').val() !== '';
        
        if (hasItems) {
            $('#sale-summary').show();
        } else {
            $('#sale-summary').hide();
        }

        if (hasItems && hasCustomer) {
            $('#submit_sale').prop('disabled', false);
            $('#submit-help').text('آماده ثبت فروش');
        } else {
            $('#submit_sale').prop('disabled', true);
            if (!hasCustomer && !hasItems) {
                $('#submit-help').text('ابتدا مشتری و محصولات را انتخاب کنید');
            } else if (!hasCustomer) {
                $('#submit-help').text('ابتدا مشتری را انتخاب کنید');
            } else {
                $('#submit-help').text('حداقل یک محصول انتخاب کنید');
            }
        }
    }

    // Handle customer selection
    $('#customer_id').on('change', function() {
        updateSummary();
    });

    // Format numbers
    function formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    }

    // Form validation
    $('#buffet-sale-form').on('submit', function(e) {
        var customerId = $('#customer_id').val();
        var hasItems = Object.keys(selectedItems).length > 0;

        if (!customerId) {
            alert('لطفا مشتری را انتخاب کنید');
            e.preventDefault();
            return false;
        }

        if (!hasItems) {
            alert('لطفا حداقل یک محصول انتخاب کنید');
            e.preventDefault();
            return false;
        }

        if (totalPrice <= 0) {
            alert('مبلغ کل باید بیشتر از صفر باشد');
            e.preventDefault();
            return false;
        }

        return confirm('آیا از ثبت این فروش اطمینان دارید؟\nجمع کل: ' + formatNumber(totalPrice) + ' تومان');
    });
});
</script>