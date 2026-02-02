<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Section extends Klass
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'section';

    protected $list_title = 'Section List';

    /**
     * Initialize instance
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Constructor
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        // Register subject
        add_action( 'init', [ $this, 'registerSection' ] );

        // filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter publish fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ] );

        // filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterSearchFields' ] );

    }

    /**
     * Register subject custom post type
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerSection()
    {

        if (EduPress::isActive('section')) {

            register_post_type('section',
                array(
                    'labels' => array(
                        'name' => __('Sections', 'edupress'),
                        'singular_name' => __('Section', 'edupress'),
                        'add_new' => __('Add New Section', 'edupress'),
                        'add_new_item' => __('Add New Section', 'edupress'),
                        'new_item' => __('New Section', 'edupress'),
                        'edit_item' => __('Edit Section', 'edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'section'),
                    'menu_position' => 4,
                    'show_ui' => true,
                    'supports' => array('author', 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments', 'custom-fields')
                )
            );

        }
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
        $new_fields = [];

        if ( EduPress::isActive('branch') ){
            $branch = new Branch();
            $branch_options = $branch->getPosts( array('orderby'=>'title','order'=>'ASC'), true );
            $new_fields['branch_id'] = array(
                'type'          => 'select',
                'name'          => 'branch_id',
                'settings'      => array(
                    'options'   => $branch_options,
                    'required'  => true,
                    'label'     => 'Branch',
                    'placeholder'=> 'Select a Branch',
                    'value'=> $this->getMeta('branch_id'),
                )
            );
        }

        if ( EduPress::isActive('shift') ){
            $shift = new Shift();
            $shift_options = $shift->getPosts(array('orderby'=>'title','order'=>'ASC','meta_query'=>array(array('key'=>'branch_id','value'=>$this->getMeta('branch_id'),'compare'=>'='))), true);
            $new_fields['shift_id'] = array(
                'type'          => 'select',
                'name'          => 'shift_id',
                'settings'      => array(
                    'options'   => $shift_options,
                    'required'  => true,
                    'label'     => 'Shift',
                    'placeholder'=>'Select a Shift',
                    'value' => $this->getMeta('shift_id'),
                )
            );
        }

        if ( EduPress::isActive('class') ){

            $meta_query = [];
            if(EduPress::isActive('branch')){
                $meta_query[] = array(
                    'key'   => 'branch_id',
                    'value' => $this->getMeta('branch_id'),
                    'compare'=> '='
                );
            }
            if(EduPress::isActive('shift')){
                $meta_query[] = array(
                    'key'   => 'shift_id',
                    'value' => $this->getMeta('shift_id'),
                    'compare' => '='
                );
            }


            if(count($meta_query) > 1) $meta_query['relation'] = 'AND';

            $class = new Klass();
            $class_options = $class->getPosts( array( 'meta_query' => $meta_query ), true );
            $new_fields['class_id'] = array(
                'type'          => 'select',
                'name'          => 'class_id',
                'settings'      => array(
                    'options'   => $class_options,
                    'required'  => true,
                    'label'     => 'Class',
                    'placeholder'=>'Select a Class',
                    'value'     => $this->getMeta('class_id')
                )
            );

        }

        $fields['start_date'] = array(
            'type'          => 'date',
            'name'          => 'start_date',
            'settings'      => array(
                'label'     => 'Start Date',
                'value' => $this->getMeta('start_date'),
            )
        );
        $fields['end_date'] = array(
            'type'          => 'date',
            'name'          => 'end_date',
            'settings'      => array(
                'label'     => 'End Date',
                'value' => $this->getMeta('end_date'),
            )
        );


        if(Admin::getSetting('absence_sms') == 'active'){
            $fields['absence_sms'] = array(
                'type'          => 'select',
                'name'          => 'absence_sms',
                'settings'      => array(
                    'label'     => 'Absence SMS',
                    'value' => $this->getMeta('absence_sms'),
                    'options' => ['active' => 'Yes', 'inactive' => 'No'],
                    'placeholder' => 'Select',
                    'id' => 'absence_sms'
                )
            );
            $fields['absence_sms_cutoff_time'] = array(
                'type'          => 'time',
                'name'          => 'absence_sms_cutoff_time',
                'settings'      => array(
                    'label'     => 'Absence SMS cutoff time',
                    'value' => $this->getMeta('absence_sms_cutoff_time'),
                )
            );
        }

        return $new_fields + $fields;


    }

    /**
     * Filter list fields
     *
     * @return array
     *
     * @since 1.0
     * @access
     */
    public function filterListFields( $fields )
    {

        unset($fields['status']);
        $fields['total'] = 'Students';
        $fields['start_date'] = 'Start Date';
        $fields['end_date'] = 'End Date';
        if( EduPress::isActive('calendar')){
            $fields['calendar'] = 'Calendar';
        }
        $fields['status'] = 'Status';

        $new_fields = [];

        if( EduPress::isActive('branch') ){
            $new_fields['branch_id'] = 'Branch';
        }

        if( EduPress::isActive('shift') ){
            $new_fields['shift_id'] = 'Shift';
        }

        if( EduPress::isActive('class') ){
            $new_fields['class_id'] = 'Class';
        }

        if(Admin::getSetting('absence_sms') == 'active'){
            $fields['absence_sms'] = 'Absence SMS';
            $fields['absence_sms_cutoff_time'] = 'Cutoff time';
        }

        return $new_fields + $fields;

    }

    /**
     * Filter search fields
     *
     * @return arary
     *
     * @since 1.0
     * @access public
     */
    public function filterSearchFields( $fields = [] )
    {
        $branch = new Branch();
        $fields['branch_id'] = array(
            'type' => 'select',
            'name' => 'branch_id',
            'settings' => array(
                'value' => intval($_REQUEST['branch_id'] ?? 0),
                'placeholder' => 'Select a branch',
                'label' => 'Branch',
                'options' => $branch->getPosts( [], true ),
            )
        );

        if( EduPress::isActive('shift') ){
            $fields['shift_id'] = array(
                'type' => 'select',
                'name' => 'shift_id',
                'settings' => array(
                    'value' => intval($_REQUEST['shift_id'] ?? 0),
                    'placeholder' => 'Select a shift',
                    'label' => 'Shift',
                )
            );
        }

        if( EduPress::isActive('class') ){
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'value' => intval($_REQUEST['class_id'] ?? 0),
                    'placeholder' => 'Select a class',
                    'label' => 'Class',
                )
            );
        }

        return $fields;
    }

}

Section::instance();