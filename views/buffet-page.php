<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">مدیریت بوفه</h1>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox">
        <h2 class="hndle">ثبت فروش جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <?php wp_nonce_field('my_gym_buffet_sale_nonce', 'my_gym_buffet_sale_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="product_id">محصول</label></th>
                        <td>
                            <?php
                            $products = get_posts(array('post_type' => 'buffet_product', 'numberposts' => -1));
                            ?>
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
                            <input type="number" name="sale_price" id="sale_price" required class="regular-text" min="0"
                                   step="1">
                            <p id="formatted-sale-price-text" class="description"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="customer_id">مشتری</label></th>
                        <td>
                            <?php
                            $users = get_users(array('role__in' => array('subscriber', 'administrator')));
                            ?>
                            <select name="customer_id" id="customer_id" class="select2-searchable">
                                <option value="">انتخاب مشتری</option>
                                <?php foreach ($users as $user) : ?>
                                    <option
                                        value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button" id="add-new-customer-btn">افزودن مشتری جدید</button>
                        </td>
                    </tr>
                    <tr id="new-customer-fields" style="display:none;">
                        <th><label for="new_customer_name">نام مشتری جدید</label></th>
                        <td>
                            <input type="text" name="new_customer_name" id="new_customer_name" class="regular-text">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_sale" class="button button-primary" value="ثبت فروش">
                </p>
            </form>
        </div>
    </div>

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