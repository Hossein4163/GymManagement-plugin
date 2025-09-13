<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Members_List_Table extends WP_List_Table
{
    private $members_data;

    function __construct($data)
    {
        parent::__construct(array(
            'singular' => 'عضو',
            'plural' => 'اعضا',
            'ajax' => false
        ));
        $this->members_data = $data;
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'display_name' => 'نام کاربر',
            'national_id' => 'کد ملی',
            'sport_discipline' => 'رشته ورزشی',
            'payment_amount' => 'مبلغ شهریه',
            'coach_name' => 'نام مربی',
        );
        return $columns;
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'display_name':
            case 'national_id':
            case 'sport_discipline':
            case 'payment_amount':
            case 'coach_name':
                return $item->$column_name;
            default:
                return print_r($item, true); // برای اشکال‌زدایی
        }
    }

    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="member[]" value="%s" />', $item->ID);
    }

    function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->members_data;
    }
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">اعضای باشگاه</h1>
    <a href="<?php echo admin_url('user-new.php'); ?>" class="page-title-action">افزودن عضو جدید</a>
    <hr class="wp-header-end">

    <?php
    $users = get_users(array('role__in' => array('subscriber'))); // دریافت کاربران
    $members_data = [];
    foreach ($users as $user) {
        $members_data[] = (object)[
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'national_id' => get_user_meta($user->ID, 'national_id', true),
            'sport_discipline' => get_user_meta($user->ID, 'sport_discipline', true),
            'payment_amount' => get_user_meta($user->ID, 'payment_amount', true),
            'coach_name' => get_user_meta($user->ID, 'coach_name', true),
        ];
    }

    $members_list_table = new Members_List_Table($members_data);
    $members_list_table->prepare_items();
    $members_list_table->display();
    ?>
</div>