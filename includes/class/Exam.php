<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Exam extends Post
{

    /**
     * @var $post_type
     */
    protected $post_type = 'exam';

    /**
     * @var $_instance
     */
    private static $_instance;

    protected $list_title = 'Exam List';

    /**
     * Initialize instance
     *
     * @return Exam
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ) self::$_instance = new self();
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

        parent::__construct( $id );

        // Register Exam post type
        add_action( 'init', [ $this, 'registerExam' ] );

        // filter publish fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // filter edit fields
        add_filter( "edupress_edit_{$this->post_type}_fields", [ $this, 'filterEditFields' ] );

        // Filter list fields
        add_filter( "edupress_list_{$this->post_type}_fields", [ $this, 'filterListFields' ] );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterSearchFields' ] );

        // Add Edit Result Button
        add_filter( "edupress_list_{$this->post_type}_action_html", [ $this, 'filterActionHtml' ], 10, 2 );

    }

    /**
     * Register exam post type
     *
     * @return void
     *
     * @since 1.0
     * @acecess public
     */
    public function registerExam()
    {

        register_post_type('exam',
            array(
                'labels' => array(
                    'name' => __( 'Exams','edupress' ),
                    'singular_name' => __( 'Exam','edupress' ),
                    'add_item' => __('New Exam','edupress'),
                    'add_new_item' => __('Add New Exam','edupress'),
                    'edit_item' => __('Edit Exam','edupress')
                ),
                'public' => false,
                'has_archive' => false,
                'rewrite' => array('slug' => 'exam'),
                'menu_position' => 4,
                'show_ui' => true,
                'supports' => array('author', 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments', 'custom-fields')
            )
        );
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

        if( EduPress::isActive('branch') ){

            $branch = new Branch();
            $new_fields['branch_id'] = array(
                'name'  => 'branch_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Branch',
                    'options'=> $branch->getPosts( [], true ),
                    'placeholder'=>'Select a branch',
                    'required' => true,
                )
            );

        }

        if( EduPress::isActive('shift') ){

            $new_fields['shift_id'] = array(
                'name'  => 'shift_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Shift',
                    'options'=> [],
                    'placeholder'=>'Select a shift',
                    'required' => true,
                )
            );

        }

        if( EduPress::isActive('class') ){

            $new_fields['class_id'] = array(
                'name'  => 'class_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Class',
                    'options'=> [],
                    'placeholder'=>'Select a class',
                    'required' => true,
                )
            );

        }

        if( EduPress::isActive('section') ){

            $new_fields['section_id'] = array(
                'name'  => 'section_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Section',
                    'options'=> [],
                    'placeholder'=>'Select a section',
                    'required' => true,
                )
            );

        }

        $term = new Term();
        $new_fields['term_id'] = array(
            'name'  => 'term_id',
            'type'  => 'select',
            'settings' => array(
                'label' => 'Term',
                'options'=> $term->getPosts( [], true ),
                'placeholder'=>'Select a term',
                'required' => true,
            )
        );

        $subject = new Subject();
        $new_fields['subject_id'] = array(
            'name'  => 'subject_id',
            'type'  => 'select',
            'settings' => array(
                'label' => 'Subject',
                'options'=> $subject->getPosts( array( 'orderby' => 'title', 'order' => 'ASC' ), true ),
                'placeholder'=>'Select a subject',
                'required' => true,
            )
        );

        $mark_options = explode( ',', Admin::getSetting('exam_mark_heads') );
        if( !empty($mark_options ) ) {
            $mark_options = array_map( 'trim', $mark_options );
            $mark_options = array_combine( $mark_options, $mark_options );
        }
        $new_fields['mark_heads'] = array(
            'name'  => 'exam_mark_heads',
            'type'  => 'checkbox',
            'settings' => array(
                'options' => $mark_options,
                'required'=> false,
                'label' => 'Mark Heads',
                'value' => $this->getMeta('exam_mark_heads', false )
            )
        );


        $new_fields['exam_date'] = array(
            'name'  => 'exam_date',
            'type'  => 'date',
            'settings'  => array(
                'label' => 'Exam Date',
                'placeholder' => 'Select a time',
                'required' => true,
            )
        );

        $new_fields['exam_time'] = array(
            'name'  => 'exam_time',
            'type'  => 'time',
            'settings'  => array(
                'label' => 'Exam Time (Optional)',
                'placeholder' => 'Select a time',
                'required' => false,
            )
        );

        $fields['post_title'] = array(
            'name'  => 'post_title',
            'type'  => 'hidden',
            'settings' => array(
                'value' => 'Exam'
            )
        );

        return $new_fields + $fields;

    }

    /**
     * Publish an exam
     *
     * @return int
     *
     * @param array $args
     *
     * @since 1.0
     * @access public
     */
    public function publish( $args = [] )
    {

        $post_data = [];

        $branch = get_the_title( intval($args['branch_id'] ?? 0) );
        $shift = get_the_title( intval($args['shift_id'] ?? 0) );
        $class = get_the_title( intval($args['class_id'] ?? 0) );
        $section = get_the_title( intval($args['section_id'] ?? 0) );
        $subject = get_the_title( intval($args['subject_id'] ?? 0) );

        $post_data['post_title'] = "Subject: $subject | Section: $section | Class: $class | Shift: $shift | Branch: $branch";
        $post_data['post_content'] = '';
        $post_data['post_author'] = get_current_user_id();
        $post_data['post_status'] = 'publish';
        $post_data['post_type'] = $this->post_type;

        $post = wp_insert_post($post_data);
        if($post){
            $skip_fields = self::skipFieldsForMetadata();
            foreach( $args as $k=>$v ){

                if( in_array( $k, $skip_fields ) ) continue;

                if( is_array($v) ){

                    foreach( $v as $item_v ){

                        add_post_meta( $post, $k, $item_v );

                    }

                } else {

                    add_post_meta( $post, $k, $v );

                }

            }
        }

        return $post;


    }

    /**
     * Filter Edit Fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterEditFields( $fields )
    {

        $new_fields = [];

        $args = [];

        if( EduPress::isActive('branch') ){

            $branch = new Branch();
            $new_fields['branch_id'] = array(
                'name'  => 'branch_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Branch',
                    'options'=> $branch->getPosts( $args, true ),
                    'placeholder'=>'Select a branch',
                    'value' => $this->getMeta('branch_id'),
                    'required' => true,
                )
            );

            // Adding branch
            $args['meta_query'][] = array(
                'key'   => 'branch_id',
                'value' => $this->getMeta('branch_id'),
                'compare'   => '='
            );
        }


        if( EduPress::isActive('shift') ){

            $shift = new Shift();
            $new_fields['shift_id'] = array(
                'name'  => 'shift_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Shift',
                    'options'=> $shift->getPosts( $args , true ),
                    'placeholder'=>'Select a shift',
                    'required' => true,
                    'value' => $this->getMeta('shift_id')
                )
            );

            // Adding shift
            $args['meta_query'][] = array(
                'key'   => 'shift_id',
                'value' => $this->getMeta('shift_id'),
                'compare'   => '='
            );

        }

        if( EduPress::isActive('class') ){

            if( count($args['meta_query'] ) > 1 ) $args['meta_query']['relation'] = 'AND';

            $class = new Klass();
            $new_fields['class_id'] = array(
                'name'  => 'class_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Class',
                    'options'=> $class->getPosts( $args, true ),
                    'placeholder'=>'Select a class',
                    'required' => true,
                    'value' => $this->getMeta('class_id'),
                )
            );

            // adding class
            $args['meta_query'][] = array(
                'key'   => 'class_id',
                'value' => $this->getMeta('class_id'),
                'compare'   => '='
            );

        }

        if( EduPress::isActive('section') ){

            if( count($args['meta_query'] ) > 1 ) $args['meta_query']['relation'] = 'AND';

            $section = new Section();
            $new_fields['section_id'] = array(
                'name'  => 'section_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => 'Section',
                    'options'=> $section->getPosts( $args, true ),
                    'placeholder'=>'Select a section',
                    'required' => true,
                    'value' => $this->getMeta('section_id')
                )
            );

        }

        $subject = new Subject();
        $new_fields['subject_id'] = array(
            'name'  => 'subject_id',
            'type'  => 'select',
            'settings' => array(
                'label' => 'Subject',
                'options'=> $subject->getPosts(  [], true ),
                'placeholder'=>'Select a subject',
                'required' => true,
                'value' => $this->getMeta('subject_id')
            )
        );

        $term = new Term();
        $new_fields['term_id'] = array(
            'name'  => 'term_id',
            'type'  => 'select',
            'settings' => array(
                'label' => 'Term',
                'options'=> $term->getPosts( [], true ),
                'placeholder'=>'Select a term',
                'required' => true,
                'value' => $this->getMeta( 'term_id' )
            )
        );

        $mark_options = explode( ',', Admin::getSetting('exam_mark_heads') );
        if( !empty($mark_options ) ) {
            $mark_options = array_map( 'trim', $mark_options );
            $mark_options = array_combine( $mark_options, $mark_options );
        }
        $new_fields['mark_heads'] = array(
            'name'  => 'exam_mark_heads',
            'type'  => 'checkbox',
            'settings' => array(
                'options' => $mark_options,
                'required'=> false,
                'label' => 'Mark Heads',
                'value' => $this->getMeta('exam_mark_heads', false )
            )
        );

        $new_fields['exam_date'] = array(
            'name'  => 'exam_date',
            'type'  => 'date',
            'settings' => array(
                'label' => 'Exam Date',
                'required' => true,
                'value' => $this->getMeta( 'exam_date' )
            )
        );

        $new_fields['exam_time'] = array(
            'name'  => 'exam_time',
            'type'  => 'time',
            'settings' => array(
                'label' => 'Exam Time',
                'required' => false,
                'value' => $this->getMeta( 'exam_time' )
            )
        );


        $fields['post_title'] = array(
            'type'  => 'hidden',
            'name'  => 'post_title',
            'settings' => array(
                'value' => 'Exam'
            )
        );
        unset($fields['post_content']);

        return $new_fields + $fields;
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
        if( EduPress::isActive('section') ){
            $new_fields['section_id'] = 'Section';
        }
        if( EduPress::isActive('term') ){
            $new_fields['term_id'] = 'Term';
        }
        if( EduPress::isActive('subject') ){
            $new_fields['subject_id'] = 'Subject';
        }

        $new_fields['exam_date'] = 'Exam Date';
        $new_fields['exam_time'] = 'Exam Time';

        return $new_fields;

    }

    /**
     * Filter action html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterActionHtml( $html, $post_id )
    {

        $metadata = get_metadata('post', $post_id);
        $branch_id = $metadata['branch_id'] ? $metadata['branch_id'][0] : 0;
        $class_id = $metadata['class_id'] ? $metadata['class_id'][0] : 0;
        $shift_id = $metadata['shift_id'] ? $metadata['shift_id'][0] : 0;
        $section_id = $metadata['section_id'] ? $metadata['section_id'][0] : 0;
        $subject_id = $metadata['subject_id'] ? $metadata['subject_id'][0] : 0;
        $term_id = $metadata['term_id'] ? $metadata['term_id'][0] : 0;
        $url = is_ssl() ? 'https://' : 'http://';
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $urls = explode('?', $url);
        $url = $urls[0];
        $url .= "?panel=result&branch_id={$branch_id}&shift_id={$shift_id}&class_id={$class_id}&section_id={$section_id}&exam_id={$post_id}&subject_id={$subject_id}&ranking_method=marks&term_id={$term_id}";
        $view_html = " <a title='View Result Analysis' href='{$url}'>".EduPress::getIcon('view')."</a>";

        // Skipping duplicate action link
        if ( str_contains( $html, 'Edit Result') || str_contains( $html, 'View Result') ) return $html . $view_html;


        if( User::currentUserCan( 'edit', $this->post_type ) ){

            $html .= " <a data-ajax_action='getPostEditForm' data-post_type='result' data-before_send_callback='' data-success_callback='resultSuccessCallback' data-error_callback='' href='javascript:void(0)' class='edpupress-show-result-update-form edupress-ajax-link' data-post_id='{$post_id}' data-id='{$post_id}'>".__( 'Edit Result', 'edupress' ). "</a>";

        } else {

            $html .= " <a data-ajax_action='getPostEditForm' data-post_type='result' data-before_send_callback='' data-success_callback='resultSuccessCallback' data-error_callback='' href='javascript:void(0)' class='edpupress-show-result-update-form edupress-ajax-link' data-post_id='{$post_id}' data-id='{$post_id}'>".__( 'View Result', 'edupress' ). "</a>";

        }

        return $html;
        
    }

    /**
     * Filter Search Fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterSearchFields( $fields )
    {

        $fields = [];
        $fields['exam_date'] = array(
            'name'  => 'exam_date',
            'type'  => 'date',
            'settings' => array(
                'value' => sanitize_text_field($_REQUEST['exam_date'] ?? ''),
                'label' => 'Exam Date'
            )
        );

        if( EduPress::isActive('branch') ){
            $branch = new Branch();
            $fields['branch_id'] = array(
                'name'  => 'branch_id',
                'type'  => 'select',
                'settings' => array(
                    'options' => $branch->getPosts(  [], true ),
                    'value' => sanitize_text_field($_REQUEST['branch_id'] ?? ''),
                    'label' => 'Branch',
                    'placeholder' => 'Select a branch',
                )
            );
        }
        if( EduPress::isActive('shift') ){
            $fields['shift_id'] = array(
                'name'  => 'shift_id',
                'type'  => 'select',
                'settings' => array(
                    'options' => [],
                    'value' => sanitize_text_field($_REQUEST['shift_id'] ?? ''),
                    'label' => 'Shift',
                    'placeholder' => 'Select a shift',
                )
            );
        }
        if( EduPress::isActive('class') ){
            $fields['class_id'] = array(
                'name'  => 'class_id',
                'type'  => 'select',
                'settings' => array(
                    'options' => [],
                    'value' => sanitize_text_field($_REQUEST['class_id'] ?? ''),
                    'label' => 'Class',
                    'placeholder' => 'Select a class',
                )
            );
        }
        if( EduPress::isActive('section') ){
            $fields['section_id'] = array(
                'name'  => 'section_id',
                'type'  => 'select',
                'settings' => array(
                    'options' => [],
                    'value' => sanitize_text_field($_REQUEST['section_id'] ?? ''),
                    'label' => 'Section',
                    'placeholder' => 'Select a section',
                )
            );
        }

        return $fields;

    }

}

Exam::instance();