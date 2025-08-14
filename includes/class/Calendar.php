<?php
namespace EduPress;

defined('ABSPATH' ) || die();

class Calendar extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'calendar';

    protected $list_title = 'Academic Calendars';

    /**
     * @Initialize instance
     *
     * @return Calendar
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * @constructor
     *
     * @return void
     *
     * @since 1.0
     * @accces public
     */
    public function __construct( $id = 0 )
    {
        parent::__construct( $id );

        // Filter publish Fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ], 10, 1 );

        // Filter Query Fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterCalendarFilterFields' ] );

        // Disable publishing post
        add_filter( "edupress_publish_{$this->post_type}_button_html", "__return_false" );

    }

    /**
     * Filter publish fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterPublishFields( $fields = [] )
    {
        $fields = [];
        if(Admin::getSetting('branch_active') == 'active'){
            $branch = new Branch();
            $fields['branch_id'] = array(
                'type'  => 'select',
                'name'  => 'branch_id',
                'settings' => array(
                    'label' => 'Branch',
                    'options' => $branch->getPosts( [], true ),
                    'required' => true,
                    'placeholder' => 'Select',
                    'value' => intval($_REQUEST['branch_id'] ?? 0)
                ),
            );
        }

        if(Admin::getSetting('shift_active') == 'active'){
            $fields['shift_id'] = array(
                'type'  => 'select',
                'name'  => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'required' => true,
                    'placeholder' => 'Select',
                    'options' => [],
                    'value' => intval($_REQUEST['shift_id'] ?? 0)
                ),
            );
        }

        if( Admin::getSetting('class_active') == 'active' ){
            $fields['class_id'] = array(
                'type'  => 'select',
                'name'  => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'required' => true,
                    'placeholder' => 'Select',
                    'options' => [],
                    'value' => intval($_REQUEST['class_id'] ?? 0)
                ),
            );
        }

        if( Admin::getSetting('section_active') == 'active' ){
            $fields['section_id'] = array(
                'type'  => 'select',
                'name'  => 'section_id',
                'settings' => array(
                    'label' => 'Section',
                    'required' => true,
                    'placeholder' => 'Select',
                    'options' => [],
                    'value' => intval($_REQUEST['section_id'] ?? 0)
                ),
            );
        }
        return $fields;

    }

    /**
     * @override
     * Publish a calendar
     *
     * @var array $args
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function publish( $args = [] )
    {

        // Create Key
        $key = "academic_calendar";

        $exists = get_post_meta( $this->id, $key, true );
        if( !empty($exists) ) return 0;

        $data = [];
        $data['doe'] = current_time('mysql');
        $data['data'] = [];
        return update_post_meta( $this->id, $key, $data );

    }

    /**
     * Delete calendar
     *
     * @return boolean
     * @since 1.0
     * @access public
     */
    public function delete()
    {
        return delete_post_meta( $this->id, 'academic_calendar' );
    }

    /**
     * Listing all calendars
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {
        $branch_id = $_REQUEST['branch_id'] ?? 0;
        $shift_id = $_REQUEST['shift_id'] ?? 0;
        $class_id = $_REQUEST['class_id'] ?? 0;

        global $wpdb;
        $posts_per_page = 20;
        $paged = max( get_query_var( 'paged'), 1 );
        $offset = $paged > 1 ? $paged - 1 * $posts_per_page : 0;
        $qry = "SELECT t1.* FROM {$wpdb->postmeta} t1 ";
        if( $branch_id ) $qry .= " LEFT JOIN {$wpdb->postmeta} t2 ON t1.post_id = t2.post_id ";
        if( $shift_id ) $qry .= " LEFT JOIN {$wpdb->postmeta} t3 ON t1.post_id = t3.post_id ";
        if( $class_id ) $qry .= " LEFT JOIN {$wpdb->postmeta} t4 ON t1.post_id = t4.post_id ";

        $key = "academic_calendar";
        $qry .= " WHERE 1 = 1 AND t1.meta_key LIKE '{$key}' ";
        if( $branch_id ) $qry .= " AND t2.meta_key = 'branch_id' AND t2.meta_value = {$branch_id} ";
        if( $shift_id ) $qry .= " AND t3.meta_key = 'shift_id' AND t3.meta_value = {$shift_id} ";
        if( $class_id ) $qry .= " AND t4.meta_key = 'class_id' AND t4.meta_value = {$class_id} ";

        $qry .= " ORDER BY t1.meta_id DESC LIMIT {$posts_per_page} OFFSET {$offset}";
        $results = $wpdb->get_results($qry);


        if(empty($results)) return __('No calendars found!', 'edupress');

        $branch_active = Admin::getSetting('branch_active');
        $shift_active = Admin::getSetting('shift_active');
        $class_active = Admin::getSetting('class_active');
        $section_active = Admin::getSetting('section_active');
        $titles = [];
        ob_start();
        ?>
            <div class="edupress-table-wrap">
                <table class="edupress-table edupress-master-table">
                    <thead>
                        <tr>
                            <?php if($branch_active == 'active'): ?>
                                <th><?php _e( 'Branch', 'edupress' ); ?></th>
                            <?php endif; ?>
                            <?php if($shift_active == 'active'): ?>
                                <th><?php _e( 'Shift', 'edupress' ); ?></th>
                            <?php endif; ?>
                            <?php if($class_active == 'active'): ?>
                                <th><?php _e( 'Class', 'edupress' ); ?></th>
                            <?php endif; ?>
                            <?php if($section_active == 'active'): ?>
                                <th><?php _e( 'Section', 'edupress' ); ?></th>
                            <?php endif; ?>
                            <th><?php _e( 'Start Date', 'edupress' ); ?></th>
                            <th><?php _e( 'End Date', 'edupress' ); ?></th>
                            <th><?php _e( 'Action', 'edupress' ); ?></th>
                        </tr>
                    </thead>

                    <?php foreach($results as $r): ?>
                        <?php
                            $meta_value = maybe_unserialize($r->meta_value);
                            $post = get_post($r->post_id);
                            $branch_id = get_post_meta($r->post_id, 'branch_id', true);
                            $shift_id = get_post_meta($r->post_id, 'shift_id', true);
                            $class_id = get_post_meta($r->post_id, 'class_id', true);
                            if($post->post_type == 'class') $class_id = $r->post_id;
                            $section_id = get_post_meta($r->post_id, 'section_id', true);
                            if($post->post_type == 'section') $section_id = $r->post_id;
                            $cal_data = maybe_unserialize(get_post_meta( $r->post_id, 'academic_calendar', true));
                            if(empty($cal_data)) continue;

                            $branch_name = $section_name = $class_name = $section_name = '';
                            if($branch_id && !isset($titles[$branch_id])) $titles[$branch_id] = get_the_title($branch_id);
                            if($shift_id && !isset($titles[$shift_id])) $titles[$shift_id] = get_the_title($shift_id);
                            if($class_id && !isset($titles[$class_id])) $titles[$class_id] = get_the_title($class_id);
                            if($section_id && !isset($titles[$section_id])) $titles[$section_id] = get_the_title($section_id);

                            $start_date = get_post_meta( $r->post_id, 'start_date', true );
                            if(!empty($start_date)) $start_date = date('d/m/y', strtotime($start_date));
                            $end_date = get_post_meta( $r->post_id, 'end_date', true );
                            if(!empty($end_date)) $end_date = date('d/m/y', strtotime($end_date));
                        ?>
                        <tr data-post-id="<?php echo $r->post_id; ?>" >
                            <?php if($branch_active == 'active'): ?>
                                <td><?php echo $titles[$branch_id] ?? ''; ?></td>
                            <?php endif; ?>
                            <?php if($shift_active == 'active'): ?>
                                <td><?php echo $titles[$shift_id] ?? ''; ?></td>
                            <?php endif; ?>
                            <?php if($class_active == 'active'): ?>
                                <td><?php echo $titles[$class_id] ?? ''; ?></td>
                            <?php endif; ?>
                            <?php if($section_active == 'active'): ?>
                                <td><?php echo $titles[$section_id] ?? ''; ?></td>
                            <?php endif; ?>
                            <td><?php echo $start_date; ?></td>
                            <td><?php echo $end_date; ?></td>
                            <td>
                                <?php if(User::currentUserCan('edit', $this->post_type) ) : ?>
                                    <a data-post_type="calendar" data-success_callback="editCalendarSuccessCallback" data-branch_id="<?php echo $branch_id;?>" data-shift_id="<?php echo $shift_id;?>" data-class_id="<?php echo $class_id; ?>" data-section_id="<?php echo $section_id; ?>" data-post_id="<?php echo $r->post_id; ?>" data-year="<?php echo $meta_value['year'] ?? 0; ?>" data-ajax_action="getPostEditForm" href="javascript:void(0)" class="<?php echo EduPress::getClassNames(array('showEditCalendarScreen'), 'link'); ?>"><?php echo EduPress::getIcon('edit'); ?></a>
                                <?php endif; ?>
                                <?php if(User::currentUserCan('delete', 'calendar')): ?>
                                    <a data-post-type='calendar' data-before_send_callback='deleteCalendarBeforeSendCallback' data-success_callback='deleteCalendarSuccessCallback' data-action='edupress_admin_ajax' data-id='<?php echo $r->post_id; ?>' data-post_id='<?php echo $r->post_id; ?>' data-ajax_action='deleteCalendar' href='javascript:void(0)' class='edupress-ajax edupress-ajax-link'><?php echo EduPress::getIcon('delete'); ?></a>
                                <?php endif; ?>
                                <a data-post_type="calendar" data-success_callback="editCalendarSuccessCallback" data-branch_id="<?php echo $branch_id;?>" data-shift_id="<?php echo $shift_id;?>" data-class_id="<?php echo $class_id; ?>" data-section_id="<?php echo $section_id; ?>" data-post_id="<?php echo $r->post_id; ?>" data-year="<?php echo $meta_value['year'] ?? 0; ?>" data-ajax_action="getPostEditForm" href="javascript:void(0)" class="<?php echo EduPress::getClassNames(array('showEditCalendarScreen'), 'link'); ?>"><?php echo EduPress::getIcon('view'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php
        return ob_get_clean();

    }


    /**
     * Show calendar
     *
     * @param array $data
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getCalendar( $start_date = '',  $end_date = '', $data = [] )
    {
        ob_start();
        ?>
        <script>
            if( typeof  $j === 'undefined' ) var $j = jQuery;
            jQuery(document).ready(function(){

                // Bulk update
                $j(document).on( 'change', ".bulkStatusUpdate", function(e){
                    let dayName = $j(this).data('dayname');
                    let dayStatus = $j(this).val();
                    let month = $j(this).data('month');
                    $j(`li[data-dayname='${dayName}'][data-month='${month}']`).attr('data-status', dayStatus);
                    $j(`li[data-dayname='${dayName}'][data-month='${month}'] .calendar-day-status`).val(dayStatus);
                })

                $j(document).on('change', '.calendar-day-status', function(e){
                    let status = $j(this).val();
                    $j(this).parent('li').attr('data-status', status);
                })

                // Bulk note update
                $j(document).on( 'change keyup keydown', '.bulkCalendarNote', function(){
                    let dayName = $j(this).data('dayname');
                    let note = $j(this).val();
                    $j(`li[data-dayname='${dayName}'] textarea`).val(note);

                })
            })
        </script>
        <style>

            .calendarMonthSelector{
                margin-bottom: 20px;
            }
            table.edupress-table tr th,
            table.edupress-table tr td{
                padding: 3px 5px !important;
            }
            .calendar-month ul,
            .calendar-month ul li{
                position: relative;
            }
            .header_weekly_title_wrap,
            .header_weekly_selector_wrap{
                display: flex;
                font-size: 12px;
                flex-wrap: no-wrap;
                flex: 1;
                justify-content: space-between;
                align-items: center;
                gap: 3px;

            }

            select.calendar-day-status{
                padding: 3px !important;
                font-size: 10px !important;
                width: auto !important;
                position: absolute;
                left: 5px;
                top: 5px;
            }
            .header_weekly_selector_wrap input,
            .header_weekly_selector_wrap label{
                font-size: 10px !important;
                align-items: center;
            }

            .header-calendar-day,
            .header-calendar-month{
                font-size: 10px;
                font-weight: bold;
                color: #aaa;
                display: inline-block;
            }
            .legend_open, li[data-status='o']{ background-color: rgba( 0, 255, 0, 0.1); }
            .legend_holiday, li[data-status='h']{ background-color: rgba(255, 255, 0, 0.1); }
            .legend_close, li[data-status='c']{ background-color: rgba(255, 0, 0, 0.1); }
            .legend_open,
            .legend_close,
            .legend_holiday{
                height: 20px;
                width: 100px;
                display: inline-block;
                font-size:12px;
                text-align: center;
                line-height: 20px;
                border: 1px solid #fff;
                margin: 0 10px;
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
                border-radius: 10px;
            }
        </style>
        <div class="edupress-calendar-wrap">
        <?php
        $all_week_days = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
        $can_edit = User::currentUserCan('edit', 'calendar');

        if(empty($start_date)){
            $start_date = date('Y-m-d', strtotime(current_time('mysql')));
            $start_date = new \DateTime($start_date);
            $start_date->modify('first day of january this year');
        } else {
            $start_date = new \DateTime($start_date);
        }

        if(empty($end_date)) {
            $end_date = new \DateTime($start_date->format('Y-m-d'));
            $end_date->modify('last day of december this year');
        } else {
            $end_date = new \DateTime($end_date);
        }

        $day_interval = new \DateInterval('P1D');
        $day_period = new \DatePeriod($start_date, $day_interval, $end_date);

        $all_days = [];
        $all_months_years = [];
        foreach($day_period as $day) {
            $my = $day->format('m-Y');
            $all_days[$my][] = $day->format('Y-m-d');
            $x = $day->format('Y-m');
            $all_months_years[$x] = $day->format('F, Y');
        }

        // Showing header
        ?>
            <!-- Hiding Yearwise Control Option
            <ul class="calendar-month">
                <?php foreach($all_week_days as $day): ?>
                    <li>
                        <span class="day-head"><strong><?php _e( $day, 'edupress' ); ?></strong></span>
                        <?php if($can_edit) : ?>
                            <select data-dayname="<?php echo $day;?>" name="dayBulkSelect" id="">
                                <option value=""><?php _e( 'Select', 'edupress' ); ?></option>
                                <option value="1"><?php _e( 'Open', 'edupress' ); ?></option>
                                <option value="0"><?php _e( 'Close', 'edupress' ); ?></option>
                                <option value=""><?php _e( 'No Decision', 'edupress' ); ?></option>
                            </select>
                            <textarea placeholder="Note" style="margin-top: 10px;height:50px;" rows="1" data-dayname="<?php echo $day;?>" name="bulkCalendarNote" class="bulkCalendarNote"></textarea>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            -->

        <?php $default_hide_style = ''; ?>
        <?php if(User::currentUserCan('edit', 'calendar')) : $default_hide_style = " style='display:none;' "; ?>
            <div class="month-selector">
                <label for="calendarMonthSelector"><?php _e( 'Select a Month', 'edupress' ); ?></label>
                <?php
                    echo EduPress::generateFormElement( 'select', 'calendarMonthSelector',
                    array(
                        'options' => $all_months_years,
                        'label' => 'Select Month to show calendar',
                        'placeholder' => 'Select a Month',
                        'id'    => 'calendarMonthSelector',
                        'class' => 'calendarMonthSelector',
                    ))
                ?>
            </div>
        <?php endif; ?>

        <?php
        foreach($day_period as $day){

            // Month start
            $my = $day->format('m-Y');
            $firstday = isset($all_days[$my]) && $all_days[$my][0] === $day->format('Y-m-d');
            if( $firstday ) {

                if(User::currentUserCan('edit', 'calendar')):
                    echo "<ul class='calendar-month' {$default_hide_style} data-my='{$day->format('Y-m')}' data-month='{$day->format('m')}' data-year='{$day->format('Y')}'>";
                    foreach($all_week_days as $week_day){
                        $month_week_day = $day->format('m') . "_{$week_day}";
                        $monthly_day_selector = $week_day . $day->format('_m_y');
                        $month = $day->format('F');
                        echo "
                            <li>
                                <div class='header_weekly_title_wrap'>
                                    <span style='float:left' class='header-calendar-day'>{$day->format('F')}</span>
                                    <span style='float:right' class='header-calendar-month' for='monthly_day_selector_{$monthly_day_selector}'>{$week_day}</span>
                                </div>
                                <div class='header_weekly_selector_wrap'>
                                    <span title='Open' class='weekly_selector'>
                                        <input class='bulkStatusUpdate' id='{$month_week_day}_open' data-month='{$month}' data-dayname='{$week_day}' type='radio' name='weekly_selector_{$week_day}' value='o'>
                                        <label for='{$month_week_day}_open'>Open</label>
                                    </span>
                                    <span title='Close' class='weekly_selector'>
                                        <input class='bulkStatusUpdate' id='uid_{$month_week_day}_close' data-month='{$month}' data-dayname='{$week_day}' type='radio' name='weekly_selector_{$week_day}' value='c'>
                                        <label for='uid_{$month_week_day}_close'>Close</label>
                                    </span>
                                    <span title='Holiday' class='weekly_selector'>
                                        <input class='bulkStatusUpdate' id='uid_{$month_week_day}_holiday' data-month='{$month}' data-dayname='{$week_day}' type='radio' name='weekly_selector_{$week_day}' value='h'>
                                        <label for='uid_{$month_week_day}_holiday'>Holiday</label>
                                    </span>
                                </div>
                            </li>";
                    }
                    echo "</ul>";
                endif;

                echo "<ul class='calendar-month' {$default_hide_style} data-my='{$day->format('Y-m')}' data-month='{$day->format('m')}' data-year='{$day->format('Y')}'>";
            }

            // Day wise details
            $day_month = "<span class='month'>{$day->format('M')}</span><br><span class='day'>{$day->format('d')}</span>";
            $day_name = $day->format('l');

            // This is for day alignment
            if( $firstday ){
                switch ($day_name){
                    case 'Sunday':
                        break;
                    case 'Monday':
                        echo "<li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Tuesday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Wednesday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Thursday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Friday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Saturday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                }
            }

            $uniqid = uniqid();
            $date = $day->format('Y-m-d');
            $date_data = $data[$date] ?? [];
            $weekend_holidays = Admin::getSetting('attendance_weekend_holidays');
            if(!is_array($weekend_holidays)) $weekend_holidays = explode(',', $weekend_holidays);
            $is_weekend_holiday = in_array($day->format('l'), $weekend_holidays) ? 'h' : '';
            $status = $date_data['status'] ?? $is_weekend_holiday;
            $note = $date_data['note'] ?? '';
            $disabled = $can_edit ? "" : " disabled='disabled' aria-disabled='true' ";
            ?>
                <li data-status="<?php echo $status; ?>" data-dayname="<?php echo $day->format('l'); ?>" data-date="<?php echo $date;?>" data-year="<?php echo $day->format('Y'); ?>" data-day="<?php echo $day->format('l');?>" data-month="<?php echo $day->format('F'); ?>" data-year="<?php echo $day->format('Y'); ?>">
                    <input type="hidden" name="date[]" value="<?php echo $date; ?>">
                    <span class="fixed-calendar-day"><?php echo $day->format('l'); ?> </span>
                    <?php echo $day_month; ?>
                    <?php if( $can_edit ) : ?>
                        <select name=day_status[] class='calendar-day-status' data-year="<?php echo $day->format('Y'); ?>" data-dayname="<?php echo $day->format('l'); ?>" data-month="<?php echo $day->format('m'); ?>" <?php echo $day->format('Y'); ?> data-date='<?php echo $date; ?>' id="<?php echo $uniqid; ?>">
                            <option value='o' <?php echo $status == 'o' ? 'selected' : ''; ?>>Open</option>
                            <option value='c' <?php echo $status == 'c' ? 'selected' : ''; ?>>Close</option>
                            <option value='h' <?php echo $status == 'h' ? 'selected' : ''; ?>>Holiday</option>
                        </select>
                        <textarea placeholder="" style="height: 50px;" rows="1" name="note[]" <?php echo $disabled; ?>><?php echo $note; ?></textarea>
                    <?php else: ?>
                        <div class="calendar-note" title="<?php echo $note; ?>"><?php echo $note; ?></div>
                    <?php endif; ?>
                </li>
            <?php

            // Month end
            $lastday = isset($all_days[$my]) && end($all_days[$my]) == $day->format('Y-m-d');
            if( $lastday ){
                switch ($day->format('l')){
                    case 'Sunday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Monday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Tuesday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Wednesday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Thursday':
                        echo "<li class='invisible'>&nbsp;</li><li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Friday':
                        echo "<li class='invisible'>&nbsp;</li>";
                        break;
                    case 'Saturday':
                        break;
                }
                echo "</ul>";
            }
        }
        ?>
        </div>

        <?php return ob_get_clean();
    }

    /**
     * Filter query fields
     *
     * @retrun array
     *
     * @since 1.0
     * @access public
     */
    public function filterCalendarFilterFields( $fields = [] )
    {

        $new_fields = [];

        $branch = new Branch();
        $new_fields['branch_id'] = array(
            'type'      => 'select',
            'name'      => 'branch_id',
             'settings' => array(
                 'label' => 'Branch',
                 'options' => $branch->getPosts( [], true ),
                 'placeholder' => 'Select',
                 'value'    => intval($_REQUEST['branch_id'] ?? 0),
             )
        );

        if( Admin::getSetting('shift_active') == 'active' ){
            $new_fields['shift_id'] =  array(
                'type'      => 'select',
                'name'      => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'placeholder' => 'Select',
                    'options' => [],
                    'value'    => intval($_REQUEST['shift_id'] ?? 0),
                )
            );
        }

        if( Admin::getSetting('class_active') == 'active' ){
            $new_fields['class_id'] =  array(
                'type'      => 'select',
                'name'      => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'placeholder' => 'Select',
                    'options' => [],
                    'value'    => intval($_REQUEST['class_id'] ?? 0),
                )
            );
        }

        if( Admin::getSetting('section_active') == 'active' ){
            $new_fields['section_id'] =  array(
                'type'      => 'select',
                'name'      => 'section_id',
                'settings' => array(
                    'label' => 'Section',
                    'placeholder' => 'Select',
                    'options' => [],
                    'value'    => intval($_REQUEST['section_id'] ?? 0),
                )
            );
        }
        return $new_fields;

    }

    /**
     * Get edit form
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getEditForm()
    {

        $key = 'academic_calendar';
        $data = get_post_meta( $this->id, $key, true );
        if(empty($data)){
            $data = [];
            update_post_meta($this->id, $key, $data);
        }
        $data = maybe_unserialize( $data );

        $post = get_post($this->id);
        $branch_id = (int) get_post_meta( $this->id, 'branch_id', true );
        $shift_id = (int) get_post_meta( $this->id, 'shift_id', true );
        $class_id = (int) get_post_meta( $this->id, 'class_id', true );
        $section_id = (int) get_post_meta( $this->id, 'section_id', true );

        switch ( $post->post_type ){
            case 'branch':
                $branch_id = $this->id;
                break;
            case 'shift':
                $shift_id = $this->id;
                break;
            case 'class':
                $class_id = $this->id;
                break;
            case 'section':
                $section_id = $this->id;
                break;

        }

        $start_date = get_post_meta( $this->id, 'start_date', true );
        if(!empty($start_date)) $start_date = date('Y-m-d', strtotime($start_date));

        $end_date = get_post_meta( $this->id, 'end_date', true );
        if(!empty($end_date)) $end_date = date('Y-m-d', strtotime($end_date));

        ob_start();
        ?>
        <div class="edupress-form-wrap">
            <h2 style="text-align: center;"><?php _e( 'Academic Calendar', 'edupress' ); ?></h2>
            <form action="" class="<?php echo EduPress::getClassNames( array('publishCalendar'), 'form'); ?>">
                <table class="edupress-table">
                <?php
                if(Admin::getSetting('branch_active') == 'active'){
                    ?>
                    <tr>
                        <th width="150"><?php _e('Branch', 'edupress'); ?></th>
                        <td><?php echo get_the_title($branch_id); ?></td>
                    </tr>
                    <?php
                }
                if(Admin::getSetting('shift_active') == 'active'){
                    ?>
                    <tr>
                        <th><?php _e('Shift', 'edupress'); ?></th>
                        <td><?php echo get_the_title($shift_id); ?></td>
                    </tr>
                    <?php
                }
                if(Admin::getSetting('class_active') == 'active' && $class_id){
                    ?>
                    <tr>
                        <th><?php _e('Class', 'edupress'); ?></th>
                        <td><?php echo get_the_title($class_id); ?></td>
                    </tr>
                    <?php
                }
                if(Admin::getSetting('section_active') == 'active' && $section_id > 0){
                    ?>
                    <tr>
                        <th><?php _e('Section', 'edupress'); ?></th>
                        <td><?php echo get_the_title($section_id); ?></td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <th><?php _e('Dates', 'edupress'); ?></th>
                        <td><?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date) );?> </td>
                    </tr>
                </table>
                <div class="legends-wrap">
                    Legends: <span class="legend_open">Open</span>  <span class="legend_close">Closed</span>  <span class="legend_holiday">Holiday</span> 
                </div>
                <?php echo self::getCalendar( $start_date, $end_date, $data['data'] ?? [] ); ?>
                <div class="form-row cal-save-btn" style="display:none;">
                    <div class="value-wrap">
                        <?php
                        echo EduPress::generateFormElement( 'hidden', 'post_type', array('value'=>$this->post_type) );
                        echo EduPress::generateFormElement( 'hidden', 'post_id', array('value'=>$this->id) );
                        echo EduPress::generateFormElement( 'hidden', 'branch_id', array('value'=>$branch_id) );
                        echo EduPress::generateFormElement( 'hidden', 'shift_id', array('value'=>$shift_id) );
                        echo EduPress::generateFormElement( 'hidden', 'class_id', array('value'=>$class_id) );
                        echo EduPress::generateFormElement( 'hidden', 'section_id', array('value'=>$section_id) );
                        if(User::currentUserCan('edit', 'calendar')):
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>'Save') );
                        endif;
                        echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax') );
                        echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'saveCalendar') );
                        wp_nonce_field('edupress');
                        ?>
                    </div>
                </div>
            </form>
        </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Get Stats for a certain period
     *
     * @param string $start_date
     * @param string $end_date
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getStats($start_date = '', $end_date = '' )
    {
        if(empty($start_date)) $start_date = get_post_meta( $this->id, 'start_date', true );
        if(empty($end_date)) $end_date = get_post_meta( $this->id, 'end_date', true );

        $start = new \DateTime( date('Y-m-d', strtotime($start_date)) );
        $end = new \DateTime( date('Y-m-d', strtotime($end_date)) );
        $end->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $date_range = new \DatePeriod($start, $interval, $end);
        $all_days = [];
        foreach($date_range as $day){
            $all_days[] = $day->format('Y-m-d');
        }

        $response = [];
        $response['details'] = [];
        $response['o'] = [];
        $response['c'] = [];
        $response['h'] = [];

        $calendar = maybe_unserialize(get_post_meta( $this->id, 'academic_calendar', true ) );
        $weekend_holidays = Admin::getSetting('attendance_weekend_holidays');
        if(!is_array($weekend_holidays)) $weekend_holidays = explode(',', $weekend_holidays);

        // national holidays 
        $national_holidays = Admin::getSetting('attendance_national_holidays');
        if(!is_array($national_holidays)) $national_holidays = explode("\r\n", $national_holidays);
        $national_holidays = array_map(function($day){
            return date('Y-m-d', strtotime($day));
        }, $national_holidays);
        $national_holidays = [];

        if(empty($calendar)) $calendar = [];
        if(!isset($calendar['data'])) $calendar['data'] = [];

        $data = $calendar['data'];
        foreach($all_days as $day){

            $day_data = $data[$day] ?? null;
            $response['details'][] = $day_data;

            $status = $day_data['status'] ?? '';
            $day_formatted= new \DateTime($day);
            if(empty($status) && in_array($day_formatted->format('l'), $weekend_holidays)) $status = 'h';
            if(in_array($day_formatted->format('d-m-Y'), $national_holidays)) $status = 'h';
            if(empty($status)) $status = 'o';

            if($status == 'o') $response['o'][] = $day;
            if($status == 'c') $response['c'][] = $day;
            if($status == 'h') $response['h'][] = $day;
         }

        $response['count_o']= count($response['o']);
        $response['count_c']= count($response['c']);
        $response['count_h']= count($response['h']);
        return $response;
    }

}

Calendar::instance();