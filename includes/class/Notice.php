<?php
namespace EduPress;

class Notice extends Post
{
    /**
     * @var self 
     */
    private static $instance;
    /**
     * @var string
     */
    protected $post_type = 'notice';

    
    /**
     * @return self
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    /**
     * constructor
     * @return void 
     * 
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {
        parent::__construct( $id );

        // Register Notice type
        add_action( 'init', [ $this, 'registerNotice' ] );

        // filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ], 10, 1 );

        // Filter edit fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterPublishFields' ], 10, 1 );

        // Filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ], 10, 1 );

    }


    /**
     * Register Notice type
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerNotice()
    {

        if ( EduPress::isActive('notice') ){

            register_post_type('notice',
                array(
                    'labels' => array(
                        'name' => __( 'Notices','edupress' ),
                        'singular_name' => __( 'Notice','edupress' ),
                        'add_item' => __('New Notice','edupress'),
                        'add_new_item' => __('Add New Notice','edupress'),
                            'edit_item' => __('Edit Notice','edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'notice'),
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
     * @param array $fields
     * @return array
     * 
     * @since 1.0
     * @access public
     */
    public function filterPublishFields( $fields )
    {
        $new_fields = [];
        if( EduPress::isActive('branch') ){
            $branch = new Branch();
            $new_fields['branch_id'] = array(
                'type'  => 'select',
                'name'  => 'branch_id',
                'settings' => array( 
                    'options' => $branch->getPosts( [], true ), 
                    'label' => 'Branch',
                    'value' => $this->getMeta('branch_id'),
                    'placeholder' => 'Select Branch'
                )
            );
        }
        if( EduPress::isActive('shift') ){
            $shift = new Shift();
            $new_fields['shift_id'] = array(
                'type'  => 'select',
                'name'  => 'shift_id',
                'settings' => array( 
                    'options' => $shift->getPosts( [], true ), 
                    'label' => 'Shift',
                    'value' => $this->getMeta('shift_id'),
                    'placeholder' => 'Select Shift'
                )
            );
        }
        if( EduPress::isActive('class') ){
            $class = new Klass();
            $new_fields['class_id'] = array(
                'type'  => 'select',
                'name'  => 'class_id',
                'settings' => array( 
                    'options' => $class->getPosts( [], true ), 
                    'value' => $this->getMeta('class_id'), 
                    'label' => 'Class',
                    'placeholder' => 'Select Class'
                )
            );
        }
        if( EduPress::isActive('section') ){
            $section = new Section();
            $new_fields['section_id'] = array(
                'type'  => 'select',
                'name'  => 'section_id',
                'settings' => array( 
                    'options' => $section->getPosts( [], true ), 
                    'label' => 'Section',
                    'value' => $this->getMeta('section_id'),
                    'placeholder' => 'Select Section'
                )
            );
        }
        $fields['post_title']['settings']['label'] = __('Notice Title','edupress');
        $fields['post_content']['settings']['label'] = __('Notice Details','edupress');
        $fields['post_content']['settings']['data'] = array( 'rows' => 5, 'cols' => 50 );
        $fields['post_content']['settings']['required'] = true;
        
        $all_fields = array_merge( $fields, $new_fields );

        // Hiding status field for existing posts 
        unset( $all_fields['status'] );

        return $all_fields;
    }

    /**
     * Filter list fields
     * 
     * @param array $fields
     * @return array
     * 
     * @since 1.0
     * @access public
     */
    public function filterListFields( $fields )
    {
        unset( $fields['post_content'] );
        unset( $fields['status'] );
        if( EduPress::isActive('branch') ) $fields['branch_id'] = 'Branch';
        if( EduPress::isActive('shift') ) $fields['shift_id'] = 'Shift';
        if( EduPress::isActive('class') ) $fields['class_id'] = 'Class';
        if( EduPress::isActive('section') ) $fields['section_id'] = 'Section';
        $fields['view_action'] = 'Details';
        return $fields;
    }

}

Notice::instance();