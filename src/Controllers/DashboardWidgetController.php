<?php

namespace GymManagement\Controllers;

final class DashboardWidgetController
{
    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    public function add_dashboard_widget()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'rame_gym_summary_widget',
            __('خلاصه عملکرد باشگاه Rame Gym', 'rame-gym'),
            [$this, 'render_widget_content']
        );
    }

    public function render_widget_content()
    {
        global $wpdb;
        $memberships_table = $wpdb->prefix . 'gym_memberships';
        $installments_table = $wpdb->prefix . 'gym_installments';

        $active_members = $wpdb->get_var("SELECT COUNT(*) FROM {$memberships_table} WHERE status = 'active'");
        $overdue_installments = $wpdb->get_var("SELECT COUNT(*) FROM {$installments_table} WHERE status = 'overdue'");

        ?>
        <div class="rame-gym-widget">
            <div class="widget-stat">
                <span class="stat-number"><?php echo intval($active_members); ?></span>
                <span class="stat-label"><?php _e('عضو فعال', 'rame-gym'); ?></span>
            </div>
            <hr>
            <div class="widget-stat">
                <span class="stat-number stat-overdue"><?php echo intval($overdue_installments); ?></span>
                <span class="stat-label"><?php _e('قسط معوق', 'rame-gym'); ?></span>
            </div>
            <hr>
            <p style="text-align: center; margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=rame-gym'); ?>" class="button button-primary">
                    <?php _e('مشاهده داشبورد کامل', 'rame-gym'); ?>
                </a>
            </p>
        </div>
        <style>
            .rame-gym-widget .widget-stat {
                padding: 10px 0;
                text-align: center;
            }

            .rame-gym-widget .stat-number {
                font-size: 2em;
                font-weight: bold;
                display: block;
            }

            .rame-gym-widget .stat-label {
                color: #777;
            }

            .rame-gym-widget .stat-overdue {
                color: #dc3545;
            }

            .rame-gym-widget hr {
                border: 0;
                border-top: 1px solid #eee;
                margin: 5px 0;
            }
        </style>
        <?php
    }
}