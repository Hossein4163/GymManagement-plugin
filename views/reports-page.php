<?php
/**
 * View for the financial reports page.
 * Contains filters and canvas elements for charts.
 */

use Morilog\Jalali\Jalalian;

// Default dates to the current month
$start_date_gregorian = date('Y-m-01');
$end_date_gregorian = date('Y-m-t');

$start_date_jalali = Jalalian::fromDateTime($start_date_gregorian)->format('Y/m/d');
$end_date_jalali = Jalalian::fromDateTime($end_date_gregorian)->format('Y/m/d');
?>
<div class="wrap my-gym-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('گزارشات مالی', 'rame-gym'); ?></h1>
    <hr class="wp-header-end">

    <div class="postbox">
        <h2 class="hndle"><?php esc_html_e('فیلتر گزارش', 'rame-gym'); ?></h2>
        <div class="inside">
            <form id="report-filter-form">
                <div class="report-filters">
                    <div class="filter-item">
                        <label for="start_date"><?php esc_html_e('از تاریخ:', 'rame-gym'); ?></label>
                        <input type="text" id="start_date" name="start_date" class="rame-gym-datepicker ltr"
                               value="<?php echo esc_attr($start_date_jalali); ?>">
                    </div>
                    <div class="filter-item">
                        <label for="end_date"><?php esc_html_e('تا تاریخ:', 'rame-gym'); ?></label>
                        <input type="text" id="end_date" name="end_date" class="rame-gym-datepicker ltr"
                               value="<?php echo esc_attr($end_date_jalali); ?>">
                    </div>
                    <div class="filter-item">
                        <label for="filter_user_id"><?php esc_html_e('عضو خاص:', 'rame-gym'); ?></label>
                        <select id="filter_user_id" name="user_id" style="width:250px;"></select>
                    </div>
                    <div class="filter-item">
                        <button type="submit"
                                class="button button-primary"><?php esc_html_e('نمایش گزارش', 'rame-gym'); ?></button>
                        <a href="#" id="export-csv-btn"
                           class="button"><?php esc_html_e('دریافت خروجی CSV', 'rame-gym'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-main-content" style="margin-top: 30px;">
        <div class="main-chart-container">
            <div class="postbox">
                <h2 class="hndle"><?php esc_html_e('گزارش سود و زیان', 'rame-gym'); ?></h2>
                <div class="inside">
                    <canvas id="profit-loss-chart" height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="side-chart-container">
            <div class="postbox">
                <h2 class="hndle"><?php esc_html_e('درآمد بر اساس رشته ورزشی', 'rame-gym'); ?></h2>
                <div class="inside">
                    <canvas id="discipline-income-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .report-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
    }

    .filter-item label {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .filter-item .button {
        margin-top: 25px; /* Align with inputs */
    }
</style>