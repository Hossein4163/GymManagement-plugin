<?php
if (!is_user_logged_in()) {
    return '<p>لطفاً ابتدا وارد حساب کاربری خود شوید.</p>';
}

$user_id = get_current_user_id();
$member = new \GymManagement\Models\Member($user_id);
$membership_controller = new \GymManagement\Controllers\MembershipController();
?>
<style>
    .rame-profile-container {
        direction: rtl;
        text-align: right;
        padding: 20px;
        font-family: 'vazirmatn', sans-serif;
    }

    .rame-profile-container h2 {
        font-size: 2rem;
    }

    .rame-profile-container h3 {
        font-size: 1.5rem;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-top: 20px;
    }

    .rame-profile-container ul {
        list-style: none;
        padding: 0;
    }

    .rame-profile-container li {
        margin-bottom: 10px;
    }

    .installment-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .installment-table th, .installment-table td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: right;
    }
</style>
<div class="rame-profile-container">
    <h2>پروفایل کاربری</h2>
    <div class="profile-info">
        <h3>اطلاعات شخصی</h3>
        <ul>
            <li><strong>نام:</strong> <?php echo esc_html($member->display_name); ?></li>
            <li><strong>شماره تماس:</strong> <?php echo esc_html($member->phone_number ?: 'ثبت نشده'); ?></li>
            <li><strong>رشته
                    ورزشی:</strong> <?php echo esc_html(get_the_title($member->sport_discipline) ?: 'ثبت نشده'); ?></li>
        </ul>
    </div>

    <div class="payment-status">
        <h3>وضعیت پرداخت</h3>
        <p>نوع پرداخت شما: <?php echo ($member->payment_type == 'full') ? 'نقدی/کامل' : 'اقساطی'; ?></p>

        <?php if ($member->payment_type == 'installments'): ?>
            <h4>وضعیت اقساط</h4>
            <table class="installment-table">
                <thead>
                <tr>
                    <th>مبلغ قسط</th>
                    <th>تاریخ سررسید</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $installments = $membership_controller->get_installments_for_user($member->user_id);
                foreach ($installments as $installment):
                    ?>
                    <tr>
                        <td><?php echo number_format($installment->amount, 2); ?> تومان</td>
                        <td><?php echo esc_html($installment->due_date); ?></td>
                        <td>
                            <span class="status-badge <?php echo esc_attr($installment->status); ?>">
                                <?php echo esc_html($membership_controller->get_installment_status_label($installment->status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>