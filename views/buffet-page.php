<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">مدیریت بوفه</h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <h2>لیست محصولات</h2>
    <?php
    $products = get_posts(array('post_type' => 'buffet_product', 'numberposts' => -1));
    ?>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th>عنوان</th>
            <th>موجودی</th>
            <th>قیمت (تومان)</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($products)) : ?>
            <?php foreach ($products as $product) : ?>
                <?php $stock = get_post_meta($product->ID, 'stock', true);
                $price = get_post_meta($product->ID, 'price', true); ?>
                <tr>
                    <td><?php echo esc_html($product->post_title); ?></td>
                    <td><?php echo esc_html($stock ?: '-'); ?></td>
                    <td><?php echo number_format(floatval($price), 0); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="3">هیچ محصولی وجود ندارد.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="postbox">
        <h2 class="hndle">ثبت فروش جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <?php wp_nonce_field('my_gym_buffet_sale_nonce', 'my_gym_buffet_sale_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="product_id">محصول</label></th>
                        <td>
                            <select name="product_id" id="product_id" required class="select2-searchable">
                                <option value="">انتخاب محصول</option>
                                <?php foreach ($products as $product) : ?>
                                    <option
                                        value="<?php echo esc_attr($product->ID); ?>"><?php echo esc_html($product->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="quantity">تعداد</label></th>
                        <td><input type="number" name="quantity" id="quantity" value="1" min="1" required
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="sale_price">مبلغ فروش (تومان)</label></th>
                        <td>
                            <input type="number" name="sale_price" id="sale_price" readonly required
                                   class="regular-text" min="0" step="1">
                            <p id="formatted-sale-price-text" class="description"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="customer_id">مشتری</label></th>
                        <td>
                            <?php $users = get_users(array('role__in' => array('subscriber'))); ?>
                            <select name="customer_id" id="customer_id" required>
                                <option value="">انتخاب مشتری</option>
                                <?php foreach ($users as $user) : ?>
                                    <option
                                        value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_sale" class="button button-primary" value="ثبت فروش">
                </p>
            </form>
        </div>
    </div>

    <!-- باقی گزارش فروش مانند قبل... -->

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function updateSalePrice() {
                var productId = $('#product_id').val();
                var quantity = parseInt($('#quantity').val()) || 1;
                if (productId) {
                    $.post(my_gym_vars.ajax_url, {
                        action: 'my_gym_get_product_price',
                        product_id: productId,
                        security: my_gym_vars.security_nonce
                    }, function (response) {
                        if (response.success) {
                            var total = response.data.price * quantity;
                            $('#sale_price').val(total);
                            $('#formatted-sale-price-text').text(new Intl.NumberFormat('fa-IR').format(total) + ' تومان');
                        }
                    });
                } else {
                    $('#sale_price').val(0);
                    $('#formatted-sale-price-text').text('');
                }
            }

            $('#product_id, #quantity').on('change', updateSalePrice);
        });
    </script>

    <h2>گزارش فروش بوفه</h2>
    <?php
    $sales = get_posts(array('post_type' => 'buffet_sale', 'numberposts' => 10, 'orderby' => 'date', 'order' => 'DESC'));
    ?>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th>محصول</th>
            <th>تعداد</th>
            <th>مبلغ</th>
            <th>مشتری</th>
            <th>تاریخ</th>
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
                    <td><?php echo esc_html($product_title ?: 'نامشخص'); ?></td>
                    <td><?php echo esc_html($quantity); ?></td>
                    <td><?php echo number_format(floatval($price), 2); ?> تومان</td>
                    <td><?php echo esc_html($customer_name ?: 'نامشخص'); ?></td>
                    <td><?php echo esc_html($sale->post_date); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="5">هیچ فروشی ثبت نشده است.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>