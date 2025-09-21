<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php _e('مدیریت بوفه', 'rame-gym'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=buffet_product'); ?>"
       class="page-title-action"><?php _e('افزودن محصول جدید', 'rame-gym'); ?></a>
    <?php if (taxonomy_exists('buffet_category')): ?>
        <a href="<?php echo admin_url('edit-tags.php?taxonomy=buffet_category&post_type=buffet_product'); ?>"
           class="page-title-action"><?php _e('مدیریت دسته‌بندی‌ها', 'rame-gym'); ?></a>
    <?php endif; ?>
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
                            <select name="customer_id" id="customer_id" required style="width: 300px;"
                                    class="select2-searchable">
                                <option value=""><?php _e('جستجوی مشتری...', 'rame-gym'); ?></option>
                            </select>
                            <p class="description"><?php _e('نام یا ایمیل مشتری را تایپ کنید', 'rame-gym'); ?></p>
                        </td>
                    </tr>

                    <?php if (taxonomy_exists('buffet_category')): ?>
                        <tr>
                            <th><label for="category_filter"><?php _e('فیلتر بر اساس دسته‌بندی', 'rame-gym'); ?></label>
                            </th>
                            <td>
                                <?php
                                $categories = get_terms(array(
                                    'taxonomy' => 'buffet_category',
                                    'hide_empty' => false,
                                ));
                                $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
                                ?>
                                <select id="category_filter">
                                    <option value=""><?php _e('همه محصولات', 'rame-gym'); ?></option>
                                    <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option
                                                value="<?php echo esc_attr($category->term_id); ?>" <?php selected($selected_category, $category->term_id); ?>>
                                                <?php echo esc_html($category->name); ?>
                                                (<?php echo $category->count; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <h3><?php _e('لیست محصولات', 'rame-gym'); ?></h3>
                <table class="widefat fixed striped" id="products-table">
                    <thead>
                    <tr>
                        <th style="width: 25%;"><?php _e('محصول', 'rame-gym'); ?></th>
                        <?php if (taxonomy_exists('buffet_category')): ?>
                            <th style="width: 20%;"><?php _e('دسته‌بندی', 'rame-gym'); ?></th>
                        <?php endif; ?>
                        <th style="width: 15%;"><?php _e('موجودی', 'rame-gym'); ?></th>
                        <th style="width: 15%;"><?php _e('قیمت (تومان)', 'rame-gym'); ?></th>
                        <th style="width: 25%;"><?php _e('تعداد خرید', 'rame-gym'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $args = array(
                        'post_type' => 'buffet_product',
                        'numberposts' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC'
                    );
                    if (isset($_GET['category']) && $_GET['category'] > 0) {
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => 'buffet_category',
                                'field' => 'term_id',
                                'terms' => intval($_GET['category']),
                            ),
                        );
                    }
                    $products = get_posts($args);
                    if (!empty($products)) : ?>
                        <?php foreach ($products as $product) : ?>
                            <?php
                            $stock = get_post_meta($product->ID, 'stock', true);
                            $price = get_post_meta($product->ID, 'price', true);
                            $price = floatval($price);
                            $categories = wp_get_post_terms($product->ID, 'buffet_category', array('fields' => 'names'));
                            ?>
                            <tr class="product-row" data-product-id="<?php echo esc_attr($product->ID); ?>"
                                data-price="<?php echo esc_attr($price); ?>"
                                data-stock="<?php echo esc_attr($stock !== '' ? $stock : '999999'); ?>">
                                <td>
                                    <strong><?php echo esc_html($product->post_title); ?></strong>
                                    <?php if ($product->post_content): ?>
                                        <br>
                                        <small><?php echo esc_html(wp_trim_words($product->post_content, 10)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <?php if (taxonomy_exists('buffet_category')): ?>
                                    <td><?php echo esc_html(implode(', ', $categories) ?: '-'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <span
                                        style="color: <?php echo ($stock !== '' && intval($stock) > 0) ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $stock !== '' ? intval($stock) : 'نامحدود'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($price, 0); ?></strong></td>
                                <td>
                                    <?php if ($price > 0): ?>
                                        <input type="number"
                                               name="quantities[<?php echo esc_attr($product->ID); ?>]"
                                               min="0"
                                               max="<?php echo esc_attr($stock !== '' ? $stock : '999999'); ?>"
                                               value="0"
                                               class="quantity-input regular-text"
                                               style="width: 80px;">
                                    <?php else: ?>
                                        <span style="color: #dc3545;">قیمت تعریف نشده</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="<?php echo taxonomy_exists('buffet_category') ? '5' : '4'; ?>"><?php _e('هیچ محصولی وجود ندارد.', 'rame-gym'); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($products)): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                        <strong><?php _e('جمع کل:', 'rame-gym'); ?> <span
                                id="total_amount">0</span> <?php _e('تومان', 'rame-gym'); ?></strong>
                    </div>
                    <p class="submit">
                        <input type="submit" name="submit_sale" class="button button-primary"
                               value="<?php _e('ثبت فروش', 'rame-gym'); ?>" id="submit_sale">
                        <span id="submit-help"
                              style="margin-left: 10px; color: #666;"><?php _e('ابتدا مشتری و محصولات را انتخاب کنید', 'rame-gym'); ?></span>
                    </p>
                <?php endif; ?>
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
                    <td><?php echo number_format(floatval($price), 0); ?><?php _e('تومان', 'rame-gym'); ?></td>
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