<div class="wrap my-gym-wrap">
    <div class="rame-gym-header">
        <div class="rame-gym-header-icon">
            <span class="dashicons dashicons-analytics"></span>
        </div>
        <div class="rame-gym-header-title">
            <h1><?php esc_html_e('داشبورد مدیریت Rame Gym', 'rame-gym'); ?></h1>
            <p class="description"><?php esc_html_e('تحلیل جامع عملکرد باشگاه شما', 'rame-gym'); ?></p>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3><?php esc_html_e('درآمد ماه جاری', 'rame-gym'); ?></h3>
            <p id="total-income" class="stat-income">--</p>
        </div>
        <div class="stat-card">
            <h3><?php esc_html_e('هزینه‌های ماه جاری', 'rame-gym'); ?></h3>
            <p id="total-expense" class="stat-expense">--</p>
        </div>
        <div class="stat-card">
            <h3><?php esc_html_e('اقساط معوق', 'rame-gym'); ?></h3>
            <p id="overdue-installments" class="stat-overdue">--</p>
        </div>
        <div class="stat-card">
            <h3><?php esc_html_e('اعضای فعال', 'rame-gym'); ?></h3>
            <p id="total-members" class="stat-members">--</p>
        </div>
    </div>

    <div class="dashboard-main-content">
        <div class="main-chart-container">
            <div class="postbox">
                <h2 class="hndle"><?php esc_html_e('نمودار درآمد و هزینه ماهانه', 'rame-gym'); ?></h2>
                <div class="inside">
                    <canvas id="monthly-chart" height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="side-chart-container">
            <div class="postbox">
                <h2 class="hndle"><?php esc_html_e('توزیع رشته‌های ورزشی', 'rame-gym'); ?></h2>
                <div class="inside">
                    <canvas id="disciplines-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>