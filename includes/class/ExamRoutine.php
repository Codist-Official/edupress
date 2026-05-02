<?php
namespace EduPress;

defined( 'ABSPATH' ) || die ();

class ExamRoutine extends Post
{
    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'exam_routine';

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

        // Register exam_routine post type
        add_action( 'init', [ $this, 'registerExamRoutine' ] );

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
    public function registerExamRoutine()
    {

        if( EduPress::isActive('exam_routine') ){
            register_post_type('exam_routine',
                array(
                    'labels' => array(
                        'name' => __( 'Exam Routines','edupress' ),
                        'singular_name' => __( 'Exam Routine','edupress' ),
                        'add_item' => __('New Exam Routine','edupress'),
                        'add_new_item' => __('Add New Exam Routine','edupress'),
                        'edit_item' => __('Edit Exam Routine','edupress')
                    ),
                    'public' => false,
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'exam-routine'),
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

        if( EduPress::isActive('branch') ){
            $branch = new Branch();
            $new_fields['branch_id'] = array(
                'name'  => 'branch_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Branch'),
                    'options'=> $branch->getPosts( [], true ),
                    'placeholder'=> t('Select a branch'),
                    'required' => true,
                )
            );
        }

        if( EduPress::isActive('shift') ){
            $new_fields['shift_id'] = array(
                'name'  => 'shift_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Shift'),
                    'options'=> [],
                    'placeholder'=> t('Select a shift'),
                    'required' => true,
                )
            );
        }

        if( EduPress::isActive('class') ){
            $new_fields['class_id'] = array(
                'name'  => 'class_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Class'),
                    'options'=> [],
                    'placeholder'=> t('Select a class'),
                    'required' => true,
                )
            );
        }

        if( EduPress::isActive('section') ){

            $new_fields['section_id'] = array(
                'name'  => 'section_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Section'),
                    'options'=> [],
                    'placeholder'=> t('Select a section'),
                    'required' => true,
                )
            );

        }

        $term = new Term();
        $new_fields['term_id'] = array(
            'name'  => 'term_id',
            'type'  => 'select',
            'settings' => array(
                'label' => t('Term'),
                'options'=> $term->getPosts( [], true ),
                'placeholder'=> t('Select a term'),
                'required' => true,
            )
        );

        $subject = new Subject();
        $subjects = $subject->getPosts([], true);
        
        $html = "<div class='exam-routine-row' style='width:100%;display:inline-block;margin-bottom:5px;'>";
        $html .= "<div style='float:left; padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('select', 'subject[]', ['options'=>$subjects, 'placeholder'=>t('Select a subject'), 'required'=>true] ) . "</div>";
        $html .= "<div style='float:left;padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('datetime-local', 'date[]', ['options'=>$subjects, 'placeholder'=>t('Select a date'), 'required' => true] ) . "</div>";
        $html .= "
                <div style='width:60px;float:left;'>
                    <a href='javascript:void(0)' data-action='copy' class='exam_routine_btn ep_tag_btn'>+</a>
                    <a href='javascript:void(0)' data-action='delete' class='exam_routine_btn ep_tag_btn'>-</a>
                </div>";
        $html .= "</div>";

        $new_fields['subject_date'] = array(
            'name' => 'subject_date',
            'type' => 'html',
            'settings' => array(
                'label' => t('Exams'),
                'html' => $html,
            )
        );

        $fields['post_title']['type'] = 'hidden';
        $fields['post_title']['settings']['value'] = uniqid();
        $fields['post_content']['type'] = 'hidden';
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
        unset($fields['post_title']);
        unset($fields['post_content']);

        $new_fields = [];

        if( EduPress::isActive('branch') ) $new_fields['branch_id'] = t('Branch');
        if( EduPress::isActive('shift') ) $new_fields['shift_id'] = t('Shift');
        if( EduPress::isActive('class') ) $new_fields['class_id'] = t('Class');
        if( EduPress::isActive('section') ) $new_fields['section_id'] = t('Section');
        $new_fields['term_id'] = t('Term');

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
            $new_fields['branch_id'] = array(
                'name'  => 'branch_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Branch'),
                    'options'=> $branch->getPosts( [], true ),
                    'placeholder'=> t('Select a branch'),
                    'required' => true,
                    'value' => $this->getMeta('branch_id')
                )
            );
        }

        if( EduPress::isActive('shift') ){
            $shift  = new Shift();
            $new_fields['shift_id'] = array(
                'name'  => 'shift_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Shift'),
                    'options'=> $shift->getPosts([], true),
                    'placeholder'=> t('Select a shift'),
                    'required' => true,
                    'value' => $this->getMeta('shift_id')
                )
            );
        }

        if( EduPress::isActive('class') ){
            $klass = new Klass();
            $new_fields['class_id'] = array(
                'name'  => 'class_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Class'),
                    'options'=> $klass->getPosts([], true),
                    'placeholder'=> t('Select a class'),
                    'required' => true,
                    'value' => $this->getMeta('class_id')
                )
            );
        }

        if( EduPress::isActive('section') ){
            $section = new Section();
            $new_fields['section_id'] = array(
                'name'  => 'section_id',
                'type'  => 'select',
                'settings' => array(
                    'label' => t('Section'),
                    'options'=> $section->getPosts([], true),
                    'placeholder'=> t('Select a section'),
                    'required' => true,
                    'value' => $this->getMeta('section_id')
                )
            );

        }

        $term = new Term();
        $new_fields['term_id'] = array(
            'name'  => 'term_id',
            'type'  => 'select',
            'settings' => array(
                'label' => t('Term'),
                'options'=> $term->getPosts( [], true ),
                'placeholder'=> t('Select a term'),
                'required' => true,
                'value' => $this->getMeta('term_id')
            )
        );

        $subject = new Subject();
        $subjects = $subject->getPosts([], true);

        $html = '';

        if($this->id){

            $exams = maybe_unserialize($this->getMeta('exams'));
            if(!empty($exams)){
                foreach($exams as $k){
                    $html .= "<div class='exam-routine-row' style='width:100%;display:inline-block;margin-bottom:5px;'>";
                    $html .= "<div style='float:left; padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('select', 'subject[]', ['options'=>$subjects, 'placeholder'=>t('Select a subject'), 'required'=>true, 'value'=>$k['subject']] ) . "</div>";
                    $html .= "<div style='float:left;padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('datetime-local', 'date[]', ['placeholder'=>t('Select a date'), 'required' => true, 'value'=>$k['date']] ) . "</div>";
                    $html .= "
                            <div style='width:60px;float:left;'>
                                <a href='javascript:void(0)' data-action='copy' class='exam_routine_btn ep-tag-btn'>+</a>
                                <a href='javascript:void(0)' data-action='delete' class='exam_routine_btn ep-tag-btn'>-</a>
                            </div>";
                    $html .= "</div>";        
                }
            }

    
        } else {

            $html = "<div class='exam-routine-row' style='width:100%;display:inline-block;margin-bottom:5px;'>";
            $html .= "<div style='float:left; padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('select', 'subject[]', ['options'=>$subjects, 'placeholder'=>t('Select a subject'), 'required'=>true] ) . "</div>";
            $html .= "<div style='float:left;padding-right:10px;box-sizing:border-box;'>".EduPress::generateFormElement('datetime-local', 'date[]', ['options'=>$subjects, 'placeholder'=>t('Select a date'), 'required' => true] ) . "</div>";
            $html .= "
                    <div style='width:60px;float:left;'>
                        <a href='javascript:void(0)' data-action='copy' class='exam_routine_btn ep-tag-btn'>+</a>
                        <a href='javascript:void(0)' data-action='delete' class='exam_routine_btn ep-tag-btn'>-</a>
                    </div>";
            $html .= "</div>";
    
        }
        
        $new_fields['subject_date'] = array(
            'name' => 'subject_date',
            'type' => 'html',
            'settings' => array(
                'label' => t('Exams'),
                'html' => $html,
            )
        );

        $fields['post_title']['type'] = 'hidden';
        $fields['post_title']['settings']['value'] = uniqid();
        $fields['post_content']['type'] = 'hidden';
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


    public function publish($args = [])
    {
        $meta = [];
        $meta['branch_id'] = $args['branch_id'] ?? null; 
        $meta['shift_id'] = $args['shift_id'] ?? null;
        $meta['class_id'] = $args['class_id'] ?? null; 
        $meta['section_id'] = $args['section_id'] ?? null;
        $meta['term_id'] = $args['term_id'] ?? null; 
        $post_title = $args['post_title'] ?? null; 

        if(isset($args['subject'])){
            $exams = [];
            for($i = 0; $i < count($args['subject']); $i++ ){
                $exams[] = ['subject' => $args['subject'][$i], 'date'=>$args['date'][$i]];
            }
        }

        if(!isset($args['post_id']) || $args['post_id'] == 0){
            $post = wp_insert_post([
                'post_title' => $post_title,
                'post_status' => 'publish',
                'post_type' => $this->post_type,
                'post_content' => ''
            ]);
        } else {
            $post = $args['post_id'];
        }
        

        if($post){
            foreach($meta as $k=>$v){
                update_post_meta($post, $k, $v);
            }
            update_post_meta($post, 'exams', $exams);
        }
        return $post;
    }

    public function edit($args=[])
    {
        return $this->publish($args);
    }

    public static function getAdmitCardHTML($args = [])
    {

        // Getting routine first 
        $meta_args = [];


        $user_args = [];
        if(EduPress::isActive('branch') && isset($args['branch_id'])) {
            $meta_args['branch_id'] = $args['branch_id'];
            $user_args['branch_id'] = $args['branch_id'];
        }
        if(EduPress::isActive('shift') && isset($args['shift_id'])){
            $meta_args['shift_id'] = $args['shift_id'];
            $user_args['shift_id'] = $args['shift_id'];
        }
        if(EduPress::isActive('class') && isset($args['class_id'])) {
            $meta_args['class_id'] = $args['class_id'];
            $user_args['class_id'] = $args['class_id'];
        }
        if(EduPress::isActive('section') && isset($args['section_id'])) {
            $meta_args['section_id'] = $args['section_id'];
            $user_args['section_id'] = $args['section_id'];
        }

        $meta_query = [];
        foreach($post_args as $k=>$v){
            if(empty($v)) continue; 
            $meta_query[] = [
                'key' => $k,
                'value' => $v,
                'compare' => '='
            ];
        }

        $post_args = [
            'post_type' => 'exam_routine',
            'post_status' => 'publish',
        ];
        if(!empty($meta_query)) $post_args['meta_query'] = $meta_args;

        $posts = get_posts($post_args);
        if(empty($posts)) return t('No routine found');

        if(count($meta_query) > 1) $meta_query['relation'] = 'AND';

        $routine = reset($posts);
        $exams = maybe_unserialize(get_post_meta($routine->ID, 'exams', true));
    

        $user_args['role'] = 'student';

        $columns = $args['column'] ?? 2;
        $gap = 10 * $columns;



        $users = User::getAll($user_args);
        if(empty($users)) return;
        $logo = Admin::getSetting('institute_logo_id');
        $name = Admin::getSetting('institute_name');
        $address = Admin::getSetting('institute_address');
        $term = get_the_title($_REQUEST['term_id']);


        
        ob_start();
        ?>
        <link rel="stylesheet" media="all" type="text/css" href="<?php echo EDUPRESS_CSS_URL; ?>edupress.css">
        <style>
            
            .admitCardsGrid{
                display: grid;
                grid-template-columns: repeat( <?php echo $columns; ?>,1fr);
                gap: <?php echo $gap; ?>px;
            }
            .admitCardWrap{
                padding: 10px;
                border: 3px solid #000;
                break-inside: avoid;
                page-break-inside: avoid;   /* older browsers */
                -webkit-column-break-inside: avoid; /* Chrome/Safari */
                overflow: hidden;

            }
            .headerCenterContent{
                text-align: center;
            }
            .admitCardLogo{
                width: 100%;
                max-width: 75px;
                height: auto;
            }
            .institute-title{
                font-size: 20px;
                font-weight: 700;
            }
            .institute-address{
                font-size: 12px;
                line-height: 1;
            }
            .term-title{
                font-weight: bold;
            }
            .admit-card-title{
                margin-top: 10px;
                font-size: 16px;
                font-weight: 900;
            }
            .student-details{
                display: grid; 
                grid-template-columns: repeat(3,1fr);
                gap: 10px;
                font-size: 15px;
                margin-top: 20px;
                line-height: 1;
            }
            .student-data-label{
                font-weight: bold; 
                position: relative;
            }
            .student-data-label::after{
                content: " ";
                border-bottom: 1px solid #000;
                width: 25px;
                position: absolute;
                bottom: 0;
                left: 0;
            }
            .exam-title{
                font-weight: 700;
                font-size: 15px;
                margin-top: 10px;
                margin-bottom: 5px;
                text-align: center;
                text-transform: uppercase;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                max-width: 700px;
                margin: 0 auto;
            }
            th{
                text-align: left;
            }
            table, th, td {
                border: 1px solid #000;
                font-size: 12px;
                line-height: 1;
                padding: 3px;
            }
            .admit-notes{
                margin-top: 10px;
                font-size: 11px;
                line-height: 1;
            }
            .admitAvatar{
                width: 100%;
                max-width: 75px;
                height: auto;
                padding: 2px;
                border: 1px solid #000;
                float: right;
            }
            .signature-wrap{
                margin-top: 10px;
            }
            .sign-block{
                text-align: center;
                font-size: 14px;
            }
            .signer-desig{
                padding: 2px;
                border-top: 1px dashed #000;
                display: inline-block;
                width: 100%;
            }
            .sign-block img{
                max-width: 75px;
                display: inline-block;
                float: none;
                margin-bottom: 2px;
            }
            .edupress-credit{
                padding: 3px;
                font-size: 12px;
                text-align: center; 
                line-height: 1.2;
                margin: 20px 20px 0;
                border: 1px dashed #000;
            }
        </style>
        <?php 
        foreach($users as $user): 
            $student_data = [];
            $student_data['name'] = ['key' => 'Student\'s Name', 'value' => get_user_meta($user->ID, 'first_name', true)];
            if(EduPress::isActive('shift')) $student_data['shift'] = ['key' => 'Shift', 'value' => get_the_title(get_user_meta($user->ID, 'shift_id', true))];
            $student_data['class'] = ['key' => 'Class', 'value' => get_the_title(get_user_meta($user->ID, 'class_id', true))];
            if(EduPress::isActive('section')) $student_data['section'] = ['key' => 'Section', 'value' => get_the_title(get_user_meta($user->ID, 'section_id', true))];
            $student_data['roll'] = ['key' => 'Roll', 'value' => get_user_meta($user->ID, 'roll', true)];
            $student_data['mobile'] = ['key' => 'Mobile', 'value' => get_user_meta($user->ID, 'mobile', true)];
            $avatar_id = (int) get_user_meta($user->ID, 'avatar_id', true);
            ?>
            <div class="admitCardWrap">
                <div class="ep-flex-wrap ">
                    <div class="ep-flex-2"><?php if($logo) echo wp_get_attachment_image($logo, 'full', null, ['class'=>'admitCardLogo','loading'=>'eager', 'decoding'=>'sync']); ?></div>
                    <div class="ep-flex-8 headerCenterContent">
                        <div class='institute-title'><?php _t($name); ?></div>
                        <div class='institute-address'><?php _t($address); ?></div>
                        <div class="admit-card-title"><?php _t('ADMIT CARD'); ?></div>
                        <div class='term-title'><?php _t($term); ?> <?php _t($args['year']); ?> </div>
                    </div>
                    <div class="ep-flex-2">
                        <?php 
                            if($avatar_id) echo wp_get_attachment_image($avatar_id, 'full', null, ['class'=>'admitAvatar', 'loading'=>'eager', 'decoding'=>'sync']);
                        ?>
                    </div>
                </div>

                <div class="student-details">
                    <?php 
                        foreach($student_data as $k=>$v){
                            if(empty($v['value'])) continue; 
                            ?>
                            <div class="student-data-wrap">
                                <div class="student-data-label"><?php _t($v['key']); ?></div>
                                <div class="student-data-value"><?php _t($v['value']); ?></div>
                            </div>
                            <?php 
                        }
                    ?>
                </div>

                <div class="schedule-details">
                    <?php if(!empty($exams)){
                        $count = 0; 
                        ?>
                        <div class="exam-title"><?php _t('Exam Schedule'); ?></div>
                        <table class='edupress-table ep-admit-card-table'>
                            <tbody>
                                <tr>
                                    <th width='75'><?php _t('Date'); ?></th>
                                    <th width='200'><?php _t('Subject'); ?></th>
                                    <th width='75'><?php _t('Date'); ?></th>
                                    <th width='200'><?php _t('Subject'); ?></th>
                                </tr>
                            </tbody>
                        <?php 
                            $count = 0; 
                            echo "<tr>";
                            foreach($exams as $exam){
                                $subject = get_the_title($exam['subject']);
                                $dt = new \DateTime($exam['date']);
                                echo "<td>{$dt->format('d M, Y')}</td> <td>{$subject}</td> ";
                                $count++;
                                if($count % 2 == 0) echo "</tr><tr>";
                            }
                            echo "</tr>";
                        ?> 
                        </table>
                        <?php 
                    } ?>
                </div>

                <div class="admit-notes">
                    <?php $notes = Admin::getSetting('admit_card_notes'); 
                    if(!empty($notes)){
                        _t("<strong>Notes</strong><br>");
                        _t(nl2br($notes)); 
                    }
                    ?>
                </div>

                <div class="ep-flex-wrap signature-wrap">
                    <div class="ep-flex-3 sign-block">
                        <br><br>
                        <div class="signer-desig">Class Teacher</div>
                    </div>
                    <div class="ep-flex-6">
                        <div class="edupress-credit">
                        Generated by EduPress School Management Software <br>
                        www.edupressbd.com | 01979 001 001
                        </div>
                    </div>
                    <div class="ep-flex-3 sign-block">
                        <?php 
                            $principal_signature_id = Admin::getSetting('principal_signature');
                            if($principal_signature_id) echo wp_get_attachment_image($principal_signature_id, 'thumbnail', null, ['loading'=>'eager','decoding'=>'sync']);
                            $desig = Admin::getSetting('principal_designation');
                        ?>
                        <div class="signer-desig"><?php _t($desig); ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach;
        $html = ob_get_clean();
        return "<div class='admitCardsGrid'>{$html}</div>";
    }
}

ExamRoutine::instance();