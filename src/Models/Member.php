<?php

namespace GymManagement\Models;

use WP_User;

final class Member
{
    public int $user_id;
    public string $display_name;
    public string $email;
    public string $national_id;
    public string $phone_number;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
        $user = get_user_by('id', $user_id);

        if ($user instanceof WP_User) {
            $this->display_name = $user->display_name;
            $this->email = $user->user_email;
            $this->national_id = get_user_meta($user_id, 'national_id', true) ?: '';
            $this->phone_number = get_user_meta($user_id, 'phone_number', true) ?: '';
        }
    }
}