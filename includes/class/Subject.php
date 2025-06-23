<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Subject extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'subject';

    protected $list_title = "Subject List <br> <span style='font-size:16px; color:#777;'>Drag &amp; Drop to Sort Order</span>";

    /**
     * Draggable true
     */
    protected $draggable = true;

    /**
     * Initialize instance
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

        parent::__construct( $id );

        $this->posts_per_page = -1;

        // Register subject
        add_action( 'init', [ $this, 'registerSubject' ] );

        // Filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields'] );

        // Filter edit fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterEditFields' ] );

        // Modify query to show data as per subject id
        add_filter( "edupress_list_{$this->post_type}_query", [ $this, 'filterListQuery' ] );

    }

    /**
     * Register subject custom post type
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerSubject()
    {

        if ( EduPress::isActive('subject') ) {

            register_post_type('subject',
                array(
                    'labels' => array(
                        'name' => __('Subjects', 'edupress'),
                        'singular_name' => __('Subject', 'edupress'),
                        'add_item' => __('New Subject', 'edupress'),
                        'add_new_item' => __('Add New Subject', 'edupress'),
                        'edit_item' => __('Edit Subject', 'edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'subject'),
                    'menu_position' => 4,
                    'show_ui' => true,
                    'supports' => array('author', 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments', 'custom-fields' )
                )
            );

        }
    }

    /**
     * Filter publish fields
     *
     * @return array
     * @since 1.0
     * @acecess public
     */
    public function filterPublishFields( $fields )
    {
        $new_fields = [];
        $new_fields['connected_subject_id'] = array(
            'type'  => 'select',
            'name'  => 'connected_subject_id',
            'settings' => array(
                'label' => 'Connected Subject',
                'placeholder'=> 'Select a subject',
                'options'   => $this->getPosts( [], 'id' ),
                'value' => $this->getMeta('connected_subject_id'),
            )
        );
        $new_fields['shortname'] = array(
            'type'  => 'text',
            'name'  => 'shortname',
            'settings' => array(
                'label' => 'Shortname for SMS',
                'placeholder'=> 'Shortname for SMS',
                'value' => $this->getMeta('shortname'),
            )
        );
        $new_fields['combined_name'] = array(
            'type'  => 'text',
            'name'  => 'combined_name',
            'settings' => array(
                'label' => 'Combined Name',
                'placeholder'=> 'Combined name for connected subject',
                'value' => $this->getMeta('combined_name'),
            )
        );
        $sort_order = $this->getMeta('sort_order', true);
        if(empty($sort_order)) $sort_order = 1;
        $new_fields['sort_order'] = array(
            'type'  => 'hidden',
            'name'  => 'sort_order',
            'settings' => array(
                'value' => $sort_order,
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
     * @acecess public
     */
    public function filterListFields( $fields )
    {

        unset($fields['status']);
        unset($fields['post_content']);
        $fields['connected_subject_id'] = 'Connected Subject';
        $fields['shortname'] = 'Shortname for SMS';
        $fields['combined_name'] = 'Combined Name';
        $fields['post_content'] = 'Details';
        $fields['status'] = 'Status';

        return $fields;

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
        $fields['connected_subject_id'] = array(
            'type'   => 'select',
            'name'   => 'connected_subject_id',
            'settings'  => array(
                'label' => 'Connected subject',
                'value' => $this->getMeta('connected_subject_id'),
                'placeholder' => 'Select a subject',
                'options'   => $this->getPosts( [], true ),
            )
        );

        $fields['shortname'] = array(
            'type'  => 'text',
            'name'  => 'shortname',
            'settings' => array(
                'label' => 'Shortname for SMS',
                'placeholder'=> 'Shortname for SMS',
                'value' => $this->getMeta('shortname'),
            )
        );

        $fields['combined_name'] = array(
            'type'  => 'text',
            'name'  => 'combined_name',
            'settings' => array(
                'label' => 'Combined Name',
                'placeholder'=> 'Combined name for connected subject',
                'value' => $this->getMeta('combined_name'),
            )
        );


        $new_fields = [];
        $new_fields['post_title'] = $fields['post_title'];
        $new_fields['connected_subject_id'] = $fields['connected_subject_id'];
        $new_fields['shortname'] = $fields['shortname'];
        $new_fields['combined_name'] = $fields['combined_name'];
        $new_fields['post_content'] = $fields['post_content'];
        $new_fields['status'] = $fields['status'];
        return $new_fields;
    }

    /**
     * Get connected subject id
     *
     * @return int
     *
     * @param int $subject_id
     * @since 1.0
     * @access public
     * @static
     */
    public static function getConnectedId( $subject_id )
    {
        $connected_id = (int) get_post_meta($subject_id, 'connected_subject_id', true );
        if( $connected_id ) return $connected_id;

        global $wpdb;
        return $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'connected_subject_id' AND meta_value = {$subject_id} ");

    }

    /**
     * Filter list query to show data as per sort order
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterListQuery( $args )
    {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = 'sort_order';
        $args['order'] = 'ASC';
        if(!isset($args['meta_query'])) $args['meta_query'] = array();
        $args['meta_query'][] = array(
            'key' => 'sort_order',
            'value' => -1,
            'compare' => '>',
            'type' => 'NUMERIC'
        );
//        echo "<pre>";
//        var_dump($args);
//        echo "</pre>";
        return $args;
    }

}

Subject::instance();