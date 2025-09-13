<?php

namespace GymManagement\Models;

class Member
{
    public $user_id;
    public $display_name;
    public $national_id;
    public $sport_discipline;
    public $payment_amount;
    public $coach_name;
    public $notes;

    public function __construct($user_id)
    {
        $user_data = get_userdata($user_id);
        if ($user_data) {
            $this->user_id = $user_id;
            $this->display_name = $user_data->display_name;
            $this->national_id = get_user_meta($user_id, 'national_id', true);
            $this->sport_discipline = get_user_meta($user_id, 'sport_discipline', true);
            $this->payment_amount = get_user_meta($user_id, 'payment_amount', true);
            $this->coach_name = get_user_meta($user_id, 'coach_name', true);
            $this->notes = get_user_meta($user_id, 'notes', true);
        }
    }
}