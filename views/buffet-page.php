<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">مدیریت بوفه</h1>
    <hr class="wp-header-end">

    <div class="postbox">
        <h2 class="hndle">ثبت فروش جدید</h2>
        <div class="inside">
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="product_id">محصول</label></th>
                        <td>
                            <?php
                            $products = get_posts(array('post_type' => 'buffet_product', 'numberposts' => -1));
                            ?>
                            <select name="product_id" id="product_id">
                                <option value="">انتخاب محصول</option>
                                <?php
                                foreach ($products as $product) {
                                    echo '<option value="' . esc_attr($product->ID) . '">' . esc_html($product->post_title) . '</option>';
                                }
                                ?>
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
                        <td><input type="number" name="sale_price" id="sale_price" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="customer_name">نام مشتری</label></th>
                        <td><input type="text" name="customer_name" id="customer_name" class="regular-text"></td>
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
    // نمایش گزارشات فروش (فرضی)
    $sales = get_posts(array('post_type' => 'buffet_sale', 'numberposts' => 10));
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
        <?php foreach ($sales as $sale): ?>
            <?php
            $product_id = get_post_meta($sale->ID, 'product_id', true);
            $quantity = get_post_meta($sale->ID, 'quantity', true);
            $price = get_post_meta($sale->ID, 'price', true);
            $customer_name = get_post_meta($sale->ID, 'customer_name', true);
            $product_title = get_the_title($product_id);
            ?>
            <tr>
                <td><?php echo esc_html($product_title); ?></td>
                <td><?php echo esc_html($quantity); ?></td>
                <td><?php echo number_format($price); ?> تومان</td>
                <td><?php echo esc_html($customer_name ?: 'نامشخص'); ?></td>
                <td><?php echo esc_html($sale->post_date); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>