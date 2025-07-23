<?php
namespace EduPress;

defined( 'ABSPATH' ) || die() ;

class Admin
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $admin_settings_option_name
     */
    public static $admin_settings_option_name = 'edupress_admin_settings';


    /**
     * Initialize instance
     *
     * @return self
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
     */
    public function __construct()
    {

        // Changing display settings
        add_action( 'wp_footer', [ $this, 'applyDisplayCss'] );

    }

    /**
     * Get Active Features
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getActiveFeatures()
    {

        $features = array(
            'shift' => 'Shift',
            'class' => 'Class',
            'section' => 'Section',
            'exam' => 'Exam',
            'user'  => 'User',
            'sms' => 'SMS',
            'attendance' => 'Attendance',
        );

        $active = array_filter( $features, function($v, $k){
            return EduPress::isActive($k);
//            return Admin::getSetting($k.'_active') === 'active';
        }, ARRAY_FILTER_USE_BOTH);

        $active['subject'] = 'Subject';
        $active['exam'] = 'Exam';
        $active['user'] = 'User';

        return apply_filters( 'edupress_active_features', $active );
    }

    /**
     * Get admin settings menu
     *
     * @return array
     *
     * @since 1.0
     * @acecess public
     */
    public function getSettingsMenu()
    {

        $menus = array(
            'features'       => array(
                'id'        => 'features',
                'title'     => 'Features',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'features' ),
                'active'    => 0,
            ),
            'basic'       => array(
                'id'        => 'basic',
                'title'     => 'Basic',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'basic' ),
                'active'    => 0,
            ),

            'print'         => array(
                'id'        => 'print',
                'title'     => 'Print',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'print' ),
                'active'    => 0,
            ),
            'user'          => array(
                'id'        => 'user',
                'title'     => 'User',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'user' ),
                'active'    => 0,
            ),
            
        );

        if( Admin::getSetting('exam_active') == 'active' ){
            $menus['result'] = array(
                'id'        => 'result',
                'title'     => 'Result',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'result' ),
                'active'    => 0,
            );
            $menus['exam']  = array(
                'id'        => 'exam',
                'title'     => 'Exam',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'exam' ),
                'active'    => 0,
            );
        }

        if( Admin::getSetting('transaction_active') == 'active' ){
            $menus['transaction'] = array(
                'id'        => 'transaction',
                'title'     => 'Accounting',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'transaction' ),
                'active'    => 0,
            );
        }

        if( Admin::getSetting('sms_active') == 'active' ){
            $menus['sms'] = array(
                'id'        => 'sms',
                'title'     => 'SMS',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'sms' ),
                'active'    => 0,
            );
        }

        if( Admin::getSetting('attendance_active' ) == 'active' ){
            $menus['attendance'] = array(
                'id'        => 'attendance',
                'title'     => 'Attendance',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'attendance' ),
                'active'    => 0,
            );
        }


        $menus['display'] = array(
            'id'        => 'display',
            'title'     => 'Display',
            'info'      => '',
            'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'display' ),
            'active'    => 0,
        );

        $menus['delete'] = array(
            'id'        => 'delete',
            'title'     => 'Delete Data',
            'info'      => '',
            'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'delete' ),
            'active'    => 0,
        );

        return apply_filters( 'edupress_admin_settings_menu', $menus );
    }

    /**
     * Get attendance token
     * The tokens are redefined and provided by EduPress Sync
     *
     * @return string
     *
     * @since 1.0
     * @acecess public
     */
    public static function getAttendanceToken()
    {

        $tokens = "6rCZlRhIsom8wKiDSj7BAsuPX6G3ZXwfGJbwt8ow2mISuddKxpQ7u1AfeBlIIEo4,EA4RYeUx2jZZ0V6ik72k1W8W58koTeg8mC3pbCTr1xUsVlA2d1r4iGWEpeOW8ELr,ioughS97VBDduaVo8ztFBEtBlYDWAIjkJ5pPbSxOpfWalF2IE9wAXCtcQX70KWa0";
        $tokens = explode(',', $tokens);
        $rand = rand(0, (count($tokens) - 1));
        return $tokens[$rand];

    }

    /**
     * Show settings menu
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function showSettingsMenu()
    {
        $before_html = apply_filters( 'edupress_admin_settings_before_menu_html', '' );
        $after_html = apply_filters( 'edupress_admin_settings_after_menu_html', '' );
        $menu = $this->getSettingsMenu();

        ob_start();
        ?>
        <div class="edupress-admin-settings-menu-wrap">

            <?php
                // Before Menu
                if( !empty($before_html) ) echo '<div class="edupress-admin-settings-before-menu-wrap">'.$before_html.'</div>';

                // Menu content
                if ( !empty($menu) ){
                    $menu_classes = apply_filters('edupress_admin_settings_menu_class', 'edupress-admin-settings-menu');
                    if ( is_array($menu_classes) ) $menu_classes = implode( ' ', $menu_classes );
                    ?>
                    <ul class="<?php echo $menu_classes;?>">
                        <?php
                            foreach($menu as $k=>$v){
                                $id = $v['id'] ? sanitize_text_field($v['id']) : '';
                                $title = $v['title'] ?? '';
                                $url = $v['url'] ?? '';
                                $info = $v['info'] ?? '';
                                $active = $v['active'] ? 'active' : '';
                                $activePage = isset($_REQUEST['activePage']) && $_REQUEST['activePage'] == $v['id'] ? 1 : 0;
                                $activeClass = $active || $activePage ? ' active ' : '';
                                ?>
                                    <li class="<?php echo $activeClass; ?>">
                                        <a data-menu-id="<?php echo $id; ?>" href="<?php echo $url; ?>">
                                            <?php _e($title, 'edupress'); ?>
                                        </a>
                                        <?php
                                            if( !empty($info) ){
                                                ?>
                                                <span data-info="<?php echo $info; ?>" title="<?php echo $info; ?>" class="admin-settings-menu-info">?</span>
                                                <?php
                                            }
                                        ?>
                                    </li>
                                <?php
                            }
                        ?>
                    </ul>
                    <?php
                }
            ?>

            <?php
                // After menu
                if( !empty($before_html) ) echo '<div class="edupress-admin-settings-after-menu-wrap">'.$after_html.'</div>';
            ?>
        </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Check if a menu item is valid or not
     *
     * @return boolean
     *
     * @paran string $menu
     *
     * @since 1.0
     * @access public
     */
    public function isValidSettingsMenu( $menu )
    {

        $menus = $this->getSettingsMenu();

        if ( empty( $menus ) ) return false;

        foreach( $menus as $k => $v ){

            if ( $v['id'] == $menu ) return true;

        }

        return false;

    }

    /**
     * Get settings form
     *
     * @return string
     *
     * @param string $form
     *
     * @since 1.0
     * @access public
     */
    public function getSettingsForm( $form )
    {

        $fields = $this->getSettingsFormFields($form);
        if( empty($fields) ) return '';

        ob_start();
        ?>
            <div class="edupress-admin-settings-form-wrap form-<?php echo $form; ?>">
                <form action="" method="post" class="<?php echo EduPress::getClassNames(array('edupress-admin-settings-form', 'vertical', 'form-'.$form), 'form'); ?>">
                    <?php foreach( $fields as $field ): ?>
                        <div class="form-row <?php echo $field['name'] ?? ''; ?>">
                            <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e( $field['settings']['label'] ?? '' ); ?></label></div>
                            <div class="value-wrap">
                                <?php echo EduPress::generateFormElement( $field['type'] ?? '', $field['name'] ?? '', $field['settings'] ?? [] ); ?>
                            </div>
                        </div>
                        <?php
                            if( !isset($field['settings']['id']) || empty($field['settings']['id'])) $field['settings']['id'] = uniqid();
                            if($field['type'] == 'hidden' ) :
                                echo EduPress::generateFormElement( $field['type'] ?? '', $field['name'] ?? '', $field['settings'] ?? [] );
                                continue;
                            endif;
                        ?>
                    <?php endforeach; ?>

                    <!-- Submit -->
                    <div class="form-row submit">
                        <div class="label-wrap">
                            <label for="submit"> &nbsp; </label>
                        </div>
                        <div class="value-wrap">
                            <?php
                                $disabled = !current_user_can('administrator') && $form == 'features' ? 'disabled' : '';
                                echo EduPress::generateFormElement( 'submit', 'Save', array('value'=>__('Save', 'edupress'), 'disabled'=>$disabled) );
                                echo EduPress::generateFormElement( 'hidden', 'action', array( 'value'=>'edupress_admin_ajax' ) );
                                echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value'=>'saveEduPressAdminSettingsForm' ) );
                                echo EduPress::generateFormElement( 'hidden', 'is_ajax', array( 'value'=>1 ) );
                                echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array( 'value'=> "{$form}AdminSettingsBeforeSend" ) );
                                echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value'=> "{$form}AdminSettingsSuccess" ) );
                                echo EduPress::generateFormElement( 'hidden', 'error_callback', array( 'value'=> "{$form}AdminSettingsSuccess" ) );
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
     * Get admin settings menu page content
     *
     * @return string
     * @since 1.0
     * @access public
     */
    public function getSettingsMenuContent( $menu )
    {

        if( !$this->isValidSettingsMenu($menu) ) return __('Invalid menu item', 'edupress');

        $content = apply_filters( 'edupress_admin_settings_menu_content', $this->getSettingsForm($menu), $menu );

        return "<div class='edupress-admin-settings-menu-content-wrap'><div class='edupress-admin-settings-menu-content'><div class='edupress-ajax-content-wrap'>{$content}</div></div></div>";

    }

    /**
     * Get admin setting
     *
     * @return mixed
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getSetting( $key, $default = '' )
    {

        $key = strtolower(str_replace(' ', '_', $key) );

        $option_value = maybe_unserialize( get_option( self::$admin_settings_option_name, array() ) );

        $setting = isset($option_value[$key]) && !empty($option_value[$key]) ? $option_value[$key] : $default;

        return apply_filters( 'edupress_admin_settings_'. $key, $setting );

    }

    /**
     * Get attendance device list
     *
     * @return array | string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getAttendanceDevices()
    {

        $domain = str_contains( $_SERVER['HTTP_HOST'], 'localhost' ) ? 'http://localhost/api.edupressbd.com' : 'http://api.edupressbd.com';
        $endpoint = $domain . '/wp-json/edupress_sync/v1/devices';

        $response = wp_remote_get( $endpoint );
        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return "Error: $error_message";
        }

        // Get the body of the response
        $body = wp_remote_retrieve_body($response);

        // Decode the JSON response
        $body = json_decode($body, true);

        $devices = $body['body_response']['data']['devices'];

        return array_combine( $devices, $devices );

    }


    /**
     * Update admin settings
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function updateSettings( $key, $value )
    {

        $options  = maybe_unserialize( get_option( self::$admin_settings_option_name, array() ) );

        $key = strtolower( str_replace(' ', '_', $key) );

        $options[$key] = $value;

        return update_option( self::$admin_settings_option_name, $options, 'no' );

    }

    /**
     * Checking if a link or form is ajax enabled
     *
     * @return boolean
     **
     * @since 1.0
     * @access public
     */
    public static function isAjax()
    {

        $value = self::getSetting('ajax_active');

        return $value || $value == 'active';

    }


    /**
     * Setting form fields
     *
     * @return array
     *
     * @param string $form
     *
     * @since 1.0
     * @access public
     */
    public function getSettingsFormFields($form)
    {

        $active_options = array('active' =>'Active', 'inactive' => 'Inactive' );
        $fields = [];

        switch (strtolower(trim($form))){

            case 'features':
                $fields['branch_active'] = array(
                    'type'  => 'select',
                    'name'  => 'branch_active',
                    'settings' => array(
                        'options' => array('active'=>'Active'),
                        'value' => Admin::getSetting('branch_active'),
                        'label' => 'Branch',
                        'id' => 'branch_active'
                    )
                );
                $fields['shift_active'] = array(
                    'type'  => 'select',
                    'name'  => 'shift_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('shift_active'),
                        'label' => 'Shift',
                        'id' => 'shift_active'
                    )
                );
                $fields['class_active'] = array(
                    'type'  => 'select',
                    'name'  => 'class_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('class_active'),
                        'label' => 'Class'
                    )
                );
                $fields['section_active'] = array(
                    'type'  => 'select',
                    'name'  => 'section_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('section_active'),
                        'label' => 'Section'
                    )
                );
                $fields['subject_active'] = array(
                    'type'  => 'select',
                    'name'  => 'subject_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('subject_active'),
                        'label' => 'Subject'
                    )
                );
                $fields['term_active'] = array(
                    'type'  => 'select',
                    'name'  => 'term_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('term_active'),
                        'label' => 'Term'
                    )
                );
                $fields['exam_active'] = array(
                    'type'  => 'select',
                    'name'  => 'exam_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('exam_active'),
                        'label' => 'Exam'
                    )
                );
                $fields['transaction_active'] = array(
                    'type'  => 'select',
                    'name'  => 'transaction_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('transaction_active'),
                        'label' => 'Accounting'
                    )
                );
                $fields['sms_active'] = array(
                    'type'  => 'select',
                    'name'  => 'sms_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('sms_active'),
                        'label' => 'SMS'
                    )
                );
                $fields['attendance_active'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('attendance_active'),
                        'label' => 'Attendance'
                    )
                );
                $fields['calendar_active'] = array(
                    'type'  => 'select',
                    'name'  => 'calendar_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('calendar_active'),
                        'label' => 'Academic Calendar'
                    )
                );
                $fields['notice_active'] = array(
                    'type'  => 'select',
                    'name'  => 'notice_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('notice_active'),
                        'label' => 'Notice'
                    )
                );
                $fields['print_active'] = array(
                    'type'  => 'select',
                    'name'  => 'print_active',
                    'settings' => array(
                        'options' => $active_options,
                        'value' => Admin::getSetting('print_active'),
                        'label' => 'Global Print Button'
                    )
                );
                break;
                
            case 'institute':
            case 'basic':

                $fields['system_uid'] = array(
                    'type'  => 'text',
                    'name'  => 'system_uid',
                    'settings' => array(
                        'value' => self::getSystemUid(),
                        'id' => 'system_uid',
                        'readonly' => 'readonly',
                        'label' => 'System ID',
                    )
                );

                $fields['institute_name'] = array(
                    'type'  => 'text',
                    'name'  => 'institute_name',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_name'),
                        'label' => 'Institute Name'
                    )
                );

                $fields['institute_logo'] = array(
                    'type'  => 'file',
                    'name'  => 'institute_logo',
                    'settings' => array(
                        'class' => 'wp_ajax_upload',
                        'data'  => array(
                            'data-target-name' => 'institute_logo_id',
                            'data-target-class' => 'institute_logo_container',
                            'accept' => 'image/*',
                        ),
                        'label' => 'Logo',
                        'after' => "<div class='institute_logo_container'>".wp_get_attachment_image(Admin::getSetting('institute_logo_id'), 'full')."</div>",
                    ),

                );

                $fields['institute_logo_id'] = array(
                    'type'  => 'hidden',
                    'name'  => 'institute_logo_id',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_logo_id'),
                        'class' => 'institute_logo_id'
                    )
                );

                $fields['institute_eiin'] = array(
                    'type'  => 'text',
                    'name'  => 'institute_eiin',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_eiin'),
                        'label' => 'EIIN'
                    )
                );

                $fields['institute_address'] = array(
                    'type'  => 'text',
                    'name'  => 'institute_address',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_address'),
                        'label' => 'Address'
                    )
                );

                $fields['institute_phone'] = array(
                    'type'  => 'text',
                    'name'  => 'institute_phone',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_phone'),
                        'label' => 'Phone Number'
                    )
                );

                $fields['institute_website'] = array(
                    'type'  => 'url',
                    'name'  => 'institute_website',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_website'),
                        'label' => 'Website'
                    )
                );
                $fields['institute_email'] = array(
                    'type'  => 'email',
                    'name'  => 'institute_email',
                    'settings' => array(
                        'value' => Admin::getSetting('institute_email'),
                        'label' => 'Email'
                    )
                );
                break;


            case 'result':

                $fields['result_sms_marks_details'] = array(
                    'type'  => 'select',
                    'name'  => 'result_sms_marks_details',
                    'settings' => array(
                        'value' => Admin::getSetting('result_sms_marks_details', 'inactive'),
                        'label' => 'SMS Result Marks Details',
                        'options' => array('active' => 'Active', 'inactive'=>'Inactive'),
                        'placeholder' => 'Select'
                    )
                );

                $default = "Result of {class} {year}\n{term}";
                $fields['result_title_format'] = array(
                    'type'  => 'textarea',
                    'name'  => 'result_title_format',
                    'settings' => array(
                        'value' => Admin::getSetting('result_title_format', $default),
                        'label' => 'Title Format',
                        'after' => "Allowed Keywords: <strong>{branch} {shift} {class} {section} {term} {year}</strong>",
                    )
                );
                $fields['result_title_font_size'] = array(
                    'type'  => 'select',
                    'name'  => 'result_title_font_size',
                    'settings' => array(
                        'value' => Admin::getSetting('result_title_font_size', 20),
                        'label' => 'Title Font Size',
                        'options' => range(0,100),
                    )
                );
                $fields['result_signature_box'] = array(
                    'type'  => 'select',
                    'name'  => 'result_signature_box',
                    'settings' => array(
                        'value' => Admin::getSetting('result_signature_box', 'inactive'),
                        'label' => 'Signature Box',
                        'options' => array('inactive' => 'Inactive', 'active' => 'Active'),
                    )
                );
                $fields['result_signature_box_title'] = array(
                    'type'  => 'text',
                    'name'  => 'result_signature_box_title',
                    'settings' => array(
                        'value' => Admin::getSetting('result_signature_box_title', ''),
                        'label' => 'Signature Box Title',
                        'after' => 'Keep it blank to hide box title',
                    )
                );
                $fields['result_signature_box_columns'] = array(
                    'type'  => 'textarea',
                    'name'  => 'result_signature_box_columns',
                    'settings' => array(
                        'value' => Admin::getSetting('result_signature_box_columns', ''),
                        'label' => 'Signature Box Columns',
                        'after' => 'Each line treated as a column',
                    )
                );
                $fields['result_signature_box_height'] = array(
                    'type'  => 'number',
                    'name'  => 'result_signature_box_height',
                    'settings' => array(
                        'value' => Admin::getSetting('result_signature_box_height', '0.5'),
                        'label' => 'Signature Box Height (in)',
                        'data' => array(
                            'min' => 0,
                            'max' => 5,
                            'step' => 'any',
                        )
                    )
                );
                break;

            case 'sms':

                $fields['sms_gateway'] = array(
                    'type'  => 'select',
                    'name'  => 'sms_gateway',
                    'settings' => array(
                        'options' => Sms::getGateways(),
                        'value' => Admin::getSetting('sms_gateway'),
                        'label' => 'Gateway',
                        'placeholder' => 'Select',
                        'id' => 'sms_gateway'
                    )
                );

                $fields['sms_username'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_username',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_username'),
                        'label' => 'Username',
                        'id' => 'sms_username'
                    )
                );
                $fields['sms_password'] = array(
                    'type'  => 'password',
                    'name'  => 'sms_password',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_password'),
                        'label' => 'Password',
                        'id' => 'sms_password'
                    )
                );
                $fields['sms_api_key'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_api_key',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_api_key' ),
                        'label' => 'API Key',
                        'id' => 'sms_api_key'
                    )
                );

                $fields['sms_rate'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_rate',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_rate'),
                        'label' => 'Rate',
                        'id' => 'sms_rate'
                    )
                );
                $fields['sms_footer'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_footer',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_footer'),
                        'label' => 'Footer',
                        'id' => 'sms_footer'
                    )
                );
                $fields['sms_sender'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_sender',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_sender'),
                        'label' => 'Sender',
                        'id' => 'sms_sender'
                    )
                );
                $fields['sms_balance_section'] = array(
                    'type'  => 'html',
                    'name'  => 'sms_balance_section',
                    'settings' => array(
                        'html' => '<h6>SMS Notification for Low Balance</h6>',
                    )
                );
                $fields['sms_balance_notification'] = array(
                    'type'  => 'select',
                    'name'  => 'sms_balance_notification',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_balance_notification', 'active'),
                        'label' => 'Low Balance Notification',
                        'id' => 'sms_balance_notification',
                        'options' => array('inactive' => 'Inactive', 'active'=>'Active'),
                        'placeholder' => 'Select',
                    )
                );
                $fields['sms_balance_limit'] = array(
                    'type'  => 'number',
                    'name'  => 'sms_balance_limit',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_balance_limit', ),
                        'label' => 'Balance Limit',
                        'id' => 'sms_balance_limit',
                    )
                );
                $fields['sms_balance_notification_mobile'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_balance_notification_mobile',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_balance_notification_mobile'),
                        'label' => 'Notification Mobile',
                        'id' => 'sms_low_balance_notification_mobile',
                        'after' => 'Seperated by , for multiple mobiles'
                    )
                );
                $fields['sms_balance_notification_email'] = array(
                    'type'  => 'text',
                    'name'  => 'sms_balance_notification_email',
                    'settings' => array(
                        'value' => Admin::getSetting('sms_balance_notification_email'),
                        'label' => 'Notification Email',
                        'id' => 'sms_balance_notification_email',
                        'after' => 'Seperated by , for multiple emails'
                    )
                );

                $days = [15, 30, 45, 60, 90, 365];
                $days_options = array_combine($days, $days);
                $days_options = array_map(function($day){ return $day . ' Days'; }, $days_options);
                $fields['sms_store_log'] = array(
                    'type'  => 'select',
                    'name'  => 'sms_store_log',
                    'settings' => array(
                        'options' => $days_options,
                        'label' => 'Store log for last x days',
                        'id' => 'sms_store_log',
                        'value' => Admin::getSetting('sms_store_log', 45),
                    )
                );

                break;

            case 'accounting':
            case 'transaction':

                $fields['transaction_accounts'] = array(
                    'type'  => 'textarea',
                    'name'  => 'transaction_accounts',
                    'settings' => array(
                        'value' => Admin::getSetting('transaction_accounts'),
                        'label' => 'Account Names',
                        'id'    => 'transaction_accounts',
                        'after' => 'User comma , to separate multiple values'
                    )
                );

                $accounts = explode(',', Admin::getSetting('transaction_accounts' ) );
                $accounts = array_map( 'trim', $accounts );
                if( !empty($accounts) ){
                    foreach($accounts as $account){
                        $field_name = "transaction_account_{$account}_initial_balance";
                        $fields[$field_name] = array(
                            'type' => 'number',
                            'name' => $field_name,
                            'settings' => array(
                                'value' => Admin::getSetting($field_name),
                                'label' => $account . ' Initial Balance',
                                'data' => array(
                                    'steps' => 'any'
                                ),
                                'id' => $field_name,
                            )
                        );
                    }
                }

                $fields['transaction_fee_names'] = array(
                    'type'  => 'textarea',
                    'name'  => 'transaction_fee_names',
                    'settings' => array(
                        'value' => Admin::getSetting('transaction_fee_names'),
                        'label' => 'Fee Names',
                        'id' => 'transaction_fee_names',
                        'after' => 'User comma , to separate multiple values'
                    )
                );
                $fields['transaction_online_payment'] = array(
                    'type'  => 'select',
                    'name'  => 'transaction_online_payment',
                    'settings' => array(
                        'value' => Admin::getSetting('transaction_online_payment'),
                        'label' => 'Online Payment',
                        'options' => array( 'active' => 'Active', 'inactive' => 'Inactive' ),
                        'placeholder' => 'Select',
                        'id' => 'transaction_online_payment'
                    )
                );
                $fields['transaction_currency_sign'] = array(
                    'type'  => 'text',
                    'name'  => 'transaction_currency_sign',
                    'settings' => array(
                        'value' => Admin::getSetting('transaction_currency_sign'),
                        'label' => 'Currency Sign',
                        'id' => 'transaction_currency_sign'
                    )
                );


                break;

            case 'attendance':

                $devices = self::getAttendanceDevices();
                $device_ids = Admin::getSetting('attendance_device_id');
                if(!empty($device_ids) && is_array($device_ids)){
                    $device_ids = array_map( 'intval', $device_ids );
                }
                
                $fields['attendance_device_name'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_device_name',
                    'settings' => array(
                        'value' => Admin::getSetting('attendance_device_name', ''),
                        'label' => "Attendance Device",
                        'id' => 'attendance_device_name',
                        'options' => $devices,
                        'placeholder' => ' Off ',
                    )
                );

                $fields['attendance_device_count'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_device_count',
                    'settings' => array(
                        'value' => Admin::getSetting('attendance_device_count', ''),
                        'label' => "Number of Attendance Devices",
                        'id' => 'attendance_device_count',
                        'options' => range(0,10),
                    )
                );
                $fields['register_attendance_device'] = array(
                    'type'  => 'button',
                    'name'  => 'register_attendance_device',
                    'settings' => array(
                        'value' => 'Register Attendance Device',
                        'label' => "",
                        'id' => 'register_attendance_device',
                        'class' => 'register_attendance_device',
                        'data' => [
                            'data-before_send_callback' => 'registerDevice',
                        ]
                    )
                );
                $fields['attendance_api_key'] = array(
                    'type'  => 'text',
                    'name'  => 'attendance_api_key',
                    'settings' => array(
                        'value' => Admin::getSetting('attendance_api_key', ''),
                        'label' => "Attendance API Key (<a href='#' class='getApiKey'>Generate API Key</a>)",
                        'id' => 'attendance_api_key',
                        'readonly' => 'readonly'
                    )
                );

                $fields['attendance_token'] = array(
                    'type'  => 'hidden',
                    'name'  => 'attendance_token',
                    'settings' => array(
                        'value' => self::getAttendanceToken(),
                        'id' => 'attendance_token',
                    )
                );

                $branch = new Branch();
                $branches = $branch->getPosts( [] , true );
                $device_count = Admin::getSetting('attendance_device_count', 0);
                if($device_count > 0){
                    if(!empty($device_ids) && is_array($device_ids)){
                        foreach($device_ids as $device_id){
                            $field_name = 'attendance_device_'.$device_id . '_branch_id';
                            $fields[$field_name] = array(
                                'type'  => 'select',
                                'name'  => $field_name,
                                'settings' => array(
                                    'value' => Admin::getSetting($field_name),
                                    'label' => "Device-Branch Linking (ID# {$device_id})",
                                    'id' => $field_name,
                                    'placeholder' => 'Select',
                                    'options' => $branches,
                                )
                            );
                        }
                    }
                }

                $fields['generate_attendance_ids'] = array(
                    'type'  => 'button',
                    'name'  => 'generate_attendance_ids',
                    'settings' => array(
                        'value' => 'Generate Attendance IDs',
                        'label' => "",
                        'id' => 'generate_attendance_ids',
                        'class' => 'generate_attendance_ids',
                    )
                );

                $fields['attendance_sms'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_sms',
                    'settings' => array(
                        'options' => array('active' => 'Yes', 'inactive' => 'No'),
                        'value' => Admin::getSetting('attendance_sms'),
                        'label' => 'SMS notification to guardian',
                        'placeholder' => 'Select',
                        'id' => 'attendance_sms'
                    )
                );
                $default_text = "{name} {action} {institute} ({branch}) on {time}.";
                $value = Admin::getSetting('attendance_sms_format');
                if(empty($value)) $value = $default_text;
                $fields['attendance_sms_format'] = array(
                    'type'  => 'textarea',
                    'name'  => 'attendance_sms_format',
                    'settings' => array(
                        'value' => $value,
                        'label' => 'Guardian SMS format',
                        'placeholder' => $default_text,
                        'id' => 'attendance_sms_format',
                    )
                );

                $fields['attendance_sms_to_admin'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_sms_to_admin',
                    'settings' => array(
                        'options' => array('active' => 'Yes', 'inactive' => 'No'),
                        'value' => Admin::getSetting('attendance_sms_to_admin'),
                        'label' => 'Attendance SMS to admin',
                        'placeholder' => 'Select',
                        'id' => 'attendance_sms_to_admin',
                    )
                );

                $fields['attendance_sms_to_admin_numbers'] = array(
                    'type'  => 'text',
                    'name'  => 'attendance_sms_to_admin_numbers',
                    'settings' => array(
                        'value' => Admin::getSetting('attendance_sms_to_admin_numbers'),
                        'label' => 'Attendance SMS to admin mobile numbers',
                        'id' => 'attendance_sms_to_admin_numbers',
                    )
                );
                $fields['attendance_sms_to_admin_for_roles'] = array(
                    'type'  => 'checkbox',
                    'name'  => 'attendance_sms_to_admin_for_roles',
                    'settings' => array(
                        'options' => array_combine(User::getRoles(), User::getRoles()),
                        'value' => Admin::getSetting('attendance_sms_to_admin_for_roles'),
                        'label' => 'Attendance SMS to admin for what roles?',
                        'id' => 'attendance_sms_to_admin_for_roles',
                    )
                );

                $default_text = "{name} [{role}] {action} {institute} ({branch}) at {time}.";
                $value = Admin::getSetting('attendance_sms_format_to_admin');
                if(empty($value)) $value = $default_text;

                $fields['attendance_sms_format_to_admin'] = array(
                    'type'  => 'textarea',
                    'name'  => 'attendance_sms_format_to_admin',
                    'settings' => array(
                        'value' => $value,
                        'label' => 'Admin SMS format',
                        'placeholder' => $default_text,
                        'id' => 'attendance_sms_format_to_admin',
                    )
                );

                $fields['attendance_email'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_email',
                    'settings' => array(
                        'options' => array('active' => 'Yes', 'inactive' => 'No'),
                        'value' => Admin::getSetting('attendance_email'),
                        'label' => 'Email notification',
                        'placeholder' => 'Select',
                        'id' => 'attendance_email'
                    )
                );
                $days = [15, 30, 45, 60, 90, 365];
                $days_options = array_combine($days, $days);
                $days_options = array_map(function($day){ return $day . ' Days'; }, $days_options);
                $fields['attendance_store_log'] = array(
                    'type'  => 'select',
                    'name'  => 'attendance_store_log',
                    'settings' => array(
                        'options' => $days_options,
                        'label' => 'Store log for last x days',
                        'id' => 'attendance_store_log',
                        'value' => Admin::getSetting('attendance_store_log', 45),
                    )
                );

                break;

            case 'user':
                $fields['user_welcome_sms'] = array(
                    'type' => 'select',
                    'name' => 'user_welcome_sms',
                    'settings' => array(
                        'options' => array('active' => 'Yes', 'inactive' => 'No'),
                        'label' => 'Welcome SMS',
                        'value' => Admin::getSetting('user_welcome_sms', 'inactive'),
                        'id' => 'user_welcome_sms',
                    )
                );
                $fields['user_welcome_email'] = array(
                    'type' => 'select',
                    'name' => 'user_welcome_email',
                    'settings' => array(
                        'options' => array('active' => 'Yes', 'inactive' => 'No'),
                        'label' => 'Welcome Email',
                        'value' => Admin::getSetting('user_welcome_email'),
                        'id' => 'user_welcome_email'
                    )
                );
                $fields['user_profile_custom_fields'] = array(
                    'type' => 'textarea',
                    'name' => 'user_profile_custom_fields',
                    'settings' => array(
                        'label' => 'Profile Custom Fields',
                        'value' => stripslashes(Admin::getSetting('user_profile_custom_fields')),
                        'id' => 'user_profile_custom_fields',
                        'after' => 'Use comma , to separate multiple fields'
                    )
                );
                break;

            case 'exam':

                $fields['exam_mark_heads'] = array(
                    'type'  => 'textarea',
                    'name'  => 'exam_mark_heads',
                    'settings' => array(
                        'value' => Admin::getSetting('exam_mark_heads'),
                        'label' => 'Exam Marks',
                        'required' => true,
                        'after' => 'Use comma , to separate multiple values'
                    )
                );

                $mark_heads = Admin::getSetting('exam_mark_heads' );
                $mark_heads = explode(',', $mark_heads );
                if(!empty($mark_heads)){
                    $mark_heads = array_map( 'trim', $mark_heads );

                    foreach($mark_heads as $head){
                        $name = $head . '_pass_percentage';
                        $fields[$name] = array(
                            'type'  => 'number',
                            'name'  => $name,
                            'settings' => array(
                                'value' => Admin::getSetting($name, 33),
                                'label' => $head . ' Pass Percentage',
                                'data' => array(
                                    'min' => 0,
                                    'max' => 100,
                                    'step' => 'any',
                                )
                            )
                        );
                    }
                }
                break;

            case 'print':
                $fields['print_header_elements'] = array(
                    'type'      => 'checkbox',
                    'name'      => 'print_header_elements',
                    'settings'  => array(
                        'value' => Admin::getSetting('print_header_elements'),
                        'label' => 'Header Elements',
                        'options'=> array(
                            'logo'              => 'Logo',
                            'institute_name'    => 'Institute Name',
                            'eiin'              => 'EIIN',
                            'address'           => 'Address',
                            'phone'             => 'Phone Number',
                            'website'           => 'Website',
                            'email'             => 'Email',
                        ),
                        'after' => "<input class='no-print no-view' style='display:none' type='checkbox' name='print_header_elements[]' value='demo_element' checked>",
                    )
                );

                $fields['print_logo_height'] = array(
                    'type'  => 'number',
                    'name'  => 'print_logo_height',
                    'settings' => array(
                        'value' => Admin::getSetting('print_logo_height', 0.5),
                        'class' => 'print_logo_height',
                        'label' => 'Logo Height (in)',
                        'data'  => array(
                            'min' => 0,
                            'max' => 5,
                            'step' => 'any',
                        )
                    )
                );

                $fields['print_header_height'] = array(
                    'type'      => 'number',
                    'name'      => 'print_header_height',
                    'settings'  => array(
                        'value' => Admin::getSetting('print_header_height', 1),
                        'label' => 'Header Height (in)',
                        'data'  => array(
                            'min' => 0,
                            'max' => 10,
                            'step' => 'any'
                        ),
                    )
                );
                $fields['print_footer_height'] = array(
                    'type'      => 'number',
                    'name'      => 'print_footer_height',
                    'settings'  => array(
                        'value' => Admin::getSetting('print_footer_height', 0.5),
                        'label' => 'Footer Height (in)',
                        'data'  => array(
                            'min' => 0,
                            'max' => 5,
                            'step' => 'any'
                        ),
                    )
                );
                $fields['print_qr_code'] = array(
                    'type'  => 'select',
                    'name'  => 'print_qr_code',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code'),
                        'label' => 'QR Code',
                        'options' => array(
                            'active'    => 'Active',
                            'inactive'  => 'Inactive',
                        )
                    )
                );

                $fields['print_qr_code_size'] = array(
                    'type'  => 'number',
                    'name'  => 'print_qr_code_size',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_size', 1),
                        'label' => 'QR Code Size',
                        'placeholder' => 'Select',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 100,
                            'step' => 'any',
                        )
                    )
                );

                $fields['print_qr_code_position'] = array(
                    'type'  => 'select',
                    'name'  => 'print_qr_code_position',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_position', 'topright'),
                        'label' => 'QR Code Position',
                        'placeholder' => 'Select',
                        'options' => array(
                            'topright' => 'Top Right',
                            'topleft' => 'Top Left',
                            'bottomright' => 'Bottom Right',
                            'bottomleft' => 'Bottom Left',
                        )
                    )
                );

                $fields['print_qr_code_top_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_qr_code_top_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_top_margin', 0),
                        'label' => 'QR Code Top Margin (in)',
                        'data'  => array(
                            'max'   => 10,
                            'step'  => 'any',
                        ),
                    )
                );

                $fields['print_qr_code_bottom_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_qr_code_bottom_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_bottom_margin', 0),
                        'label' => 'QR Code Bottom Margin (in)',
                        'data'  => array(
                            'max'   => 10,
                            'step'  => 'any',
                        ),
                    )
                );
                
                $fields['print_qr_code_left_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_qr_code_left_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_left_margin', 0),
                        'label' => 'QR Code Left Margin (in)',
                        'data'  => array(
                            'max'   => 10,
                            'step'  => 'any',
                        ),
                    )
                );

                $fields['print_qr_code_right_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_qr_code_right_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_right_margin', 0),
                        'label' => 'QR Code Right Margin (in)',
                        'data'  => array(
                            'max'   => 10,
                            'step'  => 'any',
                        ),
                    )
                );

                $qr_code_expiry_options = array(
                    'no' => 'DO NOT DELETE',
                    5 => '5 Mins',
                    10 => '10 Mins',
                    60 => '1 Hour',
                    1200 => '12 Hours',
                    2400 => '1 Day',
                );
                $fields['print_qr_code_expiry'] = array(
                    'type'  => 'select',
                    'name'  => 'print_qr_code_expiry',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_expiry', 'no'),
                        'label' => 'Delete QR Codes after',
                        'options' => $qr_code_expiry_options,
                        'required' => true,
                    ),
                );
                $fields['print_qr_code_text'] = array(
                    'type'  => 'text',
                    'name'  => 'print_qr_code_text',
                    'settings' => array(
                        'value' => Admin::getSetting('print_qr_code_text', 'Scan to verify online'),
                        'label' => 'Text below QR Code',
                    ),
                );

                $fonts = self::getGoogleFonts();
                $fields['print_font_family'] = array(
                    'type'  => 'select',
                    'name'  => 'print_font_family',
                    'settings' => array(
                        'value' => Admin::getSetting('print_font_family', 'Roboto'),
                        'label' => 'Font Family',
                        'options' => array_combine($fonts, $fonts),
                        'placeholder' => 'Select',
                        'required' => true,
                    ),
                );

                $fields['print_font_size'] = array(
                    'type'  => 'select',
                    'name'  => 'print_font_size',
                    'settings' => array(
                        'value' => Admin::getSetting('print_font_size', 12),
                        'label' => 'Font Size',
                        'options' => range(0,100)
                    )
                );

                $fields['print_line_height'] = array(
                    'type'  => 'select',
                    'name'  => 'print_line_height',
                    'settings' => array(
                        'value' => Admin::getSetting('print_line_height', 16),
                        'label' => 'Line Height',
                        'options' => range(0,100)
                    )
                );

                $paper_options = "A0: 33.1 × 46.8 inches, A1: 23.4 × 33.1 inches, A2: 16.5 × 23.4 inches, A3: 11.7 × 16.5 inches, A4: 8.3 × 11.7 inches, A5: 5.8 × 8.3 inches, A6: 4.1 × 5.8 inches, A7: 2.9 × 4.1 inches, A8: 2.0 × 2.9 inches, A9: 1.5 × 2.0 inches, A10: 1.0 × 1.5 inches, Letter: 8.5 × 11 inches, Legal: 8.5 × 14 inches, Tabloid: 11 × 17 inches, Ledger: 17 × 11 inches, B0: 39.4 × 55.7 inches, B1: 27.8 × 39.4 inches, B2: 19.7 × 27.8 inches, B3: 13.9 × 19.7 inches, B4: 9.8 × 13.9 inches, B5: 6.9 × 9.8 inches";
                $paper_options = explode( ',', $paper_options);
                $paper_options = array_map( 'trim', $paper_options );
                $paper_options2 = $paper_options;
                $paper_options2 = array_map(function($v){
                    $v = explode(':', $v);
                    return $v[0];
                }, $paper_options2);


                $fields['print_paper_size'] = array(
                    'type'  => 'select',
                    'name'  => 'print_paper_size',
                    'settings' => array(
                        'value' => Admin::getSetting('print_paper_size', 'A4'),
                        'label' => 'Paper Size',
                        'options' => array_combine($paper_options2, $paper_options),
                        'placeholder' => 'Select',
                    ),
                    'required' => true,
                );


                $fields['print_top_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_top_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_top_margin', 0.25),
                        'label' => 'Paper Top Margin (in)',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => true,

                    )
                );

                $fields['print_bottom_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_bottom_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_bottom_margin', 0.25),
                        'label' => 'Paper Bottom Margin (in)',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => true,
                    )
                );

                $fields['print_left_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_left_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_left_margin', 0.25),
                        'label' => 'Paper Left Margin (in)',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => true,
                    )
                );

                $fields['print_right_margin'] = array(
                    'type'  => 'number',
                    'name'  => 'print_right_margin',
                    'settings' => array(
                        'value' => Admin::getSetting('print_right_margin', 0.25),
                        'label' => 'Paper Right Margin (in)',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => true,
                    )
                );

                $fields['print_show_edupress_credits'] = array(
                    'type'  => 'select',
                    'name'  => 'print_show_edupress_credits',
                    'settings' => array(
                        'value' => Admin::getSetting('print_show_edupress_credits', 'active'),
                        'label' => 'Show EduPress Credits',
                        'options' => array(
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        )
                    )
                );

                break;

            case 'display':
                $fields['display_posts_per_page'] = array(
                    'type'  => 'number',
                    'name'  => 'display_posts_per_page',
                    'settings' => array(
                        'value' => Admin::getSetting('display_posts_per_page', 20 ),
                        'label' => 'Posts per page',
                        'data' => array(
                            'min' => 0,
                            'max'   => 500,
                        )
                    )
                );
                $fields['display_primary_color'] = array(
                    'type'  => 'number',
                    'name'  => 'display_primary_color',
                    'settings' => array(
                        'value' => Admin::getSetting('display_primary_color', 0.5),
                        'label' => 'Primary Color',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => true,
                    )
                );
                $fields['display_secondary_color'] = array(
                    'type'  => 'number',
                    'name'  => 'display_secondary_color',
                    'settings' => array(
                        'value' => Admin::getSetting('display_secondary_color', 1),
                        'label' => 'Secondary Color',
                        'data'  => array(
                            'min'   => 0,
                            'max'   => 5,
                            'step'  => 'any',
                        ),
                        'required' => false,
                    )
                );
                $fonts = self::getGoogleFonts();
                $fields['display_font_family'] = array(
                    'type'  => 'select',
                    'name'  => 'display_font_family',
                    'settings' => array(
                        'value' => Admin::getSetting('display_font_family', 'Roboto'),
                        'label' => 'Font Family',
                        'options' => array_combine($fonts, $fonts),
                    )
                );
                $fields['display_menu_font_size'] = array(
                    'type'  => 'select',
                    'name'  => 'display_menu_font_size',
                    'settings' => array(
                        'value' => Admin::getSetting('display_menu_font_size', 17),
                        'label' => 'Menu Font Size',
                        'options' => range(0,100),
                    )
                );
                $fields['display_content_font_size'] = array(
                    'type'  => 'select',
                    'name'  => 'display_content_font_size',
                    'settings' => array(
                        'value' => Admin::getSetting('display_content_font_size', 17),
                        'label' => 'Content Font Size',
                        'options' => range(0,100),
                    )
                );
                break;

            case 'delete':
                $features = self::getActiveFeatures();
                foreach($features as $k=>$v){
                    $features[$k]  = $v . "(" . Statistics::countPosts($k) . ")";
                }
                $fields['delete_data_types'] = array(
                    'type'  => 'checkbox',
                    'name'  => 'delete_data_types',
                    'settings' => array(
                        'value' => Admin::getSetting('display_content_font_size', 17),
                        'label' => 'Data Types',
                        'options' => $features,
                    )
                );
                $fields['delete_start_date'] = array(
                    'type'  => 'date',
                    'name'  => 'delete_start_date',
                    'settings' => array(
                        'value' => Admin::getSetting('delete_start_date'),
                        'label' => 'Start Date',
                    )
                );
                $fields['delete_end_date'] = array(
                    'type'  => 'date',
                    'name'  => 'delete_end_date',
                    'settings' => array(
                        'value' => Admin::getSetting('delete_end_date'),
                        'label' => 'End Date',
                    )
                );
                $fields['delete_data_stats'] = array(
                    'type'  => 'html',
                    'name'  => 'delete_data_stats',
                    'settings' => array(
                        'label' => 'Data Stats',
                        'html'  => "<div class='delete_data_stats'></div>",
                    )
                );
                $btns = EduPress::generateFormElement('button', 'delete_process_btn', array('value'=>'Process','class'=>'process_delete_data'));
                $btns .=  " &nbsp; &nbsp; " . EduPress::generateFormElement('button', 'delete_confirm_btn', array('value'=>'Confirm Delete','class'=>'delete_confirm_btn none'));
                $fields['delete_process_btn'] = array(
                    'type'  => 'html',
                    'name'  => 'delete_process_btn',
                    'settings' => array(
                        'html' => $btns,
                    )
                );
                $fields['delete_html'] = array(
                    'type'  => 'html',
                    'name'  => 'delete_html',
                    'settings' => array(
                        'html' => "<style> .form-delete .form-row.submit{ display: none; }</style>",
                    )
                );
                break;

            default:
                break;
        }

        return apply_filters( "edupress_admin_settings_form_{$form}_fields", $fields );

    }

    /**
     * Get google font name
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getGoogleFonts()
    {
        $google_fonts = "Roboto, Open Sans, Lato, Montserrat, Oswald, Source Sans Pro, Slabo 27px, Raleway, PT Sans, Merriweather, Roboto Condensed, Noto Sans, Poppins, Ubuntu, Playfair Display, Lora, Fira Sans, Nunito, Karla, Rubik, Mukta, PT Serif, Inconsolata, Anton, Quicksand, Teko, Oxygen, Exo 2, Cabin, Arimo, Dancing Script, Hind, Abel, Josefin Sans, Cairo, Nunito Sans, Fjalla One, Heebo, Asap, Varela Round, Comfortaa, Arvo, Cormorant Garamond, Yanone Kaffeesatz, Muli, Signika, Zilla Slab, Catamaran, Libre Baskerville, Amatic SC, Rokkitt, Fira Sans Condensed, Overpass, IBM Plex Sans, Abril Fatface, Ubuntu Condensed, Manrope, Frank Ruhl Libre, Assistant, Saira, Exo, Work Sans, Dosis, Tinos, Play, Abril Display, Red Hat Display, Khand, Righteous, Barlow, Cuprum, Pacifico, Patua One, Lexend Deca, Balsamiq Sans, Saira Condensed, Inder, Concert One, Rajdhani, DM Serif Display, Crete Round, Noto Serif, Alegreya Sans, Fjord One, Domine, Archivo Narrow, Viga, El Messiri, Elza Display, Chivo, Changa, Questrial, Asar, Source Code Pro, Monda, Bree Serif, Nobile, Public Sans, Andika, Zilla Slab Highlight, Scope One, Cardo, Pathway Gothic One, Fira Mono, Alata, Podkova, Rasa";
        return array_map( 'trim', explode(',', $google_fonts) );
    }

    /**
     * Save admin settings form
     *
     * @return boolean
     *
     * @param array $data
     *
     * @since 1.0
     * @access public
     */
    public function updateEduPressSettingsForm( $data = [] )
    {
        $options = maybe_unserialize( get_option( self::$admin_settings_option_name, array() ) );
        $skip_fields = array('action', 'ajax_action', 'is_ajax', 'before_send_callback', 'success_callback', 'error_callback', '_wpnonce', '_wp_http_referer');
        if( is_array($data) && !empty($data) ){

            foreach($data as $k=>$v ){

                if ( in_array( $k, $skip_fields) ) continue;
                self::updateSettings($k, $v);

            }

        }

        return 1;

    }

    /**
     * Show admin settings panel
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getSettingsPanel()
    {
        if ( !User::currentUserCan('manage', 'setting') ) return User::getCapabilityErrorMsg( 'read', 'setting' );
        $page = isset($_REQUEST['activePage']) ? sanitize_text_field($_REQUEST['activePage']) : 'features';
        $title = ucwords( $page . ' settings' );
        ob_start();
        ?>
        <div class="edupress-admin-panel-wrap">
            <div class="sidebar">
                <div class="edupress-content-box">
                    <div class="title">Settings Menu</div>
                    <div class="content"><?php echo $this->showSettingsMenu(); ?></div>
                </div>
            </div>
            <div class="main">
                <div class="edupress-content-box">
                    <div class="title"><?php echo $title; ?></div>
                    <div class="content"><?php echo $this->getSettingsMenuContent( $page ); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Apply display settings css
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function applyDisplayCss()
    {
        ob_start();
        $menu_font_size = Admin::getSetting('display_menu_font_size', 17);
        $content_font_size = Admin::getSetting('display_content_font_size', 17);
        $font_family = Admin::getSetting('display_font_family', 'Roboto');
        $font_family_escaped = str_replace(' ', '+', $font_family);
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=<?php echo $font_family_escaped; ?>:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">

        <style>
            .edupress-frontend-panel-wrap .sidebar-wrap ul.frontend-menu li a {
                font-size: <?php echo $menu_font_size; ?>px !important;
                line-height: <?php echo $menu_font_size + 10; ?>px !important;
                font-family: '<?php echo $font_family; ?>', sans-serif !important;
            }
            .edupress-frontend-panel-wrap .sidebar-wrap ul.frontend-menu li a .menu-icon-wrap{
                height: <?php echo $menu_font_size; ?>px !important;
            }
            .edupress-frontend-panel-wrap .content-wrap div,
            .edupress-frontend-panel-wrap .content-wrap p,
            .edupress-frontend-panel-wrap .content-wrap a,
            .edupress-frontend-panel-wrap .content-wrap li,
            .edupress-frontend-panel-wrap .content-wrap td,
            .edupress-frontend-panel-wrap .content-wrap th,
            .edupress-frontend-panel-wrap .content-wrap input,
            .edupress-frontend-panel-wrap .content-wrap select,
            .edupress-frontend-panel-wrap .content-wrap label,
            .edupress-frontend-panel-wrap .content-wrap option,

            .edupress-popup-overlay div,
            .edupress-popup-overlay p,
            .edupress-popup-overlay a,
            .edupress-popup-overlay li,
            .edupress-popup-overlay td,
            .edupress-popup-overlay th,
            .edupress-popup-overlay input,
            .edupress-popup-overlay select,
            .edupress-popup-overlay option,
            .edupress-popup-overlay label

            {
                font-size: <?php echo $content_font_size; ?>px !important;
                font-family: '<?php echo $font_family; ?>', sans-serif !important;
            }
        </style>
        <?php
        echo ob_get_clean();

    }

    /**
     * Get system uid
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getSystemUid()
    {
        $id = Admin::getSetting('system_uid');
        if(empty($id)){
            $id = uniqid() . time();
            Admin::updateSettings('system_uid', $id);
        }
        return $id;
    }

}

Admin::instance();