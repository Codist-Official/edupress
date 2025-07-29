<?php 
namespace EduPress;

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

    }

    public static function getItemsMenu()
    {
        $menus = array(
            'admit_card'       => array(
                'id'        => 'admit_card',
                'title'     => 'Admit Card',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'admit_card' ),
                'active'    => 0,
            ),
            'id_card'       => array(
                'id'        => 'id_card',
                'title'     => 'ID Card',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'id_card' ),
                'active'    => 0,
            ),
            'certificate'       => array(
                'id'        => 'certificate',
                'title'     => 'Certificate',
                'info'      => '',
                'url'       => EduPress::changeUrlParam( EduPress::getCurrentUrl(), 'activePage', 'certificate' ),
                'active'    => 0,
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
        $fields = [];

        $fields['print_type'] = array(
            'type'          => 'select',
            'name'          => 'print_type',
            'settings'      => array(
                'options'   => array(
                    'students_roll_wise' => 'Students Roll Wise',
                    'class_wise' => 'Class / Section Wise',
                    'teachers' => 'Teachers',
                    'managers' => 'Managers',
                ),
                'required'  => true,
                'label'     => 'Print Type',
                'placeholder'=>'Select a Print Type',
            )
        );

        $fields['roll'] = array(
            'type'          => 'text',
            'name'          => 'roll',
            'settings'      => array(
                'required'  => false,
                'label'     => 'Roll (separated by comma)',
                'placeholder'=>'Roll',
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
                'value'     => 'printIdCard',
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
                'value'     => 'printIdCardBeforeSend',
            )
        );
        $fields['after_success_callback'] = array(
            'type'      => 'hidden',
            'name'      => 'after_success_callback',
            'settings'  => array(
                'value'     => 'printIdCardAfterSuccess',
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
                jQuery(document).on('change', '[name="print_type"]', function(){
                    let printType = jQuery("[name='print_type']").val();
                    if( printType == 'students_roll_wise' ){
                        jQuery("[data-name='roll']").show();
                        jQuery("[data-name='branch_id'], [data-name='shift_id'], [data-name='class_id'], [data-name='section_id'] ").hide();
                    } else if( printType == 'class_wise' ){
                        jQuery("[data-name='roll']").hide();
                        jQuery("[data-name='branch_id'], [data-name='shift_id'], [data-name='class_id'], [data-name='section_id'] ").show();
                    } else {
                        jQuery("[data-name='branch_id'], [data-name='shift_id'], [data-name='class_id'], [data-name='section_id'], [data-name='roll'] ").hide();
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
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>'Print ID Cards'));
                            echo EduPress::generateFormElement( 'hidden', 'panel', array('value'=>'printMaterials'));
                        ?>
                    </div>
                </div>

            </form>
        </div>
        <?php 
        return ob_get_clean();
    }



    public static function getPanels()
    {
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
                                        $activePage = isset($_REQUEST['activePage']) && $_REQUEST['activePage'] == $menu['id'] ? 1 : 0;
                                        $activeClass = $activePage ? ' active ' : '';
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
                    <div class="title">Print</div>
                    <div class="content">
                        <?php echo self::showIdCardPrintOptions(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        return ob_get_clean();
        
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

        include_once EDUPRESS_LIB_DIR . 'mpdf/vendor/autoload.php';
        $pdf = new \Mpdf\Mpdf($options);
        $pdf->WriteHTML($html);
        $pdf->Output($target_dir . $filename);
        $file = $target_dir . $filename;
        return str_replace( WP_CONTENT_DIR, site_url() . '/wp-content/', $file );
    }
    
}

PrintMaterial::instance();