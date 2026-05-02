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
        <div class="edupress-filter-list-wrap" style="margin-bottom: 0px;" data-active="1">
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
                    'options'   => [],
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
                    'options'   => [],
                    'required'  => false,
                    'label'     => 'Section',
                    'placeholder'=>'Select a Section',
                )
            );
        }

        if(EduPress::isActive('term')){
            $term = new Term();
            $fields['term_id'] = array(
                'type'          => 'select',
                'name'          => 'term_id',
                'settings'      => array(
                    'options'   => $term->getPosts([], true),
                    'required'  => false,
                    'label'     => t('Term'),
                    'placeholder'=>'Select a Term',
                )
            );
        }
        $current_year = current_time('Y');
        $range_starts = $current_year - 10;
        $range_ends = $current_year + 10;
        $years = range($range_starts, $range_ends);

        $fields['year'] = array(
            'type'          => 'select',
            'name'          => 'year',
            'settings'      => array(
                'options'   => array_combine($years, $years),
                'required'  => true,
                'label'     => t('Year'),
                'placeholder'=>t('Select a Year'),
                'value' => current_time('Y')
            )
        );

        $columns = range(1,3);
        $fields['columns'] = array(
            'type'          => 'select',
            'name'          => 'column',
            'settings'      => array(
                'options'   => array_combine($columns, $columns),
                'required'  => true,
                'label'     => t('Columns'),
                'placeholder'=>t('Select a column'),
                'value' => 1,
            )
        );
        $fields['orientation'] = array(
            'type'          => 'select',
            'name'          => 'orientation',
            'settings'      => array(
                'options'   => ['portrait'=>t('Portrait'),'landscape'=>t('Landscape')],
                'required'  => true,
                'label'     => t('Paper Orientation'),
                'value'     => 'Lanscape',
            )
        );


        $fields['ajax'] = array(
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
                'value'     => 'downloadAdmitCard',
            )
        );
        $fields['_wpnonce'] = array(
            'type'      => 'hidden',
            'name'      => '_wpnonce',
            'settings'  => array(
                'value'     => wp_create_nonce('edupress'),
            )
        );
        $fields['success_callback'] = array(
            'type'      => 'hidden',
            'name'      => 'success_callback',
            'settings'  => array(
                'value'     => 'printAdmitCard',
            )
        );

        ob_start();
        ?>
        <div class="edupress-filter-list-wrap" style="margin-bottom: 0px;" data-active='1'>
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
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>t('Download Admit Card')));
                            echo EduPress::generateFormElement( 'hidden', 'panel', array('value'=>'printMaterials'));
                            echo EduPress::generateFormElement( 'hidden', 'activePage', array('value'=>sanitize_text_field($_REQUEST['activePage'])));
                        ?>
                    </div>
                </div>

            </form>
        </div>
        <?php
        return ob_get_clean();

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
}

PrintMaterial::instance();