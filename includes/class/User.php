<?php
namespace EduPress;

use WP_User;

defined( 'ABSPATH' ) || die();
class User
{

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var $user
     */
    private $user;

    /**
     * @var $usermeta
     */
    private $usermeta;

    /**
     * @var $role
     */
    private $role;

    /**
     * @var $instance
     */
    private static $_instance;

    /**
     * @var $user_type
     */
    protected $post_type = 'user';

    /**
     * Posts per page
     */
    protected $posts_per_page;

    /**
     * Initialize instance
     *
     * @return User
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
     */
    public function __construct( $id = 0 )
    {

        $this->posts_per_page = !empty($_REQUEST['posts_per_page']) ? intval($_REQUEST['posts_per_page']) : Admin::getSetting('display_posts_per_page');
        if(empty($this->posts_per_page)) $this->posts_per_page = 20;

        if ( is_numeric( $id ) && $id > 0 ){

            $user = get_user_by( 'id', $id );

            if ( $user ) {

                $this->id = $id;
                $this->setUser( $user );

            }

        } else if ( $id instanceof  \WP_User ) {

            $this->id = $id->ID;
            $this->setUser( $id );

        }

        if($this->id){

            $this->setUsermeta( get_metadata( 'user', $this->id ) );
            $this->setRole( reset($this->getUser()->roles) );

        }

        $this->setUsermeta( get_metadata( 'user', $this->id ) );


        // Add custom roles
        add_action( 'init', [ $this, 'addCustomRoles' ] );

        // Custom avatar
        add_filter('get_avatar', [ $this, 'customUserAvatar' ], 10, 5);

        // User register trigger
        add_action( 'user_register', [ $this, 'processAttendanceId' ] );

    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUsermeta()
    {
        return $this->usermeta;
    }

    /**
     * @param mixed $usermeta
     */
    public function setUsermeta($usermeta): void
    {
        $this->usermeta = $usermeta;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * Get user meta
     *
     * @param string $key
     * @param boolean $single
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getMeta( $key, $single = true )
    {

        $metadata = $this->getUsermeta();


        if ( isset($metadata[$key]) ){

            return $single ? $metadata[$key][0] : $metadata[$key];

        }

        return null;

    }

    /**
     * Update meta
     *
     * @param string $key
     * @param mixed $value
     * @param string $prev_value
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function updateMeta( $key, $value, $prev_value = '' )
    {

        $update = update_user_meta( $this->id, $key, $value, $prev_value );

        $usermeta = $this->getUsermeta();

        $usermeta[$key] = get_user_meta( $this->id, $key, false );

        $this->setUsermeta( $usermeta );

        return $update;

    }

    /**
     * Delete user meta
     *
     * @param string $key
     * @param mixed $prev_value
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public function deleteMeta( $key, $prev_value = '' )
    {

        $delete = delete_user_meta( $this->id, $key, $prev_value );

        $usermeta = $this->getUsermeta();

        $usermeta[$key] = get_user_meta( $this->id, $key, false );

        $this->setUsermeta( $usermeta );

        return $delete;

    }

    /**
     * Get current user registration date
     *
     * @return string
     *
     * @since 1.0
     * @access pubic
     */
    public function getRegisterDate($format = 'd/m/y')
    {
        return date($format, strtotime(get_the_author_meta( 'user_registered', $this->id )));
    }

    /**
     * Check current user's capability
     *
     * @return boolean
     *
     * @param string $action
     * @param string $post_type
     */
    public static function currentUserCan( $action, $post_type )
    {
        $cap = trim(strtolower($action . '_' . $post_type));
        if($post_type === 'user') $post_type = 'people';
        if($post_type === 'term') $post_type = 'exam_term';
        if( in_array( $post_type, array( 'exam', 'result', 'calendar', 'notice' ) ) && $action == 'read' ) return true;
        return current_user_can('manage_options') || current_user_can(trim(strtolower($action . '_' . $post_type)));

    }

    /**
     * Get capability related message when error occurs
     *
     * @return string
     *
     * @param string $action
     * @param string $post_type
     *
     * @since 1.0
     * @access public
     */
    public static function getCapabilityErrorMsg( $action,  $post_type )
    {

        return __( "Sorry! You are not authorized to $action $post_type", 'edupress' );

    }

    /**
     * Add custom roles
     *
     * @return void
     * @since 1.0
     * @access public
     */
    public function addCustomRoles()
    {

        // Student
        add_role(
            'student',
            'Student'
        );
        $student = get_role('student');
        $student->add_cap('read_result');
        $student->add_cap( 'read_attendance');

        // Alumni
        add_role(
            'alumni',
            'Alumni'
        );
        $alumni = get_role( 'alumni' );
        $alumni->add_cap('read_result');

        // Parent
        add_role(
            'parent',
            'Parent'
        );
        $parent = get_role('parent');
        $parent->add_cap('read_result');
        $student->add_cap( 'read_attendance');

        // Teacher
        add_role(
            'teacher',
            'Teacher'
        );
        $teacher = get_role('teacher');
        $teacher->add_cap('read_branch');
        $teacher->add_cap('read_class');
        $teacher->add_cap('read_section');
        $teacher->add_cap('read_shift');
        $teacher->add_cap('read_subject');
        $teacher->add_cap('read_exam_term');
        $teacher->add_cap('read_calendar');

        $teacher->add_cap('read_exam');
        $teacher->add_cap('publish_exam');
        $teacher->add_cap('edit_exam');
        $teacher->add_cap('delete_exam');

        $teacher->add_cap('read_result');
        $teacher->add_cap('publish_result');
        $teacher->add_cap('edit_result');
        $teacher->add_cap('delete_result');

        $teacher->add_cap('read_user' );
        $teacher->add_cap('read_sms' );
        $teacher->add_cap( 'read_attendance');

        // Accountant
        add_role(
          'accountant',
          'Accountant'
        );
        $accountant = get_role('accountant');
        $accountant->add_cap('read_branch');
        $accountant->add_cap('read_class');
        $accountant->add_cap('read_section');
        $accountant->add_cap('read_shift');
        $accountant->add_cap('read_subject');
        $accountant->add_cap('read_exam_term');
        $accountant->add_cap('read_result');
        $accountant->add_cap('read_exam');
        $accountant->add_cap('read_calendar');

        $accountant->add_cap('read_transaction');
        $accountant->add_cap('publish_transaction');
        $accountant->add_cap('edit_transaction');
        $accountant->add_cap('delete_transaction');

        $accountant->add_cap( 'read_people');
        $accountant->add_cap( 'publish_people');
        $accountant->add_cap( 'add_people');
        $accountant->add_cap( 'edit_people');
        $accountant->add_cap( 'delete_people');

        $accountant->add_cap( 'read_sms');
        $accountant->add_cap( 'send_sms');

        $accountant->add_cap( 'read_attendance');


        // Manager
        $caps = array(
            'manage_setting',
            'read_setting',
            'edit_setting',
            'read_branch',
            'publish_branch',
            'edit_branch',
            'delete_branch',
            'read_shift',
            'publish_shift',
            'edit_shift',
            'delete_shift',
            'read_class',
            'publish_class',
            'edit_class',
            'delete_class',
            'read_section',
            'publish_section',
            'edit_section',
            'delete_section',
            'read_subject',
            'publish_subject',
            'edit_subject',
            'delete_subject',
            'read_exam_term',
            'publish_exam_term',
            'edit_exam_term',
            'delete_exam_term',
            'read_exam',
            'publish_exam',
            'edit_exam',
            'delete_exam',
            'read_result',
            'publish_result',
            'edit_result',
            'delete_result',
            'read_grade_table',
            'publish_grade_table',
            'edit_grade_table',
            'delete_grade_table',
            'read_calendar',
            'publish_calendar',
            'edit_calendar',
            'read_transaction',
            'publish_transaction',
            'delete_transaction',
            'read_people',
            'publish_people',
            'edit_people',
            'delete_people',
            'read_sms',
            'send_sms',
            'delete_sms',
            'read_attendance',
            'delete_attendance',
        );

        add_role(
            'manager',
            'Manager'
        );
        $manager = get_role('manager');
        foreach($caps as $cap){
            $manager->add_cap($cap);
        }
    }

    /**
     * Return filter form
     *
     * @return string
     *
     * @since 1.0
     * @acecess public
     */
    public function getFilterForm( $wrap = false )
    {
        $fields = $this->getFilterFields();

        if(empty($fields)) return '';

        ob_start();
        ?>
        <div class="edupress-filter-list-wrap" data-post-type="<?php echo $this->post_type; ?>">
            <form data-post-type="<?php echo $this->post_type; ?>" action="" method="GET" class="edupress-form edupress-filter-list">

                <?php
                foreach ($fields as $field) {

                    if( $field['type'] == 'submit') continue;

                    ?>
                    <div class="form-column">
                        <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e($field['settings']['label'] ?? '', 'edupress'); ?></label></div>
                        <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                    </div>
                <?php } ?>

                <div class="form-column">
                    <div class="label-wrap"> &nbsp; </div>
                    <div class="value-wrap">
                        <?php
                        echo EduPress::generateFormElement( 'submit', '', array('value'=>'Filter'));
                        echo EduPress::generateFormElement( 'hidden', 'panel', array('value'=>$this->post_type));
                        ?>
                    </div>
                </div>

            </form>
        </div>

        <?php
        $html = ob_get_clean();
        $html = apply_filters( "edupress_filter_{$this->post_type}_form_html", $html );

        if ( !$wrap ) return $html;

        return EduPress::wrapInContentBox( 'Filter Users', $html );

    }

    /**
     * Get filter fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getFilterFields()
    {
        $fields = [];

        $branch = new Branch();
        $branch_options = $branch->getPosts( [], true );
        $branch_id = sanitize_text_field($_REQUEST['branch_id'] ?? '');
        $shift_id = sanitize_text_field($_REQUEST['shift_id'] ?? '');
        $class_id = sanitize_text_field($_REQUEST['class_id'] ?? '');
        $section_id = sanitize_text_field($_REQUEST['section_id'] ?? '');


        $fields['branch_id'] = array(
            'type' => 'select',
            'name' => 'branch_id',
            'settings' => array(
                'label' => 'Branch',
                'id'=>'branch_id',
                'options' => $branch_options,
                'placeholder' => 'Select',
                'required' => true,
                'value' => $branch_id,
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
            $klass = new Klass();
            $options = $klass->getPosts( [], true );
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'id'=>'class_id',
                    'placeholder' => 'Select',
                    'value' => sanitize_text_field($_REQUEST['class_id'] ?? ''),
                    'options' => $options,
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
        $fields['name'] = array(
            'type' => 'text',
            'name' => 'first_name',
            'settings' => array(
                'label' => 'Name',
                'value' => sanitize_text_field($_REQUEST['first_name'] ?? ''),
                'id'=>'first_name'
            )
        );
        $fields['roll'] = array(
            'type' => 'number',
            'name' => 'roll',
            'settings' => array(
                'label' => 'Roll / Student ID',
                'value' => sanitize_text_field($_REQUEST['roll'] ?? ''),
                'id'=>'roll'
            )
        );
        $fields['mobile'] = array(
            'type'  => 'text',
            'name'  => 'mobile',
            'settings' => array(
                'value' => sanitize_text_field($_REQUEST['mobile'] ?? ''),
                'label' => 'Mobile',
                'id'=>'mobile'
            )
        );
        $fields['role'] = array(
            'type'  => 'select',
            'name'  => 'role',
            'settings' => array(
                'options' => self::getRoles(),
                'value' => sanitize_text_field($_REQUEST['role'] ?? ''),
                'label' => 'Role',
                'id'=>'user_role',
                'placeholder'=> 'Select a role'
            )
        );
        $users_options = array(
            25 => 25,
            50 => 50,
            75 => 75,
            100 => 100,
            '-1' => 'All'
        );
        $fields['posts_per_page'] = array(
            'type' => 'select',
            'name' => 'posts_per_page',
            'settings' => array(
                'label' => 'Users Per Page',
                'value' => intval( $_REQUEST['posts_per_page'] ?? 25 ),
                'options' => $users_options,
            )
        );

        return apply_filters( "edupress_{$this->post_type}_filter_fields", $fields ) ;
    }


    /**
     * Get all roles
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public static function getRoles()
    {

        $roles = array(
            'student' => 'Student',
            'teacher' => 'Teacher',
            'manager' => 'Manager',
            'accountant' => 'Accountant',
            'alumni'  => 'Alumni',
            'parent' => 'Parent',
        );

        return apply_filters( 'edupress_user_roles', $roles );

    }

    /**
     * Get publish fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getPublishFields( $settings = [] )
    {

        $fields = array();
        $args = array();

        $branch = new Branch();
        $branch_options = $branch->getPosts( [], true );

        $fields['branch_id'] = array(
            'name'  => 'branch_id[]',
            'type'  => 'select',
            'settings' => array(
                'label' => 'Branch',
                'options' => $branch_options,
                'value' => $settings['branch_id'] ?? '',
                'required' => true,
                'placeholder' => count($branch_options) > 1 ? 'Select' : '',
            )
        );

        if ( $settings['role'] === 'student' ){

            if ( Admin::getSetting('shift_active' ) == 'active' ){

                $branch_id = $settings['branch_id'] ?? '';
                if( $branch_id ) {
                    $args['meta_query'][] = array(
                        'key' => 'branch_id',
                        'value' => $branch_id,
                        'compare' => '='
                    );
                }

                $shift_id = $settings['shift_id'] ?? '';
                $shift = new Shift();

                $fields['shift_id'] = array(
                    'name'  => 'shift_id[]',
                    'type'  => 'select',
                    'settings' => array(
                        'label' => 'Shift',
                        'options' => $shift->getPosts( $args, true ),
                        'value' => $shift_id,
                        'required' => isset($settings['role']) && $settings['role'] === 'student' ? true : false,
                        'placeholder'=> 'Select'
                    )
                );
                if( $shift_id ){
                    $args['meta_query'][] = array(
                        'key'   => 'shift_id',
                        'value' => $shift_id,
                        'compare' => '='
                    );
                }

            }

            if( Admin::getSetting('class_active') == 'active' ){

                $class_id = $settings['class_id'] ?? '';
                if( isset($args['meta_query']) && count( $args['meta_query'] ) > 1 ) $args['meta_query']['relation'] = 'AND';

                $class = new Klass();
                $fields['class_id'] = array(
                    'name'  => 'class_id[]',
                    'type'  => 'select',
                    'settings'  => array(
                        'label' => 'Class',
                        'value' => $class_id,
                        'options' => $class->getPosts( $args, true ),
                        'required' =>  isset($settings['role']) && $settings['role'] === 'student' ? true : false,
                        'placeholder'=> 'Select'
                    )
                );

                $args['meta_query'][] = array(
                    'key'   => 'class_id',
                    'value' => $class_id,
                    'compare' => '='
                );
            }

            if( Admin::getSetting('section_active') == 'active' ){

                $section_id = $settings['section_id'] ?? '';
                if( isset($args['meta_query']) && count( $args['meta_query'] ) > 1 ) $args['meta_query']['relation'] = 'AND';

                $section = new Section();

                $fields['section_id'] = array(
                    'name'  => 'section_id[]',
                    'type'  => 'select',
                    'settings'  => array(
                        'label' => 'Section',
                        'value' => $section_id,
                        'options' => $section->getPosts( $args, true ),
                        'required' => isset($settings['role']) && $settings['role'] === 'student' ? true : false,
                        'placeholder'=> 'Select'
                    )
                );

                $args['meta_query'][] = array(
                    'key'   => 'section_id',
                    'value' => $section_id,
                    'compare' => '='
                );
            }
        }


        $fields['role'] = array(
            'name'  => 'role[]',
            'type'  => 'select',
            'settings'=>array(
                'label' => 'Role',
                'options' => self::getRoles(),
                'value' => $settings['role'] ?? '',
            )
        );

        $fields['first_name'] = array(
            'name'  => 'first_name[]',
            'type'  => 'text',
            'settings'=> array(
                'label' => 'Name',
                'placeholder' => 'Full name',
                'value' => $settings['first_name'] ?? '',
                'required' => true,
            )
        );

        if( $settings['role'] === 'student' ){
            $fields['roll'] = array(
                'name'  => 'roll[]',
                'type'  => 'text',
                'settings'=>array(
                    'label' => 'Roll / Student ID',
                    'value' => $settings['roll'] ?? '',
                    'placeholder' => 'Roll / Student ID',
                    'required' => true,
                )
            );
        }

        $fields['mobile'] = array(
            'name'  => 'mobile[]',
            'type'  => 'text',
            'settings'=> array(
                'label' => 'Mobile',
                'placeholder' => 'Mobile',
                'value' => $settings['mobile'] ?? '',
            )
        );

        $fields['user_email'] = array(
            'name'  => 'user_email[]',
            'type'  => 'email',
            'settings'=> array(
                'label' => 'Email',
                'placeholder' => 'Email',
                'value' => $settings['user_email'] ?? '',
            )
        );

        $fields['user_pass'] = array(
            'name'  => 'user_pass[]',
            'type'  => 'password',
            'settings'=> array(
                'label' => 'Password',
                'placeholder' => 'Password',
            )
        );

        if ( $settings['role'] === 'student' ){
            $subject = new Subject();
            $fields['optional_subject_id'] = array(
                'name'  => 'optional_subject_id[]',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Optional Subject',
                    'options' => $subject->getPosts( [], true ),
                    'value' => $settings['optional_subject_id'] ?? '',
                    'placeholder' => 'Select a subject'
                )
            );

            $fields['payment_type'] = array(
                'name'  => 'payment_type[]',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Payment Type',
                    'options' => array('Monthly' => 'Monthly', 'Package' => 'Package'),
                    'required' => true,
                    'placeholder' => 'Select a payment type',
                    'value' => $settings['payment_type'] ?? '',
                )
            );

            $fields['payment_amount'] = array(
                'name'  => 'payment_amount[]',
                'type'  => 'number',
                'settings' => array(
                    'label' => 'Payment Amount',
                    'required' => true,
                    'placeholder' => 'Enter an amount',
                    'value' => $settings['payment_amount']
                )
            );
        }

        return $fields;


    }

    /**
     * Get publish form
     *
     * @return string
     *
     * @param array $settings
     * @param int $rows
     *
     * @since 1.0
     * @access public
     */
    public function getPublishForm( $settings = [], $rows = 10  )
    {

        $fields = $this->getPublishFields( $settings );

        if (empty($fields)) return '';

        // unsetting fields based on role
        switch ( strtolower($settings['role'])){
            case 'student':
                // do nothing
                break;
            case 'teacher':
            case 'accountant':
            case 'manager':
            case 'parent':
            case 'alumni':
                unset($fields['class_id']);
                unset($fields['section_id']);
                break;
            default:
                break;
        }

        // divide fields into 2
        // 1 for fields with value available
        // another for empty values

        $value_av = [];
        $value_uav = [];

        foreach ($fields as $field){

            if( empty($field['settings']['value'] ) ) $value_uav[] = $field;
            else $value_av[] = $field;

        }


        ob_start();
        ?>
        <script>
            let edupressBulkUserRowsCount = <?php echo $rows; ?>;
        </script>
        <form action="" method="post" class="<?php echo EduPress::getClassNames(array( 'edupress-ajax', 'edupress-publish-bulk-user' ), 'form' ); ?>">

            <!-- Fields already set up -->
            <div class="edupress-table-wrap">
                <table class="edupress-table edupress-publish-user-declared-value">
                    <tr>
                        <?php
                            foreach($value_av as $k){
                                $k['settings']['disabled'] = true;
                                $name = str_replace('[]', '', $k['name']);
                                ?>
                                <td>
                                    <label for=""><?php _e( $k['settings']['label'] ?? '', 'edupress' ); ?></label>
                                    <?php echo EduPress::generateFormElement( $k['type'], $name, $k['settings'] ); ?>
                                </td>
                                <?php
                            }
                        ?>
                    </tr>
                </table>
            </div>

            <!-- Fields to input value -->
            <div class="edupress-table-wrap">
                <table class="edupress-table">

                    <tr>
                        <?php foreach($value_uav as $field) : ?>

                            <?php
                                // Skip if role is set and its student
                                if ( isset($settings['role']) && $settings['role'] != 'student' && $field['name'] == 'roll' ) continue;
                            ?>

                            <th data-key="<?php echo $field['name'] ?? ''; ?>"><?php _e( $field['settings']['label'] ?? '', 'edupress' ); ?></th>

                        <?php endforeach; ?>
                        <th></th>
                    </tr>

                    <?php for( $i = 0; $i < $rows; $i++ ): ?>
                    <tr data-row-id="<?php echo $i; ?>">

                        <?php foreach($value_uav as $field) : ?>

                            <?php
                                // Skip if role is set and its student
                                if ( isset($settings['role']) && $settings['role'] != 'student' && $field['name'] == 'roll' ) continue;
                            ?>

                            <td data-row-id="<?php echo $i; ?>" data-key="<?php echo $field['name'] ?? ''; ?>">

                                <!-- delete row -->
                                <?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ) ; ?>

                            </td>

                        <?php endforeach; ?>

                        <td data-row-id="<?php echo $i; ?>" data-cell="action">

                            <span class="publish-bulk-user-status" data-row-id="<?php echo $i; ?>"></span>
                            <a href="javascript:void(0)" title="Delete" class="publish-bulk-user-delete" data-row-id="<?php echo $i; ?>">x</a>
                            <a href="javascript:void(0)" title="Duplicate" class="publish-bulk-user-copy" data-row-id="<?php echo $i; ?>">+</a>

                            <!-- hidden fields that already have value -->
                            <?php foreach( $value_av as $field ): ?>

                                <?php echo EduPress::generateFormElement( 'hidden', $field['name'], $field['settings']); ?>

                            <?php endforeach; ?>

                            <?php echo EduPress::generateFormElement( 'hidden', 'row_id[]', array( 'value'=>$i )); ?>

                        </td>
                    </tr>
                    <?php endfor; ?>

                    <!-- submit -->
                    <tr>
                        <td colspan="<?php echo count($value_uav) + 1; ?>">
                            <?php echo EduPress::generateFormElement( 'submit', '', array('value'=>'Add Users', 'class' => 'edupress-btn')); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax')); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'publishBulkUser')); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array('value'=>'publishBulkUserBeforeSendCallback')); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'success_callback', array('value'=>'publishBulkUserSuccessCallback')); ?>
                            <?php echo EduPress::generateFormElement( 'hidden', 'error_callback', array('value'=>'publishBulkUserErrorCallback')); ?>
                            <?php wp_nonce_field('edupress'); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Edit Fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getEditFields()
    {

        $settings = [];
        $settings['user_login'] = $this->getUser()->user_login;
        $settings['user_email'] = $this->getUser()->user_email;
        $settings['first_name'] = $this->getMeta('first_name');
        $settings['branch_id'] = $this->getMeta('branch_id');
        $settings['shift_id'] = $this->getMeta('shift_id');
        $settings['class_id'] = $this->getMeta('class_id');
        $settings['mobile'] = $this->getMeta('mobile');
        $settings['section_id'] = $this->getMeta('section_id');
        $settings['roll'] = $this->getMeta('roll');
        $settings['role'] = $this->getRole();
        $settings['father_name'] = 'Father Name';
        $settings['mother_name'] = 'Mother Name';
        $settings['payment_type'] = $this->getMeta('payment_type');
        $settings['payment_amount'] = $this->getMeta('payment_amount');
        $settings['optional_subject_id'] = $this->getMeta('optional_subject_id');

        $fields = $this->getPublishFields($settings);
        $photo = "";
        $photo_id = $this->getMeta('avatar_id');
        if($photo_id) {
            $photo = wp_get_attachment_image( $photo_id, array(75,75));
        }
        $fields['dp'] = array(
            'type' => 'file',
            'name'  => 'dp',
            'settings'=> array(
                'label' => 'Profile photo',
                'data' => array(
                    'data-target-name' => 'avatar_id',
                    'data-target-class' => 'user-photo-wrap'
                ),
                'class' => 'wp_ajax_upload',
                'after' => "<div class='user-photo-wrap' style='width: 100%;max-width:75px;'>$photo</div>",
            )
        );
        $fields['avatar_id'] = array(
            'type' => 'hidden',
            'name'  => 'avatar_id',
            'settings' => array(
                'value'  => $this->getMeta('avatar_id')
            )
        );

        $custom_fields = self::getCustomProfileFieldNames();
        if(!empty($custom_fields)) {
            foreach($custom_fields as $k=>$v) {
                $fields[$k] = array(
                    'type' => 'text',
                    'name'  => $k,
                    'settings' => array(
                        'label' => $v,
                        'value' => $this->getMeta( $k ),
                    )
                );
            }
        }


        $fields['status'] = array(
            'type' => 'select',
            'name' => 'status',
            'settings' => array(
                'options' => array('Active'=>'Active','Inactive'=>'Inactive'),
                'value' => $this->getMeta('status'),
                'label'=> 'Status'
            )
        );
        return apply_filters( 'edupress_user_edit_fields', $fields );

    }

    /**
     * Get form to publish or edit user
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getEditForm( $action = 'edit', $wrap = false )
    {

        $fields = $this->getEditFields();

        foreach($fields as $k=>$v){
            $fields[$k]['name'] = str_replace( '[]', '', $fields[$k]['name'] );
        }

        if (empty($fields)) return '';

        ob_start();
        ?>

        <form action="" method="post" class="<?php echo EduPress::getClassNames( array("edupress-$action-$this->post_type", "edupress-$action-post-form" ), 'form' ); ?>">

            <?php
            $only_manager_fields = array('role', 'branch_id', 'shift_id', 'class_id', 'section_id', 'payment_type', 'payment_amount', 'roll', 'mobile', 'status' );
            foreach( $fields as $k => $field ){

                if( $field['type'] == 'submit' ) continue;

                if ( $field['type'] == 'hidden' ) {

                    echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] );
                    continue;

                }

                if( !User::currentUserCan('edit', 'user') && in_array( $field['name'], $only_manager_fields ) ) continue;

                $label = $field['settings']['label'] ?? '';
                ?>

                <div class="form-row">
                    <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e( $label, 'edupress' ); ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                </div>

                <?php
            }
            ?>
            <div class="form-row">
                <div class="label-wrap"></div>
                <div class="value-wrap">

                    <?php

                    echo EduPress::generateFormElement( 'submit', '', array( 'value' => $action == 'edit' ? 'Update' : 'Publish' ) );
                    echo EduPress::generateFormElement( 'hidden', 'action', array( 'value' => 'edupress_admin_ajax' ) );
                    echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value' => $action.'User' ) );
                    echo EduPress::generateFormElement( 'hidden', 'user_id', array( 'value' => $this->id ) );

                    // this elements are for js further action
                    $before_send_callback = apply_filters( "edupress_{$action}_{$this->post_type}_before_send_callback", "{$this->post_type}BeforeSendCallback" );
                    $success_callback = apply_filters( "edupress_{$action}{$this->post_type}_success_callback", "{$this->post_type}SuccessCallback" );
                    $error_callback = apply_filters( "edupress_{$action}{$this->post_type}_error_callback", "{$this->post_type}ErrorCallback" );

                    if( !empty($before_send_callback) ):
                        echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array( 'value' => $before_send_callback ) );
                    endif;

                    if( !empty($success_callback) ):
                        echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value' => $success_callback ) );
                    endif;

                    if( !empty($error_callback) ):
                        echo EduPress::generateFormElement( 'hidden', 'error_callback', array( 'value' => $error_callback ) );
                    endif;

                    wp_nonce_field( 'edupress' );

                    ?>
                </div>
            </div>
        </form>

        <?php
        $html = ob_get_clean();

        $html = $action == 'publish' ? apply_filters( "edupress_publish_{$this->post_type}_form_html", $html ) : apply_filters( "edupress_edit_{$this->post_type}_form_html", $html );

        if( !$wrap ) return $html;

        $title = $action == 'publish' ? 'Publish New ' . $this->post_type : 'Update ' . $this->post_type;

        return EduPress::wrapInContentBox( ucwords($title) , $html );
    }


    /**
     * Return list html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {

        $paged = max( get_query_var('paged'), 1 );
        $page = max( get_query_var('page'), 1 );

        $args = [];
        $args['orderby'] = !empty($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        $args['order'] = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        $args['count_total'] = true;
        $args['paged'] = max($page, $paged);
        $args['number'] = $this->posts_per_page;
        $args['role__in'] = !empty($_REQUEST['role']) ? [ sanitize_text_field($_REQUEST['role']) ] : array_keys( self::getRoles() ) ;

        if( isset($_REQUEST['first_name']) && !empty($_REQUEST['first_name'])){
            $args['meta_query'][] = array(
                'key'   => 'first_name',
                'value' => sanitize_text_field($_REQUEST['first_name']),
                'compare' => '='
            );
        }

        if( isset($_REQUEST['roll']) && !empty($_REQUEST['roll'])){
            $args['meta_query'][] = array(
                'key'   => 'roll',
                'value' => intval($_REQUEST['roll']),
                'compare' => '='
            );
        }

        if( isset($_REQUEST['mobile']) && !empty($_REQUEST['mobile'])){
            $args['meta_query'][] = array(
                'key'   => 'mobile',
                'value' => sanitize_text_field($_REQUEST['mobile']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
        }
        if( isset($_REQUEST['branch_id']) && !empty($_REQUEST['branch_id'])){
            $args['meta_query'][] = array(
                'key'   => 'branch_id',
                'value' => intval($_REQUEST['branch_id']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
        }
        if( isset($_REQUEST['shift_id']) && !empty($_REQUEST['shift_id'])){
            $args['meta_query'][] = array(
                'key'   => 'shift_id',
                'value' => intval($_REQUEST['shift_id']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
        }
        if( isset($_REQUEST['class_id']) && !empty($_REQUEST['class_id'])){
            $args['meta_query'][] = array(
                'key'   => 'class_id',
                'value' => intval($_REQUEST['class_id']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
        }
        if( isset($_REQUEST['section_id']) && !empty($_REQUEST['section_id'])){
            $args['meta_query'][] = array(
                'key'   => 'section_id',
                'value' => intval($_REQUEST['section_id']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
        }

        if ( isset($args['meta_query']) && count($args['meta_query']) > 1 ) $args['meta_query']['relation'] = 'AND';

        $qry = new \WP_User_Query($args);
        $results = $qry->get_results();
        $pages = ceil( $qry->get_total() / $args['number'] );


        if ( empty($results) ) return 'No users found!';

        $shift_active = Admin::getSetting('shift_active') == 'active';
        $class_active = Admin::getSetting('class_active') == 'active';
        $section_active = Admin::getSetting('section_active') == 'active';
        $titles = [];

        ob_start();
        ?>
        <div class="count-results"><?php _e( 'Total ' .$qry->get_total() . ' users found!', 'edupress') ; ?> </div>
        <div class="edupress-table-wrap">
            <table class="edupress-table edupress-list edupress-master-table tablesorter" data-post-type="user">

                <thead>
                    <tr>
                        <th class="no-print">
                            <span class="no-print">
                                <input data-post-type="<?php echo $this->post_type; ?>" type="checkbox" name="edupress-select-bulk-delete" class="edupress-bulk-select-all" id="edupress-select-bulk-delete">
                                <!--                            <label for="edupress-select-bulk-delete">--><?php //_e('All', 'edupress'); ?><!--</label>-->
                                <span style="float:right">
                                    <a title="Bulk Delete" href="javascript:void(0)" class="edupress-bulk-delete"><?php echo EduPress::getIcon('delete'); ?></a>
                                    <a title="Bulk Update" href="javascript:void(0)" class="edupress-bulk-update-users"><?php echo EduPress::getIcon('edit'); ?></a>
                                </span>
                            </span>
                        </th>
                        <th><?php _e( 'Branch', 'eduprsss'); ?></th>
                        <th><?php _e( 'Role', 'eduprsss'); ?></th>
                        <th><?php _e( 'Name', 'eduprsss'); ?></th>
                        <?php if($class_active): ?>
                            <th><?php _e( 'Class', 'eduprsss'); ?></th>
                        <?php endif; ?>

                        <?php if($section_active) : ?>
                            <th><?php _e( 'Section', 'eduprsss'); ?></th>
                        <?php endif; ?>

                        <th><?php _e( 'Roll', 'eduprsss'); ?></th>

                        <th><?php _e( 'Reg. Date', 'edupress' ); ?></th>
                        <?php if( self::currentUserCan( 'edit', $this->post_type ) ): ?>

                            <th class="no-print" style="text-align:center;"><?php _e( 'Action', 'eduprsss'); ?></th>

                        <?php endif; ?>
                    </tr>
                </thead>



                <?php foreach( $results as $user ) :
                    $this->__construct( $user );
                ?>
                <tr data-id="<?php echo $this->id; ?>" data-user-id="<?php echo $this->id; ?>" data-attendance-id="<?php echo $this->getMeta('attendance_id'); ?>">
                    <td class="no-print" width="100">

                        <?php
                            // current user cannot delete himself
                            $disable_own = $this->id === get_current_user_id() ? " disabled='disabled' " :  '';
                            // ID field, we'll show attendance id if its active
                            // otherwise show user id
                            // in checkbox we'll always show user id

                            $user_visible_id = $this->id;
                            if(Admin::getSetting('attendance_active') == 'active'){
                                $user_visible_id = $this->getMeta('attendance_id');
                            }
                        ?>
                        <input <?php echo $disable_own; ?> data-id="<?php echo $this->id; ?>" data-post-type="<?php echo $this->post_type; ?>" type="checkbox" name="edupress-bulk-delete-post[]" class="edupress-bulk-select-item no-print" value="<?php echo $this->id; ?>" id="id_<?php echo $this->id; ?>">
                        <label for="id_<?php echo $this->id; ?>"><?php echo $user_visible_id; ?></label>

                    </td>

                    <td><?php echo get_the_title( $this->getMeta('branch_id')); ?></td>
                    <td><?php echo ucwords($this->getRole()); ?></td>
                    <td>
                        <?php $text = get_avatar($this->id, 18) . $this->getMeta('first_name');  ?>
                        <?php echo self::showProfileOnClick( $this->id, $text ); ?>
                    </td>

                    <?php if($class_active):?>
                        <?php if(!isset($titles[$this->getMeta('class_id')])) $titles[$this->getMeta('class_id')] = get_the_title($this->getMeta('class_id')); ?>
                        <td><?php echo !empty($this->getMeta('class_id')) && $this->getRole() == 'student' ? $titles[$this->getMeta('class_id')] : ''; ?></td>
                    <?php endif; ?>

                    <?php if($section_active):?>
                        <?php if(!isset($titles[$this->getMeta('section_id')])) $titles[$this->getMeta('section_id')] = get_the_title($this->getMeta('section_id')); ?>
                        <td><?php echo !empty($this->getMeta('section_id')) && $this->getRole() == 'student' ? $titles[$this->getMeta('section_id')] : ''; ?></td>
                    <?php endif; ?>

                    <td><?php echo $this->getRole() == 'student' ? $this->getMeta('roll') : ''; ?></td>

                    <td><?php echo $this->getRegisterDate(); ?> </td>
                    <?php if( User::currentUserCan( 'edit', $this->post_type ) ): ?>
                        <td align="center" style="text-align: center;" class="no-print">

                            <a href="javascript:void(0)" data-action="edit" class="edupress-modify-user no-print" data-user-id="<?php echo $this->id; ?>"> <?php echo EduPress::getIcon('edit'); ?></a>

                            <?php if ( self::currentUserCan('delete', $this->post_type ) ) : ?>

                                <a href="javascript:void(0)" data-action="delete" class="edupress-modify-user" data-user-id="<?php echo $this->id; ?>"><?php echo EduPress::getIcon('delete'); ?></a>

                            <?php endif; ?>

                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
        if( $pages > 1 ) echo EduPress::getPagination( $pages );
        return ob_get_clean();

    }

    /**
     * Show profile on clicking text
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function showProfileOnClick($user_id,$text)
    {
        $classes = EduPress::getClassNames(array('showUserProfile'), 'link');
        return "<a data-user-id='{$user_id}' data-success_callback='showUserProfileSuccessCallback' data-error_callback='' data-action='edupress_admin_ajax' data-ajax_action='showUserProfile'  href='javascript:void(0)'  class='{$classes}'>{$text}</a>";
    }


    /**
     * Get List html
     *
     * @return string
     *
     * @since 1.0
     * @acecess public
     */
    public function getList()
    {
        if( !User::currentUserCan('read', $this->post_type ) ) return User::getCapabilityErrorMsg( 'see', $this->post_type . ' entries.' );
        return $this->getPublishButton() . $this->getFilterForm() . $this->getListHtml();
    }

    /**
     * Return publish new button
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getPublishButton( $wrap = true )
    {

        if( !User::currentUserCan('publish', $this->post_type ) ) return '';

        ob_start();
        ?>
        <form style="display: none;" action="" method="POST" data-action="showPublishBulkUserForm" class="<?php echo EduPress::getClassNames(array('edupress-ajax', 'form-bulk-add-user', 'grid-4-col'), 'form' ); ?>">
            
            <div class="form-col rows">
                <div class="label-wrap"><label for="rows"><?php _e( 'No. of users', 'edupress' ); ?></label></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement( 'number', 'rows', array( 'value' => 1, 'required' => true, 'id' => 'rows', 'data' => array( 'min' => 1 ) ) ); ?></div>
            </div>

            <div class="form-col role">
                <div class="label-wrap"><label for="role"><?php _e( 'Role', 'edupress' ); ?></label></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement( 'select', 'role', array( 'options' => self::getRoles(), 'required' => true, 'id' => 'role', 'placeholder' => 'Select' ) ); ?></div>
            </div>

            <?php $branch = new Branch(); ?>
            <div class="form-col branch_id">
                <div class="label-wrap"><label for="branch_id"><?php _e( 'Branch', 'edupress' ); ?></label></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement( 'select', 'branch_id', array( 'options' => $branch->getPosts( [], true ), 'required' => true, 'id' => 'branch_id', 'placeholder' => 'Select' ) ); ?></div>
            </div>

            <!-- shift -->
            <?php if( Admin::getSetting('shift_active') == 'active' ) : ?>
                <div class="form-col shift_id">
                    <div class="label-wrap"><label for="shift_id"><?php _e( 'Shift', 'edupress' ); ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( 'select', 'shift_id', array( 'options' => [], 'required' => false, 'id' => 'shift_id', 'placeholder' => 'Select' ) ); ?></div>
                </div>
            <?php endif; ?>

            <!-- class -->
            <?php if( Admin::getSetting('class_active') == 'active' ) : ?>
                <div class="form-col class_id">
                    <div class="label-wrap"><label for="class_id"><?php _e( 'Class', 'edupress' ); ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( 'select', 'class_id', array( 'options' => [], 'required' => false, 'id' => 'class_id', 'placeholder' => 'Select' ) ); ?></div>
                </div>
            <?php endif; ?>

            <!-- section -->
            <?php if( Admin::getSetting('section_active') == 'active' ) : ?>
                <div class="form-col section_id">
                    <div class="label-wrap"><label for="section_id"><?php _e( 'Section', 'edupress' ); ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( 'select', 'section_id', array( 'options' => [], 'required' => false, 'id' => 'section_id', 'placeholder' => 'Select' ) ); ?></div>
                </div>
            <?php endif; ?>

            <!-- submit -->
            <div class="form-col submit">
                <div class="label-wrap"><label for="bulkSubmit"> &nbsp; </label></div>
                <div class="value-wrap">
                    <?php echo EduPress::generateFormElement( 'submit', '', array( 'value' => 'Show Form' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'button', '', array( 'value' => 'CSV Upload', 'class'=>'showFormToUploadCsvToAddUsers' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'action', array( 'value' => 'edupress_admin_ajax' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value' => 'showPublishBulkUserForm' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array( 'value' => 'bulkUserBeforeSendCallback' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value' => 'bulkUserSuccessCallback' ) ); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'error_callback', array( 'value' => 'bulkUserErrorCallback' ) ); ?>
                    <?php wp_nonce_field('edupress'); ?>
                    <div style="display: none">
                        <input type="file" class="bulk_users_csv_trigger" name="csv_data" accept="text/csv">
                    </div>
                </div>
                <?php $csv_url = EDUPRESS_URL . 'assets/csv/demo-user-data.csv'; ?>
                <?php echo EduPress::generateFormElement( 'html', '', array( 'html' => "<a download target='_blank' href='{$csv_url}'>Demo CSV File</a>" ) ); ?>

            </div>

        </form>

        <?php
        $html = ob_get_clean();

        if ( !$wrap ) return $html;

        return EduPress::wrapInContentBox( "ADD NEW USERS <a style='color: #fff;' href='javascript:void(0)' class='toggleForm'>[Show]</a>", $html );

    }

    /**
     * Get profile custom fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getCustomProfileFieldNames()
    {
        // Custom fields by user
        $custom_fields = Admin::getSetting('user_profile_custom_fields');
        $custom_fields = explode( ',', $custom_fields );
        $custom_fields = array_map('trim', $custom_fields);
        $custom_fields = array_filter($custom_fields);
        $fields_sanitized = [];
        if(!empty($custom_fields)){
            foreach($custom_fields as $k){
                $cleaned_string = preg_replace('/[^a-zA-Z0-9\s]/', '', $k);
                $cleaned_string = strtolower(trim(str_replace(' ', '_', $cleaned_string)));
                $fields_sanitized[$cleaned_string] = $k;
            }
        }
        return $fields_sanitized;
    }

    /**
     * Count lifetime presence of a user
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function countTotalAttendance()
    {
        global $wpdb;
        $qry = $wpdb->prepare("SELECT COUNT(DISTINCT(DATE(record_time))) as total_days FROM {$wpdb->prefix}attendance WHERE user_id = %d", $this->id );
        return (int) $wpdb->get_var($qry);
    }

    /**
     * Count total exams sat by the user
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function countTotalExam()
    {
        $format = sprintf("i:%d;", $this->id);

        global $wpdb;
        $qry = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'results' AND meta_value LIKE '%{$format}%'";
        return (int) $wpdb->get_var($qry);
    }

    /**
     * Show Profile
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function showProfile()
    {
        if( !$this->user ) return 'User not found!';

        if(!User::currentUserCan('edit', 'user')) return "Sorry! You are not authorized.";

        $present = Attendance::isUserPresent( $this->id, current_time('Y-m-d') );
        ob_start();
        ?>
        <div class="user-profile-wrap">

            <section class="header-wrap">
                <div class="avatar-wrap"><?php echo get_avatar( $this->id, 150 ); ?></div>
                <div class="title-wrap">
                    <h3 class="name"><?php echo $this->getMeta('first_name'); ?></h3>
                    <ul class="user-meta">
                        <li><?php echo EduPress::getIcon('attendance'); ?><a href="javascript:void(0)" class="<?php echo !$present ? 'absent' : 'present'; ?>"><?php echo $present ? "Present Today" : "Absent Today"; ?></a></li>
                        <li><?php echo EduPress::getIcon('user'); ?><a href="javascript:void(0)">Since <?php echo $this->getRegisterDate( 'd M, y'); ?></a></li>
                        <?php $url = sprintf("%s/?report_type=logs&branch_id=%d&shift_id=%d&class_id=%d&section_id=%d&roll=%d&start_date=%s&end_date=%s&posts_per_page=-1&panel=attendance", site_url(), $this->getMeta('branch_id'), $this->getMeta('shift_id'), $this->getMeta('class_id'), $this->getMeta('section_id'), $this->getMeta('roll'), $this->getRegisterDate('Y-m-d'), current_time('Y-m-d')); ?>
                        <li><?php echo EduPress::getIcon('calendar'); ?><a target="_blank" href="<?php echo $url; ?>">Present <?php echo $this->countTotalAttendance(); ?> days</a></li>
                        <?php if($this->getRole() == 'student'): ?>
                            <?php
                                $url = sprintf("%s/?branch_id=%d&shift_id=%d&class_id=%d&section_id=%d&panel=exam&posts_per_page=-1",   site_url(), $this->getMeta('branch_id'), $this->getMeta('shift_id'), $this->getMeta('class_id'), $this->getMeta('section_id'));
                            ?>
                            <li><?php echo EduPress::getIcon('exam'); ?><a target="_blank" href="<?php echo $url; ?>">Appeared in <?php echo $this->countTotalExam(); ?> Exams</a></li>
                        <?php endif; ?>
                        <li><?php echo EduPress::getIcon('transaction'); ?><a href="javascript:void(0)">Paid <?php echo Admin::getSetting('transaction_currency_sign') . $this->getTotalPaid(); ?></a></li>
                    </ul>
                </div>
            </section>


            <section class="details-wrap">
                <?php
                $fields = array(
                    'roll'              => 'Roll',
                    'branch_id'         => 'Branch',
                    'shift_id'          => 'Shift',
                    'class_id'          => 'Class',
                    'section_id'        => 'Section',
                    'mobile'            => 'Mobile',
                    'email'             => 'Email',
                );
                $custom_fields = self::getCustomProfileFieldNames();
                if(!empty($custom_fields))  $fields = $fields + $custom_fields;
                $fields = apply_filters( 'edupress_user_profile_fields', $fields, $this->id );
                ?>
                <div class="col-left">
                    <h4 class="section-title">User Details</h4>
                    <div class="edupress-table-wrap">
                        <table class="edupress-table user-profile">
                            <tr>
                                <th><?php _e( 'Role', 'edupress' ); ?></th>
                                <td><?php echo ucwords($this->getRole()); ?></td>
                            </tr>
                            <?php
                            foreach($fields as $k=>$v){
                                $value = $this->getMeta($k);
                                if(empty($value)) continue;
                                if( str_contains($k, '_id') ) $value = get_the_title($value);
                                ?>
                                <tr>
                                    <th><?php _e( $v, 'edupress' ); ?></th>
                                    <td><?php echo $value; ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                    </div>


                    <?php if( $this->getRole() === 'student' ) : ?>
                    <?php $sign = Admin::getSetting('transaction_currency_sign', ''); ?>

                    <!--
                    <h4 class="section-title">Payment Info</h4>
                    <div class="edupress-table-wrap">
                        <table class="edupress-table user-profile">
                            <tr>
                                <th style="width:25%"> <?php _e( 'Type', 'edupress' ); ?></th>
                                <td style="width:25%"><?php echo $this->getMeta('payment_type'); ?></td>
                                <th style="width:25%"><?php _e( 'Amount', 'edupress' ); ?></th>
                                <td style="width:25%"><?php echo $sign . $this->getMeta('payment_amount'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Total Paid', 'edupress' ); ?></th>
                                <td><?php echo $sign . $this->getTotalPaid(); ?></td>
                                <th><?php _e( 'Total Due', 'edupress' ); ?></th>
                                <td><?php echo $sign . $this->getTotalDue(); ?></td>
                            </tr>
                        </table>
                    </div>
                    -->

                    <h4 class="section-title">Payment Details</h4>
                    <?php echo $this->showTransactionDetails(); ?>
                    <?php endif; ?>

                </div>

                <?php 

                //a%20%7C%20Role%3A%20Student%20%7C%20Roll%3A%201103%20%7C%20Class%3A%201st%20Year%20%7C%20Section%3A%20HSC-2026%20%7C%20Mobile%3A%2001815521778&t_user_id=5&panel=transaction

                    $url = get_permalink(get_the_ID());
                    $url = preg_replace( '/\/page\/\d+/', '', $url);
                    $url = preg_replace( '/\/paged\/\d+/', '', $url);
                    $url .= '?t_user_id=' . $this->id;
                    $url .= '&panel=transaction';
                    $url .= '&branch_id=' . $this->getMeta('branch_id');
                ?>

                <script>
                    var curUrl = window.location.href.replace(/\/page\/\d+/g, '');
                    curUrl = curUrl.replace(/\/paged\/\d+/g, '');
                    curUrl += '&t_user_id=' + <?php echo $this->id; ?>;
                    curUrl += '&panel=transaction';
                    curUrl += '&branch_id=' + <?php echo $this->getMeta('branch_id'); ?>;
                    document.querySelector('a.view-all-transaction').setAttribute('href', curUrl);
                </script>

                <div class="col-right">
                    <h4 class="section-title"><?php _e( 'Transaction Activity', 'edupress' ); ?> <a class="view-all-transaction" style="font-size: 14px;" target="_blank" href="<?php echo $url; ?>">View All</a> </h4> 
                    <?php echo $this->showTransactionActivity( 'all' ); ?>

                    <h4 class="section-title" style="margin-top: 50px;"><?php _e( 'Attendance Report', 'edupress' ); ?></h4>
                    <?php echo $this->showAttendanceReport(); ?>


                </div>
            </section>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Get payment activities
     *
     * @return array
     * @since 1.0
     * @access public
     */
    public function getTransactionActivity( $type = 'all', $start_date = null, $end_date = null )
    {
        global $wpdb;

        $qry = "SELECT * FROM {$wpdb->prefix}transaction WHERE 1 = 1 ";
        $qry .= " AND user_id = {$this->id} ";
        if( $type !== 'all' ){
            if( $type === 'inflow' ){
                $qry .= " AND is_inflow = 1 ";
            } else {
                $qry .= " AND is_inflow = 0 ";
            }
        }

        if( !is_null($start_date) ){
            $qry .= " AND DATE(t_time) >= '{$start_date}' ";
        }

        if( !is_null($end_date) ){
            $qry .= " AND DATE(t_time) <= '{$end_date}' ) ";
        }

        $qry .= " ORDER BY ID DESC ";

        $results = $wpdb->get_results( $qry, ARRAY_A );
        $wpdb->flush();
        return $results;

    }


    /**
     * Show transaction activity
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function showTransactionActivity( $type = 'all', $start_date = null, $end_date = null )
    {

        $activities = $this->getTransactionActivity( $type, $start_date, $end_date );

        if(empty($activities)) return __( 'No transaction activity found!', 'edupress' );

        ob_start();
        $name = $this->getMeta('first_name');
        ?>
            <ul class="transaction-activity">
                <?php foreach( $activities as $activity ) : ?>
                <li>
                    <?php
                        $text = "<span class='activity-time'>" . date( 'h:i A, d/m/Y', strtotime($activity['t_time'])) . "</span>";
                        $text .= "<span class='activity-details'>";
                            $type = $activity['is_inflow'] ? ' paid ' : ' spent ';
                            $text .= "$name <strong>$type</strong> ";
                            $text .= number_format( $activity['amount'], 2 );
                            $text .= " in <strong>{$activity['account']}</strong> {$activity['method']} ";
                        $text .= "</span>";
                        echo $text;

                    ?>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php
        return ob_get_clean();

    }


    /**
     * Upload with CSV
     *
     * @return mixed
     *
     * @since 1.0
     * @access public
     * @acccess
     */
    public static function bulkUpload( $file = '' )
    {

        $data = EduPress::readCSV($file);
        if(empty($data)) return false;
        $response = [];
        foreach($data as $user){

            if(empty($user['role'])) {
                $response['error'] = isset($response['error']) ? $response['error'] + 1 : 1;
                continue;
            }

            $email = $user['email'] ?? '';
            $password = $user['password'] ?? '';

            $uniqid = uniqid();

            if(!empty($email)){
                $user_exist = get_user_by('email', $email);
                if($user_exist) $email = $uniqid . '@' . $_SERVER['HTTP_HOST'];
            } else {
                $email = $uniqid . '@' . $_SERVER['HTTP_HOST'];
            }

            if(empty($password)) $password = 'E' . rand( 100000, 100000000 ) . 'P';

            $args = array(
                'user_login'    => $uniqid,
                'user_pass'     => $password,
                'user_email'    => $email,
                'first_name'    => $user['name'] ?? '',
                'display_name'  => $user['name'] ?? '',
                'role'          => $user['role'],
            );
            $user_id = wp_insert_user( $args );
            if(!is_wp_error($user_id)){

                $response['success'] = isset($response['success']) ? $response['success'] + 1 : 1;

                $new_user = new \WP_User( $user_id );
                $new_user->set_role(strtolower(trim($user['role'])));

                if(isset($user['mobile'])) update_user_meta($user_id, 'mobile', $user['mobile']);

                if( strtolower(trim($user['role'])) == 'student' ){
                    $section_id = (int) $user['section_id'] ?? $user['section'];
                    $class_id = $user['class_id'] ?? $user['class'];

                    if( $section_id ){
                        $class_id = get_post_meta( $section_id, 'class_id', true );
                        $shift_id = get_post_meta( $section_id, 'shift_id', true );
                        $branch_id = get_post_meta( $section_id, 'branch_id', true );
                    } else if ( $class_id ){
                        $shift_id = get_post_meta( $class_id, 'shift_id', true );
                        $branch_id = get_post_meta( $class_id, 'branch_id', true );
                    }
                    update_user_meta( $user_id, 'branch_id', $branch_id );
                    update_user_meta( $user_id, 'shift_id', $shift_id );
                    update_user_meta( $user_id, 'class_id', $class_id );
                    update_user_meta( $user_id, 'section_id', $section_id );
                    if(!empty($user['roll'])) update_user_meta( $user_id, 'roll', $user['roll'] );
                    if(isset($user['payment'])) update_user_meta( $user_id, 'payment_type', ucwords(strtolower(trim($user['payment']))));
                    if(isset($user['amount'])) update_user_meta( $user_id, 'payment_amount', floatval($user['amount']) );
                    if(isset($user['optional']) && !empty($user['optional'])) update_user_meta( $user_id, 'optional_subject_id', intval($user['optional']) );
                }

                // Send welcome sms or email if enabled
                self::notifyAfterRegister(
                    array(
                        'id'    => $user_id,
                        'mobile'=> $user['mobile'],
                        'email' => $email,
                        'password' => $password,
                        'name'  => $user['name'],
                    )
                );

            } else {
                $response['error'] = isset($response['error']) ? $response['error'] + 1 : 1;
            }
        }
        if( EduPress::isActive('attendance') ) self::generateRemoteIdForAll();
        return $response;

    }


    /**
     * Insert user
     *
     * @return int
     *
     * @param array $userdata
     * @param array $metadata
     *
     * @since 1.0
     * @access public
     */
    public function insert(array $userdata = [], array $metadata = [] )
    {

        $user = wp_insert_user( $userdata );
        if( is_wp_error( $user ) ) return $user->get_error_message();

        if ( !empty( $metadata ) ){

            $skip_fields = array( 'action', 'ajax_action', '_wpnonce', '_wp_http_referer', 'user_login', 'user_email', 'role', 'before_send_callback', 'success_callback', 'error_callback', 'row_id' );

            foreach( $metadata as $k => $v ){

                if ( in_array( $k, $skip_fields ) ) continue;

                update_user_meta( $user, $k, $v );

            }
        }

        return $user;

    }

    /**
     * Edit a user
     *
     * @return int
     *
     * @param array $userdata
     * @param array $metadata
     *
     * @since 1.0
     * @access public
     */
    public function edit( $userdata, $metadata = [] )
    {

        $userdata['ID'] = $this->id;
        $update = wp_update_user( $userdata );

        if( !empty($metadata) ){
            foreach( $metadata as $k => $v ){
                update_user_meta( $this->id, $k, $v );
            }
        }

        // Deleting user meta when role is not student
        if(isset($userdata['role']) && $userdata['role'] != 'student'){
            $keys = array('class_id', 'section_id', 'roll', 'payment_type', 'payment_amount', 'optional_subject_id');
            foreach($keys as $key){
                delete_user_meta($this->id, $key);
            }
        }

        return $update;

    }

    /**
     * Get all users
     *
     * @param array $conds
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getAll( $conds = [] )
    {
        $args = [];

        if(!empty($conds['branch_id']) && $conds['branch_id'] > 0){
            $args['meta_query'][] = array(
                'key' => 'branch_id',
                'value' => intval($conds['branch_id'])
            );
        }
        if(!empty($conds['shift_id']) && $conds['shift_id'] > 0){
            $args['meta_query'][] = array(
                'key' => 'shift_id',
                'value' => intval($conds['shift_id'])
            );
        }
        if(!empty($conds['class_id']) && $conds['class_id'] > 0){
            $args['meta_query'][] = array(
                'key' => 'class_id',
                'value' => intval($conds['class_id'])
            );
        }
        if(!empty($conds['section_id']) && $conds['section_id'] > 0){
            $args['meta_query'][] = array(
                'key' => 'section_id',
                'value' => intval($conds['section_id'])
            );
        }

        if(isset($conds['orderby']) && $_REQUEST['orderby'] == 'roll'){
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            $args['meta_key'] = 'roll';
            $args['meta_type'] = 'NUMERIC';
        }

        if( isset($conds['role']) && !empty($conds['role']) ) $args['role__in'] = esc_attr($conds['role']);

        if( isset($args['meta_query']) && count($args['meta_query']) > 1) $args['meta_query']['relation'] = 'AND';

        $qry = new \WP_User_Query($args);

        return $qry->get_results();


    }


    /**
     * Show user login form
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function getLoginForm()
    {
        if( is_user_logged_in() ) return __( "You are alrady logged in!", 'edupress' );
        ob_start();
        ?>
        <div class="edupress-form-wrap">
            <form action="" class="<?php echo EduPress::getClassNames( array('edupressLoginForm'), 'form'); ?> ">

                <h2><?php _e( 'Login', 'edupress' ); ?></h2>
                <!-- Email -->
                <div class="form-row email">
                    <div class="label-wrap">
                        <label for="email"><?php _e( 'Email / ID', 'edupress' ); ?></label>
                    </div>
                    <div class="value-wrap">
                        <?php echo EduPress::generateFormElement( 'text', 'email', array('required'=>true, 'placeholder'=>'Type your ID or email')); ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-row password">
                    <div class="label-wrap">
                        <label for="email"><?php _e( 'Password', 'edupress' ); ?></label>
                    </div>
                    <div class="value-wrap">
                        <?php echo EduPress::generateFormElement( 'password', 'password', array('required'=>true, 'placeholder'=>'Type your password')); ?>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-row submit">
                    <div class="value-wrap">
                        <?php
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>'Login', 'placeholder'=>'Input your email'));
                            echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax', 'placeholder'=>'Input your email'));
                            echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'doLogin' ));
                            echo EduPress::generateFormElement( 'hidden', 'success_callback', array('value'=>'loginSuccessCallback' ));
                            echo EduPress::generateFormElement( 'hidden', 'error_callback', array('value'=>'loginErrorCallback' ));
                            wp_nonce_field('edupress');
                        ?>

                    </div>
                </div>
            </form>
        </div>

        <?php return ob_get_clean();
    }

    /**
     * DO user login
     *
     * @return boolean | string
     *
     * @param string $email
     * @param string $password
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function doLogin( $email, $password )
    {
        $email = sanitize_text_field($email);

        $search_by = is_numeric($email) ? 'ID' : 'email';

        $user = get_user_by( $search_by, $email );

        if( !$user ){
            $user = get_user_by( 'login', $email );
        }

        if( !$user ) return __('Invalid Email', 'edupress' );

        if( empty($password) ) return __( 'Password cannot be empty!', 'edupress' );

        $user = wp_authenticate( $user->user_email, $password );

        if ( is_wp_error( $user ) ) {

            // Authentication failed
            return __( 'Invalid username or password!', 'edupress' );

        } else {

            // Authentication successful, log the user in
            wp_set_auth_cookie( $user->ID );
            return 1;

        }

    }

    /**
     * Show profile update form
     *
     * @return string
     *
     *
     * @since 1.0
     * @access public
     */
    public function getProfileUpdateForm()
    {
        $fields = [];
        $fields['heading'] = array(
            'type' => 'html',
            'name' => 'heading',
            'settings' => array(
                'html' => "<h4>Update your profile</h4>"
            )
        );
        $fields['name'] = array(
            'type' => 'text',
            'name' => 'first_name',
            'settings' => array(
                'label' => 'Name',
                'value' => $this->getMeta('first_name'),
                'required' => true,
                'placeholder' => 'Input your name here'
            )
        );

        $fields['user_email'] = array(
            'type' => 'email',
            'name' => 'user_email',
            'settings' => array(
                'label' => 'Email',
                'value' => $this->getUser()->user_email,
                'required' => true,
                'placeholder'=>'Input your email here'
            )
        );
        $fields['mobile'] = array(
            'type' => 'text',
            'name' => 'mobile',
            'settings' => array(
                'label' => 'Mobile',
                'value' => $this->getMeta('mobile'),
                'required' => true,
                'placeholder' => 'Input your mobile number here'
            )
        );
        $fields['html'] = array(
            'type' => 'html',
            'name' => 'Password highlight',
            'settings' => array(
                'html' => "<h4>Password</h4>",
            ),
        );
        $fields['user_pass'] = array(
            'type' => 'password',
            'name'  => 'user_pass',
            'settings' => array(
                'value' => '',
                'placeholder' => 'Input only if you want to update password',
                'label' => 'Password'
            )
        );
        $fields['confirm_user_pass'] = array(
            'type' => 'password',
            'name'  => 'confirm_user_pass',
            'settings' => array(
                'value' => '',
                'placeholder' => 'Confirm password',
                'label' => 'Confirm Password'
            )
        );

        $custom_fields = self::getCustomProfileFieldNames();
        if(!empty($custom_fields)){
            foreach($custom_fields as $k=>$v){
                $fields[$k] = array(
                    'type' => 'text',
                    'name' => $k,
                    'settings' => array(
                        'value' => $this->getMeta($k),
                        'label' => $v,
                    )
                );
            }
        }

        ob_start();
        if(!empty($fields)){
            ?>
            <div class="edupress-form-wrap">
                <form action="" class="<?php echo EduPress::getClassNames(array('edupress-update-user-profile'), 'form'); ?>">
                    <?php
                    foreach($fields as $k => $v){
                        ?>
                        <div class="form-row">

                            <?php if( $v['type'] !== 'html' ): ?>
                            <div class="label-wrap">
                                <label for=""><?php _e( $v['settings']['label'] ?? ''); ?></label>
                            </div>
                            <?php endif; ?>

                            <div class="value-wrap">
                                <?php echo EduPress::generateFormElement( $v['type'] ?? '', $v['name'] ?? '', $v['settings'] ?? [] ); ?>
                            </div>

                        </div>
                            <?php 
                    }
                    ?>
                    <div class="form-row">
                        <div class="label-wrap"> &nbsp; </div>
                        <div class="value-wrap">
                            <?php
                                echo EduPress::generateFormElement( 'submit', '', array('value'=>'Update Profile','class'=>'edupress-button'));
                                echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax'));
                                echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'updateUserProfile'));
                                echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array('value'=>'editUserBeforeSendCallback'));
                                wp_nonce_field('edupress');
                            ?>
                        </div>
                    </div>
                </form>
            </div>
            <?php
        }
        ?>


        <?php
        return ob_get_clean();

    }

    /**
     * Custom user avatar
     *
     * @return string
     *
     * @sine 1.0
     * @access public
     */
    public function customUserAvatar( $avatar, $id_or_email, $size, $default, $alt )
    {
        $user = false;

        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', $id_or_email);
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $user = get_user_by('id', $id_or_email->user_id);
            }
        } else {
            $user = get_user_by('email', $id_or_email);
        }

        $avatar_url = EDUPRESS_IMG_URL . 'avatar.png';

        if ($user && is_object($user)) {
            $avatar_id = get_user_meta($user->ID, 'avatar_id', true);
            if ($avatar_id) {
                $avatar_url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
            }
            if ($avatar_url) {
                $avatar = "<img loading='lazy' alt='{$alt}' src='{$avatar_url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }

        return $avatar;

    }

    /**
     * Calculate total payment made by a user for the current section -> class -> shift
     *
     * @param array $conds
     *
     * @return float
     *
     * @since 1.0
     * @access public
     */
    public function getTotalPaid( $conds = [] )
    {

        $details = $this->getTransactionDetails();
        return $details['total_paid'];

    }

    /**
     * Calculate total due for the current class
     *
     * @param array $conds
     * @return float
     *
     * @since 1.0
     * @access public
     */
    public function getTotalDue( $conds = [] )
    {

        $details = $this->getTransactionDetails();
        return $details['total_due'];


    }

    /**
     * Get payment details of the user
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getPaymentDetails()
    {

        $branch_id = $this->getMeta('branch_id');
        $shift_id = $this->getMeta('shift_id');
        $class_id = $this->getMeta('class_id');
        $section_id = $this->getMeta('section_id');

        global $wpdb;

        $qry = "SELECT * FROM {$wpdb->prefix}transaction WHERE 1 = 1  ";
        $qry .= " AND is_inflow = 1 ";
        if($branch_id) $qry .= " AND branch_id = {$branch_id} ";
        if($shift_id) $qry .= " AND shift_id = {$shift_id} ";
        if($class_id) $qry .= " AND class_id = {$class_id} ";
        if($section_id) $qry .= " AND section_id = {$section_id} ";

        $qry .= " ORDER BY id DESC ";

        $results = $wpdb->get_results($qry);
        if(empty($results)) return __('No transactions found!', 'edupress');

        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table">
                <tr>
                    <th><?php _e( 'Date', 'edupress' ); ?></th>
                    <th><?php _e( 'Amount', 'edupress' ); ?></th>
                    <th><?php _e( 'Account', 'edupress' ); ?></th>
                    <th><?php _e( 'Method', 'edupress' ); ?></th>
                </tr>
                <?php
                    foreach($results as $r){
                        ?>
                        <tr>
                            <td><?php echo date('d M, y, h:i a', strtotime($r->t_time)); ?></td>
                            <td><?php echo $r->amount; ?></td>
                            <td><?php echo $r->account; ?></td>
                            <td><?php echo $r->method; ?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Show users for bulk update
     *
     * @param string $users
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getBulkUpdateScreen( $users = '' )
    {
        ob_start();
        ?>
        <h3>Update Bulk Users</h3>
        <form action="" class="<?php echo EduPress::getClassNames(array('updateBulkUsers'), 'form'); ?>">
            <div class="form-row">
                <div class="label-wrap"><?php _e('Role', 'edupress'); ?></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement('select','role', array(
                        'options' => self::getRoles(),
                        'placeholder' => 'Select',
                    )); ?></div>
            </div>
            <?php if(Admin::getSetting('branch_active') == 'active') : ?>
                <?php $branch = new Branch(); ?>
                <div class="form-row">
                    <div class="label-wrap"><?php _e('Branch', 'edupress'); ?></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement('select','branch_id', array(
                            'options' => $branch->getPosts([], true),
                            'placeholder' => 'Select',
                        )); ?></div>
                </div>
            <?php endif; ?>

            <?php if(Admin::getSetting('shift_active') == 'active') : ?>
                <div class="form-row">
                    <div class="label-wrap"><?php _e('Shift', 'edupress'); ?></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement('select','shift_id', array(
                            'placeholder' => 'Select',
                            'options' => [],
                        )); ?></div>
                </div>
            <?php endif; ?>

            <?php if(Admin::getSetting('class_active') == 'active') : ?>
                <div class="form-row">
                    <div class="label-wrap"><?php _e('Class', 'edupress'); ?></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement('select','class_id', array(
                            'placeholder' => 'Select',
                            'options' => [],
                        )); ?></div>
                </div>
            <?php endif; ?>

            <?php if(Admin::getSetting('section_active') == 'active') : ?>
                <div class="form-row">
                    <div class="label-wrap"><?php _e('Section', 'edupress'); ?></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement('select','section_id', array(
                            'placeholder' => 'Select',
                            'options' => [],
                        )); ?></div>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="label-wrap"><?php _e('Status', 'edupress'); ?></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement('select','status', array(
                        'placeholder' => 'Select',
                        'options' => array('active'=>'Active','inactive'=>'Inactive'),
                        'value' => 'active',
                        'required' => true,
                    )); ?></div>
            </div>

            <div class="form-row">
                <div class="label-wrap"><?php _e('Payment Type', 'edupress'); ?></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement('select','payment_type', array(
                        'placeholder' => 'Select',
                        'options' => array('Monthly'=>'Monthly','Package'=>'Package'),
                    )); ?></div>
            </div>
            <div class="form-row">
                <div class="label-wrap"><?php _e('Payment Amount', 'edupress'); ?></div>
                <div class="value-wrap"><?php echo EduPress::generateFormElement('number','payment_amount', array(
                    )); ?></div>
            </div>

            <div class="form-row">
                <div class="label-wrap"></div>
                <div class="value-wrap">
                    <?php echo EduPress::generateFormElement( 'submit', '', array( 'value' => 'Save' )); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'action', array( 'value' => 'edupress_admin_ajax' )); ?>
                    <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value' => 'updateBulkUsers' )); ?>
                    <?php wp_nonce_field('edupress'); ?>
                    <?php
                        if(is_array($users)) $users = implode(',', $users);
                        echo EduPress::generateFormElement( 'hidden', 'users', array( 'value' => $users ));
                    ?>
                </div>
            </div>

        </form>
        <?php
        return ob_get_clean();

    }

    /**
     * Update bulk users
     *
     * @param array $data
     * @param array $users
     *
     * @return int
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function updateBulkUsers( $data = [], $users = [] )
    {
        if( !is_array($users) ){
            $users = explode(',', $users);
        }

        if(empty($users)) return 0;

        $users = array_map( 'intval', $users );
        $role = strtolower(trim($data['role'] ?? ''));
        foreach($users as $u){

            // Assigning role
            if(!empty($role)){
                $user = new \WP_User($u);
                $user->set_role($role);
            }

            if(!empty($data['branch_id'])) update_user_meta( $u, 'branch_id', $data['branch_id'] );
            if(!empty($data['status'])) update_user_meta( $u, 'status', $data['status'] );
            if(!empty($data['shift_id'])) update_user_meta( $u, 'shift_id', $data['shift_id'] );
            if(!empty($data['class_id'])) update_user_meta( $u, 'class_id', $data['class_id'] );
            if(!empty($data['section_id'])) update_user_meta( $u, 'section_id', $data['section_id'] );
            if(!empty($data['payment_type'])) update_user_meta( $u, 'payment_type', ucwords(strtolower(trim($data['payment_type']))) );
            if(!empty($data['payment_amount'])) update_user_meta( $u, 'payment_amount', $data['payment_amount'] );
        }

        return 1;
    }

    /**
     * Get due details
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getDueDetails()
    {
        $branch_id = $this->getMeta('branch_id');
        $shift_id = $this->getMeta('shift_id');
        $class_id = $this->getMeta('class_id');
        $section_id = $this->getMeta('section_id');

        global $wpdb;

        $qry = "SELECT t2.* FROM {$wpdb->prefix}transaction t1 LEFT JOIN {$wpdb->prefix}transaction_items t2 ON t1.id = t2.transaction_id WHERE 1 = 1 ";
        $qry .= " AND t1.branch_id = {$branch_id} ";
        if($shift_id) $qry .= " AND t1.shift_id = {$shift_id} ";
        if($class_id) $qry .= " AND t1.class_id = {$class_id} ";
        if($section_id) $qry .= " AND t1.section_id = {$section_id} ";
        $qry .= " AND t1.user_id = {$this->id} ";
        $qry .= " GROUP BY t2.item_name, t2.item_month, t2.item_year ORDER BY t2.id DESC ";
        $results = $wpdb->get_results($qry);
        if(empty($results)) return __('No dues found!', 'edupress');
        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table">
                <tr>
                    <th><?php _e( 'Name', 'edupress' ); ?></th>
                    <th><?php _e( 'Amount', 'edupress' ); ?></th>
                    <th><?php _e( 'Month', 'edupress' ); ?></th>
                </tr>
                <?php
                foreach($results as $r){
                    if($r->item_due <= 0 ) continue
                    ?>
                    <tr>
                        <td><?php echo ucwords($r->item_name); ?></td>
                        <td><?php echo $r->item_due; ?></td>
                        <td><?php echo date('M Y', strtotime("{$r->item_year}-{$r->item_month}-1") ); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>
        <?php return ob_get_clean();

    }

    /**
     * Get stduents of a class 
     * 
     * @return array 
     * 
     * @param array $conds 
     * 
     * @since 1.0
     * @access public
     * @static
     */
    public static function getStudents( $conds = [], $count_only = false  )
    {
        $args = [];
        $args['role__in'] = array('student');
        if(!empty($conds)){
            foreach($conds as $k=>$v){
                $args['meta_query'][] = array(
                    'key' => $k,
                    'value' => $v,
                    'compare' => '=',
                );
            }
        }
        if(isset($args['meta_query']) && count($args['meta_query']) > 1){
            $args['meta_query']['relation'] = 'AND';
        }

        $qry = new \WP_User_Query( $args );        
        $response = $count_only ? $qry->get_total() : $qry->get_results();
        wp_reset_query();
        return $response;

    }

    /**
     * Count total students
     *
     * @return int
     *
     * @param array $conds
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function countStudents( $conds = [] )
    {
        $count = self::getStudents( $conds, true );
        return (int) $count;
    }

    /**
     * Notify user afer register
     *
     * @param array $data
     *
     * @return void
     *
     * @since 1.0
     * @access pubic
     * @static
     */
    public static function notifyAfterRegister( $data = [] )
    {
        $name = $data['name'] ?? 'There';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $mobile = $data['mobile'] ?? 0;

        if( Admin::getSetting('user_welcome_sms' ) === 'active' && !empty($mobile) ){

            $text = "Hi {$name}!\n";
            $text .= "An account is created for you! Details:\n\n";
            $text .= "Website: {$_SERVER['HTTP_HOST']}\n";
            $text .= "Email: {$email}\n";
            $text .= "Password: {$password}\n";
            $text .= "\nThank you!\n";

            Sms::send(array('mobile'=>$mobile, 'sms'=>$text));

        }

        if( Admin::getSetting('user_welcome_email' ) === 'active' && !empty($email)){

            $institute_name = Admin::getSetting('institute_name');
            $subject = !empty($name) ? "Account created for {$name}" : "Acccount created for you";
            $body = "<p>";
            $body .= "Hi $name!<br><br>";
            $body .= "An account is created for you with the website of {$institute_name}. Here goes the detais: <br><br>";
            $body .= "Email: <strong>{$email}</strong><br>";
            $body .= "Password: <strong>{$password}</strong><br>";
            $body .= "<br>You can also reset password with the website.";
            $body .= "<br><br>Thank you!";
            $body .= "</p>";

            wp_mail( $email, $subject, $body );
        }
    }

    /**
     * Generate remote user id for all users
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function generateRemoteIdForAll()
    {
        $users = get_users();
        if(empty($users)) return [];

        $response = [];
        foreach($users as $user){
            $attendance_id = (int) get_user_meta( $user->ID, 'attendance_id', true );
            if($attendance_id) continue;
            $remote_id = Attendance::getRemoteId($user->ID);
            $response[$user->ID] = $remote_id;
            if( is_numeric($remote_id) ) update_user_meta( $user->ID, 'attendance_id', $remote_id );
        }

        return $response;

    }

    /**
     * Process attendance Id for user if attendance is active
     *
     * @param int $user_id
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function processAttendanceId( $user_id = 0 )
    {
        if( Admin::getSetting('attendance_active') != 'active' ) return;
        $id = Attendance::getRemoteId( $user_id );
        update_user_meta( $user_id, 'attendance_id', $id );
    }

    /**
     * Get user id by attendance id
     *
     * @return int
     *
     * @param int $user_id
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getIdByAttendanceId($user_id)
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'attendance_id' AND meta_value = %d", $user_id));
    }

    /**
     * Update user status active or inactive
     *
     * @return mixed
     *
     * @param string $status
     *
     * @since 1.0
     * @access public
     */
    public function updateStatus( $status = '' )
    {
        $this->updateMeta('status', $status);
        $logs = maybe_unserialize( $this->getMeta('status_logs'));
        if(!is_array($logs)) $logs = [];
        $logs[] = array(
            'status'    => $status,
            'date'      => current_time( 'mysql' ),
        );
        return $this->updateMeta( 'status_logs', $logs );


    }

    /**
     * Get user attendance report
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getAttendanceReport( $start_date = '', $end_date = '' )
    {
        if( Admin::getSetting('attendance_active') !== 'active'  ) return array( 'error' => 'Attendance Inactive' );
        if ( !$this->id )return array('error' => 'Invalid user id');

        $id_selector = Admin::getSetting('section_active') == 'active' ? 'section_id' : 'class_id';
        $section_id = $this->getMeta($id_selector);
        $calendar_data = get_post_meta( $section_id, 'academic_calendar', true );
        if(empty($calendar_data)) {
            $section_id = $this->getMeta('class_id');
        }


        if(empty($start_date)){

            $user_reg_date = $this->getRegisterDate('Y-m-d');
            $section_start_date = get_post_meta( $section_id, 'start_date', true );
            $start_date = empty($section_start_date) || strtotime($section_start_date) < strtotime($user_reg_date) ? $user_reg_date : $section_start_date;

        }

        if(empty($end_date)){

            $today = current_time( 'Y-m-d' );
            $section_end_date = get_post_meta( $section_id, 'end_date', true );

            $end_date = empty($section_end_date) || strtotime($section_end_date) > strtotime($today) ? $today : $section_end_date;

        }

        $dt_start = new \DateTime($start_date);
        $dt_end = new \DateTime($end_date);
        $dt_interval = new \DateInterval('P1D');
        $dt_end->modify('+1 day');
        $diff = $dt_start->diff($dt_end)->days;

        // Getting calendar details
        $calendar = new Calendar( $section_id );
        $cal_stats = $calendar->getStats( $start_date, $end_date );
        // echo "<pre>";
        // var_dump($cal_stats);
        // echo "</pre>";

        // Response data
        $res = [];
        $res['start_date'] = $start_date;
        $res['end_date']  = $end_date;
        $res['total_days'] = $diff;
        $res['open'] = $cal_stats['count_open'];
        $res['close'] = $cal_stats['count_close'];
        $res['present_data'] = [];


        // Finding user logs
        global $wpdb;
        $qry = $wpdb->prepare("SELECT COUNT(*) AS total, DATE(record_time) as dt FROM {$wpdb->prefix}attendance WHERE user_id = %d AND DATE(record_time) >= %s AND DATE(record_time) <= %s GROUP BY DATE(record_time) ", $this->id, $start_date, $end_date );
        $results = $wpdb->get_results($qry);
        if(!empty($results)){
            foreach($results as $result){
                $res['present_data'][] = $result->dt;
            }
        }

        $res['present'] = count($res['present_data']);
        $res['absent'] = $res['open'] - $res['present'];
        $res['present_percentage'] = $res['open'] > 0 ? number_format($res['present'] / $res['open'] * 100, 2) : 0;

        return $res;


    }

    /**
     * Show Attendance Report
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function showAttendanceReport( $start_date = '', $end_date = '' )
    {
        $data = $this->getAttendanceReport($start_date, $end_date);
        if(empty($data)) return 'No data found';
        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table user-profile">
                <tr>
                    <th style="width:25%"><?php _e( 'Date Range', 'edupress' ); ?></th>
                    <td style="width:25%"><?php echo date('d/m/y', strtotime($data['start_date'])) . ' - ' . date('d/m/y', strtotime($data['end_date'])) ; ?></td>
                    <th style="width:25%"><?php _e( 'Total Days', 'edupress' ); ?></th>
                    <td style="width:25%"><?php echo $data['total_days'] ; ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Open', 'edupress' ); ?> </th>
                    <td><?php echo $data['open'] ; ?></td>
                    <th><?php _e( 'Close', 'edupress' ); ?></th>
                    <td><?php echo $data['close'] ; ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Present', 'edupress' ); ?></th>
                    <td><?php echo $data['present'] ; ?></td>
                    <th><?php _e( 'Absent', 'edupress' ); ?></th>
                    <td><?php echo $data['absent'] ; ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Presence %', 'edupress' ); ?></th>
                    <td><?php echo $data['present_percentage'] ; ?> %</td>
                    <th><?php _e( 'Absence %', 'edupress' ); ?></th>
                    <td><?php echo 100 - $data['present_percentage'] ; ?> %</td>
                </tr>
            </table>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Check if a user has any transaction
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public function hasTransaction()
    {
        global $wpdb;
        $tran = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}transaction WHERE user_id = {$this->id} LIMIT 1 ");
        return $tran > 0;
    }

    /**
     * Delete a user if no transaction found
     * If transaction found, make the user inactive
     *
     * @return boolean
     *
     * @since 1.0
     * @acecess public
     */
    public function delete()
    {
        if($this->hasTransaction()){
            $this->updateMeta('status', 'inactive');
            return false;
        }
        return wp_delete_user( $this->id );
    }


    /**
     * Get a user transaction
     * 
     * @param int $user_id
     * @return array
     * 
     * @since 1.0
     * @access public
     */
    public function getTransactionDetails()
    {
        $reg_date = $this->getUser()->user_registered;
        $reg_date_formatted = date('Y-m-d', strtotime($reg_date));
        $start_date = '';
        if(EduPress::isActive('section')){
            $class_post_id = $this->getMeta('section_id');
        } else if (EduPress::isActive('class')){
            $class_post_id = $this->getMeta('class_id');
        } else if(EduPress::isActive('shift')){
            $class_post_id = $this->getMeta('shift_id');
        }

        $start_date = get_post_meta($class_post_id,'start_date', true);
        $end_date = get_post_meta($class_post_id,'end_date', true);

        $start_date_formatted = date('Y-m-d', strtotime($start_date));
        $end_date_formatted = date('Y-m-d', strtotime($end_date));
        
        $dt1 = $reg_date_formatted < $start_date_formatted ? $start_date_formatted : $reg_date_formatted;
        $dt2 = !empty($end_date_formatted) && $end_date_formatted > $dt1 ? $end_date_formatted : '';
        
        $dt1 = new \DateTime($dt1);
        if(!empty($dt2)){
            $dt2 = new \DateTime($dt2);
        } else {
            $dt2 = new \DateTime($dt1->format('Y-m-d'));
            $dt2->add(new \DateInterval('P12M'));
        }        

        $period = new \DatePeriod($dt1, new \DateInterval('P1M'), $dt2);
        foreach($period as $dt){
            $months[] = $dt->format('m');
            $years[] = $dt->format('Y');
        }
        $months = array_map('intval', $months);
        $years = array_map('intval', $years);

        global $wpdb;
        $table = $wpdb->prefix . 'transaction';
        $qry = "SELECT t1.*, SUM(t2.item_amount) as total_paid, MIN(item_due) AS total_due, t2.item_month as item_month, t2.item_year as item_year FROM {$table} t1 LEFT JOIN {$wpdb->prefix}transaction_items t2 ON t1.id = t2.transaction_id WHERE t1.user_id = {$this->id} AND t2.item_month IN ( ". implode(',', $months) ." ) AND t2.item_year IN ( ". implode(',', array_unique($years)) ." ) GROUP BY t2.item_month, t2.item_year ORDER BY t1.id DESC";
        $rows = $wpdb->get_results( $qry, ARRAY_A );

        $payment_type = strtolower($this->getMeta('payment_type'));
        $payment_amount = (float) $this->getMeta('payment_amount');

        $formatted_data = [];
        foreach($rows as $row){
            $year = $row['item_year'];
            $month = $row['item_month'];
            $paid = (float) $row['total_paid'];
            $due = (float) $row['total_due'];

            if($paid == 0 && $due == 0 && $payment_type == 'monthly') $due = $payment_amount; 
            $formatted_data[$year][$month] = array(
                'paid' => $paid,
                'due' => $due,
            );
        }

        $response = [];
        for( $i = 0; $i < count($months); $i++ ){
            $m = $months[$i];
            $y = $years[$i];
            if($payment_type != 'monthly') $response['details'][$y][$m] = $formatted_data[$y][$m] ?? array('paid'=>0,'due'=>0);
            else $response['details'][$y][$m] = $formatted_data[$y][$m] ?? array('paid'=>0,'due'=>$payment_amount);
        }
        
        $total_paid = $total_due = 0;
        foreach($response['details'] as $y => $m){
            foreach($m as $month => $data){
                $total_paid += $data['paid'];
                $total_due += $data['due'];
            }
        }

        if($payment_type != 'monthly'){
            $total_due = $payment_amount - $total_paid;
            if($total_due < 0) $total_due = 0;
        }
        $response['type'] = $payment_type;
        $response['amount'] = $payment_amount;
        $response['total_paid'] = $total_paid;
        $response['total_due'] = $total_due;
        $response['start_date'] = $start_date_formatted;
        $response['end_date'] = $end_date_formatted;
        $response['post_id'] = $class_post_id;
        $response['user_id'] = $this->id;
        $response['query'] = $qry;
        $response['months'] = count($months);
        $response['payable'] = $payment_type == 'monthly' ? $response['months'] * $payment_amount : $payment_amount;
        return $response;
    }

    /**
     * Get a user transaction
     * 
     * @return string
     * 
     * @since 1.0
     * @access public
     * @static
     */
    public function showTransactionDetails()
    {
        $details = $this->getTransactionDetails();
        ob_start();
        ?>
        <style>
            .red{ color: red; }
        </style>
        <?php 
        if($details['type'] == 'monthly'): 
        ?>
            <div class="edupress-table-wrap" style="margin-top: 10px;">
                <table class="edupress-table compact">
                    <tbody>
                        <?php foreach($details['details'] as $y => $m): ?>
                            <tr data-year="<?php echo $y; ?>">
                                <?php foreach($m as $month => $data): ?>
                                    <?php $dt = new \DateTime($y . '-' . $month . '-01'); ?>
                                    <td data-month="<?php echo $month; ?>">
                                        <div class="cell-full"><?php echo $dt->format('M y'); ?></div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr data-year="<?php echo $y; ?>">
                                <?php foreach($m as $month => $data): ?>
                                    <td data-month="<?php echo $month; ?>">
                                        <div class="cell-half">Paid</div>
                                        <div class="cell-half">Due</div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr data-year="<?php echo $y; ?>">
                                <?php foreach($m as $month => $data): ?>
                                    <?php $highlight = $data['due'] > 0 ? 'red' : ''; ?>
                                    <td data-month="<?php echo $month; ?>">
                                        <div class="cell-half"><?php echo $data['paid'] ; ?></div> 
                                        <div class="cell-half"><span class="<?php echo $highlight; ?>"><?php echo $data['due']; ?></span></div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="edupress-table-wrap" style="margin-top: 20px;">
            <table class="edupress-table compact">
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <?php if($details['type'] == 'monthly'): ?>
                        <th>Months</th>
                        <th>Payable</th>
                    <?php endif; ?>
                    <th>Paid</th>
                    <th>Due</th>
                </tr>
                <tr>
                    <td><?php echo ucwords($details['type']); ?></td>
                    <td><?php echo $details['amount']; ?></td>
                    <?php if($details['type'] == 'monthly'): ?>
                        <td><?php echo $details['months'] ?? 0; ?></td>
                        <td><?php echo $details['payable'] ?? 0; ?></td>
                    <?php endif; ?>
                    <td><?php echo $details['total_paid'] ?? 0; ?></td>
                    <td><?php echo $details['total_due'] ?? 0; ?></td>
                </tr>
            </table>
        </div>

        <?php return ob_get_clean();
        
    }

}

User::instance();