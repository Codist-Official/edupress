<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Section extends Post
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
            if(Admin::getSetting('branch_active') == 'active'){
                $meta_query[] = array(
                    'key'   => 'branch_id',
                    'value' => $this->getMeta('branch_id'),
                    'compare'=> '='
                );
            }
            if(Admin::getSetting('shift_active') == 'active'){
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
        if( Admin::getSetting('calendar_active') == 'active' ){
            $fields['calendar'] = 'Calendar';
        }
        $fields['status'] = 'Status';

        $new_fields = [];

        if( Admin::getSetting('branch_active') == 'active' ){

            $new_fields['branch_id'] = 'Branch';

        }

        if( Admin::getSetting('shift_active') == 'active' ){

            $new_fields['shift_id'] = 'Shift';

        }

        if( Admin::getSetting('class_active') == 'active' ){

            $new_fields['class_id'] = 'Class';

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

        if( Admin::getSetting('shift_active') == 'active' ){
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

        if( Admin::getSetting('class_active') == 'active' ){
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