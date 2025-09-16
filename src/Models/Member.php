<?php

namespace MyGym\Models;

class Member
{
    public $user_id;
    public $display_name;
    public $national_id;
    public $phone_number;
    public $sport_discipline;
    public $payment_type;
    public $total_amount;
    public $installment_count;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $user = get_user_by('id', $user_id);
        if ($user) {
            $this->display_name = $user->display_name;
            $this->national_id = get_user_meta($user_id, 'national_id', true);
            $this->phone_number = get_user_meta($user_id, 'phone_number', true);
            $this->sport_discipline = get_user_meta($user_id, 'sport_discipline', true);
            $this->payment_type = get_user_meta($user_id, 'payment_type', true);
            $this->total_amount = get_user_meta($user_id, 'total_amount', true);
            $this->installment_count = get_user_meta($user_id, 'installment_count', true);
        }
    }
}