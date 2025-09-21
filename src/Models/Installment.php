<?php

namespace GymManagement\Models;

final class Installment
{
    public ?int $id = null;
    public int $membership_id;
    public int $user_id;
    public float $amount;
    public string $due_date;
    public ?string $payment_date = null;
    public string $status;

    public function __construct(array $data = [])
    {
        if (empty($data)) {
            return;
        }

        $this->id = isset($data['id']) ? intval($data['id']) : null;
        $this->membership_id = intval($data['membership_id'] ?? 0);
        $this->user_id = intval($data['user_id'] ?? 0);
        $this->amount = floatval($data['amount'] ?? 0);
        $this->due_date = sanitize_text_field($data['due_date'] ?? '');
        $this->payment_date = isset($data['payment_date']) ? sanitize_text_field($data['payment_date']) : null;
        $this->status = sanitize_text_field($data['status'] ?? 'pending');
    }
}