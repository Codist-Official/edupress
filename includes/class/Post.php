<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();
class Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var string $post_type
     */
    protected $post_type = 'post';

    /**
     * @var $post
     */
    protected $post;

    /**
     * @param array $metadata
     */
    protected $metadata;

    /**
     * @param string $list_title
     */
    protected $list_title = '';

    /**
     * @param int $posts_per_page
     */
    protected $posts_per_page = 10;

    /**
     * Checking if draggable or not
     */
    protected $draggable = false;

    /**
     * Initialize instance
     *
     * @retrun Post
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Constructor
     * 
     * @param int $id
     * 
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {

        $this->id = $id;

        $this->posts_per_page = isset($_REQUEST['posts_per_page']) && intval($_REQUEST['posts_per_page']) ? intval($_REQUEST['posts_per_page']) : Admin::getSetting('display_posts_per_page');
        if(empty($this->posts_per_page)) $this->posts_per_page = 20;

        if ( is_numeric( $this->id ) ){

            $this->setPost( get_post( $this->id ) );

        } else if ( $id instanceof \WP_Post ) {

            $this->id = $id->ID;
            $this->setPost( $id );
        }

        if( $this->post ) $this->setMetadata( get_metadata( 'post', $this->id ) );

    }

    /**
     * @return string
     */
    public function getPostType(): string
    {
        return $this->post_type;
    }

    /**
     * @param string $post_type
     * 
     * @since 1.0
     * @access public
     */
    public function setPostType(string $post_type): void
    {
        $this->post_type = $post_type;
    }

    /**
     * @return \WP_Post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param \WP_Post $post
     * 
     * @since 1.0
     * @access public
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed $metadata
     * 
     * @since 1.0
     * @access public
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get post meta
     *
     * @param string $key
     * @pararm boolean $single
     *
     * @return string | int | mixed
     * @since 1.0
     * @access public
     */
    public function getMeta( $key = '', $single = true )
    {

        $metadata = $this->getMetadata();
        if ( isset( $metadata[$key]) ) return $single ? $metadata[$key][0] : maybe_unserialize( $metadata[$key] );
        return null;

    }

    /**
     * Update post meta
     *
     * @param string $key
     * @param string $value
     * @param string $prev_value
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public function updateMeta( $key, $value, $prev_value = '' )
    {

        $update = update_post_meta( $this->id, $key, $value, $prev_value );

        $metadata= $this->getMetadata();

        $metadata[$key] = get_post_meta( $this->id, $key, false );

        $this->setMetadata( $metadata );

        return $update;

    }

    /**
     * Delete meta
     *
     * @param string $key
     * @param string $prev_value
     *
     * @since 1.0
     * @access public
     */
    public function deleteMeta( $key, $prev_value = '' )
    {

        $delete = delete_post_meta( $this->id, $key, $prev_value );

        $metadata = $this->getMetadata();

        $metadata[$key] = get_post_meta( $this->id, $key, false );

        $this->setMetadata( $metadata );

        return $delete;

    }

    /**
     * Publish a post
     *
     * @return int
     *
     * @since 1.0
     * @access public
     */
    public function publish( $args = [] )
    {

        $args['post_type'] = $this->post_type;
        $args['post_status'] = $args['post_status'] ?? 'publish';
        $args['post_author'] = $args['post_author'] ?? get_current_user_id();
        $args['post_title'] = isset($args['post_title']) ? sanitize_text_field($args['post_title']) : '';
        if(!empty($args['post_content'])) $args['post_content'] = wp_kses_post($args['post_content']);

        $insert = wp_insert_post( $args );

        if ( !$insert || is_wp_error($insert) ) {
            EduPress::logData('insert error');
            EduPress::logData( $insert );
            return $insert->get_error_message();
        }

        $skip_fields = apply_filters( "edupress_publish_{$this->post_type}_skip_fields", self::skipFieldsForMetadata() );

        foreach( $args as $k=>$v ){
            if ( in_array( $k, $skip_fields ) ) continue;
            add_post_meta( $insert, $k, $v );
        }

        if( !isset( $args['status'] ) ) add_post_meta( $insert, 'status', 'Active' );
        return $insert;

    }

    /**
     * Skip meta fields for metadata
     *
     * @return array
     *
     * @since 1.0
     * @aceess public
     */
    public static function skipFieldsForMetadata ()
    {

        return array('post_type', 'post_status', 'post_author', 'post_title','ID', 'post_content', 'action', 'ajax_action', '_wpnonce', '_wp_http_referer', 'post_type', 'before_send_callback','success_callback', 'error_callback' );

    }

    /**
     * Get fields for publish
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getPublishFields()
    {

        $fields = array(
            'post_title' => array(
                'type'  => 'text',
                'name'  => 'post_title',
                'settings'=> array(
                    'label'     => ucwords(str_replace( '_', ' ' , $this->post_type ) . ' name'),
                    'required' => true,
                    'id' => 'post_title'
                )
            ),
            'post_content' => array(
                'type'  => 'textarea',
                'name'  => 'post_content',
                'settings'=> array(
                    'label'     => 'Description',
                    'required'  => false,
                    'id' => 'post_content'
                )
            ),
            'submit'    => array(
                'type'  => 'submit',
                'name' => 'submit',
                'settings'=> array(
                    'value' => 'Submit'
                )
            )
        );

        return apply_filters( "edupress_publish_{$this->post_type}_fields", $fields );

    }

    /**
     * Get form to publish a $this post type
     *
     * @return string
     * @since 1.0
     * @access public
     */
    public function getForm( $action, $wrap = true )
    {

        $fields = $action == 'publish' ? $this->getPublishFields() : $this->getEditFields();

        if (empty($fields)) return '';

        ob_start();
        ?>
        <form action="" method="post" class="<?php echo EduPress::getClassNames( array("edupress-{$action}-{$this->post_type}", "edupress-{$action}-post-form" ), 'form' ); ?>">

            <?php
            foreach( $fields as $k => $field ){

                if(!isset($field['settings']['id'])) $field['settings']['id'] = $field['name'];

                if( $field['type'] == 'submit' ) continue;

                if ( $field['type'] == 'hidden' ) {

                    echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] );
                    continue;

                }

                $label = $field['settings']['label'] ?? '';
                ?>

                <div class="form-row <?php echo $field['name'] ?? ''; ?>">
                    <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e( $label, 'edupress' ); ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                </div>

                <?php
            }
            echo apply_filters("edupress_{$action}_{$this->post_type}_before_submit_html", "", $this->id );
            ?>
            <div class="form-row">
                <div class="label-wrap"></div>
                <div class="value-wrap">
                    <?php
                        echo EduPress::generateFormElement( 'submit', 'submit', array( 'value' => $action == 'edit' ? 'Update' : 'Publish' ) );
                        echo EduPress::generateFormElement( 'hidden', 'action', array( 'value' => 'edupress_admin_ajax' ) );
                        echo EduPress::generateFormElement( 'hidden', 'ajax_action', array( 'value' => $action.'Post' ) );
                        echo EduPress::generateFormElement( 'hidden', 'post_type', array( 'value' => $this->post_type ) );
                        echo EduPress::generateFormElement( 'hidden', 'post_author', array( 'value' => get_current_user_id() ) );
                        if( $action != 'publish' ){
                            echo EduPress::generateFormElement( 'hidden', 'post_id', array( 'value' => $this->id ) );
                        }

                        $before_send_callback = "{$this->post_type}BeforeSendCallback";
                        $success_callback = "{$this->post_type}SuccessCallback";
                        $error_callback = "{$this->post_type}ErrorCallback";

                        // this elements are for js further action
                        $before_send_callback = apply_filters( "edupress_{$action}_{$this->post_type}_before_send_callback", $before_send_callback );
                        $success_callback = apply_filters( "edupress_{$action}{$this->post_type}_success_callback", $success_callback );
                        $error_callback = apply_filters( "edupress_{$action}{$this->post_type}_error_callback", $error_callback );

                        if( !empty($before_send_callback) ):
                            echo EduPress::generateFormElement( 'hidden', 'before_send_callback', array( 'value' => $before_send_callback ) );
                        endif;

                        if( !empty($success_callback) ):
                            echo EduPress::generateFormElement( 'hidden', 'success_callback', array( 'value' => $success_callback ) );
                        endif;

                        if( !empty($error_callback) ):
                            echo EduPress::generateFormElement( 'hidden', 'error_callback', array( 'value' => $error_callback ) );
                        endif;
                        wp_nonce_field( 'edupress' );

                    ?>
                </div>
            </div>
        </form>

        <?php
        $html = ob_get_clean();

        $html = $action == 'publish' ? apply_filters( "edupress_publish_{$this->post_type}_form_html", $html ) : apply_filters( 'edupress_edit_{$this->post_type}_form_html', $html );

        if( !$wrap ) return $html;

        $title = $action == 'publish' ? "Publish New {$this->post_type}" : "Update {$this->post_type}";

        return EduPress::wrapInContentBox( ucwords($title) , $html );

    }

    /**
     * Return posts as WP_Query returns
     *
     * @return \WP_Query | array
     *
     * @since 1.0
     * @access public
     */
    public function getPosts( $args = [], $only_id_title = false )
    {
        $args['post_type'] = $this->post_type;
        $args['post_status']  = $args['post_status'] ?? 'publish';
        $args['posts_per_page'] = $args['posts_per_page'] ?? -1;
        $args['orderby'] = $args['orderby'] ?? 'title';
        $args['order'] = $args['order'] ?? 'ASC';

        $qry = new \WP_Query($args);

        if( !$only_id_title ) {
            wp_reset_postdata();
            return $qry;
        }

        if ( !$qry->have_posts() ){
            wp_reset_postdata();
            return [];
        }

        $response = [];

        if($qry->have_posts()){
            while( $qry->have_posts() ){
                $qry->the_post();
                $response[$qry->post->ID] = $qry->post->post_title;
            }
            wp_reset_postdata();
        }
        return $response;
    }

    /**
     * Get Edit fields of a form
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getEditFields()
    {
        $fields = array(
            'post_title' => array(
                'type'  => 'text',
                'name'  => 'post_title',
                'settings'=> array(
                    'label'     => ucwords( str_replace('_', ' ', $this->post_type ) ),
                    'required' => true,
                    'value'     => $this->getPost()->post_title,
                    'id'        => 'post_title'
                )
            ),
            'post_content' => array(
                'type'  => 'textarea',
                'name'  => 'post_content',
                'settings'=> array(
                    'label'     => 'Description',
                    'required'  => false,
                    'value'     => $this->getPost()->post_content,
                    'id'        => 'post_content',
                )
            ),
            'status'    => array(
                'type'  => 'select',
                'name'  => 'status',
                'settings' => array(
                    'label' => 'Status',
                    'value' => $this->getMeta('status'),
                    'options' => array('Active'=>'Active','Inactive'=>'Inactive')
                )
            ),
            'submit'    => array(
                'type'  => 'submit',
                'name' => '',
                'settings'=> array(
                    'value' => 'Update'
                )
            )
        );

        return apply_filters( "edupress_edit_{$this->post_type}_fields", $fields, $this->id );

    }

    /**
     * Edit a post
     *
     * @return int | boolean
     *
     * @param array $data
     *
     * @since 1.0
     * @access public
     */
    public function edit( $data = [] )
    {

        if ( !$this->id ) return false;

        $args = [];
        $args['ID'] = $this->id;
        if( isset($data['post_title']) ) $args['post_title'] = $data['post_title'];
        if( isset($data['post_content']) ) $args['post_content'] = $data['post_content'];
        if( isset($data['post_author']) ) $args['post_author'] = $data['post_author'];

        $update = wp_update_post( $args );

        $skip_fields = array( 'ID', 'post_id', 'post_title', 'post_content', 'post_author', 'action', 'ajax_action', '_wpnonce', '_wp_http_referrer', 'post_type' );
        $skip_fields = apply_filters( "edupress_edit_{$this->post_type}_skip_fields ", $skip_fields );

        $allow_array_fields = [ 'grade_data' ];

        foreach( $data as $k => $v ) {

            if ( in_array( $k, $skip_fields ) ) continue;

            if( !in_array( $k, $allow_array_fields ) && is_array($v) ) {

                delete_post_meta( $this->id, $k );
                foreach( $v as $item_v ){

                    add_post_meta( $this->id, $k, $item_v );

                }

            } else {

                update_post_meta( $this->id, $k, $v );

            }

        }

        return $update;

    }

    /**
     * Delete a post
     *
     * @retuen boolean | \WP_Error
     *
     * @since 1.0
     * @access public
     */
    public function delete()
    {

        if ( !$this->id ) return false;
        return wp_delete_post( $this->id );

    }



    /**
     * Fields for filtering posts
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getFilterFields()
    {

        $fields = [];
        $fields['name'] = array(
            'type'  => 'text',
            'name'  => 'post_title',
            'settings'  => array(
                'label' => 'Name',
                'value' => isset($_REQUEST['post_title']) ? sanitize_text_field($_REQUEST['post_title']) : '',
                'id'    => 'post_title'
            )
        );

        return apply_filters( "edupress_filter_{$this->post_type}_fields", $fields );

    }

    /**
     * Get Filter form
     *
     * @return string
     *
     * @since 1.0
     * @aceess public
     */
    public function getFilterForm()
    {

        $fields = $this->getFilterFields();

        if(empty($fields)) return '';

        ob_start();
        echo apply_filters( "edupress_filter_{$this->post_type}_before_form_html", '' );
        ?>
        <div class="edupress-filter-list-wrap" data-post_type="<?php echo $this->post_type; ?>">
            <form data-post_type="<?php echo $this->post_type; ?>" action="" method="GET" class="edupress-form edupress-filter-list">

                <?php

                    $hidden_fields = [];
                    foreach ($fields as $field) {

                        if(!isset($field['settings']['id'])) $field['settings']['id'] = $field['name'];

                        if( $field['type'] === 'submit' ) continue;
                        if( $field['type'] === 'hidden' ) {
                            $hidden_fields[] = EduPress::generateFormElement($field['type'], $field['name'], $field['settings']);
                            continue;
                        }

                    ?>
                    <div class="form-column" data-name="<?php echo $field['name'] ?? ''; ?>">
                        <div class="label-wrap"><label for="<?php echo $field['settings']['id'] ?? ''; ?>"><?php _e($field['settings']['label'] ?? '', 'edupress'); ?></label></div>
                        <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                    </div>
                <?php } ?>

                <div class="form-column" data-name="submit">
                    <div class="label-wrap"> &nbsp; </div>
                    <div class="value-wrap">
                        <?php
                            echo implode( ' ', $hidden_fields ) ;
                            echo EduPress::generateFormElement( 'submit', '', array('value'=>'Filter'));
                            echo EduPress::generateFormElement( 'hidden', 'panel', array('value'=>$this->post_type));
                        ?>
                    </div>
                </div>

            </form>
        </div>

        <?php
        echo apply_filters( "edupress_filter_{$this->post_type}_after_form_html", '' );
        $html = ob_get_clean();

        return apply_filters( "edupress_filter_{$this->post_type}_form_html", $html );


    }

    /**
     * Get list query
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getListQuery($args = [])
    {

        $args['post_type'] = $this->post_type;

        $args['orderby'] = 'ID';

        $args['order'] = 'DESC';

        $args['posts_per_page'] = $this->posts_per_page;

        if( !isset($args['post_status']) ){
            $args['post_status'] = 'publish';
        }

        if(!empty($_REQUEST['post_title'])){
            $args['s'] = sanitize_text_field($_REQUEST['post_title']);
        }

        $paged = max( get_query_var('paged'), 1 );
        $page = max( get_query_var('page'), 1 );
        $args['paged'] =  max($paged, $page);

        $fields = $this->getFilterFields();
        $skip_fields = array('id', 'post_type', 'post_title', 'post_content', 'post_author' );

        $meta_query = [];

        if(!empty($fields)){

            foreach($fields as $k=>$v){

                $name = $v['name'];
                if( in_array( $name, $skip_fields ) || empty($_REQUEST[$name]) ) continue;

                if( isset($_REQUEST[$name]) ){
                    $meta_query[] = array(
                        'key'    => $name,
                        'value' => sanitize_text_field($_REQUEST[$name]),
                        'compare' => '='
                    );
                }

            }

        }

        if( count($meta_query) > 1 ) $meta_query['relation'] = 'AND';
        if( count($meta_query) > 0 ) $args['meta_query'] = $meta_query;

        return apply_filters( "edupress_list_{$this->post_type}_query", $args );

    }

    /**
     * Get list fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function getListFields()
    {

        $fields = [];
        $fields['post_title'] = ucwords( str_replace('_', ' ', $this->post_type ) );
        $fields['post_content'] = 'Description';
        $fields['status'] = 'Status';
        return apply_filters( "edupress_list_{$this->post_type}_fields", $fields );

    }

    /**
     * Return publish new button
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getPublishButton()
    {

        if( !User::currentUserCan('publish',  $this->post_type ) ) return '';
        ob_start();
        ?>
        <div class="edupress-publish-btn-wrap">
            <button data-post_type="<?php echo $this->post_type; ?>" class="edupress-btn edupress-publish-post"><?php _e( 'Add New ' . ucwords( str_replace( '_', ' ', $this->post_type ) ), 'edupress' ); ?></button>
        </div>

        <?php
        $html = ob_get_clean();
        return apply_filters( "edupress_publish_{$this->post_type}_button_html" , $html );

    }

    /**
     * Get list html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {

        $qry = $this->getPosts( $this->getListQuery() );

        if( !$qry->have_posts() ){
            $html = __( "No ". ucwords(str_replace('_', ' ', $this->post_type ) ) ." found!", 'edupress' );
            return apply_filters( "edupress_list_{$this->post_type}_html", $html );
        }

        $found_posts = (int) $qry->found_posts;
        $posts_per_page = (int) $this->getListQuery()['posts_per_page'] ?? 10;
        if(!$posts_per_page) $posts_per_page = 10;

        $total_pages = ceil( $found_posts / $posts_per_page );

        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="<?php echo $this->draggable ? 'draggable' : ''; ?> edupress-table edupress-master-table edupress-post-list tablesorter" data-post_type="<?php echo $this->post_type; ?>">
                <thead>
                    <tr>
                        <?php if( User::currentUserCan('delete', $this->post_type ) ): ?>
                            <th class="no-print">
                                <input id="select_all" title="Select All" type="checkbox"  class="edupress-bulk-select-all" data-post_type="<?php echo $this->post_type; ?>">
                                <label for="select_all"><?php _e( 'All', 'edupress' ); ?></label>
                                <a style="float: right" href="javascript:void(0)" class="edupress-bulk-delete" data-post_type="<?php echo $this->post_type;?>"><?php echo EduPress::getIcon('delete'); ?></a>
                            </th>
                        <?php endif; ?>

                        <?php foreach($this->getListFields() as $k=>$v) : ?>
                            <th><?php echo $v; ?></th>
                        <?php endforeach; ?>

                        <?php if( $this->post_type == 'exam' || User::currentUserCan('edit', $this->post_type )): ?>
                            <th class="no-print">Action</th>
                        <?php endif; ?>

                    </tr>
                </thead>
                <tbody>
                <?php $filterable_strings = array( '_id', '_date', '_data', '_time', 'total', 'calendar', 'view_action'); ?>
                <?php while ( $qry->have_posts() ) {?>
                    <?php $qry->the_post(); ?>
                    <?php $this->id = $qry->post->ID; ?>
                    <?php $this->setPost($qry->post); ?>
                    <?php $this->setMetadata( get_metadata('post', $qry->post->ID) ); ?>
                    <tr draggable="<?php echo $this->draggable? 'true' : 'false'; ?>"  data-post_type="<?php echo $this->post_type; ?>"  data-post-id="<?php echo $this->id; ?>" data-id="<?php echo $this->id; ?>">

                        <?php
                        if( User::currentUserCan( 'delete', $this->post_type ) ){
                            ?>
                            <td class="no-print">
                                <input id="id_<?php echo $this->id; ?>" type="checkbox" class="edupress-bulk-select-item" name="edupress-bulk-delete-post[]" data-id="<?php echo $this->id; ?>" data-post_type="<?php echo $this->post_type; ?>">
                                <label for="id_<?php echo $this->id; ?>"><?php echo $this->id; ?></label>
                            </td>
                            <?php
                        }
                        foreach($this->getListFields() as $k=>$v){
                            $value = '';
                            if($k == 'id' ){
                                $value = $this->id;
                            } else if ( str_contains( $k, 'post_') ){
                                $value = $this->getPost()->$k;
                            } else {
                                $value = $this->getMeta($k);
                            }
                            foreach($filterable_strings as $string){
                                if(str_contains( $k, $string) ){
                                    $value = apply_filters('edupress_list_item_value', $value, $k, $this->post );
                                }
                            }
                            ?>
                            <td><?php echo $value; ?></td>
                            <?php
                        }
                        ?>

                        <?php if( $this->post_type == 'exam' || User::currentUserCan('edit', $this->post_type) ) : ?>

                            <?php $edit_action_html = ''; ?>

                            <td class="no-print">
                                <?php

                                    if( User::currentUserCan( 'edit', $this->post_type ) ) {
                                        $edit_action_html .= "<a data-target='popup' class='edupress-edit-post' data-action='edit' data-post_type='{$this->post_type}' data-id='{$this->id}' href='javascript:void(0)'>". EduPress::getIcon('update') ."</a> ";
                                    }

                                    if( User::currentUserCan( 'delete', $this->post_type ) ) {
                                        $edit_action_html .= " <a data-target='status' class='edupress-delete-post' data-action='delete' data-post_type='{$this->post_type}'  data-id='{$this->id}'  href='javascript:void(0)'>". EduPress::getIcon('delete') ."</a>";
                                    }

                                    echo apply_filters( "edupress_list_{$this->post_type}_action_html", $edit_action_html, $this->id );

                                ?>

                            </td>

                        <?php endif; ?>
                    </tr>
                <?php }
                wp_reset_postdata();
                ?>
                </tbody>
            </table>
        </div>
        <?php
        echo EduPress::getPagination($total_pages);

        $html = ob_get_clean();

        return apply_filters( "edupress_list_{$this->post_type}_html", $html );

    }

    /**
     * Get list
     * Showing posts in table
     *
     * @return string
     *
     * @param array $settings
     *
     * @since 1.0
     * @access public
     */
    public function getList( $settings = [] )
    {

        if( !User::currentUserCan('read', $this->post_type) ) return User::getCapabilityErrorMsg('see', $this->post_type . ' entries.' );

        ob_start();
        echo apply_filters( "edupress_list_{$this->post_type}_filter_form_before_html", '' );
        echo $this->getFilterForm();
        echo apply_filters( "edupress_list_{$this->post_type}_filter_form_after_html", '' );
        echo $this->getPublishButton();
        ?>
        <div>
            <div class="edupress-before-list-wrap">
                <?php echo $this->getBeforeListHtml(); ?>
            </div>
        </div>
        <?php echo $this->getListHtml(); ?>
        <div>
            <div class="edupress-after-list-wrap">
                <?php echo $this->getAfterListHtml(); ?>
            </div>
        </div>
        <div class="clear"></div>
        <?php
        return ob_get_clean();

    }

    /**
     * Get content for before list table
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getBeforeListHtml()
    {
        ob_start();

        if(!empty($this->list_title)): ?>
            <h2 class="master-title"><?php _e( $this->list_title, 'edupress' ); ?></h2>
        <?php endif;

        $params = array(
            'branch_id'  => 'Branch',
            'class_id'  => 'Class',
            'shift_id'  => 'Shift',
            'section_id'  => 'Section',
            'term_id'  => 'Term',
            'subject_id'  => 'Subject',
            'first_name'  => 'Name',
            'roll'   => 'Roll/ID',
            'start_date' => 'Start Date',
            'end_date'  => 'End Date',
            'exam_date' => 'Exam Date',
            'post_title' => 'Search'
        );
        if(count($_REQUEST)){
            ?>
            <div class="edupress-table-wrap">
                <table class="edupress-table">
                    <tr>
                    <?php foreach($params as $k=>$v): ?>
                        <?php if(isset($_REQUEST[$k]) && !empty($_REQUEST[$k])) : ?>
                            <?php
                                $value = '';
                                if(str_contains($k, '_id')){
                                    $value =  get_the_title(sanitize_text_field($_REQUEST[$k]));
                                } else if ( str_contains($k, '_date')) {
                                    $value = date('d/m/y', strtotime(sanitize_text_field($_REQUEST[$k])));
                                } else {
                                    $value = $_REQUEST[$k];
                                }
                            ?>
                            <td style="text-align: left;"><strong><?php _e($v, 'edupress'); ?>: </strong> <?php echo $value; ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tr>
                </table>
            </div>
            <?php
        }

        $html = ob_get_clean();
        return apply_filters( "edupress_list_{$this->post_type}_before_html", $html, $this->id );
    }

    /**
     * Get content for before list table
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getAfterListHtml()
    {
        $html = '';
        return apply_filters( "edupress_list_{$this->post_type}_after_html", $html );

    }
}

Post::instance();