<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Result extends Post
{


    /**
     * @var $_instance
     */
    public static $_instance;

    /**
     * @var string $post_type
     */
    protected $post_type = 'result';

    /**
     * @var string $list_title
     */
    protected  $list_title = 'Exam Result';

    /**
     * @var int $posts_per_page
     */
    protected $posts_per_page;


    /**
     * Initialize instance
     *
     * @return Result
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {

        if ( is_null ( self::$_instance ) ){

            self::$_instance = new self();

        }

        return self::$_instance;

    }

    /**
     * @constructor
     *
     * @retrun void
     *
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        // Setting Posts Per Page
        $this->posts_per_page = -1;

        // Publish new item button
        add_filter( "edupress_publish_{$this->post_type}_button_html", function(){
            return '';
        });

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterSearchFields' ] );

        // After list guardian signature option
        add_filter( "edupress_list_{$this->post_type}_after_html", [ $this, 'getAfterListHtml' ] );

        // List before html
        add_filter( "edupress_list_{$this->post_type}_before_html", [ $this, 'filterBeforeListHtml' ], 10, 2 );

    }

    /**
     * Get publish form
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getForm( $action = 'edit' , $wrap = false )
    {

        return $this->getPostEditForm();

    }


    /**
     * Show result edit form
     *
     * @return string
     *
     * @since 1.0
     * @acesss public
     */
    public function getPostEditForm()
    {
        $args = [];
        $args['role__in'] = array('student');

        $branch_id = (int) $this->getMetadata()['branch_id'][0];
        if( Admin::getSetting( 'branch_active') == 'active' && !empty($branch_id) ){
            $args['meta_query'][] = array(
                'key'   => 'branch_id',
                'value' => $branch_id,
                'compare' => '='
            );
        }

        $shift_id = (int) $this->getMetadata()['shift_id'][0];
        if( Admin::getSetting( 'shift_active') == 'active' && !empty($shift_id) ){
            $args['meta_query'][] = array(
                'key'   => 'shift_id',
                'value' => $shift_id,
                'compare' => '='
            );
        }

        $class_id = (int) $this->getMetadata()['class_id'][0];
        if( Admin::getSetting( 'class_active') == 'active' && !empty($class_id) ){
            $args['meta_query'][] = array(
                'key'   => 'class_id',
                'value' => $class_id,
                'compare' => '='
            );
        }

        $section_id = (int) $this->getMetadata()['section_id'][0];
        if( Admin::getSetting( 'section_active') == 'active' && !empty($section_id) ){
            $args['meta_query'][] = array(
                'key'   => 'section_id',
                'value' => $section_id,
                'compare' => '='
            );
        }

        if ( count( $args['meta_query'] ) > 1 ){
            $args['meta_query']['relation'] = 'AND';
        }

        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'roll';
        $args['order'] = 'ASC';

        $user_qry = new \WP_User_Query($args);
        if( !$user_qry->get_results() ) return __('No students found.', 'edupress' );

        $heads = $this->getMetadata()['exam_mark_heads'];

        $results = maybe_unserialize( $this->getMetadata()['results'][0] );

        $exam_marks = maybe_unserialize( $this->getMeta('exam_marks') );
        if( !is_array( $exam_marks) ) $exam_marks = explode(',', $exam_marks );

        $can_user_edit = User::currentUserCan('edit', 'exam' ) === true ;
        $viewer_class = $can_user_edit ? '' : 'input-no-border';
        ob_start();
        ?>

        <h2 style="text-align: center"><?php echo $can_user_edit  ? 'Update Result' : 'Academic Result'; ?></h2>

        <div class="exam-result-head">
            <?php if( Admin::getSetting('branch_active') == 'active' ): ?>
                Branch: <?php echo get_the_title($this->getMetadata()['branch_id'][0]); ?> <br>
            <?php endif; ?>
            <?php if( Admin::getSetting('shift_active') == 'active' ): ?>
                Shift: <?php echo get_the_title($this->getMetadata()['shift_id'][0]); ?> <br>
            <?php endif; ?>
            <?php if( Admin::getSetting('class_active') == 'active' ): ?>
                Class: <?php echo get_the_title($this->getMetadata()['class_id'][0]); ?> <br>
            <?php endif; ?>
            <?php if( Admin::getSetting('section_active') == 'active' ): ?>
                Section: <?php echo get_the_title($this->getMetadata()['section_id'][0]); ?><br>
            <?php endif; ?>
            <?php _e( 'Subject', 'edupress' ); ?> : <strong> <?php echo get_the_title($this->getMetadata()['subject_id'][0]); ?></strong><br>
            <?php _e( 'Exam date', 'edupress' ); ?>: <?php echo date( 'd/m/Y', strtotime($this->getMetadata()['exam_date'][0])); ?><br><br>
        </div>

        <style>
            input.input-no-border,
            select.input-no-border,
            .input-no-border{
                border:none !important;
            }
        </style>
        <form action="" method="post" class="<?php echo EduPress::getClassNames(array( 'edupress-result-form'), 'form') ?>">
            <div class="edupress-table-wrap">
                <table class="edupress-table edupress-master-table tablesorter">
                    <thead>
                        <?php if( User::currentUserCan('edit', 'exam' ) ) : ?>
                        <tr>
                            <th colspan="<?php echo is_array($heads) ? count($heads) + 4 : 0; ?>" align="right" style="text-align: right;">
                                <?php echo EduPress::generateFormElement( 'submit', '', array('value'=>'Save Results')); ?>
                            </th>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Roll</th>
                            <th>Name</th>
                            <th>Status</th>
                            <?php
                            if( !empty($heads) ){
                                foreach($heads as $h){
                                    ?>
                                    <th>
                                        <?php _e( $h, 'edupress' ); ?>
                                        <?php echo EduPress::generateFormElement( 'number', $h .'_exam_mark', array( 'value' => $exam_marks[$h], 'required' => true, 'data' => array('title'=>$h. ' Exam Mark', 'data-mark-head'=>$h, 'step'=>'any', 'min' => 0), 'readonly' => !$can_user_edit, 'class' => "{$viewer_class}", )) ?>
                                    </th>
                                    <?php
                                }
                            }
                            ?>
                            <th>Total<br><span class="edupress-value-container"><?php echo array_sum($exam_marks); ?></span></th>
                        </tr>
                    </thead>
                    <?php
                        foreach($user_qry->get_results() as $user ){
                            $user = new User($user);
                            $unregistered = isset($results[$user->id]['unregistered']) ? intval($results[$user->id]['unregistered']) : 0;
                            ?>
                            <tr data-user-id="<?php echo $user->id; ?>">
                                <td>
                                    <?php echo $user->getMeta( 'roll' ); ?>
                                    <?php echo EduPress::generateFormElement( 'hidden', 'user_id[]', array( 'value' => $user->id ) ) ; ?>
                                </td>
                                <td><?php echo User::showProfileOnClick( $user->id, $user->getMeta( 'first_name' ) ); ?></td>
                                <td><?php echo EduPress::generateFormElement( 'select', "unregistered[]", array( 'options' => array('Registered', 'Unregistered'), 'value' => $unregistered, 'class'=>"edupress-result-unregistered-status {$viewer_class}", 'disabled' => !$can_user_edit ) ); ?></td>
                                <?php
                                if( !empty($heads) ){
                                    $total_mark = 0;
                                    foreach($heads as $h){
                                        $mark = isset($results[$user->id]['results'][$h]) ? $results[$user->id]['results'][$h]['obtained'] : 0;
                                        $absent = isset($results[$user->id]['results'][$h]['absent']) ? intval($results[$user->id]['results'][$h]['absent']) : !Attendance::isUserPresent($user->id, $this->getMetadata()['exam_date'][0]);
                                        if($unregistered) $absent = 1;
                                        if( $unregistered || $absent) $mark = 0;
                                        $total_mark += $mark;
                                        $col_class = $absent ? 'col-error' : '';
                                        ?>
                                        <td class="<?php echo $col_class; ?>">
                                            <?php echo EduPress::generateFormElement( 'number', "{$h}[]", array( 'value' => $mark, 'readonly' => $absent, 'disabled' => !$can_user_edit, 'data' => array( 'step' => 'any', 'min' => 0, 'max'=> $exam_marks[$h], 'style' => 'margin-bottom:5px !important;', ),  'class' => $viewer_class, ) ); ?>
                                            <?php echo EduPress::generateFormElement( 'select', "{$h}_absent[]", array( 'options' => array('Present', 'Absent'), 'value' => $absent, 'class'=>"{$viewer_class} edupress-result-absent-status", 'disabled' => !$can_user_edit  ) ); ?>
                                        </td>
                                        <?php
                                    }
                                } else {

                                    _e("You must select mark types", 'edupress' );
                                    return ob_get_clean();

                                }
                                ?>
                                <td><br><span class="edupress-value-container"><?php echo $total_mark; ?></span></td>
                            </tr>
                            <?php
                        }
                    ?>
                    <?php if( User::currentUserCan('edit', 'exam' ) ) : ?>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo is_array($heads) ? count($heads) + 4 : 0; ?>">
                                <?php echo EduPress::generateFormElement( 'submit', '', array('value'=>'Save Results')); ?>
                                <?php echo EduPress::generateFormElement( 'hidden', 'action', array('value'=>'edupress_admin_ajax')); ?>
                                <?php echo EduPress::generateFormElement( 'hidden', 'ajax_action', array('value'=>'saveExamResult')); ?>
                                <?php echo EduPress::generateFormElement( 'hidden', 'heads', array('value'=> is_array($heads) ? implode(',', $heads) : [], )); ?>
                                <?php echo EduPress::generateFormElement( 'hidden', 'post_id', array( 'value'=> $this->id ) ); ?>
                                <?php wp_nonce_field('edupress'); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </form>


        <?php
        return ob_get_clean();

    }

    /**
     * Save result
     *
     * @return boolean
     *
     * @since 1.0
     * @access public
     */
    public function edit( $data = [] )
    {

        if ( !$this->id ) return false;

        return update_post_meta( $this->id, 'results', $data );

    }

    /**
     * Filter list html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getListHtml( )
    {
        $args = [];
        $args['post_type'] = 'exam';
        $args['post_status'] = 'publish';
        $orderby = isset($_REQUEST['order_by']) ? esc_attr($_REQUEST['order_by']) : 'exam_date';

        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'exam_date';
        $args['meta_type'] = 'DATE';

        $args['order'] = 'ASC';

        $args['posts_per_page'] = $this->posts_per_page;

        $branch_id = intval($_REQUEST['branch_id'] ?? 0);
        $shift_id = intval($_REQUEST['shift_id'] ?? 0);
        $class_id = intval($_REQUEST['class_id'] ?? 0);
        $section_id = intval($_REQUEST['section_id'] ?? 0);
        $term_id = intval($_REQUEST['term_id'] ?? 0);
        $subject_id = intval($_REQUEST['subject_id'] ?? 0);
        $start_date = esc_attr($_REQUEST['start_date'] ?? '');
        $end_date = esc_attr($_REQUEST['end_date'] ?? '');
        $ranking_method = esc_attr($_REQUEST['ranking_method'] ?? '');

        if(!empty($start_date)) $start_date = date( 'Y-m-d', strtotime($start_date) );
        if(!empty($end_date)) $end_date = date( 'Y-m-d', strtotime($end_date) );

        if($branch_id){
            $args['meta_query'][] = array(
                'key' => 'branch_id',
                'value' => $branch_id,
            );
        }

        if($shift_id){
            $args['meta_query'][] = array(
                'key' => 'shift_id',
                'value' => $shift_id,
            );
        }

        if($class_id){
            $args['meta_query'][] = array(
                'key' => 'class_id',
                'value' => $class_id,
            );
        }

        if($section_id){
            $args['meta_query'][] = array(
                'key' => 'section_id',
                'value' => $section_id,
            );
        }

        if($term_id){
            $args['meta_query'][] = array(
                'key' => 'term_id',
                'value' => $term_id,
            );
        }

        if($subject_id){
            $args['meta_query'][] = array(
                'key' => 'subject_id',
                'value' => $subject_id,
            );
        }

        if(!empty($start_date)){
            $args['meta_query'][] = array(
                'key' => 'exam_date',
                'value' => $start_date,
                'type' => 'Date',
                'compare' => '>='
            );
        }

        if(!empty($end_date)){
            $args['meta_query'][] = array(
                'key' => 'exam_date',
                'value' => $end_date,
                'type' => 'Date',
                'compare' => '<='
            );
        }

        if(isset($args['meta_query']) && is_array($args['meta_query']) && count($args['meta_query']) > 1) $args['meta_query']['relation'] = 'AND';

        $min_req_fields = 2;
        if( Admin::getSetting('shift_active') ) $min_req_fields++;
        if( Admin::getSetting('class_active') ) $min_req_fields++;
        if( Admin::getSetting('section_active') ) $min_req_fields++;

        if( !isset($args['meta_query']) || (is_array($args['meta_query']) && count($args['meta_query']) < $min_req_fields) ) return __( 'Please select all fields!', 'edupress' );



        $qry = new \WP_Query( $args );

        if(!$qry->have_posts()) return __('No exams found!', 'edupress' );

        $subject_ordered = [];

        if($orderby === 'title'){

            // This is used for subject ordering
            $sub_order = [];
            $exam_id_sub_id_mapping = [];
            while($qry->have_posts()):
                $qry->the_post();
                $sub_id = get_post_meta( $qry->post->ID, 'subject_id', true );
                $order = get_post_meta( $sub_id, 'sort_order', true );
                $sub_order[$order] = $sub_id;
                $exam_id_sub_id_mapping[$qry->post->ID] = (int) $sub_id;
            endwhile;

            // Sorting as per key number
            ksort($sub_order);

            // Getting values as per sorted order
            $subject_ordered = array_values($sub_order);
            $subject_ordered = array_map('intval', $subject_ordered);

            // Sorting qry posts as per subject order
            usort($qry->posts, function($a, $b) use ($subject_ordered, $exam_id_sub_id_mapping){
                $sub_id_a = (int) $exam_id_sub_id_mapping[$a->ID];
                $sub_id_b = (int) $exam_id_sub_id_mapping[$b->ID];


                $pos_a = array_search($sub_id_a, $subject_ordered);
                $pos_b = array_search($sub_id_b, $subject_ordered);

                return $pos_a - $pos_b;
            });

        } else {
            $subject_ordered = [];
            while($qry->have_posts()):
                $qry->the_post();
                $subject_ordered[] = get_post_meta( $qry->post->ID, 'subject_id', true );
            endwhile;
        }

        $_REQUEST['role'] = 'student';

        $_REQUEST['orderby'] = 'roll';

        $students = User::getAll($_REQUEST);

        if(empty($students)) return __('No students found!', 'edupress');

        $branch_active = EduPress::isActive('branch');
        $branch_title = get_the_title($branch_id);
        $shift_active = EduPress::isActive('shift');
        $class_title = get_the_title($class_id);
        $class_active = EduPress::isActive('class');
        $shift_title = get_the_title($shift_id);
        $section_active = EduPress::isActive('section');
        $section_title = get_the_title($section_id);
        $term_title = get_the_title($term_id);

        $class_data = array(
            'term' => array(
                'id' => $term_id,
                'title' => $term_title
            )
        );

        $students_data = [];
        $exam_marks_head_wise_data = [];
        $subject_wise_highest_obtained_marks = [];

        // Storing all dates for exam dates to be shown

        $all_dates = [];

        // Pass percentage for merit-based result
        $mark_heads = explode(',', Admin::getSetting('exam_mark_heads') );
        $mark_heads = array_map('trim', $mark_heads );
        $pass_percentage = [];
        foreach($mark_heads as $k){
            $pass_percentage[$k] = (float) Admin::getSetting( $k.'_pass_percentage' );
        }

        foreach($students as $student ){
            $user = new User($student);
            $students_data[$user->id] = array(
                'id' => $user->id,
                'optional_subject_id' => (int) $user->getMeta('optional_subject_id' ),
            );
        }

        // Connected subject id mapping
        $connected_subject_id_mapping = [];

        // Marks based ranking
        while($qry->have_posts()):

            $qry->the_post();
            $exam_id = $qry->post->ID;
            $results = maybe_unserialize(get_post_meta( $qry->post->ID, 'results', true ));
            $subject_id = get_post_meta( $qry->post->ID, 'subject_id', true );
            $subject_title = get_the_title($subject_id);
            $exam_date = get_post_meta( $qry->post->ID, 'exam_date', true );
            $all_dates[] = strtotime($exam_date);

            if( isset($connected_subject_id_mapping[$subject_id]) ){

                $connected_subject_id = $connected_subject_id_mapping[$subject_id];

            } else {

                $connected_subject_id = Subject::getConnectedId($subject_id);
                $connected_subject_id_mapping[$subject_id] = $connected_subject_id;

            }

            $combined_title = get_post_meta( $subject_id, 'combined_name', true );
            if(empty($combined_title)){
                $combined_title = get_post_meta( $connected_subject_id, 'combined_name', true );
            }

            // Exam mark details
            $exam_marks_head_wise_data[$subject_id] = maybe_unserialize( get_post_meta( $exam_id, 'exam_marks', true ) );

            foreach($results as $student_id => $marks_data ){

                $user_marks_data = $marks_data['results'];

                // Adding failed subject if registered
                if( !$marks_data['unregistered'] && $ranking_method == 'marks' ){
                    foreach($user_marks_data as $k=>$v){
                        $new_array = $v;
                        if( is_array($new_array) ){
                            $passed = $new_array['obtained'] > ($pass_percentage[$k] * $exam_marks_head_wise_data[$subject_id][$k] / 100);
                            $new_array['failed'] = !$passed ? 1 : 0;
                            $new_array['exam_marks'] = (float) $exam_marks_head_wise_data[$subject_id][$k];
                            $user_marks_data[$k] = $new_array;
                            if(!$passed) $students_data[$student_id]['failed_subjects'][] = (int) $subject_id;
                        }
                    }
                }

                $total_obtained = array_sum( array_column( $user_marks_data, 'obtained' ) );
                $students_data[$student_id]['results'][$subject_id]['unregistered'] = $marks_data['unregistered'];
                $students_data[$student_id]['results'][$subject_id]['marks'] = $user_marks_data;
                $students_data[$student_id]['results'][$subject_id]['obtained'] = $total_obtained;
                if( !isset($subject_wise_highest_obtained_marks[$subject_id]) ) $subject_wise_highest_obtained_marks[$subject_id] = $total_obtained;
                if( $subject_wise_highest_obtained_marks[$subject_id] < $total_obtained ) $subject_wise_highest_obtained_marks[$subject_id] = $total_obtained;
                $students_data[$student_id]['results'][$subject_id]['exam_marks'] = array_sum($exam_marks_head_wise_data[$subject_id]);
                // reducing data
               $students_data[$student_id]['results'][$subject_id]['exam_date'] = $exam_date;
//                $students_data[$student_id]['results'][$subject_id]['subject_title'] = $subject_title;
//                $students_data[$student_id]['results'][$subject_id]['combined_title'] = $combined_title;
                $students_data[$student_id]['results'][$subject_id]['connected_subject_id'] = $connected_subject_id;
                if( !isset($students_data[$student_id]['total_obtained_marks']) ) $students_data[$student_id]['total_obtained_marks'] = 0;
                $students_data[$student_id]['total_obtained_marks'] += array_sum(array_column($user_marks_data, 'obtained')) ;

            }
        endwhile;

        // Updating combined subject marks heads
        if( $ranking_method != 'marks' ){

            $processed_ids = [];

            foreach( $exam_marks_head_wise_data as $subject_id => $markv ){

                $connected_subject_id = $connected_subject_id_mapping[$subject_id];
                if( !$connected_subject_id || in_array( $subject_id, $processed_ids )  ) continue;

                $new_mark_heads = [];

                foreach($exam_marks_head_wise_data[$subject_id] as $k => $v){
                    $new_mark_heads[$k] = $v;
                }
                foreach($exam_marks_head_wise_data[$connected_subject_id] as $x=>$y){
                    $new_mark_heads[$x] = isset($new_mark_heads[$x]) ? $new_mark_heads[$x] + $y : $y;
                }

                $processed_ids[] = $subject_id;
                $processed_ids[] = $connected_subject_id;

                $exam_marks_head_wise_data[$subject_id] = $new_mark_heads;
                $exam_marks_head_wise_data[$connected_subject_id] = $new_mark_heads;

            }
        }


        // Calculating subject wise exam total
        foreach($exam_marks_head_wise_data as $subject_id => $subject_details ){

            if( !is_array($subject_details) ){
                $exam_marks_head_wise_data[$subject_id] = [];
                $exam_marks_head_wise_data[$subject_id]['total'] = 0;
            }

        }

        // Marks based result
        // Sorting for merit
        $students_total_marks = [];
        foreach($students_data as $student_id => $v){

            if($student_id == 'exam_marks') continue;
            if(!empty($v['failed_subjects']) || $v['total_obtained_marks'] == 0  ) continue;
            $students_total_marks[$student_id] = $v['total_obtained_marks'];

        }
        unset($students_total_marks['exam_marks']);
        arsort($students_total_marks);
        $position = 0;
        $previous_marks = null;
        foreach($students_total_marks as $student_id=>$total){

            $total !== $previous_marks ? $position++ : $position;
            $students_data[$student_id]['merit'] = $position;
            $previous_marks = $total;

        }


        // Assigning highest mark to each subject of all students
        foreach($students_data as $student_id => $marks ){
            foreach($marks['results'] as $subject_id=>$subject_details){
                $students_data[$student_id]['results'][$subject_id]['highest'] = $subject_wise_highest_obtained_marks[$subject_id];
            }
        }

        // GPA based ranking
        if( $ranking_method !== 'marks'){

            $connected_subjects_data = [];

            foreach($students_data as $student_id => $student_details ){

                if(!empty($student_details['results'])){

                    // skip if unregistered

                    $student_optional_subject_id = $students_data[$student_id]['optional_subject_id'] ?? 0;

                    foreach($student_details['results'] as $subject_id => $subject_marks ){

                        // Storing in array to avoid duplicate mysql query
                        $connected_subject_id = $connected_subject_id_mapping[$subject_id] ?? 0;

                        $connected_student_results = array_keys($connected_subjects_data[$student_id]['results'] ?? []);

                        // Skip if subject id or connected subject id already found
                        if( in_array( $connected_subject_id, $connected_student_results ) || in_array( $subject_id, $connected_student_results ) ) continue;

                        // Combining first and second papers
                        $combined_data = [];

                        if(!$connected_subject_id){

                            foreach($subject_marks['marks'] as $mark_head => $mark_details){

                                if( $mark_head == 'total' ) continue;

                                $obtained = $mark_details['obtained'] ?? 0;
                                $absent = $mark_details['absent'] ?? 0;

                                $exam_marks_head_total = $exam_marks_head_wise_data[$subject_id][$mark_head];
                                $grade_data = GradeTable::getGradeData( intval($ranking_method), $obtained, $exam_marks_head_total );
                                $combined_data[$mark_head] = array(
                                    'obtained' => $obtained,
                                    'absent' =>  $absent,
                                    'failed' => $grade_data['point'] == 0 ? 1 : 0,
                                    'grade_point' => (float) $grade_data['point'] ?? 0,
                                    'grade' => $grade_data['grade'] ?? 'F',
                                    'exam_marks' => $exam_marks_head_total,
                                );
                            }

                            $is_connected = 0;

                        } else {

                            foreach($subject_marks['marks'] as $mark_head => $mark_details){

                                $obtained_1 = $mark_details['obtained'] ?? 0;
                                $absent_1 = $mark_details['absent'] ?? 0;

                                $obtained_2 =  $students_data[$student_id]['results'][$connected_subject_id]['marks'][$mark_head]['obtained'] ?? 0;
                                $absent_2 = $students_data[$student_id]['results'][$connected_subject_id]['marks'][$mark_head]['absent'] ?? 0;

                                $obtained = $obtained_1 + $obtained_2;
                                $absent = $absent_1 + $absent_2;

                                $exam_marks_head_total = $exam_marks_head_wise_data[$subject_id][$mark_head];
                                $grade_data = GradeTable::getGradeData( intval($ranking_method), $obtained, $exam_marks_head_total );
                                $combined_data[$mark_head] = array(
                                    'obtained' => $obtained,
                                    'absent' =>  $absent,
                                    'grade_point' => $grade_data['point'] ?? 0,
                                    'grade' => $grade_data['grade'] ?? 'F',
                                    'exam_marks' => $exam_marks_head_total,
                                    'failed' => $grade_data['point'] == 0 ? 1 : 0,
                                );
                            }

                            $is_connected = 1;

                        }


                        // Summing up all failed in heads
                        // if addition is more than 0, then fail is true
                        $is_failed = array_sum( array_column( $combined_data, 'failed' ) ) > 0 ? 1 : 0 ;
                        $unregistered = 0;

                        if( isset($students_data[$student_id]['results'][$subject_id]['unregistered']) && $students_data[$student_id]['results'][$subject_id]['unregistered'] == 1 ) $unregistered = 1;
                        if($connected_subject_id && isset($connected_subjects_data[$student_id]['results'][$connected_subject_id]['unregistered']) && $connected_subjects_data[$student_id]['results'][$connected_subject_id]['unregistered'] == 1) $unregistered = 1;

                        $connected_subjects_data[$student_id]['results'][$subject_id]['unregistered'] =  $unregistered;

                        $connected_subjects_data[$student_id]['results'][$subject_id]['exam_marks'] = array_sum( array_column( $combined_data, 'exam_marks' ) );
                        $connected_subjects_data[$student_id]['results'][$subject_id]['obtained'] = array_sum( array_column( $combined_data, 'obtained' ) );
                        $connected_subjects_data[$student_id]['results'][$subject_id]['failed'] = $is_failed;
                        $connected_subjects_data[$student_id]['results'][$subject_id]['marks'] = $combined_data;
                        $connected_subjects_data[$student_id]['results'][$subject_id]['is_connected'] = $is_connected;
                        $connected_subjects_data[$student_id]['results'][$subject_id]['connected_subject_id'] = $connected_subject_id;
                        // Reducing data
//                        $connected_subjects_data[$student_id]['results'][$subject_id]['subject_title'] = $subject_marks['subject_title'];
//                        $connected_subjects_data[$student_id]['results'][$subject_id]['combined_title'] = $subject_marks['combined_title'];
//                        $connected_subjects_data[$student_id]['results'][$subject_id]['exam_date'] = $subject_marks['exam_date'];

                        // Skipping if unregistered
                        if($unregistered) continue;

                        // Adding optional subject
                        $grade_data = GradeTable::getGradeData( intval($ranking_method), $connected_subjects_data[$student_id]['results'][$subject_id]['obtained'], $connected_subjects_data[$student_id]['results'][$subject_id]['exam_marks']);

                        if( $student_optional_subject_id > 0 && ( $subject_id == $student_optional_subject_id || $connected_subject_id == $student_optional_subject_id ) ){

                            $students_data[$student_id]['optional_grade_point'][$subject_id] = $grade_data['point'] > 2 ? $grade_data['point'] - 2 : 0;
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade_point'] = $grade_data['point'];
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade'] = $grade_data['grade'];
                            $connected_subjects_data[$student_id]['results'][$subject_id]['is_optional'] = 1;
                            continue;

                        }

                        $connected_subjects_data[$student_id]['results'][$subject_id]['is_optional'] = 0;
                        // Checking failed
                        if( $is_failed == 1 ){
                            $students_data[$student_id]['failed_subjects'][] = $subject_id;
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade_point'] = 0.00;
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade'] = 'F';
                            $students_data[$student_id]['grade_points'][$subject_id] = 0.00;
                        } else {
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade_point'] = $grade_data['point'];
                            $connected_subjects_data[$student_id]['results'][$subject_id]['grade'] = $grade_data['grade'];
                            $students_data[$student_id]['grade_points'][$subject_id] = floatval($grade_data['point']);
                        }
                    }
                }
            }

            foreach($students_data as $student_id => $student_details ){
                if( $student_id == 'exam_marks' ) continue;
                $students_data[$student_id]['results'] = $connected_subjects_data[$student_id]['results'];
            }

        }

        // Storing SMS Data
        $sms_data = [];

        // For connected subjects
        // Showing only combined subjects, skipping parts
        $shown_connected_subjects = [];

        // Showing result details in SMS or not
        $result_sms_mark_details = Admin::getSetting('result_sms_marks_details') == 'active';

        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table edupress-master-table tablesorter">

                <thead>
                    <tr>
                        <th class="">
                            <?php if(User::currentUserCan('send', 'sms') ): ; ?>
                                <input type="checkbox" class="edupress-bulk-select-all no-print" id="select_all">
                                <label for="select_all"><?php _e('Roll', 'edupress'); ?></label>
                                <br><br>
                                <a title="Select Results to Print" href="javascript:void(0)" class="result-bulk-print no-print" data-term-id="<?php echo $term_id; ?>" data-start-date="<?php echo $start_date; ?>" data-end-date="<?php echo $end_date; ?>" data-rank-method="<?php echo $ranking_method; ?>"><?php echo EduPress::getIcon('print'); ?></a>
                                <a title="Send emails to all selected users" href="javascript:void(0)" class="result-bulk-email-send no-print"><?php echo EduPress::getIcon('email'); ?></a>
                                <a title="Send SMS to all selected users" href="javascript:void(0)" class="result-bulk-sms-send no-print"><?php echo EduPress::getIcon('sms'); ?></a>
                            <?php else: ?>
                                <?php _e('Roll', 'edupress' ); ?>
                            <?php endif; ?>
                        </th>
                        <th>Name</th>
                        <?php
                        $found_subjects = $qry->found_posts;
                        while($qry->have_posts()) :
                            $qry->the_post();
                            $subject_id = get_post_meta( $qry->post->ID, 'subject_id', true );
                            $subject_title = $found_subjects > 5 ? get_post_meta( $subject_id, 'shortname', true ) : get_the_title( $subject_id);
                            if( $ranking_method  !== 'marks' ){
                                $combined_name = get_post_meta( $subject_id, 'combined_name', true );
                                if(!empty($combined_name)) $subject_title = $combined_name;
                                // Continue if subject or connected subject already shown
                                if ( in_array( $subject_id, $shown_connected_subjects ) ) continue;
                                $shown_connected_subjects[] = $subject_id;
                                $shown_connected_subjects[] = $connected_subject_id_mapping[$subject_id];
                            }
                            ?>
                            <th>
                                <!-- subject -->
                                <?php echo  $subject_title; ?>
                                <br>
                                <!-- Exam date -->
                                <?php $date = get_post_meta($qry->post->ID, 'exam_date', true ); if(!empty($date)) echo date('d/m/y', strtotime($date)); ?>
                                <br>
                                <!-- Exam marks -->
                                <?php

                                // Subject heads
                                    echo "<ul class='exam-mark-details'>";
                                    if(!empty($exam_marks_head_wise_data[$subject_id])){
                                        foreach($exam_marks_head_wise_data[$subject_id] as $k=>$v){
                                            echo "<li><span class='exam-mark-title'>$k: </span><span class='exam-mark-value'>$v</span></li>";
                                        }
                                        echo "<li><span class='exam-mark-title'><strong>Total: </strong></span><span class='exam-mark-value'><strong>".array_sum($exam_marks_head_wise_data[$subject_id])."</strong></span></li>";
                                    }
                                    echo "</ul>";
                                ?>
                            </th>
                        <?php endwhile; ?>
                        <?php if( $ranking_method == 'marks' ): ?>
                            <th>Total</th>
                            <th>Merit<br>Pos.</th>
                        <?php else : ?>
                            <th>CGPA <br>(W/O Op.)</th>
                            <th>CGPA <br>(W Op.)</th>
                            <th>Grade</th>
                        <?php endif; ?>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>

                <?php
                foreach($students as $student):

                    $user = new User($student);
                    $student_id = $user->id;

                    $sms_data[$student_id]['id'] = $user->id;
                    $sms_data[$student_id]['name'] = $user->getUser()->first_name;
                    $sms_data[$student_id]['mobile'] = $user->getMeta('mobile');
                    $sms_data[$student_id]['sms'] = "$term_title Result\n";
                    $sms_data[$student_id]['sms'] .= "\n" . $user->getMeta('first_name') . "\n";
                    if( $class_active ){
                        $sms_data[$student_id]['sms'] .= get_the_title($user->getMeta('class_id'));
                    }
                    if( $section_active ){
                        $sms_data[$student_id]['sms'] .= " | " . get_the_title($user->getMeta('section_id'));
                    }
                    $sms_data[$student_id]['sms'] .= " | " . $user->getMeta('roll') . "\n";

                    ?>
                    <tr data-user-id="<?php echo $student_id; ?>">

                        <!-- Sms option -->
                        <td class="">
                            <?php if(User::currentUserCan('send', 'sms') ) : ?>
                            <input type="checkbox" class="edupress-bulk-select-item no-print" name="student_id[]" id="user_<?php echo $student_id;?>" value="<?php echo $student_id; ?>">
                            <?php endif; ?>
                            <label for="user_<?php echo $student_id; ?>"><?php echo $user->getMeta('roll'); ?></label>
                        </td>

                        <!-- student name -->
                        <td><?php echo User::showProfileOnClick( $student_id, $user->getMeta('first_name') ); ?></td>
                        <?php

                            // if rank method is combined, used this data to hide connected subjects
                            $student_shown_subjects_for_combined_subjects = [];

                            // checking if optional subject
                            $student_optional_subject_id = $students_data[$student_id]['optional_subject_id'] ?? 0;

                            while($qry->have_posts()):

                                $qry->the_post();
                                $exam_id = $qry->post->ID;
                                $subject_id = get_post_meta( $exam_id, 'subject_id', true );
                                $connected_subject_id  = $connected_subject_id_mapping[$subject_id] ?? 0;

                                $is_optional = $student_optional_subject_id == $subject_id ? 1 : 0;
                                if( !$is_optional && $connected_subject_id > 0 ){
                                    $is_optional = $student_optional_subject_id == $connected_subject_id ? 1 : 0;
                                }


                                $heads = $students_data[$student_id]['results'][$subject_id]['marks'] ?? [];
                                $is_failed = 0;
                                if( !is_null($heads) ){

                                    $is_failed = array_sum(array_column($heads, 'failed'));

                                }


                                if( $ranking_method != 'marks' ) {

                                    // Skipping if subject is already shown
                                    if( $subject_id > 0 && $connected_subject_id > 0 && ( in_array( $subject_id, $student_shown_subjects_for_combined_subjects) || in_array( $connected_subject_id, $student_shown_subjects_for_combined_subjects) ) ) continue;

                                    $student_shown_subjects_for_combined_subjects[] = (int) $subject_id;
                                    $student_shown_subjects_for_combined_subjects[] = (int) $connected_subject_id;

                                    if( $is_optional ) $is_failed = false;

                                }

                                // Checking if subject is registered or not
                                // Skip if NOT registered
                                $unregistered = $students_data[$student_id]['results'][$subject_id]['unregistered'];
                                $connected_unregistered = !empty($connected_subject_id) && isset($students_data[$student_id]['results'][$connected_subject_id]['unregistered']) && $students_data[$student_id]['results'][$connected_subject_id]['unregistered']  == 1 ? 1 : 0;

                                if($unregistered || $connected_unregistered) {
                                    echo "<td data-subject-id='{$subject_id}' data-unregistered='1'> <span class='unregistered'>Unreg</span> </td>";
                                    continue;
                                }
                                ?>

                                <!-- mark distribution -->
                                <td data-optional="<?php echo $is_optional; ?>" data-failed="<?php echo $is_failed > 0 ? 1 : 0; ?>" data-subject-id="<?php echo $subject_id; ?>" data-user-id="<?php echo $student_id; ?>">
                                    <?php
                                        $total = !is_null($heads)  ? array_sum( array_column($heads, 'obtained') ) : 0;

                                        $subject_name = get_post_meta( $subject_id, 'shortname', true );
                                        if(empty($subject_name)) $subject_name = get_the_title($subject_id);

                                        $sms_subject_wise_grade_point = '';
                                        // CGPA
                                        if( $ranking_method !== 'marks' ){

                                            $combined_name = get_post_meta( $subject_id, 'combined_name', true );
                                            if(!empty($combined_name)) $subject_name = $combined_name;

                                            $grade_data = GradeTable::getGradeData( $ranking_method, $total, array_sum( $exam_marks_head_wise_data[$subject_id] ?? [] ) );

                                            $sms_subject_wise_grade_point = '| ' . $grade_data['point'] ?? '';

                                        }

                                        $exam_total = is_array($exam_marks_head_wise_data[$subject_id]) ? array_sum($exam_marks_head_wise_data[$subject_id]) : 0;
                                        $sms_data[$student_id]['sms'] .= "\n{$subject_name}: {$total}/{$exam_total} {$sms_subject_wise_grade_point} (";

                                        // This is for print short
                                        $print_html = '';

                                        echo "<ul class='exam-mark-details no-print'>";

                                            $temp_sms_details = '';
                                            foreach($heads as $k=>$v){
                                                $indv_obtained = is_array($v)  && isset($v['obtained']) ? $v['obtained'] : 0;
                                                $indv_failed = isset($v['failed']) && $v['failed'] ? 1 : 0;
                                                $extra_class = $indv_failed ? ' highlight-failed ' : '';
                                                echo "<li class='$extra_class'><span class='exam-mark-title'>$k</span><span class='exam-mark-value'>". $indv_obtained ."</span></li>";
                                                $temp_sms_details .= "$k: $indv_obtained, ";
                                                $print_html .= floatval($indv_obtained) . "+";
                                            }
                                            if($result_sms_mark_details) $sms_data[$student_id]['sms'] .= rtrim($temp_sms_details, ', ');
                                            echo "<li><span class='exam-mark-title'><strong>Total</strong></span><span class='exam-mark-value'><strong>$total</strong></span></li>";
                                            $print_html = rtrim( trim($print_html), '+' );
                                            $print_html .= "=$total";

                                            // Showing grade and grade data
                                            if( $ranking_method != 'marks' ){

                                                echo "<li><span class='exam-mark-title'><strong>GP</strong></span><span class='exam-mark-value'><strong>{$grade_data['point']}</strong></span></li>";
                                                echo "<li><span class='exam-mark-title'><strong>Grade</strong></span><span class='exam-mark-value'><strong>{$grade_data['grade']}</strong></span></li>";

                                                $print_html .= "|{$grade_data['point']}";

                                                // If optional
                                                if($is_optional){
                                                    echo "O. S.";
                                                }

                                            }

                                        echo "</ul>";
                                        // Line break after each subject for SMS
                                        $sms_data[$student_id]['sms'] .= ")";
                                        if(!$result_sms_mark_details) $sms_data[$student_id]['sms'] = rtrim( $sms_data[$student_id]['sms'], '()' );
                                        echo "<span class='no-view'>{$print_html}</span>";
                                    ?>
                            </td>
                            <?php endwhile; ?>

                        <?php if( $ranking_method == 'marks' ) : ?>

                        <!-- total marks -->
                            <td><?php echo $students_data[$student_id]['total_obtained_marks']; ?></td>

                        <!-- Merit Position -->
                            <td><?php $merit_pos = $students_data[$student_id]['merit'] ?? 0; echo EduPress::numberToOrdinal($merit_pos); ?></td>

                        <!-- SMS -->
                            <?php
                            $student_total_obtained_marks = $students_data[$student_id]['total_obtained_marks'] ?? 0;
                            $student_merit_pos = EduPress::numberToOrdinal($students_data[$student_id]['merit'] ?? 0);
                            $exam_total = 0;
                            foreach($exam_marks_head_wise_data as $subject){
                                $exam_total += array_sum($subject);
                            }
                            if( !isset($students_data[$student_id]['total_exam_marks']) ) $students_data[$student_id]['total_exam_marks'] = 0;
                            $students_data[$student_id]['total_exam_marks'] += $exam_total;

                            $sms_data[$student_id]['sms'] .= "\n\nTotal: $student_total_obtained_marks/$exam_total";
                            $sms_data[$student_id]['sms'] .= "\nMerit: $student_merit_pos\n";
                            ?>


                        <?php else : ?>

                        <!-- Grade point without optional -->
                        <td>
                            <?php

                                $cgpa_without_op = '';
                                if(!empty($students_data[$student_id]['failed_subjects'])){
                                    $cgpa_without_op = '0.00';
                                } else {
                                    $total_subjects = count($students_data[$student_id]['grade_points'] ?? []);
                                    $grade_total = array_sum($students_data[$student_id]['grade_points'] ?? []);
                                    $cgpa_without_op = $total_subjects ? number_format( $grade_total / $total_subjects, 2 ) : 0.00;
                                }
                                echo $cgpa_without_op;
                                $students_data[$student_id]['grade_point_without_optional'] = (float) $cgpa_without_op;
                            ?>
                        </td>

                        <!-- Grade point with optional -->
                        <td>
                            <?php
                                $cgpa_with_op = '';
                                if(!empty($students_data[$student_id]['failed_subjects'])){

                                    $cgpa_with_op = '0.00';

                                } else {

                                    $grade_total_with_opt = $grade_total + array_sum($students_data[$student_id]['optional_grade_point'] ?? []);
                                    $cgpa_with_op = $total_subjects ? number_format($grade_total_with_opt / $total_subjects, 2) : 0.00;

                                    $max_grade = GradeTable::getMaxGradePoint($ranking_method);
                                    if($cgpa_with_op > $max_grade) $cgpa_with_op = number_format($max_grade, 2);

                                }
                                echo $cgpa_with_op;
                                $students_data[$student_id]['grade_point_with_optional'] = (float) $cgpa_with_op;
                            ?>
                        </td>

                        <!-- Grade  -->
                        <td>
                            <?php
                                $grade = GradeTable::getGrade( $ranking_method, $cgpa_with_op );
                                echo $grade;
                                $students_data[$student_id]['grade'] = $grade;

                            ?>
                        </td>

                        <!-- SMS -->
                            <?php
                                $sms_data[$student_id]['sms'] .= "\nGGPA W/O Op: {$cgpa_without_op}";
                                $sms_data[$student_id]['sms'] .= "\nGGPA W/ Op: {$cgpa_with_op} \n";
                                $sms_data[$student_id]['sms'] .= "\nGrade: {$grade} \n";
                            ?>

                        <?php endif; ?>

                        <!-- Action buttons -->
                            <td class="no-print">

                            <!-- Print Button -->
                                <a title="Print Result" data-term-id="<?php echo $term_id; ?>" data-rank-method="<?php echo $ranking_method; ?>" data-end-date="<?php echo $end_date; ?>" data-start-date="<?php echo $start_date; ?>" data-term-id="<?php echo $term_id; ?>" data-term-title="<?php echo $term_title; ?>" data-user-id="<?php echo $student_id; ?>" href="javascript:void(0)" class="printIndividualResult"><?php echo EduPress::getIcon('print'); ?></a>

                            <!-- Send SMS -->
                            <?php if( User::currentUserCan('send', 'sms') ): ?>
                                <a title="Email Result" data-sms_text="<?php echo $sms_data[$student_id]['sms'];?>" data-email="<?php echo $user->getUser()->user_email; ?>" data-ajax_action="emailResult" class="<?php echo EduPress::getClassNames(array('send-student-single-email'), 'link' ); ?>" href="javascript:void(0)" data-user_id="<?php echo $student_id; ?>" title="<?php _e('Send Email', 'edupress' ); ?>"><?php echo EduPress::getIcon('email'); ?></a>
                                <a title="<?php echo $sms_data[$student_id]['sms'];?>" data-sms_text="<?php echo $sms_data[$student_id]['sms'];?>" data-mobile="<?php echo $user->getMeta('mobile'); ?>" data-ajax_action="smsResult" data-success_callback="smsUserResultSuccessCallback" data-before_send_callback="smsUserResult" class="<?php echo EduPress::getClassNames(array('send-student-single-sms'), 'link' ); ?>" href="javascript:void(0)" data-user_id="<?php echo $student_id; ?>" title="<?php _e('Send SMS', 'edupress' ); ?>"><?php echo EduPress::getIcon('sms'); ?></a>
                            <?php endif; ?>

                            </td>
                        </tr>
                    <?php endforeach; ?>
            </table>
        </div>

        <script>
            jQuery(document).ready(function($){
                edupress.sms_data = <?php echo json_encode($sms_data); ?>;
                edupress.result_data = <?php echo json_encode($students_data); ?>;
                edupress.subject_order = <?php echo json_encode($subject_ordered); ?>;
                edupress.class_data = <?php echo json_encode($class_data); ?>;
            });
        </script>


        <?php
        return ob_get_clean();

    }

    /**
     * filter search fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterSearchFields( $fields = [] )
    {
        $fields = [];

        if( Admin::getSetting('branch_active') == 'active' ){
            $branch = new Branch();
            $fields['branch_id'] = array(
                'type' => 'select',
                'name' => 'branch_id',
                'settings' => array(
                    'options' => $branch->getPosts( [], true ),
                    'required'=> true,
                    'label' => 'Branch',
                    'placeholder' => 'Select',
                    'value' => intval($_REQUEST['branch_id'] ?? 0),
                )
            );
        }

        if( Admin::getSetting('shift_active') == 'active' ){
            $fields['shift_id'] = array(
                'type' => 'select',
                'name' => 'shift_id',
                'settings' => array(
                    'required'=> true,
                    'label' => 'Shift',
                    'placeholder' => 'Select',
                    'value' => intval($_REQUEST['shift_id'] ?? 0),
                )
            );
        }

        if( Admin::getSetting('class_active') == 'active' ){
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'required'=> true,
                    'label' => 'Class',
                    'placeholder' => 'Select',
                    'value' => intval($_REQUEST['class_id'] ?? 0),
                )
            );
        }

        if( Admin::getSetting('section_active') == 'active' ){
            $fields['section_id'] = array(
                'type' => 'select',
                'name' => 'section_id',
                'settings' => array(
                    'required'=> true,
                    'label' => 'Section',
                    'placeholder' => 'Select',
                    'value' => intval($_REQUEST['section_id'] ?? 0),
                )
            );
        }

        $term = new Term();
        $fields['term_id'] = array(
            'type' => 'select',
            'name' => 'term_id',
            'settings' => array(
                'required'=> true,
                'label' => 'Term',
                'placeholder' => 'Select',
                'options' => $term->getPosts( [], true ),
                'value' => intval($_REQUEST['term_id'] ?? 0),
            )
        );

        $subject  = new Subject();
        $fields['subject_id'] = array(
            'type' => 'select',
            'name' => 'subject_id',
            'settings' => array(
                'required'=> false,
                'label' => 'Subject (Optional)',
                'placeholder' => 'All subjects',
                'options' => $subject->getPosts( [], true ),
                'value' => intval($_REQUEST['subject_id'] ?? 0),
            )
        );

        $fields['start_date'] = array(
            'type' => 'date',
            'name' => 'start_date',
            'settings' => array(
                'required'=> false,
                'label' => 'Start Date (Optional)',
                'placeholder' => 'Select a date',
                'value' => esc_attr($_REQUEST['start_date'] ?? ''),
            )
        );

        $fields['end_date'] = array(
            'type' => 'date',
            'name' => 'end_date',
            'settings' => array(
                'required'=> false,
                'label' => 'End Date (Optional)',
                'placeholder' => 'Select a date',
                'value' => esc_attr($_REQUEST['end_date'] ?? ''),
            )
        );

        $options = array('marks' => 'Marks Based');
        $gt = new GradeTable();
        $options2 = $gt->getPosts( [], true );
        $fields['ranking_method'] = array(
            'type' => 'select',
            'name' => 'ranking_method',
            'settings' => array(
                'options'   => $options + $options2,
                'value' => esc_attr($_REQUEST['ranking_method'] ?? ''),
                'label' => 'Ranking Method',
                'placeholder' => 'Select',
                'required' => true,
            )
        );

        $fields['order_by'] = array(
            'type' => 'select',
            'name' => 'order_by',
            'settings' => array(
                'options'   => array('exam_date' => 'Exam Date', 'title' => 'Subject'),
                'value' => esc_attr($_REQUEST['order_by'] ?? ''),
                'label' => 'Order By',
                'placeholder' => 'Select',
            )
        );

        $fields['role'] = array(
            'type' => 'hidden',
            'name' => 'role',
            'settings' => array(
                'value' => 'student',
            )
        );

        return $fields;

    }

    /**
     * Return ordinal value of a number
     *
     * @param int $number
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function ordinal($number)
    {

        if ( $number == 0 || empty($number) ) return 'N/A';

        if( !is_numeric( $number ) ) return $number;

        if (!in_array(($number % 100), array(11, 12, 13))) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }

    /**
     * Get result signature box
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getEndorsementBox()
    {
        $title = trim(Admin::getSetting('result_signature_box_title'));
        $columns = trim(Admin::getSetting('result_signature_box_columns'));
        if(!empty($columns)){
            $columns = explode("\r\n", $columns);
            $columns = array_unique($columns);
        }
        ob_start();
        $box_height = Admin::getSetting('result_signature_box_height', 0.5);
        ?>
        <div class="edupress-table-wrap no-view" style="margin-top: 20px;">
            <table class="edupress-table">
                <?php if(!empty($title)) : ?>
                <tr>
                    <th colspan="<?php echo is_array($columns) ? count($columns) : 1; ?>" style="text-align: center"><?php echo $title; ?></th>
                </tr>
                <?php endif; ?>

                <?php if(!empty($columns)): ?>
                    <tr>
                        <?php foreach($columns as $column): ?>
                            <th style="text-align: left; text-decoration: underline;">
                                <?php echo $column; ?>
                                <div style="height: <?php echo $box_height; ?>in; "> </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>

            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get after list html
     * Added for guardian signature
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getAfterListHtml()
    {
        ob_start();
        if(Admin::getSetting('result_signature_box') == 'active' ): ?>
            <div>
                <div class="edupress-print-bottom-wrap">
                    <?php echo self::getEndorsementBox(); ?>
                </div>
            </div>
        <?php endif;
        return ob_get_clean();
    }

    /**
     * Get List Before Html
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterBeforeListHtml( $html )
    {
        if(!isset($_REQUEST['branch_id']) || empty($_REQUEST['branch_id'])) return '';
        ob_start();
        $format = Admin::getSetting('result_title_format');
        if(empty($format)) return '';
        $format = nl2br($format);
        $branch_id = $_REQUEST['branch_id'] ?? 0;
        $branch = $branch_id ? get_the_title($branch_id) : '';
        $shift_id = $_REQUEST['shift_id'] ?? 0;
        $shift = $shift_id ? get_the_title($shift_id) : '';
        $class_id = $_REQUEST['class_id'] ?? 0;
        $class = $class_id ? get_the_title($class_id) : '';
        $section_id = $_REQUEST['section_id'] ?? 0;
        $section = $section_id ? get_the_title($section_id) : '';
        $term_id = $_REQUEST['term_id'] ?? 0;
        $term = $term_id ? get_the_title($term_id) : '';

        $keywords = array(
            '{branch}'  => $branch,
            '{shift}'   => $shift,
            '{class}'   => $class,
            '{section}' => $section,
            '{term}'    => $term,
            '{year}'    => current_time('Y')
        );

        foreach($keywords as $key => $value){
            $format = str_replace( $key, $value, $format );
        }
        $font_size = Admin::getSetting('result_title_font_size', 20);
        ?>
        <h2 class="result-title-format" style="font-size: <?php echo $font_size ?>px; line-height: <?php echo $font_size; ?>px;"><?php echo $format; ?></h2>
        <?php
        return ob_get_clean();
    }
}

Result::instance();