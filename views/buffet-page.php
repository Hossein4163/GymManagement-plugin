<?php
/**
 * View for the Buffet/Point-of-Sale page.
 */
?>
<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('مدیریت بوفه', 'rame-gym'); ?></h1>
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=buffet_product')); ?>"
       class="page-title-action"><?php esc_html_e('افزودن محصول جدید', 'rame-gym'); ?></a>
    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=buffet_category&post_type=buffet_product')); ?>"
       class="page-title-action"><?php esc_html_e('مدیریت دسته‌بندی‌ها', 'rame-gym'); ?></a>
    <hr class="wp-header-end">

    <?php settings_errors('my_gym_messages'); ?>

    <div class="postbox" id="buffet-sale-form">
        <h2 class="hndle"><?php esc_html_e('ثبت فروش جدید', 'rame-gym'); ?></h2>
        <div class="inside">
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="rame_gym_buffet_sale">
                <?php wp_nonce_field('my_gym_buffet_sale_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="customer_id_select2"><?php esc_html_e('مشتری', 'rame-gym'); ?></label></th>
                        <td>
                            <select name="customer_id" id="customer_id_select2" required
                                    style="width: 100%; max-width: 400px;"></select>
                            <p class="description"><?php esc_html_e('نام مشتری را برای جستجو وارد کنید.', 'rame-gym'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="category_filter"><?php esc_html_e('فیلتر دسته‌بندی', 'rame-gym'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_categories([
                                'taxonomy' => 'buffet_category',
                                'name' => 'category_filter',
                                'id' => 'category_filter',
                                'show_option_all' => __('همه دسته‌بندی‌ها', 'rame-gym'),
                                'hide_empty' => false,
                                'class' => '',
                                'style' => 'width: 100%; max-width: 400px;',
                            ]);
                            ?>
                        </td>
                    </tr>
                </table>

                <h3 style="margin-top:20px;"><?php esc_html_e('لیست محصولات', 'rame-gym'); ?></h3>
                <div id="products-loading" style="display: none; text-align: center; padding: 20px;">
                    <p><?php esc_html_e('در حال بارگیری...', 'rame-gym'); ?></p>
                </div>
                <div id="products-container">
                    <table class="widefat fixed striped" id="products-table">
                        <thead>
                        <tr>
                            <th style="width: 30%;"><?php esc_html_e('محصول', 'rame-gym'); ?></th>
                            <th><?php esc_html_e('دسته‌بندی', 'rame-gym'); ?></th>
                            <th><?php esc_html_e('موجودی', 'rame-gym'); ?></th>
                            <th><?php esc_html_e('قیمت (تومان)', 'rame-gym'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('تعداد', 'rame-gym'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="sale-summary">
                    <strong><?php esc_html_e('جمع کل:', 'rame-gym'); ?> <span
                            id="total_amount">0</span> <?php esc_html_e('تومان', 'rame-gym'); ?></strong>
                </div>

                <p class="submit">
                    <input type="submit" name="submit_sale" id="submit_sale" class="button button-primary"
                           value="<?php esc_attr_e('ثبت فروش', 'rame-gym'); ?>" disabled>
                    <span id="submit-help"
                          class="description"><?php esc_html_e('ابتدا مشتری و محصولات را انتخاب کنید.', 'rame-gym'); ?></span>
                </p>
            </form>
        </div>
    </div>

    <h2 style="margin-top: 30px;"><?php esc_html_e('گزارش آخرین فروش‌ها', 'rame-gym'); ?></h2>
    <?php // The new, correct way to display recent sales from custom tables ?>
</div>