<?php

namespace GymManagement\Controllers;

final class FrontendController
{
    public function __construct()
    {
        add_shortcode('rame_user_profile', [$this, 'render_user_profile_shortcode']);
    }

    /**
     * Renders the user profile shortcode.
     * Uses output buffering to capture the view file's content.
     *
     * @return string The HTML content of the user profile.
     */
    public function render_user_profile_shortcode(): string
    {
        if (!is_user_logged_in()) {
            return '<p class="rame-gym-login-prompt">' . __('لطفاً برای مشاهده پروفایل خود، ابتدا وارد حساب کاربری شوید.', 'rame-gym') . '</p>';
        }

        ob_start();

        $user_id = get_current_user_id();
        // Pass variables to the view file
        require MY_GYM_PLUGIN_PATH . 'views/frontend-profile.php';

        return ob_get_clean();
    }
}