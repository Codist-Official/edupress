<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Sms extends CustomPost
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $table
     */
    protected $table = 'sms_logs';

    /**
     * @var $gateway
     */
    private $gateway = null;

    /**
     * @var int $id
     */
    public $id;

    protected $posts_per_page = 50;

    protected $post_type = 'sms';

    /**
     * Initialize instance
     *
     * @return self
     * @since 1.0
     * @acesss public
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

        parent::__construct($id);

        // include gateways files
        foreach( glob( EDUPRESS_PATH . 'includes/class/sms-gateways/*.php' ) as $file ){

            include_once ( $file );

        }

        $active_gateway = Admin::getSetting('sms_gateway', 'easysms');

        switch (strtolower(trim($active_gateway))){

            case 'easysms':
                $this->setGateway(new Easysms() );
                break;

            case 'bulksmsbd':
                $this->setGateway(new Bulksmsbd() );
                break;

            default:
                break;

        }

        // Filter list query
        add_filter( "edupress_list_{$this->post_type}_query", [ $this, 'filterListQuery' ] );

        // Before list html
        add_filter( "edupress_list_{$this->post_type}_filter_form_before_html", [ $this, 'getComposeFilterForm' ]  );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", function() {
            return [];
        });

        // Hide publish button
        add_filter( "edupress_publish_{$this->post_type}_button_html", function(){
            return '';
        });


    }

    /**
     * @return mixed
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param mixed $gateway
     */
    public function setGateway($gateway): void
    {
        $this->gateway = $gateway;
    }


    /**
     * @return mixed
     *
     * @param array $data
     *
     * @since 1.0
     * @access public
     */
    public static function send( $data = [] )
    {

        if( !EDUPRESS_SEND_SMS ) return [];

        if ( !isset($data['mobile']) || empty($data['mobile']) ) return array('status' => 0, 'error_message' => 'Mobile not found' );
        if ( !isset($data['sms']) || empty($data['sms']) ) return array( 'status' => 0, 'error_message' => 'SMS empty' );

        $data['sms_rate'] = Admin::getSetting( 'sms_rate' );

        $footer = Admin::getSetting('sms_footer');
        $data['sms'] = $data['sms'] . "\n\n" . $footer;
        $data['sms'] = stripslashes($data['sms']);
        $data['record_time'] = current_time( 'mysql' );

        $response = null;

        $self = new Sms();
        if( !is_null($self->gateway) ) $response =  $self->gateway->send( $data );

        if( empty($response) ) return array(
            'status' => 0,
            'data' => 'Unknown error occurred!'
        );

        if ( !empty( $response ) ) $response = json_decode( $response, true );

        $data['response_code'] = $response['response_code'] ?? 0;
        if( isset($response['message_id'])) $data['response_id'] = $response['message_id'];

        $insert = $self->insert($data);

        if( $data['response_code'] != 202 ){
            $key = 'sms_not_working_notification';
            $val = get_option($key);
            if(!empty($val)){
                $dt1 = new \DateTime(current_time('mysql'));
                $dt2 = new \DateTime($val);
                $interval = $dt1->diff($dt2);
                $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                if($minutes > 15){
                    $to = 'nur1952@gmail.com';
                    $subject = 'SMS not working for the domain' . $_SERVER['HTTP_HOST'];
                    $message = "Resposne code: {$response['response_code']} <br> Resposne Message: {$response['error_message']}";
                    wp_mail( $to, $subject, $message );
                    update_option($key, current_time('mysql'));
                }
            }
        }


        return array(
            'status' => $data['response_code'] == 202 ? 1 : 0,
            'response' => $response,
            'insert'    => $insert,
            'data' => $data
        );
    }

    /**
     * Get balance
     *
     * @return float
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getBalance()
    {
        $self = new self();
        if( is_null( $self->gateway ) ) return 0;
        return $self->gateway->getBalance();
    }

    /**
     * Insert into database
     *
     * @return int
     *
     * @param array $data
     *
     * @since 1.0
     * @access public
     */
    public function insert( $data = [] )
    {

        $branch = new Branch();
        $branches = $branch->getPosts( [], true );
        $branch_ids = array_keys($branches);
        $branch_id = count($branch_ids) > 1 ? 0 : reset($branch_ids);
        $user_id = get_current_user_id();
        $user_branch_id = get_user_meta( $user_id, 'branch_id', true );
        $branch_id = $user_branch_id ? $user_branch_id : $branch_id;


        $insert_data = [];
        $insert_data['mobile'] = $data['mobile'] ?? 0;
        $insert_data['sms'] = $data['sms'] ?? null;
        $insert_data['sms_len'] = ceil(strlen($data['sms'])/159);
        $insert_data['sms_rate'] = Admin::getSetting('sms_rate');
        $insert_data['user_id'] = $user_id;
        $insert_data['branch_id'] = $branch_id;
        $insert_data['response_code'] = $data['response_code'] ?? 0;
        $insert_data['response_id'] = $data['response_id'] ?? null;
        $insert_data['record_time'] = current_time('mysql');

        global $wpdb;
        $insert = $wpdb->insert(
            $this->table,
            $insert_data
        );
        return $insert ? $wpdb->insert_id : $wpdb->last_error;
    }


    /**
     * Delete a SMS
     *
     * @return void
     *
     * @param array $ids
     *
     * @since 1.0
     *
     */
    public function delete( $ids = [] )
    {
        if ( !is_array( $ids ) ) $ids = explode(',', $ids );
    }

    /**
     * Get form to send SMS
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getComposeFilterForm()
    {

        $fields = [];

        $available_roles = User::getRoles();
        array_unshift($available_roles, 'All');
        $role_options = array_combine( $available_roles, $available_roles );

        $branch = new Branch();
        $fields['send_to'] = array('type' => 'select', 'name' => 'send_to', 'settings' => array( 'options' => array( 'users'=>'Users','numbers'=>'Numbers'), 'placeholder' => 'Select', 'label' => 'Send SMS to', 'required' => true ));
        $fields['branch_id'] = array('type' => 'select', 'name' => 'branch_id', 'settings' => array( 'label'=>'Branch', 'placeholder' => 'Select a branch', 'options'=> $branch->getPosts( [], true)));
        $fields['role'] = array( 'type' => 'select', 'name' => 'role', 'settings' => array('options'=>$role_options, 'label'=>'Role', 'required' => false, 'placeholder' => 'Select a role' ));
        $fields['status'] = array( 'type' => 'select', 'name' => 'status', 'settings' => array('options' => array( 'active'=>'Active','inactive'=>'Inactive', 'any'=> 'Any' ), 'label'=>'Status'));

        if( Admin::getSetting('shift_active') == 'active'){
            $fields['shift_id'] = array('type' => 'select', 'name' => 'shift_id', 'settings' => array( 'label'=>'Shift (Optional)', 'placeholder' => 'Select a shift', 'options'=> [] ) );
        }
        if( Admin::getSetting('class_active') == 'active'){
            $fields['class_id'] = array('type' => 'select', 'name' => 'class_id', 'settings' => array( 'label'=>'Class (Optional)', 'placeholder' => 'Select a class', 'options'=> [] ) );
        }
        if( Admin::getSetting('section_active') == 'active'){
            $fields['section_id'] = array('type' => 'select', 'name' => 'section_id', 'settings' => array( 'label'=>'Section (Optional)', 'placeholder' => 'Select a section', 'options'=> [] ) );
        }

        ob_start();
        ?>
        <form action="" class="<?php echo EduPress::getClassNames( array('edupress-send-sms-form', 'grid-5-col'), 'form' ); ?>">
            <?php
                foreach($fields as $field){
                    $field['settings']['id'] = $field['name'];
                    ?>
                    <div class="form-row <?php echo $field['name'];?>">
                        <div class="label-wrap">
                            <label for="<?php echo $field['name']; ?>"><?php _e( $field['settings']['label'] ?? '', 'edupress'); ?></label>
                        </div>
                        <div class="value-wrap">
                            <?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?>
                        </div>
                    </div>
                    <?php
                }
            ?>
            <div class="form-row">
                <div class="label-wrap"> &nbsp; </div>
                <div class="value-wrap">
                    <?php echo EduPress::generateFormElement( 'submit', '', array('value'=>'Compose SMS') ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax') ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'getSmsComposeForm') ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array('value'=>'smsBeforeSendCallback') ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'success_callback', array('value'=>'smsSuccessCallback') ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'error_callback', array('value'=>'smsErrorCallback') ); ?>
                    <?php wp_nonce_field('edupress'); ?>
                </div>
            </div>
        </form>

        <?php
        return ob_get_clean();

    }

    /**
     * Get compose form
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getComposeForm( $settings = [] )
    {
        $send_to = $settings['send_to'] ?? '';
        $branch_id = $settings['branch_id'] ?? '';
        $shift_id = $settings['shift_id'] ?? '';
        $class_id = $settings['class_id'] ?? '';
        $section_id = $settings['section_id'] ?? '';
        $role = $settings['role'] ?? '';
        $status = $settings['status'] ?? '';

        $args = [];
        if( !empty($branch_id) ){
            $args['meta_query'][] = array(
                'key'   => 'branch_id',
                'value' => $branch_id,
                'compare'=>'='
            );
        }

        if( !empty($shift_id) ){
            $args['meta_query'][] = array(
                'key'   => 'shift_id',
                'value' => $shift_id,
                'compare'=>'='
            );
        }

        if( !empty($class_id) ){
            $args['meta_query'][] = array(
                'key'   => 'class_id',
                'value' => $class_id,
                'compare'=>'='
            );
        }

        if( !empty($section_id) ){
            $args['meta_query'][] = array(
                'key'   => 'section_id',
                'value' => $section_id,
                'compare'=>'='
            );
        }

        $args['role__in']  = !empty($settings['role']) && $settings['role'] != 'all' ? $settings['role'] : User::getRoles();

        if( isset($args['meta_query']) && count($args['meta_query']) > 1 ) $args['meta_query']['relation'] = 'AND';
        ob_start();
        ?>
        <form action="" class="<?php echo EduPress::getClassNames( array('edupress-sms-compose', 'vertical'), 'form'); ?>">

            <div class="sms-compose-wrap">
                <div class="left-wrap">
                    <?php
                    if( $send_to === 'users' ){
                        echo EduPress::generateFormElement('hidden', 'send_to', array('id' => 'send_to', 'value' => 'users'));
                        $user_qry = new \WP_User_Query($args);
                        if( !$user_qry->get_results() ){
                            _e('No users found!', 'edupress');
                            return ob_get_clean();
                        }
                        ?>
                        <h3>Users</h3>
                        <ul class="sms-users-list">
                        <?php
                        foreach ( $user_qry->get_results() as $user ){
                            ?>
                            <li>
                                <input class="sms-compose-user-select" type="checkbox" name="user[]" value="<?php echo $user->ID; ?>" id="id_<?php echo $user->ID; ?>" checked>
                                <label for="id_<?php echo $user->ID; ?>"><?php echo $user->first_name; ?></label>
                                <div style="display: none;">
                                    <input type="checkbox" name="mobile[]" value="<?php echo get_user_meta( $user->ID, 'mobile', true ); ?>" checked>
                                    <input type="checkbox" name="name[]" value="<?php echo $user->first_name; ?>" checked>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                        </ul>
                        <?php
                    }else { ?>
                        <div class="sms-form-item mobile-numbers">
                            <label for="mobile_numbers"><?php _e( 'Mobile Numbers', 'edupress' ); ?></label>
                            <?php echo EduPress::generateFormElement( 'textarea', 'mobile_numbers', array( 'name' => 'mobile_numbers', 'id'=>'mobile_numbers', 'required' => true, 'placeholder' => '1 mobile number per line' ) ); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'send_to', array( 'id'=>'send_to', 'value' => 'mobiles' ) ); ?>
                        </div>
                    <?php } ?>
                    <div class="sms-form-item">
                        <label for="sms_text"><?php _e( 'SMS', 'edupress' ); ?></label>
                        <?php echo EduPress::generateFormElement( 'textarea', 'sms_text', array( 'name' => 'sms_text', 'id'=>'sms_text', 'required' => true, 'placeholder' => 'Compose your SMS here' ) ); ?>
                    </div>
                    <div class="sms-form-item">
                        <?php echo EduPress::generateFormElement( 'submit', '', array( 'value' => 'Send' ) ); ?>
                        <?php echo EduPress::generateFormElement( 'hidden', 'action', array( 'value' => 'edupress_admin_ajax' ) ); ?>
                        <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value' => 'sendSms' ) ); ?>
                        <?php echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array( 'value' => 'smsComposeBeforeSendCallback' ) ); ?>
                        <?php echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value' => 'smsComposeSuccessCallback' ) ); ?>
                        <?php echo EduPress::generateFormElement( 'hidden', 'error_callback', array( 'value' => 'smsComposeErrorCallback' ) ); ?>
                        <?php wp_nonce_field('edupress'); ?>
                    </div>
                </div>
                <div class="right-wrap">
                    <h3>SMS Statistics</h3>
                    <ul class="statistics">
                        <li><span class="label-wrap"><?php _e( 'Total Numbers', 'edupress' ); ?></span>: <strong class="value-wrap total_numbers"><?php echo $send_to === 'users' ? $user_qry->get_total() : 0; ?></strong></li>
                        <li><span class="label-wrap"><?php _e( 'SMS Length', 'edupress' ); ?></span>: <strong class="value-wrap sms_len">0</strong></li>
                        <li><span class="label-wrap"><?php _e( 'SMS Count', 'edupress' ); ?></span>: <strong class="value-wrap sms_count">0</strong></li>
                        <li><span class="label-wrap"><?php _e( 'SMS Rate', 'edupress' ); ?></span>: <strong class="value-wrap sms_rate">0</strong></li>
                        <li><span class="label-wrap"><?php _e( 'SMS Cost', 'edupress' ); ?></span>: <strong class="value-wrap sms_cost">0</strong></li>
                    </ul>
                </div>
            </div>

        </form>
        <?php
        return ob_get_clean();

    }

    /**
     * Filter list qry
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterListQuery ()
    {

        $status = sanitize_text_field( $_REQUEST['status'] ?? '' );

        $paged = max( get_query_var( 'paged' ), 1 );
        $page = max( get_query_var( 'page' ), 1 );

        $paged = max($paged, $page);

        $offset = $paged > 1 ? $this->posts_per_page * ($paged - 1) : 0;

        $qry = "SELECT * FROM {$this->table} WHERE 1 = 1 ";
        if( $status == 'fail' ) $qry .= " AND response_code != 202 ";
        if( $status == 'success' ) $qry .= " AND response_code = 202 ";
        $qry .= " ORDER BY ID DESC LIMIT {$this->posts_per_page} ";

        if( $offset > 0 ) $qry .= " OFFSET {$offset} ";

        return $qry;

    }

    /**
     * Get list html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {

        if( !User::currentUserCan('read', $this->post_type ) ) return User::getCapabilityErrorMsg( 'see', $this->post_type . ' entries.' );

        global $wpdb;

        $results = $wpdb->get_results( $this->getListQuery() );
        if( empty( $results ) ) return __( "No {$this->post_type} found!", 'edupress' );
        $url = EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'panel', 'sms' );
        $fail_url = $url . '&status=failed';
        $success_url = $url . '&status=success';
        ob_start();
        ?>
        <script>
            jQuery(document).ready(function(){
                if( typeof smsGetCurBal != 'undefined' ){

                    smsGetCurBal();
                    setTimeout( smsGetCurBal, edupress.sms_balance_refresh_sec * 1000 );

                }
            })
        </script>
        <h3 style="margin-top: 50px;">Current balance: ৳ <span class="sms-current-balance">0</span></h3>
        <button class="edupress-btn edupress-btn-primary edupress-ajax-link" data-before_send_callback="confirmBeforeSendCallback" data-ajax_action="resendTodayFailedSms">Resend Today's Failed SMS (<?php echo self::countTodayFailedSms(); ?>)</button>
        <a class="edupress-btn edupress-btn-primary" href="<?php echo $fail_url; ?>"><?php _e('Filter Failed', 'edupressbd'); ?></a> | 
        <a class="edupress-btn edupress-btn-primary" href="<?php echo $success_url; ?>"><?php _e('Filter Success', 'edupressbd'); ?></a>

        <div class="edupress-table-wrap" style="margin-top: 50px;">
            <table class="edupress-table edupress-master-table tablesorter">

                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'edupress' ); ?></th>
                        <th><?php _e( 'Mobile', 'edupress' ); ?></th>
                        <th><?php _e( 'SMS', 'edupress' ); ?></th>
                        <th><?php _e( 'Count', 'edupress' ); ?></th>
                        <th><?php _e( 'Rate', 'edupress' ); ?></th>
                        <th><?php _e( 'Cost', 'edupress' ); ?></th>
                        <th><?php _e( 'Sent By', 'edupress' ); ?></th>
                        <th><?php _e( 'Record Time', 'edupress' ); ?></th>
                    </tr>
                </thead>

                <?php
                    foreach($results as $result){
                        $error_class = !is_null($result->response_code) && $result->response_code != 202 ? "error" : "success";
                        ?>
                        <tr class="row-<?php echo $error_class; ?>">
                            <td><?php echo $result->id; ?></td>
                            <td><?php echo $result->mobile; ?></td>
                            <td><?php $text = nl2br($result->sms); echo str_replace( '\n', '<br>', $text) ; ?></td>
                            <td><?php echo $result->sms_len; ?></td>
                            <td><?php echo $result->sms_rate; ?></td>
                            <td><?php echo number_format($result->sms_len * $result->sms_rate, 2); ?></td>
                            <td><?php 
                                if($result->user_id){
                                    $user = get_user_by( 'id', $result->user_id ); 
                                    if( $user ) echo User::showProfileOnClick( $result->user_id, $user->first_name ); 
                                } else {
                                    echo "System";
                                }
                                ?></td>
                            <td><?php echo date('h:i A, d/m/Y', strtotime($result->record_time)); ?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <?php echo $this->getPagination(); ?>
        <?php return ob_get_clean();

    }

    /**
     * Return gateways
     *
     * @return array
     *
     * @since 1.0
     * @access pubic
     * @static
     */
    public static function getGateways()
    {

        $gateways = array('easysms'=> 'EasySMS', 'bulksmsbd' => 'BulkSmsBd');
        return apply_filters( 'edupress_sms_gateways', $gateways );

    }



    /**
     * Delete LOgs
     *
     * @return int
     *
     * @param string $start_date
     * @param string $end_date
     *
     * @since 1.0
     * @access public
     */
    public function deleteLogs( $start_date = '', $end_date = '' )
    {

        $start = new \DateTime(Date('Y-m-d', strtotime($start_date)));
        $end = new \DateTime(Date('Y-m-d', strtotime($end_date)));
        $current = new \DateTime(current_time('Y-m-d'));
        if($end->format('Y-m') >= $current->format('Y-m') ) $end->modify('last day of previous month');

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        $month_years = [];

        foreach($period as $p) {
            $year = (int) $p->format('Y');
            $month = (int) $p->format('m');
            if( !isset($month_years[$year]) ) $month_years[$year] = [];
            if( !in_array( $month, $month_years[$year]) ) $month_years[$year][] = $month;
        }

        if( empty($month_years) ) return 0;

        // Updating SMS Stats
        foreach($month_years as $year => $months){
            foreach($months as $month){
                // self::logStats($month, $year);
            }
        }

        // Deleting SMS Logs
        global $wpdb;
        $start_date = $start->format('Y-m-d');
        $end_date = $end->format('Y-m-d');
        $qry = "DELETE FROM {$wpdb->prefix}sms_logs WHERE DATE(record_time) >= '$start_date' AND DATE(record_time) <= '$end_date' ";
        return $wpdb->query($qry);

    }

    /**
     * Send SMS low balance notification
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function sendLowBalanceNotification( $current_balance = 0 )
    {
        $active = Admin::getSetting('sms_balance_notification') == 'active';
        if(!$active) return false;
        $limit = Admin::getSetting('sms_balance_limit');
        if($current_balance > $limit){
            delete_option('sms_balance_notification_logs');
            return "Current balance {$current_balance} is higher than limit {$limit}";
        }
        $current_balance = number_format($current_balance, 2);
        $key = 'sms_balance_notification_logs';
        $value = maybe_unserialize(get_option($key, []));
        $sending_limit = 5;

        $name = Admin::getSetting('institute_name');
        $text = "Hi There! \n\nCurrent SMS balance of {$name} is BDT {$current_balance}. Please recharge soon for uninterrupted service.\n\nThank you!\n\nTeam EduPress";

        if(!empty($value)){

            if(!is_array($value)) $value = [];
            if(count($value) > $sending_limit) return 'Sending limit crossed';
            $last = end($value);
            if(!empty($last)){

                $dt1 = new \DateTime($last);
                $dt2 = new \DateTime(current_time('mysql'));
                $interval = $dt1->diff($dt2);

                // Get the total difference in hours (hours + minutes converted to hours)
                $hours = $interval->days * 24 + $interval->h + ($interval->i / 60);

                if($hours < 24) return "Last sent {$hours} hours ago.";

            }
        }

        $mobiles = Admin::getSetting('sms_balance_notification_mobile');
        if(empty($mobiles)) $mobiles = Admin::getSetting('institute_phone');
        $mobiles = explode(',', $mobiles);
        $mobiles = array_filter($mobiles);
        if(!empty($mobiles)){
            foreach($mobiles as $mobile){
                Sms::send(array(
                    'mobile' => $mobile,
                    'sms' => $text,
                ));
            }
        }

        $emails = Admin::getSetting('sms_balance_notification_email');
        if(empty($emails)) $emails = Admin::getSetting('institute_email');
        $emails = explode(',', $emails);
        if(!empty($emails)){
            foreach($emails as $email){
                $sub = "SMS Low Balance Notification for $name";
                wp_mail($email, $sub, $text);
            }
        }

        $value[] = current_time('mysql');
        update_option($key, $value, 'no');

        return 'Notification sent.';
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
        $table = $wpdb->prefix . 'sms_logs';
        $qry = $wpdb->prepare("SELECT SUM(sms_len) AS sms_count, SUM(sms_len * sms_rate) AS sms_cost FROM {$table} WHERE response_code = 202 AND branch_id = %d AND DATE(record_time) = %s", $branch_id, $date_format);
        $row = $wpdb->get_row($qry, ARRAY_A);
        $insert =$wpdb->insert($wpdb->prefix . 'sms_summary', array(
            'branch_id' => $branch_id,
            'date' => $date_format,
            'sms_count' => $row['sms_count'],
            'sms_cost' => $row['sms_cost'],
        ));
        $res = $insert ? $wpdb->insert_id : $wpdb->last_error;
        $wpdb->flush();
        return $res;
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
        $table = $wpdb->prefix . 'sms_logs';
        $qry = $wpdb->prepare("DELETE FROM {$table} WHERE branch_id = %d AND DATE(record_time) = %s", $branch_id, $date_format);
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
        $qry = $wpdb->prepare("SELECT DISTINCT(branch_id) as branch_id, MIN(DATE(record_time)) as min_date, MAX(DATE(record_time)) as max_date FROM {$wpdb->prefix}sms_logs WHERE DATE(record_time) < %s", current_time('Y-m-d'));
        $rows = $wpdb->get_results($qry);
        foreach($rows as $row){
            $start_date = $row->min_date;
            $end_date = $row->max_date;
            $branch_id = $row->branch_id;
            $store_log = Admin::getSetting('sms_store_log', 45);
    
            $start_date = new \DateTime($start_date);
            $end_date = new \DateTime($end_date);
            $end_date->modify('-' . $store_log . ' days');
    
            $period = new \DatePeriod($start_date, new \DateInterval('P1D'), $end_date);
            foreach($period as $date){
                $date_format = $date->format('Y-m-d');
                self::deleteLog($branch_id, $date_format);
            }
        }
    }

    /**
     * Count today's failed SMS
     * 
     * @return int
     * 
     * @since 1.0
     * @access public
     */
    public static function countTodayFailedSms( $day = '' )
    {
        if(empty($day)) $day = current_time('Y-m-d');
        global $wpdb;
        $table = $wpdb->prefix . 'sms_logs';
        $qry = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE response_code != 202 AND DATE(record_time) = %s", $day);
        return $wpdb->get_var($qry);
    }

    /**
     * Resend today's failed SMS
     * 
     * @return void
     * 
     * @since 1.0
     * @access public
     */
    public static function resendTodayFailedSms()
    {
        $today = current_time('Y-m-d');
        global $wpdb;
        $table = $wpdb->prefix . 'sms_logs';
        $qry = $wpdb->prepare("SELECT * FROM {$table} WHERE response_code != 202 AND DATE(record_time) = %s", $today);
        $rows = $wpdb->get_results($qry, ARRAY_A);
        $self = new Sms();
        $count = 0;
        $response_data = [];
        foreach($rows as $row){
            
            if(is_null($self->gateway)) continue;

            $response = $self->gateway->send(array(
                'mobile' => $row['mobile'],
                'sms' => $row['sms'],
            ));
            $response = json_decode($response, true);
            if($response['response_code'] == 202){
                $wpdb->update($table, array('response_code' => 202), array('id' => $row['id']));
                $count++;
            }
            $response['id'] = $row['id'];
            $response_data[] = $response;
        }
        return array( 'status' => 1, 'data' => 'SMS resended successfully!', 'response' => $response_data, 'count' => $count );
    }

}
new Sms();