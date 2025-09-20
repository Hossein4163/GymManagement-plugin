<?php
// src/Controllers/FrontendController.php

namespace GymManagement\Controllers;

use GymManagement\Models\Member;

class FrontendController
{
    public function __construct()
    {
        add_shortcode('rame_user_profile', array($this, 'render_user_profile'));
    }

    public function render_user_profile()
    {
        if (!is_user_logged_in()) {
            return '<p>لطفاً ابتدا وارد حساب کاربری خود شوید.</p>';
        }

        $user_id = get_current_user_id();
        $member = new Member($user_id);

        ob_start();
        include MY_GYM_PLUGIN_PATH . 'views/frontend-profile.php';
        return ob_get_clean();
    }
}