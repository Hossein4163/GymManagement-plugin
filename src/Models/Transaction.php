<?php

namespace GymManagement\Models;

final class Transaction
{
    public ?int $id = null;
    public int $user_id;
    public float $amount;
    public string $type;
    public string $payment_type;
    public string $description;
    public string $date;

    public function __construct(array $data = [])
    {
        if (empty($data)) {
            return;
        }

        $this->id = isset($data['id']) ? intval($data['id']) : null;
        $this->user_id = intval($data['user_id'] ?? 0);
        $this->amount = floatval($data['amount'] ?? 0);
        $this->type = sanitize_text_field($data['type'] ?? '');
        $this->payment_type = sanitize_text_field($data['payment_type'] ?? '');
        $this->description = sanitize_textarea_field($data['description'] ?? '');
        $this->date = sanitize_text_field($data['date'] ?? '');
    }
}