<?php

namespace GymManagement\Models;

class Installment
{
    public $id;
    public $user_id;
    public $amount;
    public $due_date;
    public $payment_date;
    public $status; // 'pending', 'paid', 'overdue'

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data->id) ? intval($data->id) : null;
            $this->user_id = isset($data->user_id) ? intval($data->user_id) : null;
            $this->amount = isset($data->amount) ? floatval($data->amount) : 0;
            $this->due_date = isset($data->due_date) ? sanitize_text_field($data->due_date) : '';
            $this->payment_date = isset($data->payment_date) ? sanitize_text_field($data->payment_date) : null;
            $this->status = isset($data->status) ? sanitize_text_field($data->status) : 'pending';
        }
    }
}