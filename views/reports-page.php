<?php
require_once(MY_GYM_PLUGIN_PATH . 'vendor/morilog/jalali/src/Jalali/jdate.php');
require_once(MY_GYM_PLUGIN_PATH . 'vendor/morilog/jalali/src/Jalali/jDateTime.php');

use Morilog\Jalali\Jalali;

$start_date_gregorian = date('Y-m-01');
$end_date_gregorian = date('Y-m-t');

$start_date_jalali = Jalali::fromCarbon(\Carbon\Carbon::parse($start_date_gregorian))->format('Y-m-d');
$end_date_jalali = Jalali::fromCarbon(\Carbon\Carbon::parse($end_date_gregorian))->format('Y-m-d');
?>
<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline">گزارشات مالی</h1>
    <hr class="wp-header-end">

    <div class="report-filter">
        <form id="report-filter-form">
            <?php wp_nonce_field('my_gym_security_nonce', 'my_gym_security_nonce'); ?>
            <label for="start_date">از تاریخ:</label>
            <input type="text" id="start_date" name="start_date" value="<?php echo esc_attr($start_date_jalali); ?>">
            <label for="end_date">تا تاریخ:</label>
            <input type="text" id="end_date" name="end_date" value="<?php echo esc_attr($end_date_jalali); ?>">
            <input type="submit" class="button button-primary" value="فیلتر گزارش‌ها">
        </form>
    </div>

    <div class="dashboard-charts">
        <div class="chart-box">
            <h2>گزارش سود و زیان</h2>
            <canvas id="profit-loss-chart"></canvas>
        </div>
        <div class="chart-box">
            <h2>درآمد بر اساس رشته ورزشی</h2>
            <canvas id="discipline-income-chart"></canvas>
        </div>
    </div>
</div>