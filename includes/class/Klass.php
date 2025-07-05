<?php
namespace EduPress;

defined( 'ABSPATH' || die () );

class Klass extends Post
{
    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'class';

    /**
     * @var string $list_title
     */
    protected $list_title = 'Class List';

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
     *
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        // Register klass post type
        add_action( 'init', [ $this, 'registerKlass' ] );

        // filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter edit fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterEditFields' ] );

        // filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ] );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterSearchFields' ] );

    }

    /**
     * Register class
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerKlass()
    {

        if( Admin::getSetting('class_active') !== 'inactive' ){

            register_post_type('class',
                array(
                    'labels' => array(
                        'name' => __( 'Classes','edupress' ),
                        'singular_name' => __( 'Class','edupress' ),
                        'add_item' => __('New Class','edupress'),
                        'add_new_item' => __('Add New Class','edupress'),
                        'edit_item' => __('Edit Class','edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'class'),
                    'menu_position' => 6,
                    'show_ui' => true,
                    'supports' => array('author', 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments', 'custom-fields')
                )
            );

        }

    }

    /**
     * Filter fields for publishing a post
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterPublishFields( $fields )
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
                    'placeholder'=>'Select a Branch'
                )
            );
        }

        if ( EduPress::isActive('shift') ){
            $new_fields['shift_id'] = array(
                'type'          => 'select',
                'name'          => 'shift_id',
                'settings'      => array(
                    'required'  => true,
                    'label'     => 'Shift',
                    'placeholder'=>'Select a Shift'
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
     * @acecess public
     */
    public function filterListFields( $fields )
    {
        unset($fields['status']);

        $fields['total'] = 'Students';
        $fields['start_date'] = 'Start Date';
        $fields['end_date'] = 'End Date';

        if( EduPress::isActive('calendar') ){
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

        return $new_fields + $fields;

    }

    /**
     * Filter edit fields
     *
     * @return array
     *
     * @since 1.0
     * @acecess public
     */
    public function filterEditFields( $fields )
    {
        $new_fields = [];

        if( EduPress::isActive('branch') ){
            $branch = new Branch();
            $branch_options = $branch->getPosts( [], true );
            $new_fields['branch_id'] = array(
                'type'  => 'select',
                'name'  => 'branch_id',
                'settings' => array(
                    'label' => 'Branch',
                    'options' => $branch_options,
                    'required' => true,
                    'placeholder' => 'Select a Branch',
                    'value' => $this->getMeta('branch_id'),
                )
            );
        }

        if( EduPress::isActive('shift') ){
            $shift = new Shift();
            $shift_options = $shift->getPosts( array('meta_key'=>'branch_id','meta_value'=>$this->getMeta('branch_id')), true );
            $new_fields['shift_id'] = array(
                'type'  => 'select',
                'name'  => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'options' => $shift_options,
                    'required' => true,
                    'placeholder' => 'Select a Shift',
                    'value' => $this->getMeta('shift_id')
                )
            );
        }

        if(EduPress::isActive('calendar')){
            $fields['start_date'] = array(
                'type'  => 'date',
                'name'  => 'start_date',
                'settings' => array(
                    'label' => 'Start Date',
                    'value' => $this->getMeta('start_date')
                )
            );
            $fields['end_date'] = array(
                'type'  => 'date',
                'name'  => 'end_date',
                'settings' => array(
                    'label' => 'End Date',
                    'value' => $this->getMeta('end_date')
                )
            );
        }

        return $new_fields + $fields;

    }

    /**
     * Filter search fields
     *
     * @return array
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

        return $fields;

    }
}

Klass::instance();