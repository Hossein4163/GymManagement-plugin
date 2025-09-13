<?php

namespace GymManagement\Controllers;

class MembershipController
{
    public function __construct()
    {
        add_action('show_user_profile', array($this, 'add_member_fields'));
        add_action('edit_user_profile', array($this, 'add_member_fields'));
        add_action('personal_options_update', array($this, 'save_member_fields'));
        add_action('edit_user_profile_update', array($this, 'save_member_fields'));
    }

    public function add_member_fields($user)
    {
        ?>
        <h3>اطلاعات باشگاهی</h3>
        <table class="form-table">
            <tr>
                <th><label for="national_id">کد ملی</label></th>
                <td>
                    <input type="text" name="national_id" id="national_id"
                           value="<?php echo esc_attr(get_the_author_meta('national_id', $user->ID)); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="sport_discipline">رشته ورزشی</label></th>
                <td>
                    <?php
                    $disciplines = get_posts(array('post_type' => 'sports_discipline', 'numberposts' => -1));
                    $current_discipline = get_the_author_meta('sport_discipline', $user->ID);
                    ?>
                    <select name="sport_discipline" id="sport_discipline">
                        <option value="">انتخاب رشته</option>
                        <?php
                        foreach ($disciplines as $discipline) {
                            $selected = ($current_discipline == $discipline->ID) ? 'selected' : '';
                            echo '<option value="' . esc_attr($discipline->ID) . '" ' . $selected . '>' . esc_html($discipline->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="payment_amount">مبلغ پرداختی</label></th>
                <td>
                    <input type="number" name="payment_amount" id="payment_amount"
                           value="<?php echo esc_attr(get_the_author_meta('payment_amount', $user->ID)); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="coach_name">نام مربی</label></th>
                <td>
                    <input type="text" name="coach_name" id="coach_name"
                           value="<?php echo esc_attr(get_the_author_meta('coach_name', $user->ID)); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="notes">توضیحات</label></th>
                <td>
                    <textarea name="notes" id="notes" rows="5"
                              cols="30"><?php echo esc_textarea(get_the_author_meta('notes', $user->ID)); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_member_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        update_user_meta($user_id, 'national_id', sanitize_text_field($_POST['national_id']));
        update_user_meta($user_id, 'sport_discipline', sanitize_text_field($_POST['sport_discipline']));
        update_user_meta($user_id, 'payment_amount', sanitize_text_field($_POST['payment_amount']));
        update_user_meta($user_id, 'coach_name', sanitize_text_field($_POST['coach_name']));
        update_user_meta($user_id, 'notes', sanitize_textarea_field($_POST['notes']));
    }
}