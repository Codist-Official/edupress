<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class AdminAjax
{

    /**
     * @var $_instance;
     */
    private static $_instance;

    /**
     * Initialize instance
     *
     * @return AdminAjax
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ){

            self::$_instance = new self();

        }

        return self::$_instance;

    }

    /**
     * Constructor
     *
     * @return void
     *
     * @since 1.0
     * @acess public
     */
    public function __construct()
    {

        // Process ajax requests
        add_action( 'wp_ajax_edupress_admin_ajax', [ $this, 'process'] );
        add_action( 'wp_ajax_nopriv_edupress_admin_ajax', [ $this, 'process'] );

    }


    /**
     * Process request
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function process()
    {
        if ( !wp_verify_nonce($_REQUEST['_wpnonce'], 'edupress') ){
            die(
                wp_json_encode(
                    array(
                        'status'    => 0,
                        'data'      => 'Security check fails!',
                    )
                )
            );
        }

        $method = isset($_REQUEST['ajax_action']) ? sanitize_text_field($_REQUEST['ajax_action']) : null;
        if( empty($method) || !method_exists( $this, $method ) ){

            die(
                wp_json_encode(
                    array(
                        'status'    => 0,
                        'data'      => 'No methods found to process.',
                    )
                )
            );

        }

        die(wp_json_encode( $this->$method() ));

    }

    /**
     * Save admin settings from
     *
     * @return array
     */
    public function saveEduPressAdminSettingsForm()
    {
        $admin = new Admin();
        $update = $admin->updateEduPressSettingsForm( $_REQUEST );
        return array(
            'status'    => $update ? 1 : 0,
            'data'      => $update ? 'Successfully updated!': 'Failed.'
        );

    }

    /**
     * Publish or edit a post
     *
     * @param string $action
     * @param array $data
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function processPost()
    {

        $post_type = sanitize_text_field(strtolower(trim($_REQUEST['post_type'])));

        $post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;

        $post = null;

        switch($post_type){

            case 'class':
                $post = new Klass($post_id);
                break;

            case 'branch':
                $post = new Branch($post_id);
                break;

            case 'section':
                $post = new Section($post_id);
                break;

            case 'term':
                $post = new Term($post_id);
                break;

            case 'subject':
                $post = new Subject($post_id);
                break;

            case 'shift':
                $post = new Shift($post_id);
                break;

            case 'exam':
                $post = new Exam($post_id);
                break;

            case 'transaction':
                $post = new Transaction($post_id);
                break;

            case 'attendance':
                $post = new Attendance($post_id);
                break;

            case 'calendar':
                $post = new Calendar($post_id);
                break;

            case 'notice':
                $post = new Notice($post_id);
                break;

            case 'grade_table':
                $post = new GradeTable($post_id);
                $_REQUEST['grade_data'] = array(
                    'range_start' => $_REQUEST['range_start'],
                    'range_end' => $_REQUEST['range_end'],
                    'grade_point' => $_REQUEST['grade_point'],
                    'grade' => $_REQUEST['grade']
                );
                unset($_REQUEST['range_start']);
                unset($_REQUEST['range_end']);
                unset($_REQUEST['grade_point']);
                unset($_REQUEST['grade']);
                break;

        }

        unset($_REQUEST['action']);
        unset($_REQUEST['_wpnonce']);
        unset($_REQUEST['_wp_http_referer']);
        unset($_REQUEST['before_send_callback']);
        unset($_REQUEST['success_callback']);
        unset($_REQUEST['error_callback']);

        $status = $_REQUEST['ajax_action'] == 'publishPost' ? $post->publish($_REQUEST) : $post->edit($_REQUEST) ;
        $success_msg = $_REQUEST['ajax_action'] == 'publishPost' ? 'Successfully published!' : 'Successfully updated!';
        $payload = $_REQUEST;

        unset($_REQUEST['ajax_action']);

        if($post_type == 'transaction') return $status;

        return array(
            'status'    => $status,
            'data'      => $status ? $success_msg : 'Error occurred!',
            'payload'   => $payload,
            'post_id'   => $status,
        );


    }

    /**
     * Publish a post
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function publishPost()
    {
        return $this->processPost();
    }

    /**
     * Edit a post
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function editPost()
    {
        return $this->processPost();
    }


    /**
     * Show exam result edit form
     *
     * @return array
     * @since 1.0
     * @access public
     */
    public function getExamResultForm()
    {
        $result = new Result(intval($_REQUEST['post_id'] ));
        return array(
            'status' => 1,
            'data' => $result->getPostEditForm(),
        );
    }

    /**
     * Return post edit content
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getPostEditForm()
    {

        $post_type = strtolower(trim($_REQUEST['post_type']));
        $post_id = intval($_REQUEST['post_id'] ?? 0);

        $post = null;

        switch ( $post_type ){

            case 'branch':
                $post = new Branch($post_id);
                break;

            case 'shift':
                $post = new Shift($post_id);
                break;

            case 'class':
            case 'klass':
                $post = new Klass($post_id);
                break;

            case 'section':
                $post = new Section($post_id);
                break;

            case 'subject':
                $post = new Subject($post_id);
                break;

            case 'term':
                $post = new Term($post_id);
                break;

            case 'exam':
                $post = new Exam($post_id);
                break;

            case 'grade_table':
                $post = new GradeTable($post_id);
                break;

            case 'result':
                $post = new Result($post_id);
                break;

            case 'notice':
                $post = new Notice($post_id);
                break;

            case 'transaction':
                $post = new Transaction($post_id);
                break;

            case 'calendar':
                $post = new Calendar($post_id);
                return array(
                    'status'    => 1,
                    'data'      => $post->getEditForm(),
                    'payload'   => $_REQUEST,
                );
                break;

            default:
                break;

        }

        return array(
            'status'    => 1,
            'data'      => $post->getForm('edit', false ),
            'payload'   => $_REQUEST,
        );

    }

    /**
     * Delete post
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function deletePost()
    {

        $post_id = intval($_REQUEST['post_id']);
        $post_type = sanitize_text_field($_REQUEST['post_type'] ?? '');
        if($post_type == 'transaction'){
            $post = new Transaction($post_id);
            $delete = $post->delete();
        } else {
            $delete = wp_delete_post( $post_id );
        }
        return array(
            'status'    => $delete ? 1 : 0,
            'data'      => $delete ? 'Successfully deleted!' : 'Error occurred!',
        );

    }

    /**
     * Get post publish content
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getPostPublishForm()
    {
        $post_type = strtolower(trim($_REQUEST['post_type']));

        $post = null;

        switch ($post_type) {

            case 'branch':
                $post = new Branch();
                break;

            case 'shift':
                $post = new Shift();
                break;

            case 'class':
            case 'klass':
                $post = new Klass();
                break;

            case 'section':
                $post = new Section();
                break;

            case 'subject':
                $post = new Subject();
                break;

            case 'term':
                $post = new Term();
                break;

            case 'exam':
                $post = new Exam();
                break;

            case 'grade_table':
                $post = new GradeTable();
                break;

            case 'attendance':
                $post = new Attendance();
                break;

            case 'transaction':
                $post = new Transaction();
                break;

            case 'calendar':
                $post = new Calendar();
                break;

            case 'notice':
                $post = new Notice();
                break;

            default:
                $post = new Post();
                break;
        }

        return array(
            'status'   => 1,
            'data'     => $post->getForm('publish', false),
            'payload'  => $_REQUEST,
        );

    }

    /**
     * Bulk delete post
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function bulkDeletePost()
    {
        global $wpdb;
        $posts = $_REQUEST['post_id'];
        if(!empty($posts)){
            $posts = array_map( 'intval', $posts );
            if( $_REQUEST['post_type'] === 'user' ) {
                foreach($posts as $id){
                    $count = (int) $wpdb->query("SELECT COUNT(*) FROM {$wpdb->prefix}transaction WHERE user_id = {$id}");
                    if( $count ) continue;
                    wp_delete_user( $id );
                }
            } else {
                foreach($posts as $id){
                    wp_delete_post( $id );
                }
            }
        }
        return array(
            'status'    => 1,
            'data'      => 'Successfully deleted!',
            'posts'     => $posts,
        );
    }

    /**
     * Show bulk user publish form
     *
     * @return array
     *
     * @since 1.0
     * @acesss public
     */
    public function showPublishBulkUserForm()
    {

        $args = [];

        $rows = intval($_REQUEST['rows']);
        $args['role'] = sanitize_text_field( $_REQUEST['role'] ?? '' );
        $args['branch_id'] = intval( $_REQUEST['branch_id'] ?? '' );
        $args['shift_id'] = intval( $_REQUEST['shift_id'] ?? '' );
        $args['class_id'] = intval( $_REQUEST['class_id'] ?? '' );
        $args['section_id'] = intval( $_REQUEST['section_id'] ?? '' );

        $user = new User();

        return array(
            'status'    => 1,
            'data'      => $user->getPublishForm( $args, $rows ),
            'payload'   => $_REQUEST
        );

    }

    /**
     * Publish bulk user
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function publishBulkUser()
    {

        $keys = array_keys( $_REQUEST );

        $response = [];

        for( $i = 0; $i < count($_REQUEST['first_name']); $i ++ ){

            $uniqid = uniqid();
            $userdata = [];
            $userdata['first_name'] = sanitize_text_field($_REQUEST['first_name'][$i]);
            $userdata['user_email'] = !empty($_REQUEST['user_email'][$i]) ? sanitize_text_field($_REQUEST['user_email'][$i]) : $uniqid .'@'.$_SERVER['HTTP_HOST'];
            $userdata['user_login'] = !empty($_REQUEST['user_login'][$i]) ? sanitize_text_field($_REQUEST['user_login'][$i]) : $uniqid;
            $userdata['user_pass'] = !empty($_REQUEST['user_pass'][$i]) ? $_REQUEST['user_pass'][$i]: uniqid();
            $userdata['role'] = sanitize_text_field( $_REQUEST['role'][$i] ?? '');

            $skip_fields = array( 'action', 'ajax_action', '_wpnonce', '_wp_http_referer', 'user_login', 'user_email', 'role', 'before_send_callback', 'success_callback', 'error_callback', 'row_id' );
            $metadata = [];
            foreach( $keys as $k => $v ){

                if ( in_array( $v, $skip_fields )  ) continue;
                $metadata[$v] = $_REQUEST[$v][$i];

            }

            $user = new User();
            $insert = $user->insert( $userdata, $metadata );

            if( is_numeric($insert) && $insert > 0 ){
                $notify = array(
                    'email'  => $userdata['user_email'],
                    'mobile' => $_REQUEST['mobile'][$i],
                    'name'   => $userdata['first_name'],
                    'password' => $userdata['user_pass'],
                );
                User::notifyAfterRegister($notify);
            }

            $response[$i] = array(
                'user_status' => is_numeric($insert) && $insert > 0 ? 'Successfully added!' : $insert,
                'user_id'  => intval($insert),
                'payload' => array_merge( $userdata, $metadata )
            );
        }

        return array(
            'status'    => 1,
            'data'      => 'successfull',
            'payload'   => $response,
        );

    }

    /**
     * Delete user
     *
     * @return array
     *
     * @since 1.0
     * @access public
     *
     */
    public function deleteUser()
    {
        $user_id = intval( $_REQUEST['user_id']  ?? 0);
        $user = new User($user_id);
        $delete = $user->delete();
        return array(
            'status'    => $delete ? 1 : 0,
            'data'      => $delete ? 'Successfully deleted!' : 'Error',
        );
    }

    /**
     * Show user edit form
     *
     * @since 1.0
     * @access public
     */
    public function showUserEditForm()
    {

        $user = new User( intval($_REQUEST['user_id']) );
        if( !$user->id ) return [];

        return array(
            'status'    => 1,
            'data'      => $user->getEditForm(),
        );


    }


    /**
     * Edit a user
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function editUser()
    {
        $user = new User( intval($_REQUEST['user_id'] ) );

        $userdata = [];
        $userdata['first_name'] = sanitize_text_field($_REQUEST['first_name'] ?? '');
        $userdata['role'] = sanitize_text_field($_REQUEST['role'] ?? '');

        $userdata['user_email'] = sanitize_text_field($_REQUEST['user_email'] ?? '');
        if(!empty($_REQUEST['user_pass'])) $userdata['user_pass'] = $_REQUEST['user_pass'];

        $metadata = [];

        $skip_fields = array( 'action', 'ajax_action', '_wpnonce', '_wp_http_referer', 'user_login', 'user_email', 'role', 'before_send_callback', 'success_callback', 'error_callback', 'row_id' );

        foreach ( $_REQUEST as $k => $v ){
            if( in_array( $k, $skip_fields ) ) continue;
            $metadata[$k] = $v;
        }

        $update = $user->edit( $userdata, $metadata );
        $_REQUEST['post_id'] = intval($_REQUEST['user_id'] );
        return array(
            'status'  => $update,
            'data'    => $update ? 'Successfully updated!' : 'Error occurred!',
            'payload' => $_REQUEST,
        );

    }

    /**
     * Result edit form
     *
     * @return array
     *
     * @since 1.0
     * @access pubic
     */
    public function saveExamResult()
    {

        $post_id = intval($_REQUEST['post_id']);
        $result = new Result( $post_id );
        $heads = explode(',', $_REQUEST['heads'] );
        $r = [];
        $exam_marks = [];
        for ( $i = 0; $i < count($_REQUEST['user_id']); $i++ ){
            $user_details = [];
            if(!empty($heads)){
                foreach($heads as $head){
                    $user_details[$head] = array(
                        'obtained' => (float) $_REQUEST[$head][$i],
                        'absent'=> (int) $_REQUEST[$head.'_absent'][$i],
                    );
                }
            }
            $user_id = $_REQUEST['user_id'][$i];
            $r[$user_id]['results'] = $user_details;
            $r[$user_id]['unregistered'] = intval($_REQUEST['unregistered'][$i]);
        }

        foreach($heads as $h){
            $exam_marks[$h] = $_REQUEST[$h.'_exam_mark'];
        }

        $status = update_post_meta( $post_id, 'results', $r );
        update_post_meta( $post_id, 'exam_marks', $exam_marks );

        return array(
            'status'    => $status,
            'data'      => 'Successful',
            'payload'  => $r,
        );

    }

    /**
     * Get SMS compose form
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getSmsComposeForm()
    {
        $sms = new Sms();
        $settings['send_to'] = $_REQUEST['send_to'] ?? '';
        $settings['branch_id'] = $_REQUEST['branch_id'] ?? '';
        $settings['shift_id'] = $_REQUEST['shift_id'] ?? '';
        $settings['class_id'] = $_REQUEST['class_id'] ?? '';
        $settings['section_id'] = $_REQUEST['section_id'] ?? '';
        $settings['status'] = $_REQUEST['status'] ?? '';
        $settings['role'] = $_REQUEST['role'] ?? '';
        if($settings['role'] == 'All') unset($settings['role']);

        return array(
            'status'    => 1,
            'data'      => $sms->getComposeForm( $settings ),
        );
    }

    /**
     * Send sms
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function sendSms()
    {
        $response = [];
        $send_to = sanitize_text_field($_REQUEST['send_to']);
        $sms_text = sanitize_text_field($_REQUEST['sms_text']);
        if( $send_to == 'users' ){

            for( $i = 0; $i < count($_REQUEST['user']); $i++ ){
                $data = [];
                $data['user_id'] = $_REQUEST['user'][$i];
                $data['mobile'] = $_REQUEST['mobile'][$i];
                $data['sms'] = sanitize_text_field($_REQUEST['sms_text']);
                $response[] = Sms::send( $data );
            }

        } else {
            $mobiles = explode("\r\n", $_REQUEST['mobile_numbers']);
            foreach($mobiles as $mobile){
                $data = [];
                $data['mobile'] = $mobile;
                $data['sms'] = $sms_text;
                $response[] = Sms::send( $data );
            }
        }

        if(count($response) > 0){
            $status = 1;
        } else {
            $status = $response[0]['status'];
        }

        return array(
            'status'    => $status,
            'data'      => $status ? 'Sent' : 'Not sent!',
            'response'  => $response
        );

    }

    /**
     * Get transaction user details
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getTransactionUserDetails()
    {
        die(wp_json_encode(Transaction::searchUsers($_REQUEST)));
    }

    /**
     * Show bulk user upload with csv
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function bulkUsersCsvUpload()
    {
        $files = $_FILES['files'];
        if(empty($files)) return array(
            'status'    => 0,
            'data'      => 'Failed!',
        );
        $response = [];
        for( $i = 0; $i < count($files['name']); $i++ ){
            $file = $files['tmp_name'][$i];
            $response = User::bulkUpload($file);
            if(is_array($response)){
                $response['status'] = 1;
            }
        }
        return $response;

    }

    /**
     * Get SMS current balance
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getSmsCurrentBalance()
    {
        $balance = Sms::getBalance();
        return array(
            'status'    => $balance ? 1 : 0,
            'data'      => $balance,
        );
    }

    /**
     * SMS result details
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function smsResult()
    {
        $data = [];
        $data['mobile'] = esc_attr($_REQUEST['mobile']);
        $data['sms'] = $_REQUEST['sms_text'];
        $data['user_id'] = intval($_REQUEST['user_id'] ?? 0);

        $status = Sms::send($data);

        $res_status = $status['status'] ?? 0;

        return array(
            'status' =>  $res_status ? 1 : 0,
            'data' => $res_status ? 'Sent' : 'Failed',
        );

    }

    /**
     * Send SMS to bulk users
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function sendResultSmsToBulkUsers()
    {
        $students = $_REQUEST['student_ids'];
        $sms_data = $_REQUEST['sms_data'];

        if(empty($students)) return array(
            'status' => 0,
            'data' => 'No users found!',
        );

        $response = [];

        foreach($students as $student){
            $data = [];
            $data['sms'] = $sms_data[$student]['sms'];
            $data['mobile'] = $sms_data[$student]['mobile'];
            $response[] = Sms::send($data);
        }

        return array(
            'status'    => 1,
            'data'      => 'Processed',
            'payload' => $_REQUEST,
            'response' => $response,
        );

    }

    /**
     * Get email login form
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getLoginForm()
    {
        return array(
            'status' => 1,
            'data' => User::getLoginForm(),
        );
    }

    /**
     * User login
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function doLogin()
    {
        $email = $_REQUEST['email'];
        $pass = $_REQUEST['password'];

        $login = User::doLogin( $email, $pass );

        $status = $login == 1 ? 1 : 0;

        return array(
            'status' => $status,
            'data'=> $status ? 'Successfully logged in!' : $login,
        );

    }

    /**
     * Show user profile update form
     *
     * @return array
     *
     * @since 1.0
     * @acess public
     */
    public function showUserProfileUpdateForm()
    {
        $user = new User(get_current_user_id());
        return array(
            'status' => 1,
            'data' => $user->getProfileUpdateForm()
        );

    }

    /**
     * Update user profile
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function updateUserProfile()
    {
        $user = new User(get_current_user_id());
        $userdata = [];
        $userdata['first_name'] = sanitize_text_field($_REQUEST['first_name']);
        $userdata['display_name'] = sanitize_text_field($_REQUEST['first_name']);
        $userdata['user_email'] = sanitize_email($_REQUEST['user_email']);
        if(!empty($_REQUEST['user_pass'])) $userdata['user_pass'] = $_REQUEST['user_pass'];

        $metadata = [];
        $metadata['mobile'] = sanitize_text_field($_REQUEST['mobile']);
        $user->edit( $userdata, $metadata );
        return array(
            'status'    => 1,
            'data'      => 'Profile Updated'
        );
    }

    /**
     * Print individual result
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function printIndividualResult()
    {
        $user_id = intval($_REQUEST['user_id']);
        $extra_data = [];
        $extra_data['method'] = sanitize_text_field($_REQUEST['rank_method']);
        $extra_data['subject_order'] = $_REQUEST['subject_order'];
        $extra_data['class_data'] = $_REQUEST['class_data'];
        $extra_data['term_id'] = (int) $_REQUEST['term_id'];
        $extra_data['start_date'] = sanitize_text_field($_REQUEST['start_date']);
        $extra_data['end_date'] = sanitize_text_field($_REQUEST['end_date']);
        $exam_data = $_REQUEST['data'];

        return array(
            'status'    => 1,
            'data'      => Printer::printIndividualResult( $user_id, $exam_data, $extra_data ),
        );
    }

    /**
     * Print bulk result
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function printBulkResult()
    {

        $students = $_REQUEST['student_ids'];
        if(empty($students)) return [];
        $students = array_map( 'intval', $students );

        $extra_data = [];
        $extra_data['method'] = sanitize_text_field($_REQUEST['method']);
        $extra_data['subject_order'] = $_REQUEST['subject_order'];
        $extra_data['term_id'] = sanitize_text_field($_REQUEST['term_id']);
        $extra_data['start_date'] = sanitize_text_field($_REQUEST['start_date']);
        $extra_data['end_date'] = sanitize_text_field($_REQUEST['end_date']);

        $res = '';
        $last = end($students);

        foreach($students as $id){

            $res .= Printer::printIndividualResult( $id , $_REQUEST['data'][$id], $extra_data );
            if($last !== $id) {
                $res .= "<div class='page-break'></div>";
            }

        }

        return array(
            'status'    => 1,
            'data'      => $res,
        );

    }

    /**
     * Save a calendar
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function saveCalendar()
    {
        $data = [];
        $data['branch_id'] = isset($_REQUEST['branch_id']) ? intval($_REQUEST['branch_id']) : 0;
        $data['shift_id'] = isset($_REQUEST['shift_id']) ? intval($_REQUEST['shift_id']) : 0;
        $data['class_id'] = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;
        $data['section_id'] = isset($_REQUEST['section_id']) ? intval($_REQUEST['section_id']) : 0;
        $post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;

        $key = 'academic_calendar';

        for( $i = 0; $i < count($_REQUEST['date']); $i++ ){
            $date = $_REQUEST['date'][$i];
            $status = $_REQUEST['day_status'][$i];
            $note = $_REQUEST['note'][$i];
            $data['data'][$date] = array(
                'status' => $status,
                'note' => $note
            );
        }

        $status = update_post_meta($post_id, $key, $data);

        return array(
            'status' => $status ? 1 : 0,
            'data' => $status ? 'Successful!' : 'Failed!',
        );
    }

    /**
     * Show bulk user update
     *
     * @return array
     * @since 1.0
     * @access public
     */
    public function showBulkUserUpdateScreen()
    {
        $users = sanitize_text_field($_REQUEST['users'] ?? '');
        return array(
            'status'    => 1,
            'data'      => User::getBulkUpdateScreen( $users )
        );

    }

    /**
     * User bulk update
     *
     * @return array
     *
     * @since 1.0
     * @access public
     *
     */
    public function updateBulkUsers()
    {

        $data = [];
        $users = [];
        if(!empty($_REQUEST['role'])) $data['role'] = sanitize_text_field($_REQUEST['role']);
        if(isset($_REQUEST['status'])) $data['status'] = sanitize_text_field($_REQUEST['status']);
        if(isset($_REQUEST['branch_id'])) $data['branch_id'] = (int) $_REQUEST['branch_id'];
        if(!empty($_REQUEST['shift_id'])) $data['shift_id'] = (int) $_REQUEST['shift_id'];
        if(!empty($_REQUEST['class_id'])) $data['class_id'] = (int) $_REQUEST['class_id'];
        if(!empty($_REQUEST['section_id'])) $data['section_id'] = (int) $_REQUEST['section_id'];
        if(!empty($_REQUEST['payment_type'])) $data['payment_type'] = sanitize_text_field($_REQUEST['payment_type']);
        if(!empty($_REQUEST['payment_amount'])) $data['payment_amount'] = (float) $_REQUEST['payment_amount'];
        if(isset($_REQUEST['users'])) $users = explode(',', $_REQUEST['users']);

        $status = User::updateBulkUsers( $data, $users );
        return array(
            'status'    => $status,
            'data'      => $status ? 'Successful!' : 'Failed!',
        );

    }

    /**
     * Show user profile
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function showUserProfile()
    {
        $user_id = intval($_REQUEST['userId']);
        $user = new User($user_id);
        return array(
            'status'    => 1,
            'data'      => $user->showProfile(),
        );
    }

    /**
     * Generate Attendance api key
     *
     * @return array
     *
     * @since 1.0
     * @access pubic
     */
    public function getApiKey()
    {
        $token = sanitize_text_field($_REQUEST['token']);
        return array(
            'status' => !empty($token) ? 1 : 0,
            'token' => $token,
            'api_key' => EduPress::getApiKey($token),
        );
    }

    /**
     * Generate remote user attendance id
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function generateAttendanceIdForUsers()
    {
        return array(
            'status' => 1,
            'data'  => 'Generated IDs for all users!',
            'response' => User::generateRemoteIdForAll(),
        );
    }

    /**
     * Delete SMS Logs
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function deleteSmsLogs()
    {
        $start_date = sanitize_text_field($_REQUEST['start_date']);
        $end_date = sanitize_text_field($_REQUEST['end_date']);
        $sms = new Sms();
        $delete = $sms->deleteLogs($start_date, $end_date);
        return array(
            'status' => $delete ? 1 : 0,
            'data' => $delete ? 'Successful!' : 'Unsuccessful!',
        );
    }

    /**
     * Sms attendance summary report
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function smsAttendanceSummaryReport()
    {
        $users = sanitize_text_field($_REQUEST['users']);
        $users = explode(',', $users);
        if(empty($users)) return array(
            'status' => 0,
            'data'  => 'No users found!',
        );
        $users = array_map('intval', $users);
        $data = $_REQUEST['attendance_data'];
        $response = [];
        foreach($users as $user){
            $mobile = get_user_meta($user, 'mobile', true);
            if(empty($mobile)) continue;
            $user_data = [];
            $user_data['mobile'] = $mobile;
            $user_data['sms'] = $data[$user] ?? '';
            $user_data['user_id'] = $user;
            $send = Sms::send($user_data);
            $response[] = $send;
        }
        return array(
            'status'    => 1,
            'data'      => 'Sent',
            'response'  => $response,
        );

    }

    /**
     * Delete calendar
     *
     * @return array
     *
     * @since 1.0
     * @access publiic
     */
    public function deleteCalendar()
    {
        $cal = new Calendar(intval($_REQUEST['post_id']));
        $del = $cal->delete();
        return array(
            'status' => $del ? 1 : 0,
            'data'  => $del ? 'Successful!' : 'Unsuccessful!',
        );
    }

    /**
     * Process Showing Option to delete data
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function processDeleteData( $delete = false )
    {
        $types = explode( ',', esc_attr($_REQUEST['data_types'] ?? ''));

        global $wpdb;

        $start_date = strtotime(esc_attr($_REQUEST['start_date']));
        $end_date = strtotime(esc_attr($_REQUEST['end_date']));

        if($end_date >= strtotime(current_time('Y-m-d'))){
            $end_date = $end_date - (24 * 60 * 60);
        }

        if($start_date < $end_date){
            $date_1 = $start_date;
            $date_2 = $end_date;
        } else {
            $date_2 = $start_date;
            $date_1 = $end_date;
        }

        $date_1 = date('Y-m-d', $date_1);
        $date_2 = date('Y-m-d', $date_2);

        if(empty($types)) return array(
            'status' => 0,
            'data' => 'Missing data'
        );

        $found_posts = [];
        $wp_post_types = array( 'branch', 'shift', 'class', 'section', 'subject', 'term', 'exam', 'subject' );
        foreach($types as $type){
            if( in_array($type, $wp_post_types) ){
                $args = array(
                    'posts_per_page' => -1,
                    'post_type' => $type,
                    'post_status' => 'publish',
                );
                $date_qry = [];
                if(!empty($date_1)) $date_qry[] = array(
                    'after' => $date_1,
                    'inclusive' => true,
                );
                if(!empty($date_2)) $date_qry[] = array(
                    'before' => $date_2,
                    'inclusive' => true,
                );
                if(!empty($date_qry)) $args['date_query'] = $date_qry;
                $qry = new \WP_Query($args);
                $found_posts[$type] = array(
                    'found_posts' => $qry->found_posts,
                    'args' => $args,
                );

                // Deleting posts if delete
                if($delete){
                    if($qry->have_posts()):
                        while($qry->have_posts()): $qry->the_post();
                           $delete = wp_delete_post( $qry->post->ID, true );
                           if($delete) $found_posts[$type]['deleted'][] = $qry->post->ID;
                        endwhile;;
                    endif;
                }

            } elseif ( $type == 'sms' || $type == 'attendance' ){

                $table = $type === 'sms' ? $wpdb->prefix . 'sms_logs' : $wpdb->prefix . 'attendance';
                $qry = "SELECT COUNT(*) FROM {$table} WHERE 1 = 1 ";
                $qry .= " AND DATE(record_time) BETWEEN '{$date_1}' AND '{$date_2}' ";

                $count = (int) $wpdb->get_var($qry);
                $found_posts[$type] = array(
                    'found_posts' => $count,
                    'args' => $qry,
                );

                if($delete){
                    $qry = "DELETE FROM {$table} WHERE DATE(record_time) BETWEEN '{$date_1}' AND '{$date_2}' ";
                    $delete = $wpdb->query($qry);
                    $found_posts[$type]['delete'] = $delete ? 1 : 0;
                }
            } elseif ( $type == 'user' ){

                $args = array(
                    'role'  => 'student',
                    'number' => -1
                );
                $date_qry = [];
                if(!empty($date_1)) $date_qry[] = array(
                    'after' => $date_1,
                    'inclusive' => true,
                );
                if(!empty($date_2)) $date_qry[] = array(
                    'before' => $date_2,
                    'inclusive' => true,
                );
                if(!empty($date_qry)) $args['date_query'] = $date_qry;
                $user_qry = new \WP_User_Query($args);
                $users = $user_qry->get_results();
                $found_posts[$type] = array(
                    'found_posts' => $user_qry->get_total(),
                    'args' => $args,
                );

                if($delete && $user_qry->get_total() > 0){
                    foreach($users as $u){
                        $user  = new User($u);
                        $delete = $user->delete();
                        if($delete) $found_posts[$type]['deleted'][] = $u->ID;
                    }
                }

            }
        }

        ob_start();
        ?>
        <div>Start Date : <strong><?php echo date('d/m/y', strtotime($date_1)); ?></strong> <br>End Date : <strong><?php echo date('d/m/y', strtotime($date_2)); ?></strong></div>
        <br>
        <strong>Please check following items to confirm deletion</strong>
        <ul class="data-deletion-stats">
            <?php foreach($found_posts as $k=>$v): ?>
            <li>
                <input type="checkbox" value="<?php echo $k; ?>" name="confirm_post_types[]" id="post_<?php echo $k; ?>">
                <label for="post_<?php echo $k; ?>"><?php echo ucwords($k); ?> (<?php echo $v['found_posts']; ?>)</label>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php
        $html = ob_get_clean();

        return array(
            'status'    => 1,
            'types'     => $types,
            'qry'       => $found_posts,
            'data'      => $html,
        );

    }

    /**
     * Confirm delete data
     *
     * Simply run above query
     *
     * @since 1.0
     * @access public
     *
     */
    public function confirmDeleteData()
    {
        return $this->processDeleteData( true );
    }

    /**
     * Save post order to maintain sorting
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function savePostOrder()
    {
        $order = sanitize_text_field($_REQUEST['order']);
        $orders = explode( ',', $order );
        if(empty($orders)) return [];
        $post_type = sanitize_text_field($_REQUEST['post_type']);
        if(empty($post_type)) return [];

        foreach($orders as $k=>$v){
            update_post_meta($v, 'sort_order', $k);
        }
        return array(
            'status' => 1,
            'data' => 'Sort order saved',
        );
     }

     /**
      * Show screen to add manual attendance
      *
      * @return array
      *
      * @since 1.0
      * @access public
      */
    public function showScreenToAddManualAttendance()
    {
        return array(
            'status' => 1,
            'data' => Attendance::showOptionToAddManualAttendance(),
        );
    }

    /**
     * Show users to add manual attendance
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function showUsersToAddManualAttendance()
    {
        $args = array(
            'branch_id' => intval($_REQUEST['branch_id'] ?? ''),
            'class_id' => intval($_REQUEST['class_id'] ?? ''),
            'section_id' => intval($_REQUEST['section_id'] ?? ''),
            'shift_id' => intval($_REQUEST['shift_id'] ?? ''),
        );
        $users = Attendance::showUsersToAddManualAttendance($args);
        return array(
            'status' => 1,
            'data' => $users,
        );
    }

    /**
     * Insert manual attendance
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function insertManualAttendance()
    {
        $att_data = [];
        $branch_id = intval($_REQUEST['branch_id']);
        $current_time = strtotime(current_time('mysql'));
        if( isset($_REQUEST['user_id']) && count($_REQUEST['user_id']) > 0 ){  
            for($i = 0; $i < count($_REQUEST['user_id']); $i++){
                if($_REQUEST['status'][$i] != 'present') continue;
                $att_data[] = array(
                    'branch_id' => $branch_id,
                    'user_id' => intval($_REQUEST['user_id'][$i]),
                    'uaid' => intval($_REQUEST['attendance_id'][$i]),
                    'report_time' => date('Y-m-d H:i:s', $current_time++ ),
                    'auth_type' => 'MN',
                );
            }
        }
        foreach($att_data as $key => $value){
            $response[] = Attendance::insert($att_data[$key]);
        }
        return array(
            'status' => 1,
            'data' => $response,
        );
    }

    /**
     * Resend today's failed SMS
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function resendTodayFailedSms()
    {
        $send = Sms::resendTodayFailedSms();
        return array(
            'status' => 1,
            'data' => 'Resended ' . $send['count'] . ' SMS',
            'response' => $send
        );
    }

    /**
     * Register device 
     * 
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function registerDevice()
    {
        $device_name = sanitize_text_field($_REQUEST['device_name']);
        $device_count = intval($_REQUEST['device_count']);
        $response = EduPress::registerDevice(array(
            'device_name' => $device_name,
            'device_count' => $device_count,
        ));
        Admin::updateSettings('attendance_device_name', $device_name);
        Admin::updateSettings('attendance_device_count', $device_count);
        if(isset($response['ids']) && !empty($response['ids'])){
            Admin::updateSettings('attendance_device_id', $response['ids'] );
        }
        return $response;
    }

    /**
     * View notice details 
     * 
     * @return array    
     * 
     * @since 1.0
     * @access public
     */
    public function viewPost()
    {
        $post_id = intval($_REQUEST['id'] ?? 0);
        $post = get_post($post_id);
        if(!$post) return array(
            'status' => 0,
            'data' => 'Post not found',
        );
        ob_start();
        ?>
        <?php if($post->post_type == 'notice'): ?>
            <?php $notice = new Notice($post); ?>
            <div class='notice-details'>
                <h3><?php echo $post->post_title; ?></h3>
                <p style="font-size: 14px !important; font-style: italic; color: #666;"><strong>Post Time: </strong> <?php echo date('h:i:s A d/m/y', strtotime($post->post_date)); ?></p>
                <?php 
                    $branch_id = $notice->getMeta('branch_id');
                    $shift_id = $notice->getMeta('shift_id');
                    $class_id = $notice->getMeta('class_id');
                    $section_id = $notice->getMeta('section_id');
                ?>
                <p>
                    <?php if($branch_id) : ?>
                        <strong>Branch: </strong> <?php echo get_the_title($branch_id); ?>
                    <?php endif; ?>
                    <?php if($shift_id) : ?>
                        <strong>Shift: </strong> <?php echo get_the_title($shift_id); ?>
                    <?php endif; ?>
                    <?php if($class_id) : ?>
                        <strong>Class: </strong> <?php echo get_the_title($class_id); ?>
                    <?php endif; ?>
                    <?php if($section_id) : ?>
                        <strong>Section: </strong> <?php echo get_the_title($section_id); ?>
                    <?php endif; ?>
                </p>
                <div class="post-content" style="padding: 20px 0;">
                    <strong>Details</strong>: <br>
                    <?php echo wpautop($post->post_content); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
        $data = ob_get_clean();
        return array(
            'status' => 1,
            'data' => $data,
        );
    }

    /**
     * Print ID cards 
     * 
     * @return array 
     * 
     * @since 1.5
     * @acess public 
     */
    public function printIdCard()
    {
        $args = [
            'print_type' => sanitize_text_field($_REQUEST['print_type'] ?? ''),
            'roll' => sanitize_text_field($_REQUEST['roll'] ?? ''),
            'class_id' => intval($_REQUEST['class_id'] ?? 0),
            'section_id' => intval($_REQUEST['section_id'] ?? 0),
            'shift_id' => intval($_REQUEST['shift_id'] ?? 0),
            'branch_id' => intval($_REQUEST['branch_id'] ?? 0),
        ];
        $filename = PrintMaterial::getBulkIdCardHtml( $args );
        return array(
            'status' => 1,
            'data' => $filename ,
        );
    }

    /**
     * Send transaction SMS 
     * 
     * @return array 
     * 
     * @since 1.5.3
     * @access public 
     */
    public function smsTransaction()
    {
        $id = intval($_REQUEST['post_id']);
        $transaction = new Transaction($id);
        return $transaction->sms();
    }

    /**
     * Print transaction 
     * 
     * @return array 
     * 
     * @since 1.5.3
     * @access public 
     */
    public function printTransaction()
    {
        $id = intval($_REQUEST['post_id']);
        $transaction = new Transaction($id);
        return array(
            'status' => 1,
            'data' => $transaction->printPos(),
        );
    }

    /**
     * Print user list 
     * 
     * @return array 
     * @since 1.0
     * @access public 
     */
    public function printUserList()
    {
        $html = PrintMaterial::printUserList($_REQUEST);
        return array(
            'status' => 1,
            'data' => $html,
        );
    }

}

AdminAjax::instance();