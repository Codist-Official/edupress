<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Shift extends Post
{

    /**
     * @var $_instance
     */
    public static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'shift';

    /**
     * Initialize instance
     *
     * @return Shift
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
     * @access public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct($id);

        // Register branch post type
        add_action( 'init', [ $this, 'registerShift' ] );

        // Update publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter publish fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter list filter fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterListFilterFields' ] );

        // Filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ] );

    }


    /**
     * Register Branch type
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerShift()
    {

        if ( EduPress::isActive('shift') ){

            register_post_type('shift',
                array(
                    'labels' => array(
                        'name' => __( 'Shifts','edupress' ),
                        'singular_name' => __( 'Shift','edupress' ),
                        'add_item' => __('New Shift','edupress'),
                        'add_new_item' => __('Add New Shift','edupress'),
                        'edit_item' => __('Edit Shift','edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'shift'),
                    'menu_position' => 5,
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

        if ( Admin::getSetting('branch_active' ) ){

            $branch = new Branch();
            $options = $branch->getPosts( array('orderby'=>'title','order'=>'ASC'), true );
            $placeholder = count($options) > 1 ? 'Select a Branch' : '';

            $new_fields['branch_id'] = array(
                'type'          => 'select',
                'name'          => 'branch_id',
                'settings'      => array(
                    'options'   => $options,
                    'required'  => true,
                    'label'     => 'Branch',
                    'value'     => $this->getMeta('branch_id'),
                    'placeholder'=> $placeholder
                )
            );
        }

        return $new_fields + $fields;

    }

    /**
     * Filter list columns
     *
     * @return array
     *
     * @since 1.0
     * @acecess public
     */
    public function filterListFields( $fields = [] )
    {

        unset($fields['status']);
        $fields['total'] = 'Total<br>Students';
        $fields['status'] = 'Status';
        $new_fields = [];
        $new_fields['branch_id'] = 'Branch';
        return $new_fields + $fields;

    }

    /**
     * Filter list search fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterListFilterFields( $fields = [] )
    {
        $branch = new Branch();
        $branch_options = $branch->getPosts( [], true );

        $fields['branch_id'] = array(
            'type'  => 'select',
            'name'  => 'branch_id',
            'settings' => array(
                'options'   => $branch_options,
                'placeholder' => 'Select a Branch',
                'label' => 'Branch',
                'value' => sanitize_text_field($_REQUEST['branch_id'] ?? '')
            )
        );

        return $fields;

    }

}

Branch::instance();