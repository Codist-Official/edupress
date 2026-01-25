<?php 
namespace EduPress;

use mikehaertl\wkhtmlto\Pdf;

defined( 'ABSPATH' ) || die();

class PrintMaterial{

    private static $_instance;

    public $post_type = 'print_material';

    public static function instance()
    {
        if( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('wp_ajax_print_material_ajax', [$this, 'process']);
        add_action('wp_ajax_nopriv_print_material_ajax', [$this, 'process']);
    }

    public function process()
    {
        if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'edupress')) wp_send_json_error(__('Invalid nonce', 'edupress'));
        $action = $_REQUEST['ajax_action'] ?? '';
        if(empty($action)) wp_send_json_error(__('Action is required', 'edupress'));
        if(!method_exists($this, $action)) wp_send_json_error(__('Invalid action', 'edupress'));
        $data = $this->$action($_REQUEST);
        wp_send_json_success($data);
    }

    public static function getAvailableIdCardTemplates()
    {
        $key = 'id_card_template_';
        global $wpdb; 
        $qry = "SELECT * FROM {$wpdb->options} WHERE option_name LIKE '{$key}%'";
        $results = $wpdb->get_results($qry, ARRAY_A);
        $options = [];
        if(!empty($results)){
            foreach($results as $result){
                $value = maybe_unserialize($result['option_value']);
                $options[$value['template_id']] = !empty($value['template_name']) ? __($value['template_name'], 'edupress') : __('Untitled', 'edupress');
            }
        }
        return $options;
    }

    public static function getItemsMenu()
    {
        $activePage = sanitize_text_field($_REQUEST['activePage'] ?? '');
        $menus = array(
            'user_list'       => array(
                'id'        => 'user_list',
                'title'     => 'User List',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'user_list' ),
                'active'    => $activePage == 'user_list' ? 1 : 0,
            ),
            'admit_card'       => array(
                'id'        => 'admit_card',
                'title'     => 'Admit Card',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'admit_card' ),
                'active'    => $activePage == 'admit_card' ? 1 : 0,
            ),
            'id_card_design'       => array(
                'id'        => 'id_card_design',
                'title'     => 'ID Card Design',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'id_card_design' ),
                'active'    => $activePage == 'id_card_design' ? 1 : 0,
            ),
            'id_card_print' => array(
                'id'        => 'id_card_print',
                'title'     => 'ID Card Print',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'id_card_print' ),
                'active'    => $activePage == 'id_card_print' ? 1 : 0,
            ),
            'certificate'       => array(
                'id'        => 'certificate',
                'title'     => 'Certificate',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'certificate' ),
                'active'    => $activePage == 'certificate' ? 1 : 0,
            ),
        );
        return $menus;
    }

    /**
     * Show id card print options 
     * 
     * @return string 
     * 
     * @since 1.5
     */
    public static function showIdCardPrintOptions()
    {
        $tempates = self::getAvailableIdCardTemplates();
        $class_options = [];
        $section_active = EduPress::isActive('section');
        $class_active = EduPress::isActive('class');
        if($section_active){
            $section = new Section();
            $all_sections = $section->getPosts([], true);
            var_dump($all_sections);
            foreach($all_sections as $k=>$v){
                $class_options[$k] = $v;
            }
        } else {
            $class = new Klass();
            $all_classes = $class->getPosts([], true);
            foreach($all_classes as $k=>$v){
                $class_options[$k] = $v;
            }
        }
        $template_options = [];
        foreach($tempates as $k=>$v){
            $template_options[$k] = $v;
        }
        // $template_element = EduPress::generateFormElement('select', 'template_id', ['options' => $template_options, 'placeholder' => __('Select a Template', 'edupress')]);
        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table">
                <thead>
                    <tr>
                        <th><?php _e('Branch', 'edupress'); ?></th>
                        <th><?php _e('Class', 'edupress'); ?></th>
                        <?php if($section_active): ?>
                            <th><?php _e('Section', 'edupress'); ?></th>
                        <?php endif; ?>
                        <th><?php _e('Template', 'edupress'); ?></th>
                        <th><?php _e('Actions', 'edupress'); ?></th>
                    </tr>
                </thead>
                <?php if(!empty($class_options)): ?>
                    <tbody>
                        <?php foreach($class_options as $k=>$v): 
                            $class_title = $section_active ? get_the_title(get_post_meta($k, 'class_id', true)) : $v;
                            $section_title = $section_active ? $v : '';
                            ?>
                            <tr>
                                <td><?php echo get_the_title(get_post_meta($k, 'branch_id', true)); ?></td>
                                <td><?php echo $class_title; ?></td>
                                <?php if($section_active): ?>
                                    <td><?php echo $section_title; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php echo EduPress::generateFormElement('select', 'template_id_'.$k, ['options' => $template_options, 'id' => 'template_id_'.$k, 'placeholder' => __('Select a Template', 'edupress')]); ?>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" data-before_send_callback='checkTemplateIdBeforePdfDownload' data-id="<?php echo $k;?>" data-action="print_material_ajax" data-ajax_action="downloadIdCardPdf" class="edupress-download-id-card-pdf print-id-card-btn"><?php _e('Download', 'edupress'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
            </tbody>
        </div>
        <?php
        return ob_get_clean();
    }



    public static function getPanels()
    {
        if(!current_user_can('manage_options') && !User::currentUserCan('read','people')) return __('Only admin is authorized to see content!', 'edupressbd');
        $activePage = sanitize_text_field($_REQUEST['activePage'] ?? '');
        $title = '';
        $content = '';
        if($activePage == 'user_list'){
            $title = 'Print User List';
            $content = self::showUserListPrintOptions();
        } else if($activePage == 'admit_card'){
            $title = 'Print Admit Card';
            $content = self::showAdmitCardPrintOptions();
        } else if($activePage == 'id_card_design'){
            $title = 'Design ID Card';
            $content = self::idCardEditor();
        } else if ($activePage == 'id_card_print'){
            $title = 'Print ID Card';
            $content = self::showIdCardPrintOptions();
        } else if($activePage == 'certificate'){
            $title = 'Print Certificate';
            $content = self::showCertificatePrintOptions();
        }

        ob_start();
        ?>
        <div class="edupress-admin-panel-wrap">
            <div class="sidebar">
                <div class="edupress-content-box">
                    <div class="title">Items</div>
                    <div class="content">
                        <div class="edupress-admin-settings-menu-wrap">
                            <ul class="edupress-admin-settings-menu">
                                <?php 
                                    foreach( self::getItemsMenu() as $menu ): 
                                        $activeClass = $activePage == $menu['id'] ? ' active ' : '';
                                    ?>
                                    <li class="<?php echo $activeClass; ?>"><a href="<?php echo $menu['url']; ?>"><?php echo $menu['title']; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main">
                <div class="edupress-content-box">
                    <div class="title"><?php echo $title; ?></div>
                    <div class="content"><?php echo $content; ?></div>
                </div>
            </div>
        </div>
        <?php 
        return ob_get_clean();
        
    }

    /**
     * Show user list print options 
     * 
     * @return string 
     * 
     * @since 1.5
     * @access public 
     * @static
     */
    public static function showUserListPrintOptions()
    {
        $activePage = sanitize_text_field($_REQUEST['activePage'] ?? '');
        $fields = [];
        $fields['user_type'] = array(
            'type'          => 'select',
            'name'          => 'user_type',
            'settings'      => array(
                'options'   => array(
                    'student' => 'Student',
                    'teacher' => 'Teacher',
                    'manager' => 'Manager',
                    'accountant' => 'Accountant',
                    'alumni' => 'Alumni',
                    'parent' => 'Parent'
                ),
                'required'  => true,
                'label'     => 'User Type',
                'placeholder'=>'Select a User Type',
            )
        );

        if ( EduPress::isActive('branch') ){
            $branch = new Branch();
            $branch_options = $branch->getPosts( array('orderby'=>'title','order'=>'ASC'), true );
            $fields['branch_id'] = array(
                'type'          => 'select',
                'name'          => 'branch_id',
                'settings'      => array(
                    'options'   => $branch_options,
                    'required'  => true,
                    'label'     => 'Branch',
                    'placeholder'=> 'Select a Branch',
                )
            );
        }

        if ( EduPress::isActive('shift') ){
            $shift = new Shift();
            $shift_options = $shift->getPosts([], true);
            $fields['shift_id'] = array(
                'type'          => 'select',
                'name'          => 'shift_id',
                'settings'      => array(
                    'options'   => $shift_options,
                    'required'  => false,
                    'label'     => 'Shift',
                    'placeholder'=>'Select a Shift',
                )
            );
        }

        if ( EduPress::isActive('class') ){
            $class = new Klass();
            $class_options = $class->getPosts( [], true );
            $fields['class_id'] = array(
                'type'          => 'select',
                'name'          => 'class_id',
                'settings'      => array(
                    'options'   => $class_options,
                    'required'  => false,
                    'label'     => 'Class',
                    'placeholder'=>'Select a Class',
                )
            );
        }

        if ( EduPress::isActive('section') ){
            $section = new Section();
            $section_options = $section->getPosts( [], true );
            $fields['section_id'] = array(
                'type'          => 'select',
                'name'          => 'section_id',
                'settings'      => array(
                    'options'   => $section_options,
                    'required'  => false,
                    'label'     => 'Section',
                    'placeholder'=>'Select a Section',
                )
            );
        }

        $column_options = array(
            'email' => 'Email',
            'mobile' => 'Mobile',
            'branch_id' => 'Branch',
            'shift_id' => 'Shift',
            'class_id' => 'Class',
            'section_id' => 'Section',
            'payment_type' => 'Payment Type',
            'payment_amount' => 'Payment Amount'
        );
        if(!EduPress::isActive('shift')) unset($column_options['shift_id']);
        if(!EduPress::isActive('section')) unset($column_options['section_id']);

        $fields['columns'] = array(
            'type' => 'select',
            'name' => 'columns[]',
            'settings' => array(
                'label' => 'Columns',
                'options' => $column_options,
                'multiple' => true,
            )
        ); 

        $fields['extra_columns'] = array(
            'type' => 'textarea',
            'name' => 'extra_columns',
            'settings' => array(
                'label' => 'Extra Columns <br>(One item each line)',
                'placeholder' => 'Extra Columns',
            )
        ); 

        $fields['action'] = array(
            'type'      => 'hidden',
            'name'      => 'action',
            'settings'  => array(
                'value'     => 'edupress_admin_ajax',
            )
        );
        $fields['ajax_action'] = array(
            'type'      => 'hidden',
            'name'      => 'ajax_action',
            'settings'  => array(
                'value'     => 'printUserList',
            )
        );
        $fields['_wpnonce'] = array(
            'type'      => 'hidden',
            'name'      => '_wpnonce',
            'settings'  => array(
                'value'     => wp_create_nonce('edupress'),
            )
        );
        $fields['before_send_callback'] = array(
            'type'      => 'hidden',
            'name'      => 'before_send_callback',
            'settings'  => array(
                'value'     => 'printUserListBeforeSend',
            )
        );
        $fields['success_callback'] = array(
            'type'      => 'hidden',
            'name'      => 'success_callback',
            'settings'  => array(
                'value'     => 'printUserListAfterSuccess',
            )
        );


        ob_start();
        ?>
        <style>
            [data-name='roll'],
            [data-name='class_id'],
            [data-name='section_id'],
            [data-name='shift_id'],
            [data-name='branch_id'] {
                display: none;
            }
        </style>
        <script>
            jQuery(document).ready(function(){
                jQuery(document).on('change', '[name="user_type"]', function(){
                    let userType = jQuery("[name='user_type']").val();
                    if( userType == 'student' ){
                        jQuery("[data-name='branch_id'], [data-name='shift_id'], [data-name='class_id'], [data-name='section_id'] ").show();
                    } else {
                        jQuery("[data-name='branch_id'], [data-name='shift_id'], [data-name='class_id'], [data-name='section_id'] ").hide();
                    }
                })
            })
        </script>
        <div class="edupress-filter-list-wrap" style="margin-bottom: 0px;">
            <form action="" method="POST" class="edupress-form edupress-ajax edupress-filter-list">

                <?php
                    $hidden_fields = [];
                    foreach ($fields as $field) {

                        if( $field['type'] === 'submit' ) continue;
                        if( $field['type'] === 'hidden' ) {
                            $hidden_fields[] = EduPress::generateFormElement($field['type'], $field['name'], $field['settings']);
                            continue;
                        }

                    ?>
                    <div class="form-column <?php echo $field['settings']['class'] ?? ''; ?>" data-name="<?php echo $field['name'] ?? ''; ?>">
                        <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e($field['settings']['label'] ?? '', 'edupress'); ?></label></div>
                        <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                    </div>
                <?php } ?>

                <div class="form-column" data-name="submit">
                    <div class="label-wrap"> &nbsp; </div>
                    <div class="value-wrap">
                        <?php
                            echo implode( ' ', $hidden_fields ) ;
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>'Print List'));
                            echo EduPress::generateFormElement( 'hidden', 'panel', array('value'=>'printMaterials'));
                            echo EduPress::generateFormElement( 'hidden', 'activePage', array('value'=>$activePage));
                        ?>
                    </div>
                </div>

            </form>
        </div>
        <?php 
        return ob_get_clean();
    }

    /**
     * Show admit card print options 
     * 
     * @return string 
     * 
     * @since 1.5
     * @access public 
     * @static
     */ 
    public static function showAdmitCardPrintOptions()
    {
        return '';
    }

    /**
     * Show certificate print options 
     * 
     * @return string 
     * 
     * @since 1.5
     * @access public 
     * @static
     */
    public static function showCertificatePrintOptions()
    {
        return '';
    }

    /**
     * Print bulk ID Card 
     * 
     * @return string 
     * 
     * @since 1.5
     * @access public 
     * @static
     */
    public static function printBulkIdCard( $args = [] )
    {
        $print_type = $args['print_type'] ?? '';
        $roll = $args['roll'] ?? '';
        $class_id = $args['class_id'] ?? 0;
        $section_id = $args['section_id'] ?? 0;
        $branch_id = $args['branch_id'] ?? 0;
        $shift_id = $args['shift_id'] ?? 0;

        $html = '';
        $filename = 'id-card-bulk-print-' . time() . '.pdf';
        $target_dir = WP_CONTENT_DIR . '/uploads/edupress/pdf/';
        // set mpdf page size 
        // page size be same as card width 2.25in and height 3.4in
        $options = [
            'mode' => 'utf-8',
            'format' => [57.15, 86.36], // 2.25in x 3.4in
            'orientation' => 'P', // portrait
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ];

        $html = self::getBulkIdCardHtml( $args );
        $filename = self::saveAsPdf( $html, $filename, $target_dir, $options );
        return $filename;
    }

    /**
     * Get bulk id card html 
     * 
     * @return string 
     * 
     * @since 1.0
     * @access public 
     * @static 
     */
    public static function getBulkIdCardHtml( $args = [] )
    {
        $print_type = $args['print_type'] ?? '';
        $roll = $args['roll'] ?? '';
        $class_id = $args['class_id'] ?? 0;
        $section_id = $args['section_id'] ?? 0;
        $template_id = 1;

        $users = [];
        if($print_type == 'class_wise'){

            if(!empty($section_id)){
                $users = User::getAll( [
                    'section_id' => $section_id,
                ] );
            } else if(!empty($class_id)){
                $users = User::getAll( [
                    'class_id' => $class_id,
                ] );
            }

        } else if ( $print_type == ''){

        } else if ($print_type == 'teachers') {

        } else if($print_type == 'managers'){

        }

        $logo_id = Admin::getSetting('institute_logo_id');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;

        $data = array(
            'institute' => array(
                'title' => Admin::getSetting('institute_name'),
                'address' => Admin::getSetting('institute_address'),
                'phone' => Admin::getSetting('institute_phone'),
                'email' => Admin::getSetting('institute_email'),
                'website' => Admin::getSetting('institute_website'),
                'logo' => $logo_url,
            ),
            'person' => array(
                'name' => null,
                'roll' => null,
                'class' => null,
                'section' => null,
                'shift' => null,
                'branch' => null,
                'age' => null,
                'gender' => null,
                'photo' => null,
                'role' => null,
                'mobile' => null,
            )
        );


        ob_start();
        if(!empty($users)){
            foreach($users as $user){
                $user_data = self::getUserIdCardData($user, $data['institute']);
                echo self::getIdCardHtml($user_data);
            }
        }
        
        $html = ob_get_clean();
        // return $html;
        $options = [
            'mode' => 'utf-8',
            'format' => [57.15, 86.36], // 2.25in x 3.4in
            'orientation' => 'P', // portrait
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ];
        return self::saveAsPdf($html, 'id-card-' . time() . '.pdf', WP_CONTENT_DIR . '/uploads/edupress/pdf/', $options);
    }

    /**
     * Get user card data 
     * 
     * @return array 
     * 
     * @since 1.5
     * @access public
     * @static
     */
    public static function getUserIdCardData($user, $institute=[])
    {
        $userdata = get_metadata('user', $user->ID);
        $data = [];
        $data['name'] = $userdata['first_name'][0] . ' ' . $userdata['last_name'][0];
        $data['roll'] = $userdata['roll'][0];
        $data['class'] = $userdata['class_id'][0] ? get_the_title($userdata['class_id'][0]) : null;
        $data['section'] = $userdata['section_id'][0] ? get_the_title($userdata['section_id'][0]) : null;
        $data['shift'] = $userdata['shift_id'][0] ? get_the_title($userdata['shift_id'][0]) : null;
        $data['branch'] = $userdata['branch_id'][0] ? get_the_title($userdata['branch_id'][0]) : null;
        $data['photo'] = $userdata['photo'][0] ? wp_get_attachment_image_url($userdata['photo'][0], 'full') : null;
        if(empty($data['photo'])) $data['photo'] = $institute['logo'];
        $data['photo'] = $institute['logo'];
        $data['role'] = ucwords(reset($user->roles));
        $data['mobile'] = $userdata['mobile'][0];
        $data['email'] = $userdata['email'][0];
        $data['address'] = $userdata['address'][0];
        return array(
            'institute' => $institute,
            'person' => $data,
        );
    }

    /**
     * Get id card html 
     * 
     * @return string 
     * @since 1.5
     * @access public
     * @static
     */
    public static function getIdCardHtml($data = [])
    {
        $template_id = $data['template_id'] ?? 1;
        ob_start();
        include EDUPRESS_ID_TEMPLATES_DIR . '/' . $template_id . '.php';
        return ob_get_clean();
    }

    public function saveIdCardTemplate($data=[])
    {
        if(empty($data)) $data = $_REQUEST;
        unset($data['action']);
        unset($data['ajax_action']);
        unset($data['_wpnonce']);
        $template_id = $data['template_id'] ?? null;
        $key = 'id_card_template_' . $template_id;
        // convert quotes and special characters to html entities
        // convert single quote to &#39;
        $data = array_map(function($item){
            return is_string($item) ? str_replace("'", '&#39;', $item) : $item;
        }, $data);
        $update = update_option($key, $data, 'no');
        return ['status' => $update, 'data' => $update ? 'Successfully saved' : 'Failed to save'];
    }


    /**
     * Save as pdf 
     * 
     * @return string 
     * 
     * @since 1.5
     * @access public 
     * @static 
     */
    public static function saveAsPdf( $html = '', $filename = '', $target_dir = '', $options = [] )
    {
        if(empty($target_dir)) $target_dir = WP_CONTENT_DIR . '/uploads/edupress/pdf/';
        if( !is_dir($target_dir) ) mkdir($target_dir, 0777, true);
        if(empty($filename)) $filename = 'edupress-pdf-' . time() . '.pdf';

        // include_once EDUPRESS_LIB_DIR . 'mpdf/vendor/autoload.php';
        // $pdf = new \Mpdf\Mpdf($options);
        // $pdf->WriteHTML($html);
        // $pdf->Output($target_dir . $filename);
        // $file = $target_dir . $filename;
        
        // return str_replace( WP_CONTENT_DIR, site_url() . '/wp-content/', $file );
                // Set path to wkhtmltopdf
                
        require_once EDUPRESS_LIB_DIR .'/wkhtmltopdf/autoload.php';
        $pdf = new Pdf([
            'binary' => '/usr/local/bin/wkhtmltopdf', // macOS (Intel)
            'encoding' => 'UTF-8',
            'page-width'  => '54mm',
            'page-height' => '86mm',
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,
            'disable-smart-shrinking',
            'print-media-type',
        ]);

        // Add HTML as page
        $pdf->addPage($html);

        // Save to file
        if (!$pdf->saveAs(__DIR__.'/output.pdf')) {
            echo $pdf->getError();
        } else {
            echo "PDF saved successfully!";
        }
    }

    /**
     * Print user list 
     * 
     * @return string 
     * @since 1.0
     * @access public
     */
    public static function printUserList( $data = [] )
    {
        $user_type = $data['user_type'] ?? '';
        $branch_id = (int) $data['branch_id'] ?? 0;
        $shift_id = (int) $data['shift_id'] ?? 0;
        $class_id = (int) $data['class_id'] ?? 0;
        $section_id = (int) $data['section_id'] ?? 0;

        $columns = $data['columns'] ?? [];
        $extra_columns = $data['extra_columns'] ?? '';
        $extra_columns = explode("\n", $extra_columns);
        $extra_columns = array_map('trim', $extra_columns);
        $extra_columns = array_filter($extra_columns);
        $extra_columns = array_values($extra_columns);

        $args = [];
        if(!empty($user_type)) $args['role'] = $user_type;
        if(!empty($branch_id)) $args['branch_id'] = $branch_id;
        if(!empty($shift_id)) $args['shift_id'] = $shift_id;
        if(!empty($class_id)) $args['class_id'] = $class_id;
        if(!empty($section_id)) $args['section_id'] = $section_id;
        if($user_type == 'student') {
            $args['orderby'] = 'roll';
            $args['order'] = 'ASC';
        }
        $users = User::getAll( $args );
        if(empty($users)) return '';
        ?>
        <style>
            @media print{
                .print-table{
                    width: 100%;
                    border-collapse: collapse;
                }
                .print-table th, .print-table td{
                    border: 1px solid #000;
                    padding: 5px;
                    text-align: left !important;
                }
            }
        </style>
        <div class="">
            <div style='text-align: center; margin-bottom: 20px;'>
                <h2 style='margin-bottom: 0;'><?php echo ucwords(strtolower($user_type)); ?> List</h2>
                <p style='margin-top: 0;'><?php echo Admin::getSetting('institute_name'); ?></p>
            </div>
            <table class="print-table">
                <thead>
                    <tr>
                        <?php if($user_type == 'student'): ?>
                            <th>Roll</th>
                        <?php endif; ?>
                        <th>Name</th>
                        <?php foreach($columns as $column): ?>
                            <?php 
                                $column_name = str_replace('_id', '', $column);
                                $column_name = str_replace('_', ' ', $column_name);
                            ?>
                            <th><?php echo ucwords(strtolower($column_name)); ?></th>
                        <?php endforeach; ?>
                        <?php foreach($extra_columns as $column): ?>
                            <th><?php echo $column; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <?php if($user_type == 'student'): ?>
                                <td><?php echo get_user_meta($user->ID, 'roll', true); ?></td>
                            <?php endif; ?>
                            <td><?php echo get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true); ?></td>
                            <?php foreach($columns as $column): ?>
                                <td>
                                    <?php
                                        if(in_array($column, ['branch_id', 'shift_id', 'class_id', 'section_id'])){
                                            $id = get_user_meta($user->ID, $column, true);
                                            $title = $id ? get_the_title($id) : null;
                                            echo $title;
                                        } else if($column == 'email') {
                                            echo $user->user_email;
                                        } else {
                                            echo get_user_meta($user->ID, $column, true);
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <?php foreach($extra_columns as $column): ?>
                                <td><?php echo get_user_meta($user->ID, $column, true); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function rgbaToBgOpacity($css) {
        return preg_replace_callback(
            '/background-color\s*:\s*rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9.]+)\s*\)\s*;/i',
            function ($matches) {
                $r = $matches[1];
                $g = $matches[2];
                $b = $matches[3];
                $a = $matches[4];
    
                return "background-color: rgb($r, $g, $b); opacity: $a;";
            },
            $css
        );
    }
    

    public function downloadIdCardPdf()
    {
        $id = (int) $_REQUEST['id'] ?? 0;
        $template_id = $_REQUEST['template_id'] ?? 0;
        if(empty($id) || empty($template_id)) return ['status' => 0, 'data' => 'Invalid request'];
        $section_active = EduPress::isActive('section');
        $o = $section_active ? new Section($id) : new Klass($id);
        $conds = [];
        if($section_active){
            $conds['section_id'] = $id;
        } else {
            $conds['class_id'] = $id;
        }
        $users = User::getAll($conds);
        $template = maybe_unserialize(get_option('id_card_template_' . $template_id));
        $data = $template['data'] ?? [];
        ob_start();
        if(!empty($users)){
            $editor_width = $template['settings']['editor_width'] ?? 2.125;
            $editor_height = $template['settings']['editor_height'] ?? 3.370;
            $editor_bg_color = $template['settings']['editor_bg_color'] ?? '#ffffff';
            $editor_bg = $template['settings']['editor_bg'] ?? null;
            $global_font = $template['settings']['font_family'] ?? 'Arial';
            $template_name = $template['template_name'] ?? '';
            foreach($users as $user){
                ?>
                <div style="position:relative; background-image: url('<?php echo EDUPRESS_IMG_URL; ?>front-bg.png'); background-size:cover; width: <?php echo $editor_width; ?>in; height: <?php echo $editor_height; ?>in; background-color: <?php echo $editor_bg_color; ?>;">
                    <?php 
                        if(!empty($data)){
                            foreach($data as $k=>$v){
                                $text_styles = $v['textStyles'] ?? '';
                                // skip backslash in text
                                $styles = $v['styles'];
                                // $pattern = '/background-color\s*:\s*rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9.]+)\s*\)\s*;?/i';
                                // $replacement = 'background-color: rgb($1, $2, $3); opacity: $4;';
                                // $styles = preg_replace($pattern, $replacement, $styles);

                                ?>
                                <div style="position:absolute; <?php echo $styles . " " . $text_styles; ?>"><?php echo $v['text']; ?></div>
                                <?php 
                            }
                        }
                    ?>
                </div>
                <div style="clear: both; page-break-after: always;"></div>
                <?php 
            }
        }
        $html = ob_get_clean();
        // $editor_width_mm = $editor_width * 25.4;
        // $editor_height_mm = $editor_height * 25.4;
        // $options = [
        //     'mode' => 'utf-8',
        //     'format' => [$editor_width_mm, $editor_height_mm],
        //     'orientation' => 'P', // portrait
        //     'margin_left' => 0,
        //     'margin_right' => 0,
        //     'margin_top' => 0,
        //     'margin_bottom' => 0,
        // ];
        // $pdf = self::saveAsPdf($html, 'id-card-'.$id.'.pdf', '', $options);
        // return ['status' => 1, 'data' => $html, 'pdf'=>$pdf];
        $pdf = new Pdf([
            // 'binary' => '/usr/bin/wkhtmltopdf',
            // 'enable-local-file-access',
            // 'disable-smart-shrinking',
            // 'print-media-type',
            // 'dpi' => 300
        ]);
        $pdf->addPage($html);
        $pdf->saveAs(WP_CONTENT_DIR.'/uploads/card.pdf');
        return ['status' => 1, 'data' => $html, 'pdf'=>WP_CONTENT_DIR.'/uploads/card.pdf', 'dir' => WP_CONTENT_DIR];
    }

    public static function idCardEditor()
    {
        ob_start();
        ?>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            
            <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
            
            <style>
                /* body #global-wrap div { font-family: inherit !important; } */
                #template_id{ margin-bottom: 20px; }
                .draggable-field{display:inline-block;background:#eee;border:1px solid #ccc;padding:6px;margin:5px;cursor:move}
                #editor{border:2px dashed #aaa;position:relative;background-size:cover;background-position:center}
                .editor-item{position:absolute;padding:6px;background:rgba(255,255,255,.9);border:1px solid #ccc;cursor:move;min-width:40px;min-height:20px}
                .editor-text{pointer-events:none}
                .delete-btn{position:absolute;top:-8px;right:-8px;background:red;color:#fff;width:16px;height:16px;border-radius:50%;font-size:11px;text-align:center;cursor:pointer;z-index:10}
                #style-panel,#global-settings{border:1px solid #ccc;padding:10px;background:#f9f9f9}
                #style-panel{position:fixed;right:20px;top:60px;width:280px;z-index:1000}
                label{display:block;font-weight:bold;margin-top:6px}
                input,select,textarea{width:100%}
                .close-btn{float:right;background:red;color:white;border:none;cursor:pointer}
                .inline button{width:48%}
                textarea{resize:vertical}
                #global-settings{ width: 100%; height: auto; display: inline-block; }
                #global-settings input,
                #global-settings select{ height: 25px; padding: 0 5px; line-height: 1;}
                #style-panel label{font-size: 12px !important;font-weight: normal !important;}
                label[for='template-name']{font-weight: 500; margin-top: 10px;}
                #template-name,
                #style-panel input,
                #style-panel button{ height: 20px; padding: 3px; line-height: 1; font-size: 12px; }
                #style-panel textarea {height: 50px; font-size: 12px; padding: 5px;}
                .global-settings-field{ display: inline-block; width: 50%; float:left; padding-right: 10px; box-sizing: border-box;}
                .global-settings-field label{font-weight: 500;}

            </style>

        <div id="global-wrap" style="width: 100%; max-width: 600px; padding: 20px; box-sizing: border-box;">
        <h5>Advanced Drag & Drop Editor</h5>

        <?php 
            $availble_templates = self::getAvailableIdCardTemplates();
            $template_id = sanitize_text_field($_REQUEST['template_id'] ?? uniqid());
            echo EduPress::generateFormElement('select', 
                'template_id',
                [
                    'options' => $availble_templates, 
                    'name' => 'template_id', 
                    'id' => 'template_id', 
                    'value' => $template_id,
                    'label' => 'Select Template',
                    'placeholder' => __('Select Template')
                ]
            );
            $global_font = $editor_width = $editor_height = $editor_bg_color = $editor_bg = '';
            $value = [];
            if(!empty($template_id)){
                $key = 'id_card_template_' . $template_id;
                $value = maybe_unserialize(get_option($key));
                $settings = $value['settings'] ?? [];
                $global_font = !empty($settings['font_family']) ? $settings['font_family'] : 'Arial';
                $editor_width = !empty($settings['editor_width']) ? $settings['editor_width'] : 2.125;
                $editor_height = !empty($settings['editor_height']) ? $settings['editor_height'] : 3.370;
                $editor_bg_color = !empty($settings['editor_bg_color']) ? $settings['editor_bg_color'] : '#ffffff';
                $editor_bg = !empty($settings['editor_bg']) ? $settings['editor_bg'] : null;
            }
            $template_name = $value['template_name'] ?? '';
            $fonts = ['Arial', 'Georgia', 'Times New Roman', 'Courier New'];
            $fonts = array_combine($fonts, $fonts);
        ?>

        <div id="global-settings">
            <div><strong>Global Settings</strong> RFID card size: 3.370in x 2.125in</div>
            <div class="global-settings-field">
                <label for="global-font">Font Family</label>
                <?php 
                    echo EduPress::generateFormElement('select', 'global-font', [
                        'options' => $fonts,
                        'name' => 'global-font',
                        'id' => 'global-font',
                        'value' => $global_font,
                        'label' => 'Font Family',
                        'placeholder' => __('Select Font Family')
                    ]);
                ?>
            </div>

            <div class="global-settings-field">
                <label for="editor-bg">Editor Background Color</label>
                <input type="color" id="editor-bg" value="<?php echo !empty($editor_bg_color) ? $editor_bg_color : '#ffffff'; ?>">
            </div>

            <div class="global-settings-field">
                <label for="editor-w">Editor Width (inches)</label>
                <input name="editor-w" type="number" id="editor-w" value="<?php echo !empty($editor_width) ? $editor_width : 2.125; ?>" step="0.1">
            </div>

            <div class="global-settings-field">
                <label for="editor-h">Editor Height (inches)</label>
                <input name="editor-h" type="number" id="editor-h" value="<?php echo !empty($editor_height) ? $editor_height : 3.370; ?>"  step="0.1">
            </div>

            <div class="global-settings-field">
                <label for="bg-upload">Background Image</label>
                <input type="file" id="bg-upload" accept="image/*">
            </div>
        </div>

        <br>

        <div>
            <div class="draggable-field" data-content="Sample Text">Sample Text</div>
        </div>

        <br>
        <div id="editor" style="background-image: url('<?php echo !empty($editor_bg) ? $editor_bg : ''; ?>'); width: <?php echo !empty($editor_width) ? $editor_width : 2.125; ?>in; height: <?php echo !empty($editor_height) ? $editor_height : 3.370; ?>in; background-color: <?php echo !empty($editor_bg_color) ? $editor_bg_color : '#ffffff'; ?>;"></div>

        <div>
            <label for="template-name">Template Name</label>
            <input type="text" name="template-name" required aria-required="required" id="template-name" value="<?php echo $template_name; ?>">
        </div>
        <input type="hidden" name="template-id" id="template-id" value="<?php echo $template_id; ?>">
        <br>
        <button id="save-btn">Save</button>
        <button id="pdf-btn">Export PDF</button>

        <!-- STYLE PANEL -->
        <div id="style-panel">
            <button class="close-btn">X</button>
            <strong>Selected Field</strong>

                <label>Text</label>
                <textarea id="field-text" rows="3"></textarea>

                <label>Font Size (px)</label>
                <input type="number" id="font-size">

                <div class="inline">
                    <button id="bold-btn">Bold</button>
                    <button id="italic-btn">Italic</button>
                </div>

                <label>Text Color</label>
                <input name="text-color type="color" id="text-color">

                <label>Background</label>
                <input type="color" id="bg-color">

                <label>Opacity</label>
                <input type="range" id="bg-opacity" min="0" max="100">

                <label>Border</label>
                <input type="text" id="border">

                <label>Border Radius (px)</label>
                <input type="number" id="radius">

                <label>Box Shadow</label>
                <input type="text" id="shadow">

                <label>Width (inches)</label>
                <input type="number" id="width-in" step="0.1">

                <label>Height (inches)</label>
                <input type="number" id="height-in" step="0.1">

                <label>Left (inches)</label>
                <input type="number" id="left-in" step="0.1">

                <label>Top (inches)</label>
                <input type="number" id="top-in" step="0.1">
            </div>
        </div>

        <script>

        $("#template_id").on("change", function () {
            if(!confirm('Are you sure to change the template?')){
                return false;
            }
            const val = $(this).val();
            if (!val) return;

            const url = new URL(window.location.href);
            url.searchParams.set("template_id", val);
            window.location.href = url.toString();
        });

        let selected=null;
        const DPI=96;
        const inchToPx=i=>i*DPI;
        const pxToInch=px=>px/DPI;

        function resizeEditor(){
        $("#editor").css({
            width:inchToPx($("#editor-w").val()),
            height:inchToPx($("#editor-h").val())
        });
        }
        resizeEditor();

        function makeInteractive(el) {
            el.draggable({
                containment: "#editor"
            }).resizable({
                handles: "n,e,s,w,se,sw,ne,nw"
            });
        }

        let savedHTML = `
            <?php 
                if(!empty($value['data'])){
                    foreach($value['data'] as $k=>$v){
                        // CONVERT " into quote
                        $styles = str_replace('"', "'", $v['styles']);
                        // regex to get font-family from styles 
                        $font_family = preg_match('/font-family: (.*?);/', $styles, $matches);
                        $font_family = $matches[1] ?? 'Arial';
                        ?>
                        <div class="editor-item ui-draggable ui-draggable-handle" style="<?php echo $styles; ?>">
                            <div class="delete-btn">x</div>
                            <div class="editor-text" style="font-family: <?php echo $font_family; ?>;"><?php echo $v['text']; ?></div>
                        </div>
                        <?php 
                    }
                }
            ?>
        `;

        $("#editor").html(savedHTML);

        $("#editor .editor-item").each(function(){
            makeInteractive($(this));
            $(this).on("click",function(ev){
                    ev.stopPropagation();
                    selected=$(this);
                    syncPanel();
                    $("#style-panel").show();
            });
        });

        // delete item on click x icon 
        $(".editor-item .delete-btn").on("click", function(e){
            const item = $(this).closest(".editor-item");
            if(selected && selected.is(item)){
                selected = null;  
            }
            item.remove();
        });


        // DRAG
        $(".draggable-field").draggable({
            helper:"clone",
            revert:"invalid",
            start:function(e,ui){
                ui.helper.attr("data-content",$(this).attr("data-content"));
            }
        });

        $("#editor").droppable({
            accept:".draggable-field",
            drop:function(e,ui){
                const content=ui.helper.attr("data-content");
                if(!content) return;

                const x=ui.offset.left-$(this).offset().left;
                const y=ui.offset.top-$(this).offset().top;

                const item=$(`
                <div class="editor-item">
                    <div class="delete-btn">×</div>
                    <div class="editor-text">${content}</div>
                </div>
                `)
                .css({top:y,left:x,fontFamily:$("#global-font").val()})
                .appendTo("#editor")
                .draggable({
                    containment:"#editor",
                    stop:function(){
                        syncPanel();
                    }
                });

                $(document).on("click", ".editor-item .delete-btn", function(e){
                    e.stopPropagation();      // prevent parent click
                    e.preventDefault();
                    const item = $(this).closest(".editor-item");
                    if(selected && selected.is(item)){
                        selected = null;
                    }
                    item.remove();
                });

                item.on("click",function(ev){
                    ev.stopPropagation();
                    selected=$(this);
                    syncPanel();
                    $("#style-panel").show();
                });
            }
        });

        // GLOBAL
        $("#bg-upload").change(e=>{
            const r=new FileReader();
            r.onload=()=>$("#editor").css("background-image","url("+r.result+")");
            r.readAsDataURL(e.target.files[0]);
        });
        $("#editor-bg").on("input",()=>$("#editor").css("background-color",$("#editor-bg").val()));
        $("#global-font").on("change", function () {
            const font = $(this).val();
            $(".editor-item").each(function () {
                this.style.setProperty("font-family", font, "important");
            });
            $(".editor-item .editor-text").each(function () {
                this.style.setProperty("font-family", font, "important");
            });
        });

        $("#editor-w,#editor-h").on("input",resizeEditor);

        // PANEL INTERACTIONS
        $("#field-text").on("input",()=>selected?.find(".editor-text").text($("#field-text").val()));
        $("#font-size").on("input",()=>selected?.find(".editor-text").css("font-size",$("#font-size").val()+"px"));
        $("#text-color").on("input",()=>selected?.find(".editor-text").css("color",$("#text-color").val()));
        $("#border").on("input",()=>selected?.css("border",$("#border").val()));
        $("#radius").on("input",()=>selected?.css("border-radius",$("#radius").val()+"px"));
        $("#shadow").on("input",()=>selected?.css("box-shadow",$("#shadow").val()));

        $("#width-in").on("input",()=>selected?.css("width",inchToPx($("#width-in").val())+"px"));
        $("#height-in").on("input",()=>selected?.css("height",inchToPx($("#height-in").val())+"px"));
        $("#left-in").on("input",()=>selected?.css("left",inchToPx($("#left-in").val())+"px"));
        $("#top-in").on("input",()=>selected?.css("top",inchToPx($("#top-in").val())+"px"));

        $("#bold-btn").click(()=>{
            const t=selected?.find(".editor-text");
            t?.css("font-weight",t.css("font-weight")=="700"?"400":"700");
        });
        $("#italic-btn").click(()=>{
            const t=selected?.find(".editor-text");
            t?.css("font-style",t.css("font-style")=="italic"?"normal":"italic");
        });

        function applyBg(){
            const hex=$("#bg-color").val(),a=$("#bg-opacity").val()/100;
            selected?.css("background-color",hexToRgba(hex,a));
        }
        $("#bg-color,#bg-opacity").on("input",applyBg);

        // SYNC PANEL
        function syncPanel(){
            if(!selected) return;
            const t=selected.find(".editor-text");
            $("#field-text").val(t.text());
            $("#font-size").val(parseInt(t.css("font-size")));
            $("#text-color").val(rgb2hex(t.css("color")));
            $("#border").val(selected.css("border"));
            $("#radius").val(parseInt(selected.css("border-radius")));
            $("#shadow").val(selected.css("box-shadow"));
            $("#width-in").val(pxToInch(selected.width()).toFixed(2));
            $("#height-in").val(pxToInch(selected.height()).toFixed(2));
            $("#left-in").val(pxToInch(selected.position().left).toFixed(2));
            $("#top-in").val(pxToInch(selected.position().top).toFixed(2));
        }

        // SAVE
        $("#save-btn").click(()=>{
            const template_id = $('#template-id').val();
            const template_name = $('#template-name').val();
            const settings = {};
            const data=[];
            // store global settings
            settings.font_family = $('#global-font').val();
            settings.editor_width = $('#editor-w').val();
            settings.editor_height = $('#editor-h').val();
            settings.editor_bg_color = $('#editor-bg').val();
            settings.editor_bg = null;

            const fileInput = document.getElementById("bg-upload");
            const file = fileInput.files[0];
            
            if(file){
                const reader = new FileReader();
                reader.onload = function(e){
                    const imgData = e.target.result; // Base64 string
                    console.log(imgData);
                    settings.editor_bg = imgData;
                };
                reader.readAsDataURL(file);
            }
            console.log(settings);
            $(".editor-item").each(function(){
                const el=$(this);
                data.push({
                text:el.find(".editor-text").text(),
                leftIn:pxToInch(el.position().left),
                topIn:pxToInch(el.position().top),
                wIn:pxToInch(el.width()),
                hIn:pxToInch(el.height()),
                styles:el.attr("style"),
                textStyles:el.find(".editor-text").attr("style")
                });
            });
            console.log("Saved:",data, settings);
            let dataObj = {
                    action: 'print_material_ajax',
                    data: data,
                    settings: settings,
                    _wpnonce: edupress.wpnonce,
                    ajax_action: 'saveIdCardTemplate',
                    template_id,
                    template_name
                };
            $.ajax({
                url: edupress.ajax_url,
                type: 'POST',
                data: dataObj,
                // contentType: "application/json",
                // processData: false,
                beforeSend: function(){
                    showEduPressLoading();
                },
                success: function(r) {
                    hideEduPressLoading();
                    showEduPressStatus( r.data.status == 1 ? 'success' : 'error', 2000);
                    console.log(r);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                }
            });
        });

        // PDF
        $("#pdf-btn").click(()=>{
            html2canvas(document.getElementById("editor"),{scale:2}).then(canvas=>{
                const {jsPDF}=window.jspdf;
                const pdf=new jsPDF("portrait","px",[canvas.width,canvas.height]);
                pdf.addImage(canvas.toDataURL("image/png"),"PNG",0,0,canvas.width,canvas.height);
                pdf.save("design.pdf");
            });
        });

        // HELPERS
        function rgb2hex(rgb){
            const m=rgb.match(/\d+/g);
            return m?`#${(+m[0]).toString(16).padStart(2,"0")}${(+m[1]).toString(16).padStart(2,"0")}${(+m[2]).toString(16).padStart(2,"0")}`:"#000000";
        }

        function hexToRgba(hex,a){
            const r=parseInt(hex.substr(1,2),16),
                    g=parseInt(hex.substr(3,2),16),
                    b=parseInt(hex.substr(5,2),16);
            return `rgba(${r},${g},${b},${a})`;
        }
        </script>
        <?php
        return ob_get_clean();
    }  


    function generate_pdf_from_html($html) {

        $pdf = new Pdf([
            'binary' => '/usr/bin/wkhtmltopdf', // adjust path
            'encoding' => 'UTF-8',
            'no-outline',
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,
            'page-width'    => '54mm',
            'page-height'   => '86mm',
        ]);

        $pdf->addPage($html);

        if (!$pdf->saveAs(WP_CONTENT_DIR.'/uploads/card.pdf')) {
            return $pdf->getError();
        }

        return true;
    }
}

PrintMaterial::instance();