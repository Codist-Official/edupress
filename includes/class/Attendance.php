<?php
namespace EduPress;

defined('ABSPATH') || die();

class Attendance extends CustomPost
{
    /**
     * @var $post_type
     */
    protected $post_type = 'attendance';

    /**
     * @var $table
     */
    protected $table = 'attendance';

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $list_title
     */
    protected $list_title = 'Attendance Report';

    /**
     * Initialize instance
     *
     * @return self
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Constructor
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        $this->posts_per_page = (int) isset($_REQUEST['posts_per_page']) ? $_REQUEST['posts_per_page'] : Admin::getSetting('display_posts_per_page');

        if(!$this->posts_per_page) $this->posts_per_page = 25;

        // Filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'getListFilterFields' ] );

        // Log any attendance data submitted
        add_action( 'init', [ $this, 'logData' ] );

        // Modify publish button to add custom buttons 
        add_filter( "edupress_publish_{$this->post_type}_button_html", [ $this, 'modifyPublishButtonHtml' ] );

    }

    /**
     * Modify publish button html 
     * 
     * @return string  
     * 
     * @since 1.0
     * @access public 
     */
    public function modifyPublishButtonHtml( $html = '' )
    {
        if( !User::currentUserCan('publish',  $this->post_type ) ) return '';
        ob_start();
        ?>
        <div class="edupress-publish-btn-wrap">
            <button data-post-type="<?php echo $this->post_type; ?>" class="edupress-btn edupress-publish-post"><?php _e( 'Add New ' . ucwords( str_replace( '_', ' ', $this->post_type ?? '' ) ), 'edupress' ); ?></button>
            <button data-post_type="<?php echo $this->post_type; ?>" data-ajax_action="showScreenToAddManualAttendance" data-success_callback="showPopupOnCallback" class="edupress-btn edupress-ajax-link edupress-manual-attendance"><?php _e( 'Manual Attendance', 'edupress' ); ?></button>
        </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Filter list html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {
        $report_type = sanitize_text_field($_REQUEST['report_type'] ?? '');

        $args = [];
        $args['start_date'] = sanitize_text_field($_REQUEST['start_date'] ?? '');
        $args['end_date'] = sanitize_text_field($_REQUEST['end_date'] ?? '');
        $args['branch_id'] = sanitize_text_field($_REQUEST['branch_id'] ?? '');
        $args['class_id'] = sanitize_text_field($_REQUEST['class_id'] ?? '');
        $args['section_id'] = sanitize_text_field($_REQUEST['section_id'] ?? '');
        $args['role'] = sanitize_text_field($_REQUEST['role'] ?? 'student');
        $args['posts_per_page'] = $this->posts_per_page;


        if($report_type == 'summary') return $this->getSummaryReport( $args );
        if($report_type == 'detailed') {
            $args['details']  = true;
            return $this->getSummaryReport( $args );
        }
        global $wpdb;
        $results = $wpdb->get_results( $this->getListQuery() );
        if( empty( $results) ) return __( "No {$this->post_type} found!", 'edupress' );

        $branch_active = EduPress::isActive( 'branch' );
        $shift_active = EduPress::isActive( 'shift' );
        $class_active = EduPress::isActive( 'class' );
        $section_active = EduPress::isActive( 'section' );
        $sms_active = EduPress::isActive( 'sms' );

        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table tablesorter edupress-master-table">

                <thead>
                    <tr>
                        <?php if($branch_active): ?><th><?php _e( 'Branch', 'edupress' ); ?></th><?php endif; ?>
                        <th><?php _e( 'Roll', 'edupress' ); ?></th>
                        <th><?php _e( 'User', 'edupress' ); ?></th>
                        <th><?php _e( 'Date', 'edupress' ); ?></th>
                        <th><?php _e( 'Time', 'edupress' ); ?></th>
                        <th><?php _e( 'Role', 'edupress' ); ?></th>
                        <?php if($shift_active): ?><th><?php _e( 'Shift', 'edupress' ); ?></th><?php endif; ?>
                        <?php if($class_active): ?><th><?php _e( 'Class', 'edupress' ); ?></th><?php endif; ?>
                        <?php if($section_active): ?><th><?php _e( 'Section', 'edupress' ); ?></th><?php endif; ?>
                        <?php if($sms_active): ?><th><?php _e( 'SMS', 'edupress' ); ?></th><?php endif; ?>
                        <th><?php _e( 'Record Time', 'edupress' ); ?></th>
                    </tr>
                </thead>


                <?php foreach($results as $r): ?>
                    <?php
                        $user = new User($r->user_id);
                        $branch_id = $user->getMeta('branch_id');
                        $shift_id = $user->getMeta('shift_id');
                        $class_id = $user->getMeta('class_id');
                        $section_id = $user->getMeta('section_id');
                        $sms_id = $r->sms_id;
                        global $wpdb;
                        $sms_text = $sms_id ? $wpdb->get_var("SELECT sms FROM {$wpdb->prefix}sms_logs WHERE id = {$sms_id} ") : '';
                    ?>
                    <tr data-user_id="<?php echo $r->user_id; ?>">
                        <?php if( $branch_active ): ?><td><?php  echo !empty($branch_id) ? get_the_title( $branch_id ) : ''; ?></td><?php endif; ?>
                        <td><?php if($user->getRole() == 'student') echo $user->getMeta('roll'); ?></td>
                        <td><?php echo User::showProfileOnClick( $r->user_id, $user->getMeta('first_name')); ?></td>
                        <td><?php echo date('d/m/y', strtotime($r->report_time) ); ?></td>
                        <td><?php echo date('h:i:s a', strtotime($r->report_time) ); ?></td>
                        <td><?php echo !is_null($user->getRole()) ? ucwords($user->getRole()) : ''; ?></td>
                        <?php if( $shift_active ): ?><td><?php echo !empty($shift_id) ? get_the_title( $shift_id ) : ''; ?></td><?php endif; ?>
                        <?php if( $class_active ): ?><td><?php echo !empty($class_id) && $user->getRole() == 'student' ? get_the_title( $class_id ) : ''; ?></td><?php endif; ?>
                        <?php if( $section_active ): ?><td><?php echo !empty($section_id) && $user->getRole() == 'student' ? get_the_title( $section_id ) : ''; ?></td><?php endif; ?>
                        <?php if( $sms_active ): ?><td><?php echo $sms_text;  ?></td><?php endif; ?>
                        <td><?php echo date('h:i:s a d/m/y', strtotime($r->record_time) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php echo $this->getPagination(); ?>
        <?php return ob_get_clean();
    }

    /**
     * Get summary report
     *
     * @return string
     *
     * @since 1.0
     * @access public
     *
     */
    public function getSummaryReport( $args = [] )
    {
        global $wpdb;
        $start_date = sanitize_text_field($args['start_date'] ?? '');
        $end_date = sanitize_text_field($args['end_date'] ?? '');
        if(empty($end_date)) $end_date = current_time('Y-m-d');
        $branch_id = sanitize_text_field($args['branch_id'] ?? '');
        $class_id = sanitize_text_field($args['class_id'] ?? '');
        $section_id = sanitize_text_field($args['section_id'] ?? '');
        $role = sanitize_text_field($args['role'] ?? 'student');
        $details = sanitize_text_field($args['details'] ?? false);
        $html = $args['html'] ?? true;

        $calendar_id = $section_id ? $section_id : $class_id;

        $args = [];
        if(!empty($role)) $args['role__in'] = array($role);

        $meta_query = [];
        if(!empty($branch_id)) $meta_query[] = array(
            'key' => 'branch_id',
            'value' => $branch_id,
        );
        if(!empty($class_id)) $meta_query[] = array(
            'key' => 'class_id',
            'value' => $class_id,
        );
        if(!empty($section_id)) $meta_query[] = array(
            'key' => 'section_id',
            'value' => $section_id,
        );

        if(count($meta_query) > 1) $meta_query['relation'] = 'AND';
        if(count($meta_query) > 0) $args['meta_query'] = $meta_query;


        $qry = new \WP_User_Query( $args );
        $users = $qry->get_results();
        if(empty($users)) return 'No users found!';

        $all_users = [];
        foreach($users as $user){
            $all_users[$user->ID] = (int) get_user_meta( $user->ID, 'roll', true );
        }
        asort($all_users);

        // open close stats
        $calendar = new Calendar($calendar_id);
        $calendar_stats = $calendar->getStats( $start_date, $end_date );
        $open_days = $calendar_stats['count_open'] ?? 0;


        global $wpdb;
        $qry = "SELECT * FROM {$wpdb->prefix}attendance WHERE branch_id = $branch_id ";
        if(!empty($start_date)) $qry .= " AND DATE(report_time) >= '$start_date' ";
        if(!empty($end_date)) $qry .= " AND DATE(report_time) <= '$end_date' ";
        $results = $wpdb->get_results( $qry, ARRAY_A );
        if(empty($results)) return 'No records found!';

        $logs = [];
        foreach($results as $result){
            $user_id = (int) $result['user_id'];
            $time = date('Y-m-d', strtotime($result['report_time']));
            if( !isset($logs[$user_id])) $logs[$user_id] = [];
            if( is_array($logs[$user_id]) && is_array($calendar_stats['open']) && is_array($logs[$user_id]) && in_array($time, $calendar_stats['open']) && !in_array($time, $logs[$user_id]) ) $logs[$user_id][] = $time;
        }
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        // $end->modify(modifier: '+1 day');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        $total_days = $start->diff($end)->days + 1;

        $summary_json = [];

        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table edupress-master-table tablesorter">
                <thead>
                    <tr>
                        <th style="text-align: left;"><?php _e('Branch', 'edupress'); ?></th>
                        <th style="text-align: left;">
                            <?php _e('Roll / ID', 'edupress'); ?>
                            <br>
                            <span class="no-print">
                                <input type="checkbox" class="edupress-bulk-select-all" id="select_all">
                                <label for="select_all"><?php _e('All', 'edupress'); ?></label>
                            </span>
                            <span style="float: right" class="no-print">
                                <a title="SMS Attendance Summary" class="sms-attendance-summary" href="javascript:void(0)"><?php echo EduPress::getIcon('sms'); ?></a>
                            </span>
                        </th>
                        <th style="text-align: left;"><?php _e('Name', 'edupress'); ?></th>
                        <?php
                            if($details){
                                $month = null;
                                $printed_months = [];
                                foreach($period as $day){
                                    $month_c = $day->format('M');
                                    $day_formatted = $day->format('Y-m-d');
                                    if(empty($month)) $month = $month_c;
                                    if($month !== $month_c) $month = $month_c;
                                    $print_month = !in_array($month, $printed_months) ? $month : ' ';
                                    $is_open = isset($calendar_stats['open'])  && in_array( $day_formatted, $calendar_stats['open']);
                                    $is_close = isset($calendar_stats['close']) && in_array( $day_formatted, $calendar_stats['close']);
                                    $undecided = isset($calendar_stats['undecided']) && in_array( $day_formatted, $calendar_stats['undecided']);
                                    $status_text = '';
                                    if($is_open):
                                        $status_text = 'open';
                                    elseif($is_close):
                                        $status_text = 'close';
                                    else:
                                        $status_text = 'na';
                                    endif;

                                    echo "<th style='text-align: center'>{$print_month}<br>{$day->format('d')}<br><span class='attendance-day-status {$status_text}'> &nbsp; </span></th>";
                                    $printed_months[]= $month;
                                }
                            }
                        ?>
                        <th style="text-align: center; font-size: 12px;"><?php _e('Total', 'edupress'); ?></th>
                        <th style="text-align: center; font-size: 12px;"><?php _e('Open', 'edupress'); ?></th>
                        <th style="text-align: center; font-size: 12px;"><?php _e('Present', 'edupress'); ?></th>
                        <th style="text-align: center; font-size: 12px;"><?php _e('Absent', 'edupress'); ?></th>
                        <th style="text-align: center; font-size: 12px;"><?php _e('Presence<br>%', 'edupress'); ?></th>
                    </tr>
                </thead>
                <?php
                    $branch_title = get_the_title($branch_id);
                    foreach($all_users as $k=>$v){
//                        $intersect = isset($calendar_stats['open']) && is_array($logs[$k]) ? array_intersect( $calendar_stats['open'], $logs[$k] ) : [];
                        $total_present = isset($logs[$k]) && is_countable($logs[$k]) ? count($logs[$k]) : 0;
                        $total_absent = $open_days  - $total_present;
                        $presence_percentage = $open_days > 0 ? number_format( $total_present * 100 / $open_days, 2) : 0;
                        $user_first_name = get_user_meta( $k, 'first_name', true);
                        $summary_json[$k] = "Attendance Report\n\nName: {$user_first_name}\nDuration: {$start->format('d/m/y')} - {$end->format('d/m/y')}\nOpen: {$open_days} days\nPresent: {$total_present} days\nAbsent: {$total_absent} days\nPresence: {$presence_percentage}%";
                        ?>
                        <tr>
                            <td><?php echo $branch_title; ?></td>
                            <td title="<?php echo $summary_json[$k]; ?>">
                                <input type="checkbox" id="id_<?php echo $k; ?>" class="edupress-bulk-select-item no-print" name="user_id[]" value="<?php echo $k; ?>">
                                <label for="id_<?php echo $k; ?>"><?php echo $v !== 0 ? $v : ''; ?></label>
                            </td>
                            <td><?php echo User::showProfileOnClick( $k, get_user_meta( $k, 'first_name', true )); ?></td>
                            <?php
                            if($details){
                                foreach($period as $day){
                                    $day_formatted = $day->format('Y-m-d');
                                    $is_open = isset($calendar_stats['open']) && in_array( $day_formatted, $calendar_stats['open'] );
                                    $is_close = isset($calendar_stats['close'])  && in_array( $day_formatted, $calendar_stats['close'] );
                                    $is_undecided = isset($calendar_stats['undecided']) && in_array( $day_formatted, $calendar_stats['undecided'] );
                                    $is_present = isset($logs[$k]) && is_array($logs[$k]) && in_array($day_formatted, $logs[$k]);

                                    $icon = "";

                                    if($is_close){
                                        $icon = "closed";
                                    } else {
                                        $icon = $is_present ? "present" : "absent";
                                    }

                                    echo "<td style='text-align: center'><span class='attendance-{$icon}'></span></td>";
                                }
                            }
                            ?>
                            <td style="text-align: center"><?php echo $total_days; ?></td>
                            <td style="text-align: center"><?php echo $open_days; ?></td>
                            <td style="text-align: center"><?php echo $total_present; ?></td>
                            <td style="text-align: center"><?php echo $total_absent; ?></td>
                            <td style="text-align: center"><?php echo $presence_percentage; ?>%</td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <script>
            edupress.summary_json = <?php echo json_encode($summary_json); ?>
        </script>
        <?php
        $content = ob_get_clean();
        if($html) return $content;
    }

    /**
     * Get list query
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListQuery( $args = [] )
    {
        global $wpdb;
        $qry = "SELECT t1.* FROM {$this->table} t1 ";

        $start_date = sanitize_text_field($_REQUEST['start_date'] ?? '');
        $end_date = sanitize_text_field($_REQUEST['end_date'] ?? '');
        $branch_id = sanitize_text_field($_REQUEST['branch_id'] ?? '');
        $shift_id = sanitize_text_field($_REQUEST['shift_id'] ?? '');
        $class_id = sanitize_text_field($_REQUEST['class_id'] ?? '');
        $section_id = sanitize_text_field($_REQUEST['section_id'] ?? '');
        $name = sanitize_text_field($_REQUEST['first_name'] ?? '');
        $roll = sanitize_text_field($_REQUEST['roll'] ?? '');
        $role = sanitize_text_field($_REQUEST['role'] ?? '');

        if(!empty($branch_id)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t2 ON t1.user_id = t2.user_id ";
        }
        if(!empty($shift_id)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t3 ON t1.user_id = t3.user_id ";
        }
        if(!empty($class_id)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t4 ON t1.user_id = t4.user_id ";
        }
        if(!empty($section_id)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t5 ON t1.user_id = t5.user_id ";
        }
        if(!empty($name)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t6 ON t1.user_id = t6.user_id ";
        }
        if(!empty($roll)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t7 ON t1.user_id = t7.user_id ";
        }
        if(!empty($role)){
            $qry .= " LEFT JOIN {$wpdb->usermeta} t8 ON t1.user_id = t8.user_id ";
        }

        $qry .= " WHERE 1 = 1 ";


        if(!empty($branch_id)){
            $qry .= " AND t2.meta_key = 'branch_id' AND t2.meta_value = '{$branch_id}' ";
        }
        if(!empty($shift_id)){
            $qry .= " AND t3.meta_key = 'shift_id' AND t3.meta_value = '{$shift_id}' ";
        }
        if(!empty($class_id)){
            $qry .= " AND t4.meta_key = 'class_id' AND t4.meta_value = '{$class_id}' ";
        }
        if(!empty($section_id)){
            $qry .= " AND t5.meta_key = 'section_id' AND t5.meta_value = '{$section_id}' ";
        }
        if(!empty($name)){
            $qry .= " AND t6.meta_key = 'first_name' AND t6.meta_value LIKE '%{$name}%' ";
        }
        if(!empty($roll)){
            $qry .= " AND t7.meta_key = 'roll' AND t7.meta_value = '{$roll}' ";
        }
        if(!empty($role)){
            $qry .= " AND t8.meta_key = 'wp_capabilities' AND t8.meta_value LIKE '%{$role}%' ";
        }

        if(!empty($start_date)){
            $qry .= " AND DATE(t1.report_time) >= '{$start_date}' ";
        }

        if(!empty($end_date)){
            $qry .= " AND DATE(t1.report_time) <= '{$end_date}' ";
        }

        $qry .= " ORDER BY ID DESC ";

        $paged = max( get_query_var( 'paged' ), 1 );
        $page = max( get_query_var( 'page' ), 1 );
        $paged = max( $paged, $page );

        $offset = $paged > 1 ? intval($this->posts_per_page) * ( $paged - 1 ) : 0;

        if( $this->posts_per_page > 0 ) $qry .= " LIMIT {$this->posts_per_page} ";
        if( $offset > 0 ) $qry .= " OFFSET {$offset} ";

        return $qry;
    }

    /**
     * Insert data
     *
     * @return array
     *
     * @param array $data
     *
     * @since 1.0
     * @access public
     */
    public static function insert( $data = [] )
    {

        $insert_data = [];
        $insert_data['user_id'] = intval($data['user_id'] ?? 0);
        $insert_data['device_id'] = intval($data['device_id'] ?? 0);
        $insert_data['uaid'] = intval($data['uaid'] ?? 0);
        $insert_data['report_time'] = sanitize_text_field($data['report_time']);
        $insert_data['branch_id'] = intval($data['branch_id'] ?? 0);
        if(!empty($data['auth_type'])){
            $insert_data['auth_type'] = sanitize_text_field($data['auth_type']);
        }

        $self = new self();
        global $wpdb;

        $insert = $wpdb->insert(
            $self->table,
            $insert_data
        );

        return array(
            'status' => $insert ? 1 : 0,
            'data' => $insert ? 'successful' : $wpdb->last_error,
            'query' => $wpdb->last_query,
            'error' => $wpdb->last_error
        );
    }

    /**
     * Get user id by meta value
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public static function getUserIdByMeta( $key, $value )
    {
        global $wpdb;
        return $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = '{$key}' AND meta_value = '{$value}' ");
    }

    /**
     * Publish an entry
     *
     * @return int
     *
     * @param array $data
     * @since 1.0
     * @access public
     */
    public function publish( $data = [] )
    {

        $insert_data = [];

        $report_time = esc_url($data['report_time'] ?? '');
        $report_time = empty($report_time) ? current_time('mysql') : date('Y-m-d H:i:s', strtotime($report_time));

        $insert_data['branch_id'] = intval($data['branch_id'] ?? 0);
        $insert_data['device_id'] = intval($data['device_id'] ?? 0);
        $insert_data['user_id'] = intval($data['user_id'] ?? 0);
        $insert_data['report_time'] = $report_time;

        global $wpdb;

        $insert = $wpdb->insert(
            $this->table,
            $insert_data
        );

        return $insert ? $wpdb->insert_id : 0;

    }

    /**
     * Get available devices
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getDevices()
    {

        $devices = trim(Admin::getSetting( 'attendance_devices' ));

        $default_device = array( 1=> 'Main Gate' );

        if(empty($devices)) return $default_device;

        $gates = explode(',', $devices );

        if(empty($gates)) return $default_device;

        $all = [];

        foreach($gates as $device ){

            $device_split = explode(':', $device);
            $k = intval(trim($device_split[0]));
            $v = sanitize_text_field($device_split[1]);

            $all[$k] = $v;

        }

        return $all;

    }

    /**
     * Filter publish fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterPublishFields( $fields )
    {

        $fields = [];
        $branch = new Branch();
        $fields['branch_id'] = array(
            'type'  => 'select',
            'name'  => 'branch_id',
            'settings' => array(
                'options' => $branch->getPosts( [], true ),
                'required' => true,
                'placeholder' => 'Select a branch',
                'label' => 'Branch'
            )
        );
        $fields['device_id'] = array(
            'type'  => 'select',
            'name'  => 'device_id',
            'settings' => array(
                'options' => self::getDevices(),
                'required' => true,
                'label' => 'Device'
            )
        );
        $fields['a_user'] = array(
            'type'  => 'text',
            'name'  => 'a_user',
            'settings' => array(
                'required' => true,
                'label' => 'User Name',
                'placeholder' => 'Type a name...',
                'class' => 'a_user'
            )
        );
        $fields['user_id'] = array(
            'type'  => 'text',
            'name'  => 'user_id',
            'settings' => array(
                'required' => true,
                'label' => 'User Id',
                'readonly' => true,
                'class' => 'user_id',
            )
        );
        $fields['report_time'] = array(
            'type'  => 'datetime-local',
            'name'  => 'report_time',
            'settings' => array(
                'required' => true,
                'label' => 'Attendance Time',
                'value' => current_time('mysql'),
            )
        );

        return $fields;

    }

    /*
     * Log submitted data
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function logData()
    {

        // Setting cron job and logging data
        if(isset($_REQUEST['syncAttendanceLogs'])){
            // Sync attendance logs
            var_dump(self::sync());
        }
    }

    /**
     * Sync attendance data
     *
     * @return string | int
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function sync()
    {
        $data = self::prepareSyncRequest();

        if( $data['status'] !== 200 ) return $data['body_response']['message'];

        if(empty($data['body_response']['data'])) return 'No data found';

        $sms_notif = Admin::getSetting('attendance_sms');
        $admin_notif = Admin::getSetting('attendance_sms_to_admin');
        $institute = Admin::getSetting('institute_name');

        global $wpdb;
        foreach($data['body_response']['data'] as $data){

            $aid = $data['user_id'] ?? null;
            $user_id = User::getIdByAttendanceId($aid);
            $array = array(
                'user_id'    => $user_id,
                'report_time' => $data['report_time'] ?? null,
                'record_time' => $data['record_time'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'uaid'  => $data['uaid'] ?? null,
                'auth_type' => $data['auth_type'] ?? null,
            );

            $insert = $wpdb->insert( $wpdb->prefix.'attendance', $array );
            if(!$insert){

                var_dump($wpdb->last_error);
                continue;

            } else if($user_id && $insert){

                if( $sms_notif !== 'active' && $admin_notif !== 'active') continue;

                $aid = $wpdb->insert_id;

                $time = date("h:i:s a, d/m/y", strtotime($data['report_time']));
                $user = new User($user_id);
                $name = $user->getMeta('first_name');
                $role = $user->getRole();

                $branch = $data['branch_id'] ? get_the_title($data['branch_id']) : '';
                if(!empty($branch)) $branch = " $branch Branch ";

                $action = self::getCardPunchStatus( $user_id, $data['report_time']);
                $action = $action === 'entry' ? 'entered' : 'left';

                $notifs = ['guardian_notification','admin_notification'];
                foreach($notifs as $notif){

                    if($notif == 'guardian_notification'){

                        if( strtolower($sms_notif) !== 'active' || strtolower($role) !== 'student' ) continue;
                        $mobile = get_user_meta($user_id, 'mobile', true);
                        if(!$mobile) continue;

                    }

                    if($notif == 'admin_notification'){

                        if($admin_notif !== 'active') continue;
                        $allowed_roles = Admin::getSetting('attendance_sms_to_admin_for_roles');
                        $allowed_roles = array_map('strtolower', $allowed_roles);
                        if(!in_array($role, $allowed_roles)) continue;
                        $mobile = Admin::getSetting('attendance_sms_to_admin_numbers');

                    }

                    $sms_format_key = $notif === 'guardian_notification' ? 'attendance_sms_format' : 'attendance_sms_format_to_admin';
                    $sms_text = Admin::getSetting($sms_format_key);
                    $sms_text = str_replace("{name}", $name, $sms_text);
                    $sms_text = str_replace("{role}", $role, $sms_text);
                    $sms_text = str_replace("{action}", $action, $sms_text);
                    $sms_text = str_replace("{time}", $time, $sms_text);
                    $sms_text = str_replace("{institute}", $institute, $sms_text);
                    $sms_text = str_replace("{branch}", $branch, $sms_text);

                    $sms_text = trim($sms_text);
                    if(empty($sms_text)) return false;

                    $sms = [];
                    $sms['sms'] = $sms_text;
                    $sms['mobile'] = $mobile;
                    $sms['user_id'] = $user_id;
                    $send = SMS::send($sms);
                    if($send['insert']){
                        $wpdb->update(
                            $wpdb->prefix.'attendance',
                            array(
                                'sms_id' => $send['insert'],
                            ),
                            array(
                                'id'    => $aid,
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Prepare sync request
     *
     * @return array | string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function prepareSyncRequest()
    {
        global $wpdb;

        $api_key = Admin::getSetting('attendance_api_key');
        $device = Admin::getSetting('attendance_device_name');
        $ids = Admin::getSetting('attendance_device_id', []);

        $body = array(
            'api_key' => $api_key,
            'device_name' => $device,
            'data' => array(),
        );

        // Merging all ids
        if(!empty($ids)) $ids = array_map('intval', $ids);

        if(!empty($ids)){
            foreach($ids as $id){
                $qry = "SELECT report_time FROM {$wpdb->prefix}attendance WHERE device_id = '{$id}' ORDER BY report_time DESC LIMIT 1";
                $time = $wpdb->get_var($qry);
                $body['data'][] = array(
                    'branch_id' => Admin::getSetting('attendance_device_'.$id.'_branch_id'),
                    'device_id' => $id,
                    'time' => $time,
                    'qry' => $qry
                );
            }
        }

        $body['device_id'] = implode(',', $ids);

        $endpoint = EduPress::getApiBaseUrl() . '/logs';
        $args = array(
            'method' => 'GET',
            'body' => $body,
        );

        $response = wp_remote_get($endpoint, $args);

        // Check for errors
        if(is_wp_error($response)) {
            return $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);

        return json_decode($body, true);

    }

    /**
     * Get remote attendance id
     *
     * @param int $user_id
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getRemoteId( $user_id )
    {
        $body['token'] = Admin::getAttendanceToken();
        $body['api_key'] = Admin::getSetting('attendance_api_key');
        $body['user_id'] = $user_id;
        $body['system_uid'] = Admin::getSystemUid();

        $args = array(
            'method' => 'POST',
            'body' => $body,
        );

        $url = EduPress::getApiBaseUrl() . '/registerUser';
        $response = wp_remote_post($url, $args);
        $body = wp_remote_retrieve_body($response);
        $body = json_decode($body, true);
        if($body['status'] == 200 && $body['body_response']['message'] == 'ok') return $body['body_response']['data']['user_id'] ?? $body['body_response']['data'];
        return $body['body_response']['data'];
    }

    /**
     * Checking if the card punch is enter or exit
     *
     * @param int $user_id
     * @param string $date
     *
     * @return string
     *
     * @since 1.0
     * @accces public
     * @static
     */
    public static function getCardPunchStatus($user_id, $date='today')
    {
        $date = $date === 'today' ? current_time('Y-m-d') : date('Y-m-d', strtotime($date));
        global $wpdb;
        $qry = "SELECT COUNT(*) FROM {$wpdb->prefix}attendance WHERE user_id = {$user_id} AND DATE(report_time) = '{$date}'";
        $count = (int) $wpdb->get_var($qry);
        if($count > 0) $count = $count - 1;
        if($count === 0) return 'entry';
        if($count === 1) return 'exit';
        return $count % 2 === 0 ? 'entry' : 'exit';

    }

    /**
     * Get list filter fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getListFilterFields( $fields )
    {

        $branch = new Branch();
        $branch_options = $branch->getPosts( [], true );
        $branch_id = $_REQUEST['branch_id'] ?? 0;

        $fields = [];
        $fields['report_type'] = array(
            'type' => 'select',
            'name' => 'report_type',
            'settings' => array(
                'label' => 'Report Type',
                'id'=>'report_type',
                'options' => array('logs' => 'Logs', 'detailed' => 'Detailed', 'summary' => 'Summary' ),
                'required' => true,
                'value' => sanitize_text_field($_REQUEST['report_type'] ?? 'logs'),
            )
        );
        $fields['branch_id'] = array(
            'type' => 'select',
            'name' => 'branch_id',
            'settings' => array(
                'label' => 'Branch',
                'id'=>'branch_id',
                'options' => $branch_options,
                'placeholder' => 'Select',
                'required' => true,
                'value' => sanitize_text_field($branch_id),
            )
        );
        $fields['role'] = array(
            'type' => 'select',
            'name' => 'role',
            'settings' => array(
                'label' => 'Role',
                'id'=>'role',
                'options' => array_combine(User::getRoles(),User::getRoles()),
                'value' => sanitize_text_field($_REQUEST['role'] ?? ''),
                'placeholder' => 'Select',
            )
        );
        if(Admin::getSetting('shift_active') == 'active'){
            $fields['shift_id'] = array(
                'type' => 'select',
                'name' => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'id'=>'shift_id',
                    'placeholder' => 'Select',
                )
            );
        }
        if(Admin::getSetting('class_active') == 'active'){
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'id'=>'class_id',
                    'placeholder' => 'Select',
                )
            );
        }
        if(Admin::getSetting('section_active') == 'active'){
            $fields['section_id'] = array(
                'type' => 'select',
                'name' => 'section_id',
                'settings' => array(
                    'label' => 'Section',
                    'id'=>'section_id',
                    'placeholder' => 'Select',
                )
            );
        }

        $fields['first_name'] = array(
            'type' => 'text',
            'name' => 'first_name',
            'settings' => array(
                'label' => 'Name',
                'id'=>'name',
                'placeholder' => 'Name',
                'value' => sanitize_text_field($_REQUEST['first_name'] ?? ''),
            )
        );
        $fields['roll'] = array(
            'type' => 'number',
            'name' => 'roll',
            'settings' => array(
                'label' => 'Roll / Student ID',
                'id'=>'roll',
                'placeholder' => 'Roll / Student ID',
                'value' => sanitize_text_field($_REQUEST['roll'] ?? ''),
            )
        );
        $fields['start_date'] = array(
            'type' => 'date',
            'name' => 'start_date',
            'settings' => array(
                'label' => 'Start Date',
                'id'=>'start_date',
                'placeholder' => 'Start Date',
                'value' => sanitize_text_field($_REQUEST['start_date'] ?? current_time('Y-m-d')),
            )
        );
        $fields['end_date'] = array(
            'type' => 'date',
            'name' => 'end_date',
            'settings' => array(
                'label' => 'End Date',
                'id'=>'end_date',
                'placeholder' => 'End Date',
                'value' => sanitize_text_field($_REQUEST['end_date'] ?? current_time('Y-m-d')),
            )
        );
        $fields['posts_per_page'] = array(
            'type' => 'select',
            'name' => 'posts_per_page',
            'settings' => array(
                'label' => 'Items Per Page',
                'id'=>'posts_per_page',
                'options' => array( 25 => 25, 50 => 50, 100 => 100, -1 => 'All' ),
                'value' => sanitize_text_field($_REQUEST['posts_per_page'] ?? 25),
            )
        );

        return $fields;

    }

    /**
     * Check if attendance has logs
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public static function hasLogs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'attendance';
        return (boolean) $wpdb->get_var("SELECT EXISTS(SELECT 1 FROM {$table})");

    }

    /**
     * Check if a user is present on a certain day or not
     *
     * @param int $user_id
     * @param string $date
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public static function isUserPresent($user_id, $date)
    {
        if( Admin::getSetting('attendance_active') != 'active' ) return true;
        if( !$user_id || empty($date) ) return false;
        global $wpdb;
        $table = $wpdb->prefix . 'attendance';
        $qry = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND DATE(report_time) = %s ", $user_id, date('Y-m-d', strtotime($date)));
        return (bool) $wpdb->get_var($qry);
    }

    /**
     * Show option to add manual attendance
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function showOptionToAddManualAttendance()
    {
        $branch = new Branch();
        $branch_options = $branch->getPosts( [], true );
        $fields = [];
        $fields['branch_id'] = array(
            'type' => 'select',
            'name' => 'branch_id',
            'settings' => array(
                'label' => 'Branch',
                'id'=>'branch_id',
                'options' => $branch_options,
                'placeholder' => 'Select',
                'required' => true,
            )
        );

        if(EduPress::isActive('shift')){
            $fields['shift_id'] = array(
                'type' => 'select',
                'name' => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'id'=>'shift_id',
                    'placeholder' => 'Select',
                    'required' => true,
                )
            );
        }

        if(EduPress::isActive('class')){
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'id'=>'class_id',
                    'placeholder' => 'Select',
                    'required' => true,
                )
            );
        }

        if(EduPress::isActive('section')){
            $fields['section_id'] = array(
                'type' => 'select',
                'name' => 'section_id',
                'settings' => array(
                    'label' => 'Section',
                    'id'=>'section_id',
                    'placeholder' => 'Select',
                    'required' => true,
                )
            );
        }



        ob_start();
        ?>
        <h3 class="text-center"><?php echo __('Add Manual Attendance', 'edupress'); ?></h3>
        <form action="" class="edupress-form edupress-ajax-form edupress-filter-list">
            <?php foreach($fields as $field): ?>
               <div class="form-column">
                    <div class="label-wrap">
                        <label for="<?php echo $field['settings']['id']; ?>"><?php echo $field['settings']['label']; ?></label>
                    </div>
                    <div class="value-wrap">
                        <?php echo EduPress::generateFormElement($field['type'], $field['name'], $field['settings']); ?>
                    </div>
               </div>
            <?php endforeach; ?>
            <div class="form-column">
                <div class="label-wrap"><label> &nbsp; </label></div>
                <div class="value-wrap">
                    <?php 
                        echo EduPress::generateFormElement('submit', 'submit', array( 'value' => 'Show' )); 
                        echo EduPress::generateFormElement('hidden', 'ajax_action', array( 'value' => 'showUsersToAddManualAttendance' )); 
                        echo EduPress::generateFormElement('hidden', 'action', array( 'value' => 'edupress_admin_ajax' )); 
                        echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value' => 'insertAttendanceUsersInPopup' ) ); 
                        wp_nonce_field('edupress', '_wpnonce');
                    ?>
                </div>
            </div>
        </form>

        <div class="attendance-users-wrap"></div>
        <?php 
        return ob_get_clean();

    }

    /**
     * Show users to add manual attendance
     *
     * @param array $args
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function showUsersToAddManualAttendance($args)
    {
        $users = User::getAll( $args );
        ob_start();
        ?>
        <style>
            table tr th,
            table tr td{
                text-align: left;
            }
        </style>
        <form class="edupress-form edupress-ajax-form attendance-users-list">
            <table class="edupress-table" style="width: 100%;max-width: 750px;margin: 0;">
                <thead> 
                    <tr>
                        <th>Roll</th>
                        <th>Name</th>
                        <th>
                            Status
                            <?php echo EduPress::generateFormElement('select', 'attendance-bulk-status', array( 'class' => 'attendance-bulk-status', 'options' => array( '' => 'Select', 'present' => 'Present', 'absent' => 'Absent' ), 'value' => '' )); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach($users as $user): $user = new User($user); ?>
                <?php 
                    $is_present = self::isUserPresent($user->id, current_time('Y-m-d'));
                    $style = $is_present ? 'background-color: #8ec5321c !important;' : '';
                ?>
                <tr>
                    <td style="<?php echo $style; ?>"><?php echo $user->getMeta('roll'); ?></td>
                    <td style="<?php echo $style; ?>"><?php echo $user->getMeta('first_name') . ' ' . $user->getMeta('last_name'); ?></td>
                    <td style="<?php echo $style; ?>">
                        <input type="hidden" name="user_id[]" value="<?php echo $user->id; ?>">
                        <input type="hidden" name="attendance_id[]" value="<?php echo $user->getMeta('attendance_id'); ?>">
                        <?php echo EduPress::generateFormElement('select', 'status[]', array( 'options' => array( '' => 'Select', 'present' => 'Present', 'absent' => 'Absent' ), 'value' => '' )); ?>
                        <?php if($is_present): echo "<strong>Already Present</strong>"; endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <td colspan="4">
                        <?php 
                            echo EduPress::generateFormElement('hidden', 'branch_id', array( 'value' => $args['branch_id'] ));
                            echo EduPress::generateFormElement('hidden', 'class_id', array( 'value' => $args['class_id'] ));
                            echo EduPress::generateFormElement('hidden', 'section_id', array( 'value' => $args['section_id'] ));
                            echo EduPress::generateFormElement('hidden', 'shift_id', array( 'value' => $args['shift_id'] ));
                            echo EduPress::generateFormElement('submit', 'submit', array( 'value' => 'Save' )); 
                            echo EduPress::generateFormElement('hidden', 'ajax_action', array( 'value' => 'insertManualAttendance' )); 
                            echo EduPress::generateFormElement('hidden', 'action', array( 'value' => 'edupress_admin_ajax' )); 
                            wp_nonce_field('edupress', '_wpnonce');
                        ?>
                    </td>
                </tr>
            </table>
        </form>
        <?php 
        return ob_get_clean();
    }

    /**
     * Save log summary 
     * 
     * @return mixed 
     * 
     * @param string $date 
     * 
     * @since 1.0   
     * @access public
     */
    public static function saveLogSummary($branch_id, $date)
    {
        $date = new \DateTime($date);
        $date_format = $date->format('Y-m-d');
        $today = current_time('Y-m-d');
        if( $date_format == $today ) return 'Cannot save log summary for today.';
        if(empty($branch_id)) return 'Branch ID is required.';

        global $wpdb;
        $table = $wpdb->prefix . 'attendance';
        $qry = $wpdb->prepare("SELECT COUNT(*) as log_count, COUNT(DISTINCT user_id) as user_count FROM {$table} WHERE branch_id = %d AND DATE(report_time) = %s", $branch_id, $date_format);
        $row = $wpdb->get_row($qry, ARRAY_A);
        $insert =$wpdb->insert($wpdb->prefix . 'attendance_summary', array(
            'branch_id' => $branch_id,
            'date' => $date_format,
            'log_count' => $row['log_count'],
            'user_count' => $row['user_count'],
        ));
        $wpdb->flush();
        return $insert ? $wpdb->insert_id : $wpdb->last_error;

    }

    /**
     * Delete log after storing log summary
     *
     * @param int $id
     *
     * @since 1.0
     * @access public
     */
    public static function deleteLog($branch_id, $date)
    {
        $date = new \DateTime($date);
        $date_format = $date->format('Y-m-d');
        $today = current_time('Y-m-d');
        if(empty($branch_id)) return 'Branch ID is required.';
        if( $date_format == $today ) return 'Cannot delete log for today.';
        if( $date_format > $today ) return 'Cannot delete log for future date.';

        self::saveLogSummary($branch_id, $date_format);
        
        global $wpdb;
        $table = $wpdb->prefix . 'attendance';
        $qry = $wpdb->prepare("DELETE FROM {$table} WHERE branch_id = %d AND DATE(report_time) = %s", $branch_id, $date_format);
        $wpdb->query($qry);
        $wpdb->flush();

    }

    /**
     * Delete schedule log 
     * 
     * @return void
     * 
     * @since 1.0
     * @access public
     */
    public static function scheduleDeleteLog()
    {
        global $wpdb;
        $qry = $wpdb->prepare("SELECT DISTINCT(branch_id) as branch_id, MIN(DATE(report_time)) as min_date, MAX(DATE(report_time)) as max_date FROM {$wpdb->prefix}attendance WHERE DATE(report_time) < %s", current_time('Y-m-d'));
        $rows = $wpdb->get_results($qry);
        foreach($rows as $row){
            $start_date = $row->min_date;
            $end_date = $row->max_date;
            $branch_id = $row->branch_id;
            $store_log = Admin::getSetting('attendance_store_log', 45);
    
            $start_date = new \DateTime($start_date);
            $end_date = new \DateTime($end_date);
            $end_date = $end_date->modify('-' . $store_log . ' days');
    
            $period = new \DatePeriod($start_date, new \DateInterval('P1D'), $end_date);
            foreach($period as $date){
                $date_format = $date->format('Y-m-d');
                self::deleteLog($branch_id, $date_format);
            }
        }
    }




}

Attendance::instance();