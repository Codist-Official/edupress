<?php
namespace EduPress;

/*
Plugin Name: EduPress
Plugin URI: https://edupressbd.com/
Description: School Management Software
Author: Mohammad Nur Hossain
Version: 1.5.5
Author URI: https://nur.codist.dev/
Text Domain: edupress
Domain Path: /languages
Tested up to: 6.5.1
*/

defined( 'ABSPATH' ) || die(); 

/**
 * Definining plugin dir path and url
 */
if( !defined( 'EDUPRESS_VERSION') ) define( 'EDUPRESS_VERSION', '1.5.5' );
if( !defined( 'EDUPRESS_PATH') ) define('EDUPRESS_PATH', plugin_dir_path( __FILE__ ) );
if( !defined( 'EDUPRESS_CLASS_DIR') ) define( 'EDUPRESS_CLASS_DIR', EDUPRESS_PATH .'includes/class/' );
if( !defined( 'EDUPRESS_ADMIN_DIR') ) define( 'EDUPRESS_ADMIN_DIR', EDUPRESS_PATH .'includes/admin/' );
if( !defined( 'EDUPRESS_LIB_DIR') ) define( 'EDUPRESS_LIB_DIR', EDUPRESS_PATH .'includes/libs/' );
if( !defined( 'EDUPRESS_URL') ) define( 'EDUPRESS_URL', plugin_dir_url( __FILE__ ) );
if( !defined( 'EDUPRESS_IMG_URL') ) define( 'EDUPRESS_IMG_URL', EDUPRESS_URL . 'assets/img/' );
if( !defined( 'EDUPRESS_CSS_URL') ) define( 'EDUPRESS_CSS_URL', EDUPRESS_URL . 'assets/css/' );
if( !defined( 'EDUPRESS_JS_URL') ) define( 'EDUPRESS_JS_URL', EDUPRESS_URL . 'assets/js/' );
if( !defined( 'EDUPRESS_SEND_SMS') ) define( 'EDUPRESS_SEND_SMS', true );
if( !defined( 'EDUPRESS_TEMPLATES_DIR') ) define( 'EDUPRESS_TEMPLATES_DIR', EDUPRESS_PATH . '/templates/' );
if( !defined( 'EDUPRESS_ID_TEMPLATES_DIR') ) define( 'EDUPRESS_ID_TEMPLATES_DIR', EDUPRESS_TEMPLATES_DIR . 'id-card/' );
if( !defined( 'EDUPRESS_ID_TEMPLATES_URL') ) define( 'EDUPRESS_ID_TEMPLATES_URL', EDUPRESS_URL . 'templates/id-card/' );
if( !defined( 'EDUPRESS_ID_TEMPLATES_IMG_URL') ) define( 'EDUPRESS_ID_TEMPLATES_IMG_URL', EDUPRESS_ID_TEMPLATES_URL . 'assets/img/' );
if( !defined( 'EDUPRESS_ID_TEMPLATES_CSS_URL') ) define( 'EDUPRESS_ID_TEMPLATES_CSS_URL', EDUPRESS_ID_TEMPLATES_URL . 'assets/css/' );

require EDUPRESS_LIB_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/Codist-Official/edupress/',
	__FILE__,
	'edupress'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

class EduPress
{

    /**
     * Current instance
     */
    private static $_instance;

    /**
     * Initiate Instance
     *
     * @return EduPress
     *
     * @since 1.0
     * @acccess public
     * @static
     */
    public static function instance( )
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Constructor
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function __construct()
    {

        // Include class files
        $this->includeClassFiles();

        // Create table
        register_activation_hook( __FILE__, [ $this, 'createCustomTables' ] );

        // Register Scripts & Styles
        add_action( 'wp_enqueue_scripts', [ $this, 'registerScripts' ] );

        // Enabling ajax
        add_filter( 'edupress_admin_settings_ajax_active', '__return_true' );

        // Footer HTML
        add_action( 'wp_footer', [ $this, 'printFooterHtml' ] );

        // Filter list item value
        add_filter( 'edupress_list_item_value', [ $this, 'filterListItemValue'], 10, 3 );

        // Add 5 mins cron
        add_filter('cron_schedules', [ $this, 'wpCustomCronIntervals' ] );

        // Schedule custom cron
        add_action( 'init', [ $this, 'wpScheduleCron' ]  );

        // Cron task
        add_action('ep_five_minute_event', [ $this, 'epFiveMinuteCronTask' ] );

        // Cron task
        add_action('ep_every_hour_event', [ $this, 'epHourlyCronTask' ] );

        // Cron task
        add_action('ep_every_day_event', [ $this, 'epDailyCronTask' ] );

        // database upgrade 
        add_action( 'plugins_loaded', [$this, 'checkDatabaseUpgrade'] );

        // Hide top bar
        add_filter( 'show_admin_bar' , function($show){
            if(!current_user_can('manage_options')) return false;
            return $show;
        });

    }


    /**
     * Include class files
     *
     * @return void
     *
     * @since 1.0
     * @acccess public
     */
    public function includeClassFiles()
    {

        // Admin Class
        include EDUPRESS_ADMIN_DIR . 'Admin.php';

        // Post Class
        include EDUPRESS_CLASS_DIR . 'Post.php';
        include EDUPRESS_CLASS_DIR . 'CustomPost.php';

        // Include all sms gateways
        foreach ( glob( EDUPRESS_CLASS_DIR . '/sms-gateways/' . '*.php') as $file ) {
            include( $file );
        }

        // Include all class
        foreach ( glob( EDUPRESS_CLASS_DIR . '*.php') as $file ) {
            // Skip post class
            if ( str_contains( $file, 'Post.php' ) ) continue;
            include( $file );
        }

        // Include admin class
        foreach ( glob( EDUPRESS_ADMIN_DIR . '*.php') as $file ) {
            if ( str_contains( $file, 'Admin.php' ) ) continue;
            include( $file );
        }

    }

    /**
     * Create custom tables for SMS
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function createCustomTables()
    {

        // Storing edupress install date
        $key = 'edupress_install_date';
        $value = get_option($key);
        if(empty($value)) update_option( $key, current_time('mysql'), 'no' );

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();

        // SMS logs
        $sql = "CREATE TABLE {$wpdb->prefix}sms_logs (
                id bigint NOT NULL AUTO_INCREMENT,
                branch_id int NULL, 
                mobile bigint NOT NULL, 
                user_id bigint NULL,
                sms varchar(1024) NOT NULL,
                sms_len int NULL,
                sms_rate float NULL,
                response_code smallint NULL, 
                response_id int NULL,
                record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
                PRIMARY KEY (id),
                INDEX xyz_sms_logs (mobile,user_id,sms_len,sms_rate,response_id)
            ) $charset_collate";
        dbDelta( $sql );

        // SMS stats
        $sql = "CREATE TABLE {$wpdb->prefix}sms_summary (
                id bigint NOT NULL AUTO_INCREMENT,
                branch_id int NOT NULL,
                `date` DATE NOT NULL,
                sms_count int NULL,
                sms_cost float NULL, 
                PRIMARY KEY (id),
                UNIQUE (`date`),
                UNIQUE INDEX sms_summary (branch_id, `date`)
            ) $charset_collate";
        dbDelta( $sql );
    

        // Attendance
        $sql = "CREATE TABLE {$wpdb->prefix}attendance (
                id bigint NOT NULL AUTO_INCREMENT,
                branch_id int NOT NULL,
                user_id int NOT NULL,
                device_id int NOT NULL DEFAULT 1,
                uaid int NULL,
                auth_type varchar(255) NULL,
                report_time TIMESTAMP NULL, 
                record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sms_id bigint NULL,
                PRIMARY KEY (id),
                INDEX xyz_attendance_logs (user_id,device_id,uaid,report_time,record_time),
                UNIQUE INDEX attendance_logs_device_time (device_id,report_time)
            ) $charset_collate";
        dbDelta( $sql );


        // Attendance Summary Logs
        $sql = "CREATE TABLE {$wpdb->prefix}attendance_summary (
            id bigint NOT NULL AUTO_INCREMENT,
            branch_id int NOT NULL,
            `date` DATE NOT NULL,
            log_count int NOT NULL,
            user_count int NULL,
            record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE INDEX attendance_summary (branch_id, `date`)
        ) $charset_collate";
        dbDelta( $sql );


        // Transaction
        $sql = "CREATE TABLE {$wpdb->prefix}transaction (
            id bigint NOT NULL AUTO_INCREMENT,
            branch_id bigint NOT NULL,
            shift_id bigint  NULL,
            class_id bigint NULL,
            section_id bigint NULL,
            is_inflow boolean NOT NULL DEFAULT 1,
            amount float,
            discount float,
            `user_id` bigint,
            account varchar(100) NOT NULL, 
            method varchar (10) NULL, 
            wc_order_id bigint NULL, 
            input_by bigint, 
            `status` varchar(25) DEFAULT 'pending',
            t_note varchar(1024) NULL,
            t_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            update_log text NULL, 
            PRIMARY KEY (id),
            INDEX transactions (branch_id,shift_id,class_id,section_id,is_inflow,amount,discount,user_id, account,method,wc_order_id,input_by,status)
        ) $charset_collate";
        dbDelta( $sql );

        // Transaction
        $sql = "CREATE TABLE {$wpdb->prefix}transaction_items (
            id bigint NOT NULL AUTO_INCREMENT,
            transaction_id bigint NOT NULL,
            item_name varchar(255) NOT NULL,
            item_amount float NOT NULL,
            item_month int NOT NULL, 
            item_year int NOT NULL,
            item_due float NULL, 
            PRIMARY KEY (id),
            INDEX transaction_items (transaction_id,item_name,item_amount, item_month,item_year,item_due)
        ) $charset_collate";
        dbDelta( $sql );

        // Update database 
        update_option( 'edupress_version', EDUPRESS_VERSION, 'no');

    }

    /**
     * Check datase upgrade 
     * 
     * @return void 
     */
    public function checkDatabaseUpgrade()
    {
        $current_version = get_option( 'edupress_version' );
        if( $current_version !== EDUPRESS_VERSION ) {
            $this->createCustomTables();
        }
    }

    /**
     * Register EduPress installation with central system
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function registerInstall()
    {
        $string = "SSERPUDE";
        return $string;

    }

    /**
     * Provide base url for api
     *
     * @return string
     *
     * @access public
     * @static
     */
    public static function getApiBaseUrl()
    {

        $domain = str_contains($_SERVER['HTTP_HOST'], 'localhost') ? "http://localhost/api.edupressbd.com" : "http://api.edupressbd.com";
        return $domain .'/wp-json/edupress_sync/v1';

    }

    /**
     * Generate attendance api key
     *
     * @return string
     *
     * @param string $token
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getApiKey( $token = '', $update = true )
    {

        if( empty($token) ) $token = Admin::getAttendanceToken();

        $end_point = self::getApiBaseUrl(). '/register';

        $body = array(
            'system_uid' => Admin::getSystemUid(),
            'token' => $token,
            'host' => site_url(),
        );

        $args = array(
            'method'    => 'POST',
            'body'      => $body,
        );

        $response = wp_remote_post( $end_point , $args );

        // Check for errors
        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        // Get the response body
        $body = wp_remote_retrieve_body($response);

        // Decode and display the response
        $data = json_decode($body, true);

        $key = $data['body_response']['data']['api_key'] ?? null;

        if($key && $update){
            // Updating api key
            Admin::updateSettings('attendance_api_key', $key);
        }

        return $key;

    }


    /**
     * Regsiter devices 
     * 
     * @return array
     * 
     * @since 1.0
     * @access public
     */
    public static function registerDevice( $data = [] )
    {

        $base_url = self::getApiBaseUrl();

        $end_point = $base_url . '/registerDevice';

        $device_name = sanitize_text_field($data['device_name'] ?? '' );
        $device_count = intval($data['device_count'] ?? 0);

        $api_key = Admin::getSetting('attendance_api_key');
        if(empty($api_key)) $api_key = self::getApiKey();

        $payload = array(
            'system_uid' => Admin::getSystemUid(),
            'token' => Admin::getAttendanceToken(),
            'api_key' => $api_key,
            'device_name' => $device_name,
            'device_count' => $device_count,
        );

        $args = array(
            'method'    => 'POST',
            'body'      => $payload,
        );

        $response = wp_remote_post( $end_point , $args );

        if( is_wp_error($response) ) return 'Error: ' . $response->get_error_message();

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $ids = array_column($data['data'], 'id');
        Admin::updateSettings('attendance_device_id', $ids );
        $payload['ids'] = $ids;
        return $payload;
    }



    /**
     * Filter list item value
     *
     * @param $v
     * @param $k
     * @param int $post_id
     * @return string
     *
     * @throws \Exception
     * @since 1.0
     * @access public
     *
     */
    public function filterListItemValue($v, $k, $post )
    {
        $value = $v;
        if( in_array($post->post_type, array('branch', 'class', 'section')) && $k == 'calendar'){

            $calendar_data = get_post_meta( $post->ID, 'academic_calendar', true );
            $value = '';
            if(empty($calendar_data) && User::currentUserCan('publish', 'calendar')){
                $value .= "<a data-uid='calendarEditBtnUid' data-post_type='calendar' data-success_callback='editCalendarSuccessCallback' data-post_id='{$post->ID}' data-ajax_action='getPostEditForm' href='javascript:void(0)' class='showEditCalendarScreen edupress-ajax edupress-ajax-link'>".EduPress::getIcon('create')."</a>";
            } else {
                if(User::currentUserCan('edit', 'calendar')):
                    $value .= "<a data-uid='calendarEditBtnUid' data-post_type='calendar' data-success_callback='editCalendarSuccessCallback' data-post_id='{$post->ID}' data-ajax_action='getPostEditForm' href='javascript:void(0)' class='showEditCalendarScreen edupress-ajax edupress-ajax-link'>".EduPress::getIcon('edit')."</a>";
                endif;
                if(User::currentUserCan('delete', 'calendar')):
                    $value .= "<a data-post-type='calendar' data-before_send_callback='deleteCalendarBeforeSendCallback' data-success_callback='deleteCalendarSuccessCallback' data-action='edupress_admin_ajax' data-id='{$post->ID}' data-post_id='{$post->ID}' data-ajax_action='deleteCalendar' href='javascript:void(0)' class='edupress-ajax edupress-ajax-link'>".EduPress::getIcon('delete')."</a>";
                endif;
                $value .= "<a data-uid='calendarEditBtnUid' data-post_type='calendar' data-success_callback='editCalendarSuccessCallback' data-post_id='{$post->ID}' data-ajax_action='getPostEditForm' href='javascript:void(0)' class='showEditCalendarScreen edupress-ajax edupress-ajax-link'>".EduPress::getIcon('view')."</a>";
            }
            
        } else if ( $k == 'view_action' && $post->post_type == 'notice'){

            $value = "<a href='javascript:void(0)' class='edupress-ajax-link' data-success_callback='showPopupOnCallback' data-ajax_action='viewPost' data-post-type='{$post->post_type}' data-id='{$post->ID}' href='javascript:void(0)'>View</a> ";

        } else {

            switch (strtolower(trim($k))){
                case 'branch_id':
                case 'class_id':
                case 'shift_id':
                case 'section_id':
                case 'subject_id':
                case 'connected_subject_id':
                case 'term_id':
                    if(!empty($v)){
                        $value = get_the_title( $v );
                    }
                    break;
    
                case 'total':
                    $post = get_post($post->ID);
                    $post_key = $post->post_type . '_id';
                    $count = User::countStudents( array($post_key=>$post->ID));
                    $link = '#base_url#';
                    if($post->post_type === 'branch'){
                        $link .= '?branch_id='.$post->ID;
                    } else if($post->post_type === 'shift'){
                        $branch_id = get_post_meta($post->ID, 'branch_id', true );
                        $link .= "?branch_id=$branch_id&shift_id=$post->ID";
                    } else if($post->post_type === 'class'){
                        $branch_id = get_post_meta($post->ID, 'branch_id', true );
                        $shift_id = get_post_meta($post->ID, 'shift_id', true );
                        $link .= "?branch_id=$branch_id&shift_id=$shift_id&class_id=$post->ID";
                    } else if ( $post->post_type === 'section'){
                        $branch_id = get_post_meta($post->ID, 'branch_id', true );
                        $shift_id = get_post_meta($post->ID, 'shift_id', true );
                        $class_id = get_post_meta($post->ID, 'class_id', true );
                        $link .= "?branch_id=$branch_id&shift_id=$shift_id&class_id=$class_id&section_id=$post->ID";
                    }
    
                    $link .= '&panel=user&role=student';
    
                    $value = "<a href='{$link}'>{$count}</a>";
                    break;
    
                case 'exam_date':
                case 'start_date':
                case 'end_date':
                    if( !empty($v) ){
                        $datetime = new \DateTime(date('Y-m-d', strtotime($v)));
                        $value = $datetime->format('d/m/y');
                    }
                    break;
    
                case 'exam_time':
                    $value = date("g.i A", strtotime($v));
                    break;
    
                case 'grade_data':
                    ob_start();
                    $value = maybe_unserialize($value);
                    ?>
                    <div class="edupress-table-wrap">
                        <table class="edupress-table">
                            <tr>
                                <th>Range</th>
                                <th>Grade Point</th>
                                <th>Grade</th>
                            </tr>
                            <?php
                                for( $i =0; $i < count($value['grade']); $i ++ ){
                                    ?>
                                        <tr>
                                        <td><?php echo $value['range_start'][$i] . ' - ' . $value['range_end'][$i]; ?></td>
                                        <td><?php echo number_format( $value['grade_point'][$i], 2 ); ?></td>
                                        <td><?php echo $value['grade'][$i]; ?></td>
                                        </tr>
                                    <?php
                                }
                            ?>
                        </table>
                    </div>
                    <?php
                    $value = ob_get_clean();
                    break;
    
                default:
                    break;
            }
        }

        return $value;
    }

    /**
     * Get current url
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function getCurrentUrl()
    {

        $protocol = is_ssl() ? 'https://' : 'http://';

        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    }

    /**
     * Change url param
     *
     * @param string $url
     * @param string $param_name
     * @param string $new_value
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function changeUrlParam( $url, $param_name, $new_value )
    {

        // Parse the URL into its components
        $url_parts = parse_url( $url );

        // Parse the query string into an associative array
        if ( isset( $url_parts['query'])){

            parse_str($url_parts['query'], $query_params);

        }

        // Change the value of the specified parameter
        $query_params[$param_name] = $new_value;

        // Rebuild the query string
        $url_parts['query'] = http_build_query($query_params);

        // Reconstruct the URL
        $new_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
        if (!empty($url_parts['query'])) {

            $new_url .= '?' . $url_parts['query'];

        }

        if (!empty($url_parts['fragment'])) {

            $new_url .= '#' . $url_parts['fragment'];

        }

        return $new_url;

    }

    /**
     * Generate HTML form element
     *
     * @return string
     *
     * @param string $type
     * @param string $name
     * @param array $settings
     *
     * @since 1.0
     * @acecess public
     * @static
     */
    public static function generateFormElement( $type, $name, array $settings = array() )
    {
        $required = $settings['required'] ?? false;
        $readonly = $settings['readonly'] ?? false;
        $disabled = $settings['disabled'] ?? false;
        $button = $settings['button'] ?? false;
        $placeholder = $settings['placeholder'] ?? '';
        $value = $settings['value'] ?? '';
        $id = $settings['id'] ?? '';
        $class = $settings['class'] ?? '';
        $options = $settings['options'] ?? '';
        $data = $settings['data'] ?? '';
        $before = $settings['before'] ?? '';
        $after = $settings['after'] ?? '';

        if ( !empty($options) && !is_array($options) ) $options = explode( ',', $options );

        $required_string = '';
        $readonly_string = '';
        $disabled_string = '';

        if( $required ) $required_string = " required='required' aria-required='true' ";
        if( $readonly ) $readonly_string = " readonly='readonly' aria-readonly='true' ";
        if( $disabled ) $disabled_string = " disabled='disabled' aria-disabled='true' ";

        $data_string = '';
        if( !empty( $data ) ){
            foreach( $data as $k=>$v ){
                $data_string .= " {$k} = '{$v}' ";
            }
        }

        $html = '';

        $type = strtolower(trim($type));

        switch ($type) {
            case 'tel':
            case 'text':
            case 'number':
            case 'email':
            case 'phone':
            case 'url':
            case 'hidden':
            case 'submit':
            case 'date':
            case 'datetime-local':
            case 'time':
            case 'password':
            case 'file':

                $html = "<input name='{$name}' id='{$id}' class='{$class}' type='{$type}' placeholder='{$placeholder}' value='{$value}' {$readonly_string} {$required_string} {$disabled_string} {$data_string} >";
                break;

            case 'textarea':

                $html = "<textarea name='{$name}' id='{$id}' class='{$class}' {$required_string} {$disabled_string} {$readonly_string} {$data_string} placeholder='{$placeholder}'>{$value}</textarea>";
                break;

            case 'select':
            case 'dropdown':

                $html .= "<select id='{$id}' class='{$class}' name='{$name}' {$readonly_string} {$disabled_string} {$required_string} {$data_string}>";

                if( !empty($placeholder) ) $html .= "<option value=''>".__( $placeholder, 'edupress')."</option>";
                if( !is_array($options) ) $options = explode(',', $options);

                if( !empty($options) ){
                    foreach( $options as $k => $v ){
                        $selected = $k == $value ? ' selected ' : '';
                        $html .= "<option value='{$k}' {$selected}>{$v}</option>";
                    }
                }
                $html .= "</select>";
                break;

            case 'radio':
            case 'checkbox':

                if ( $type === 'checkbox' && !str_contains( $name, '[]') ) $name = $name . '[]';

                if ( !empty($options) ){

                    $first_item = true;
                    foreach( $options as $k => $v ){

                        $k = trim($k);
                        $v = trim($v);

                        $required_string = $first_item ? $required_string : '';

                        $clean_id = preg_replace('/[^a-zA-Z0-9\s]/', '', $v );

                        $value = !is_array( $value ) ? explode(',', $value) : $value;

                        $checked = in_array( $k, $value ) ?  ' checked ' : '';

                        $html .= " <span class='{$type}-item-wrap item-{$clean_id}'><input value='{$k}' type='{$type}' name='{$name}' id='{$clean_id}' {$checked} {$required_string}> ";
                        $html .= " <label for='{$clean_id}'>". __( $v, 'edupress' ) ."</label></span> &nbsp; ";

                        $first_item = false;

                    }
                }
                break;

            case 'html':
                $html = $settings['html'] ?? '';
                break;

            case 'button':
                $html = "<button type='button' class='{$class}' id='{$id}' placeholder='{$placeholder}' {$disabled_string} {$readonly_string} {$required_string} {$data_string}>{$value}</button>";
                break;
                
            default:
                break;
        }

        return $before . $html . $after ;

    }

    /**
     * Log debug data
     *
     * @return void
     *
     * @param string $data
     * @param string  $target
     *
     * @since 1.0
     */
    public static function logData( $data, $target = null )
    {

        if( empty( $target ) ) {
            $target_dir = WP_CONTENT_DIR . '/edupress-logs';
            $target_file = $target_dir . '/logs-'. current_time('m-d-Y') . '.txt';
            if( !file_exists($target_dir) ) mkdir( $target_dir, 0777, true );
        }

        // Encoding json
        if(is_array($data) || is_object($data)) $data = json_encode($data, true);
        $data = date('Y-m-d h:i:s A : ' ) . $data . "\r\n";
        file_put_contents( $target_file, $data . file_get_contents($target_file) );
    }

    /**
     * Check if user has valid license
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function hasValidProLicense()
    {
        return false;
    }

    /**
     * Get pro option notice
     *
     * @return string
     * @since 1.0
     * @access public
     * @static
     */
    public static function getProNotice( $notice = '' )
    {

        $notice = empty( $notice ) ? '(Only for pro version)' : sanitize_text_field($notice);
        return self::hasValidProLicense() ? '' : "<span class='edupress-form-pro-notice'>" . __( $notice, 'edupress' ) . "</span>";

    }

    /**
     * Register Scripts & Styles
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerScripts()
    {
        // css file
        wp_enqueue_style( 'edupress', EDUPRESS_CSS_URL.'edupress.css', array(), rand(1, 1000000), 'all' );

        // fontawesome 
        wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), rand(1, 1000000), 'all' );

        // jquery
        wp_enqueue_script('jquery' );

        // Autocomplete
        wp_enqueue_script('jquery-ui-autocomplete' );

        // JS file
        wp_enqueue_script( 'edupress', EDUPRESS_JS_URL.'edupress.js', array('jquery'), rand(1,1000000) );

        // tablesorter file
        wp_enqueue_script( 'tablesorter', EDUPRESS_JS_URL.'jquery.tablesorter.min.js', array('jquery'), rand(1,1000000) );

        // localize script 
        wp_localize_script(
            'edupress',
            'edupress',
            array(
                'ajax_action' => 'edupress_admin_ajax',
                'ajax_url' => admin_url('admin-ajax.php'),
                'img_dir_url' => EDUPRESS_IMG_URL,
                'wpnonce' => wp_create_nonce('edupress'),
                'branch_active' => EduPress::isActive('branch') ? 1 : 0,
                'shift_active' => EduPress::isActive('shift') ? 1 : 0,
                'class_active' => EduPress::isActive('class') ? 1 : 0,
                'section_active' => EduPress::isActive('section') ? 1 : 0,
                'subject_active' => EduPress::isActive('subject') ? 1 : 0,
                'term_active' => EduPress::isActive('term') ? 1 : 0,
                'sms_balance_refresh_sec' => 60,
                'sms_rate' => Admin::getSetting( 'sms_rate', 0.25 ),
                'sms_footer_len' => !empty(Admin::getSetting('sms_footer')) ? strlen(Admin::getSetting('sms_footer')) + 2 : 0,
                'page_url' => get_permalink(get_the_ID()),
                'active_panel' => $_REQUEST['panel'] ?? "",
                'transaction_sms' => Admin::getSetting('transaction_sms') == 'active' ? 1 : 0,
                'transaction_print' => Admin::getSetting('transaction_print') == 'active' ? 1 : 0,
            )
        );

    }

    /**
     * Return edupress class is ajax active otherwise empty
     *
     * @return string
     * @since 1.0
     * @access public
     * @static
     */
    public static function getClassNames( $default_class='', $object_type = 'link' )
    {

        $classes = is_array( $default_class ) ? $default_class : explode( ' ', $default_class );

        if ( Admin::isAjax() ) $classes[] = 'edupress-ajax';

        if ( $object_type == 'link' ) $classes[] = 'edupress-ajax-link';

        if ( $object_type == 'form' ) $classes[] = 'edupress-form';

        $classes = apply_filters( 'edupress_classes', $classes, $object_type );

        return is_array($classes) && !empty($classes) ? implode(' ', $classes ) : $classes;

    }

    /**
     * Print Footer html
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function printFooterHtml()
    {
        global $post;
        $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';
        if($action == 'elementor') return;

        global $post;
        ob_start();

        if( !is_admin() && EduPress::isActive('print') && is_singular() && has_shortcode($post->post_content, 'edupress') ){ 
            ?>
            <div class="printMasterTable">
                <span class="print-icon"><img src="<?php echo EDUPRESS_IMG_URL; ?>global-print.png" alt="Print"></span>
                <a title="Print Vertical" data-orientation="v" class="print-vertical printContent" href="javascript:void(0)"><img src="<?php echo EDUPRESS_IMG_URL; ?>vertical.png" alt="vertical"></a>
                <a title="Print Horizontal" data-orientation="p" class="print-horizontal printContent" href="javascript:void(0)"><img src="<?php echo EDUPRESS_IMG_URL; ?>horizontal.png" alt="horizontal"></a>
            </div>
        <?php } ?>

        <style>
            .edupress-publish-transaction.edupress-form .form-row .value-wrap{
                flex: 6;
            }
        </style>

        <script>
            <?php

                // Branches with id and name
                $branch = new Branch();
                $qry = $branch->getPosts();
                $branches = [];
                if( $qry->have_posts() ){
                    while( $qry->have_posts() ){
                        $qry->the_post();
                        $branches[] = array(
                            'id' => $qry->post->ID,
                            'title' => $qry->post->post_title
                        );
                    }
                    wp_reset_postdata();
                }

                $shifts = [];
                if( EduPress::isActive('shift') ){
                    $shift = new Shift();
                    $qry = $shift->getPosts(array('orderby'=>'title','order'=>'ASC'));
                    if ( $qry->have_posts() ){
                        while ( $qry->have_posts() ){
                            $qry->the_post();
                            $shifts[] = array(
                                'id' => $qry->post->ID,
                                'title' => $qry->post->post_title,
                                'branch_id' => (int) get_post_meta($qry->post->ID, 'branch_id', true ),
                            );
                        }
                        wp_reset_postdata();
                    }
                }

                $classes = [];
                if( EduPress::isActive('class') ){
                    $klass = new Klass();
                    $qry = $klass->getPosts();
                    if( $qry->have_posts() ){
                        while( $qry->have_posts() ){
                            $qry->the_post();
                            $classes[$qry->post->ID] = array(
                                'id' => $qry->post->ID,
                                'title' => $qry->post->post_title,
                                'branch_id' => (int) get_post_meta($qry->post->ID, 'branch_id', true ),
                                'shift_id' => (int) get_post_meta($qry->post->ID, 'shift_id', true ),
                            );
                        }
                        wp_reset_postdata();
                    }
                    
                }

                $sections = [];
                if( EduPress::isActive('section') ){
                    $section = new Section();
                    $qry = $section->getPosts();
                    if( $qry->have_posts() ){
                        while( $qry->have_posts() ){
                            $qry->the_post();
                            $sections[$qry->post->ID] = array(
                                'id' => $qry->post->ID,
                                'title' => $qry->post->post_title,
                                'branch_id' => (int) get_post_meta($qry->post->ID, 'branch_id', true ),
                                'shift_id' => (int) get_post_meta($qry->post->ID, 'shift_id', true ),
                                'class_id' => (int) get_post_meta($qry->post->ID, 'class_id', true ),
                            );
                        }
                        wp_reset_postdata();
                    }
                }
            ?>
            edupress.branches = <?php echo json_encode($branches); ?>;
            edupress.shifts = <?php echo json_encode($shifts); ?>;
            edupress.classes = <?php echo json_encode($classes); ?>;
            edupress.sections = <?php echo json_encode($sections); ?>;
            edupress.default_branch_id = <?php $default_branch = count($branches) === 1 ? reset($branches) : 0; echo $default_branch['id'] ?? 0; ?>;
        </script>

        <?php
        echo ob_get_clean();

    }

    /**
     * Wrap in content box
     *
     * @return string
     *
     * @param string $title
     * @param string $content
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function wrapInContentBox( $title = '', $content = '' )
    {

        ob_start();
        ?>
        <div class="edupress-content-box">
            <div class="title"><?php _e( $title , 'edupress' ); ?> </div>
            <div class="content"><?php echo $content; ?> </div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Get pagination
     *
     * @return string
     *
     * @param int $total_pages
     *
     * @since 1.0
     * @access public
     */
    public static function getPagination ( $total_pages = 0 )
    {
        if( $total_pages == 0 ) return '';

        $big = 999999999; // need an unlikely integer

        $paged = max(get_query_var('paged'), 1);
        $page = max(get_query_var('page'), 1);
        $cur_page = max( $paged, $page );

        ob_start();

        echo "<div class='edupress-pagination'>";
        echo paginate_links(
            array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => $cur_page,
                'total' => $total_pages,
                'mid_size' => 1,
                'prev_text' => __('«'),
                'next_text' => __('»'),
                'type' => 'list'
            )
        );
        echo "</div>";
        return ob_get_clean();
    }

    /**
     * Get edit icon
     *
     * @return string
     *
     * @param string $icon
     * @param string $class
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getIcon( $icon = '' , $class = '', $size = '1x' )
    {

        $icon = strtolower( trim( $icon ) );

        if( str_contains( $icon, 'fa-' ) ) return "<i class='{$icon}'></i>";

        if( $icon == 'dashboard' ) return "<i class='fa-solid fa-bars'></i>";
        if( $icon == 'report' ) return "<i class='fa-solid fa-chart-line'></i>";

        switch ( strtolower( trim( $icon ) ) ){

            case 'edit':
            case 'update':
                $img = 'edit.png';
                break;

            case 'delete':
                $img = 'delete.png';
                break;

            default:
                $img = $icon . '.png';
                break;
        }

        $img_url = EDUPRESS_IMG_URL . 'icons/' . $img;

        $classes = is_array($class) ? $class : explode(' ', $class);
        $classes[] = 'edupress-icon';
        $classes[] = 'size-' . $size;
        if( !in_array('print', $classes)) $classes[] = 'no-print';
        return "<img src='{$img_url}' class='". implode(' ', $classes ) ."'>";

    }

    /**
     * Read a csv file
     * @param string $filePath
     * @param string  $delimiter
     * @return array|false
     */
    public static function readCSV($filePath, $delimiter = ',') {

        // Open the file for reading
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $result = [];

            // Read the first line and check for BOM
            $firstLine = fgets($handle);
            if (substr($firstLine, 0, 3) === "\xef\xbb\xbf") {
                // BOM detected, remove it
                $firstLine = substr($firstLine, 3);
            }

            // Convert the first line to an array
            $headers = str_getcsv($firstLine, $delimiter);

            $headers = array_map('strtolower', array_map('trim', $headers));

            // Process the file line by line
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                $row = array_combine($headers, $data);
                $result[] = $row;
            }

            // Close the file
            fclose($handle);
            return $result;
        } else {
            echo "Error: Unable to open the file.";
            return FALSE;
        }
    }

    /**
     * GET eudpress feature list
     * 
     * @return array
     */
    public static function getFeatureList()
    {
        return array(
            'branch' => array(
                'title' => 'Branch',
                'icon' => 'fa-solid fa-network-wired',
            ),
            'shift'  => array(
                'title' => 'Shift',
                'icon' => 'fa-solid fa-clock',
            ),
            'class'  => array(
                'title' => 'Class',
                'icon' => 'fa-solid fa-chalkboard-user',
            ),
            'section'=> array(
                'title' => 'Section',
                'icon' => 'fa-solid fa-building',
            ),
            'subject' => array(
                'title' => 'Subject',
                'icon' => 'fa-solid fa-book',
            ),
            'term' => array(
                'title' => 'Exam Term',
                'icon' => 'fa-solid fa-calendar-day',
            ),
            'exam' => array(
                'title' => 'Exam',
                'icon' => 'fa-solid fa-calendar-day',
            ),
            'result' => array(
                'title' => 'Result',
                'icon' => 'fa-solid fa-pen-to-square',
            ),
            'grade_table' => array(
                'title' => 'Grade Table',
                'icon' => 'fa-solid fa-table-list',
            ),
            'result' => array(
                'title' => 'Result',
                'icon' => 'fa-solid fa-pen-to-square',
            ),
            'calendar' => array(
                'title' => 'Calendar',
                'icon' => 'fa-solid fa-calendar',
            ),
            'notice' => array(
                'title' => 'Notice',
                'icon' => 'fa-solid fa-bullhorn',
            ),
            'user' => array(
                'title' => 'User',
                'icon' => 'fa-solid fa-user',
            ),
            'sms' => array(
                'title' => 'SMS',
                'icon' => 'fa-solid fa-sms',
            ),
            'attendance' => array(
                'title' => 'Attendance',
                'icon' => 'fa-solid fa-fingerprint',
            ),
            'transaction' => array(
                'title' => 'Accounting',
                'icon' => 'fa-solid fa-money-bill',
            ),
            'setting' => array(
                'title' => 'Settings',
                'icon' => 'fa-solid fa-gear',
            ),
            'print' => array(
                'title' => 'Print Materials',
                'icon' => 'fa-solid fa-print',
            ),
            'support' => array(
                'title' => 'Support',
                'icon' => 'fa-solid fa-headset',
            )
        );
    }

    /**
     * Get days of a month
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getDaysOfAMonth( $month = '', $year = '' )
    {
        $start = new \DateTime( $year . '-' . $month . '-01' );
        $end = new \DateTime( $start->format('Y-m-t') );

        $interval = new \DateInterval( 'P1D' );
        $end->add($interval);

        $period = new \DatePeriod( $start, $interval, $end );

        $days = [];
        foreach($period as $date) {
            $days[] = $date->format('Y-m-d');
        }

        return $days;
    }

    /**
     * Format number in ordinal
     *
     * @return string
     *
     * @param int $number
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function numberToOrdinal($number)
    {
        if( intval($number) === 0) return 'Oth';
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }

    /**
     * Creating QR Images for scan
     *
     * @return string
     *
     * @param string $text
     * @param string $filename
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function createQr($text, $filename = '', $size = 3)
    {

        // First converting text into hash and try to see if qr code exists
        // If qr image found, return it, otherwise generate one

        $hashed_string = self::hashUrl($text);

        include_once(EDUPRESS_LIB_DIR . '/phpqrcode/qrlib.php');

        if(empty($filename)) $filename = !empty($hashed_string) ? $hashed_string : uniqid();

        $filename .= '.png';

        $base_dir = 'edupress';

        $qr_dir = 'qrcodes';

        $wp_dirs = wp_upload_dir();

        $target_dir = $wp_dirs['basedir'] . '/' . $base_dir;

        if( !file_exists($target_dir) ) mkdir($target_dir, 0755);

        $target_dir .= '/' . $qr_dir . '/';

        if( !file_exists($target_dir) ) mkdir($target_dir, 0755);

        $dest_img = $target_dir . $filename;

        if(!file_exists($dest_img)){

            \QRcode::png($text, $dest_img, QR_ECLEVEL_L, $size );

        }

        return site_url() . "/wp-content/uploads/{$base_dir}/{$qr_dir}/{$filename}";

    }

    /**
     * Generate Qr code for current url
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function createQrForCurrentUrl( $url = null, $size = 1 )
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        if( empty($url) ) $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return self::createQr( $url, '', $size );

    }

    /**
     * Hashing url to shorten
     * @param $url
     * @param $algorithm
     * @return mixed
     */
    public static function hashUrl($url, $algorithm = 'crc32')
    {

        $shortHash = '';
        if(function_exists('crc32')) {

            // Step 1: Generate a CRC32 hash of the URL
            $crc32Hash = crc32($url); // Returns a numeric hash value

            // Step 2: Convert the numeric hash into a Base62 encoded string
            if(function_exists('base64_encode')){
                $shortHash = base64_encode($crc32Hash);
                $shortHash = rtrim($shortHash, '=');
            }
        }

        return $shortHash;
    }


    /**
     * Delete QR files
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public static function deleteQrFiles( $loc = 'edupress/qrcodes' )
    {
        if(Admin::getSetting('print_qr_code') != 'active') return array('error'=>'QR code inactive');

        $mins = Admin::getSetting('print_qr_code_expiry', 5);
        if($mins == 'no') return array('QR code delete inactive');

        $dir = wp_upload_dir();
        $target = $dir['basedir'] . '/' . $loc;
        $now = strtotime(current_time('mysql'));

        $threshold = $mins * 60;

        $files_to_delete = [];

        if( $handle = opendir($target)) {
            while (false !== ($file = readdir($handle))) {

                $file = $target . '/' . $file;

                // Skip directory
                if( $file == '.' || $file == '..' ){
                    continue;
                }

                if(is_file($file)){
                    $file_time = filemtime($file);
                    echo $now - $file_time;
                    echo " || <br>";
                    if( ($now - $file_time) >= $threshold ){
                        unlink($file);
                    }
                }
            }
        }

        return $files_to_delete;
    }

    /**
     * WordPress add custom cron intervals
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function wpCustomCronIntervals( $schedules )
    {
        $schedules['ep_every_five_minutes'] = array(
            'interval' => 300, // 5 minutes in seconds
            'display'  => esc_html__('Every 5 Minutes'),
        );
        $schedules['ep_every_hour'] = array(
            'interval' => 60 * 60, // 5 minutes in seconds
            'display'  => esc_html__('Every Hour'),
        );
        $schedules['ep_every_day'] = array(
            'interval' => 60 * 60 * 24, // 5 minutes in seconds
            'display'  => esc_html__('Every Day'),
        );
        return $schedules;
    }

    /**
     * Five minutes cron tasks
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function wpScheduleCron()
    {
        if (!wp_next_scheduled('ep_five_minute_event')) {
            wp_schedule_event(time(), 'ep_every_five_minutes', 'ep_five_minute_event');
        }
        if (!wp_next_scheduled('ep_every_hour_event')) {
            wp_schedule_event(time(), 'ep_every_hour', 'ep_every_hour_event');
        }
        if (!wp_next_scheduled('ep_every_day_event')) {
            wp_schedule_event(time(), 'ep_every_day', 'ep_every_day_event');
        }
    }

    /**
     * Run cron jobs here
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function epFiveMinuteCronTask()
    {

        self::deleteQrFiles();

    }

    /**
     * Edupress hourly cron task
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function epHourlyCronTask()
    {

        // SMS balance notification
        if( EduPress::isActive('sms' ) ){
            $bal = Sms::getBalance();
            Sms::sendLowBalanceNotification($bal);
        }
    }

    /**
     * Edupress daily cron task
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function epDailyCronTask()
    {

        Attendance::scheduleDeleteLog();
        Sms::scheduleDeleteLog();

    }




    /**
     * Checking if a feature is active or not
     *
     * @return boolean
     * @since 1.0
     * @access public
     * @static
     */
    public static function isActive($feature)
    {

        return Admin::getSetting( strtolower(trim($feature)).'_active' ) == 'active';

    }




}

EduPress::instance();