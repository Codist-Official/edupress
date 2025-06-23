<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Branch extends Post
{

    /**
     * @var $_instance
     */
    public static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'branch';

    /**
     * @var $list_title
     */
    protected $list_title = 'Branch List';

    /**
     * Initialize instance
     *
     * @return self
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
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
        add_action( 'init', [ $this, 'registerBranch' ] );

        // Filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter publish fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterEditFields' ], 10, 2 );

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
    public function registerBranch()
    {

        if ( EduPress::isActive('branch') ){

            register_post_type('branch',
                array(
                    'labels' => array(
                        'name' => __( 'Branches','edupress' ),
                        'singular_name' => __( 'Branch','edupress' ),
                        'add_item' => __('New Branch','edupress'),
                        'add_new_item' => __('Add New Branch','edupress'),
                        'edit_item' => __('Edit Branch','edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'branch'),
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
    public function filterPublishFields( $fields )
    {
        $new_fields = [];
        $new_fields['address'] = array(
            'textarea',
            'address',
            'settings'  => array(

            )
        );

        return $fields + $new_fields;
    }

    /**
     * Filter publish fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterEditFields( $fields, $post_id )
    {

        $this->setMetadata(get_metadata('post', $post_id));
        $new_fields = [];
        $new_fields['address'] = array(
            'type' => 'textarea',
            'name' => 'address',
            'settings'  => array(
                'label' => 'Address',
                'value' => $this->getMeta('address'),
                'id' => 'address',
            )
        );
        $new_fields['phone'] = array(
            'type' => 'text',
            'name'  => 'phone',
            'settings' => array(
                'label' => 'Phone',
                'value' => $this->getMeta('phone'),
                'id'    => 'phone'
            )
        );
        $new_fields['email'] = array(
            'type' => 'email',
            'name'  => 'email',
            'settings' => array(
                'label' => 'Email',
                'value' => $this->getMeta('email'),
                'id'    => 'email'
            )
        );
        $new_fields['status'] = array(
            'type' => 'select',
            'name'  => 'status',
            'settings' => array(
                'label' => 'Status',
                'value' => $this->getMeta('status'),
                'options'=> array('Active'=>'Active','Inactive'=>'Inactive')
            )
        );
        unset($fields['status']);
        return $fields + $new_fields;
    }

    /**
     * Filter list fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterListFields( $fields )
    {
        unset($fields['status']);
        $fields['address'] = 'Address';
        $fields['phone'] = 'Phone';
        $fields['email'] = 'Email';
        $fields['total'] = 'Students';
        if(EduPress::isActive('calendar')) $fields['calendar'] = 'Calendar';
        $fields['status'] = 'Status';
        return $fields;
    }

}

Branch::instance();