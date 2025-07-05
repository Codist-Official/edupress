<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Term extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'term';

    protected $list_title = 'Exam Term List';

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
        add_action( 'init', [ $this, 'registerTerm' ] );

        // filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter publish fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ] );

    }

    /**
     * Register subject custom post type
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerTerm()
    {

        if ( EduPress::isActive('term') ) {
            register_post_type('term',
                array(
                    'labels' => array(
                        'name' => __('Terms', 'edupress'),
                        'singular_name' => __('Term', 'edupress'),
                        'add_item' => __('New Term', 'edupress'),
                        'add_new_item' => __('Add New Term', 'edupress'),
                        'edit_item' => __('Edit Term', 'edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'term'),
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
        $new_fields['start_date'] = array(
            'type'          => 'date',
            'name'          => 'start_date',
            'settings'      => array(
                'label'     => 'Start Date',
                'value'=> $this->getMeta('start_date'),
            )
        );
        $new_fields['end_date'] = array(
            'type'          => 'date',
            'name'          => 'end_date',
            'settings'      => array(
                'label'     => 'End Date',
                'value'=> $this->getMeta('end_date'),
            )
        );

        return $fields + $new_fields;

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
        $fields['start_date'] = 'Start Date';
        $fields['end_date'] = 'End Date';
        $fields['status'] = 'Status';
        return $fields;

    }

}

Term::instance();