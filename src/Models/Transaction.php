<?php

namespace GymManagement\Models;

class Transaction
{
    public $id;
    public $user_id;
    public $amount;
    public $type;
    public $payment_type;
    public $description;
    public $date;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data->id) ? intval($data->id) : null;
            $this->user_id = isset($data->user_id) ? intval($data->user_id) : null;
            $this->amount = isset($data->amount) ? floatval($data->amount) : 0;
            $this->type = isset($data->type) ? sanitize_text_field($data->type) : '';
            $this->payment_type = isset($data->payment_type) ? sanitize_text_field($data->payment_type) : '';
            $this->description = isset($data->description) ? sanitize_textarea_field($data->description) : '';
            $this->date = isset($data->date) ? sanitize_text_field($data->date) : '';
        }
    }
}