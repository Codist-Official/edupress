<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class GradeTable extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'grade_table';

    protected $list_title = 'Grade Table List';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 1.0
     * @acesss public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        // Register branch post type
        add_action( 'init', [ $this, 'registerGradeTable' ] );

        // Before submit custom table
        add_filter( "edupress_publish_{$this->post_type}_before_submit_html", [ $this, 'addCustomHtmlBeforeSubmit']);
        add_filter( "edupress_edit_{$this->post_type}_before_submit_html", [ $this, 'addCustomHtmlBeforeSubmit'], 10, 2 );

        // List table heads
        add_filter( 'edupress_list_grade_table_fields', [ $this, 'filterListFields' ] );

        // Filter fields
        add_filter( "edupress_filter_{$this->post_type}_fields", function(){
            return [];
        });


    }


    /**
     * Initialize instance
     *
     * @return GradeTable
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Register post type grade_table
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function registerGradeTable()
    {

        register_post_type('grade_table',
            array(
                'labels' => array(
                    'name' => __( 'Grade Tables','edupress' ),
                    'singular_name' => __( 'Grade Table','edupress' ),
                    'add_item' => __('New Grade Table','edupress'),
                    'add_new_item' => __('Add New Grade Table','edupress'),
                    'edit_item' => __('Edit Grade Table','edupress')
                ),
                'public' => false,
                'has_archive' => false,
                'rewrite' => array('slug' => 'grade_table'),
                'menu_position' => 5,
                'show_ui' => true,
                'supports' => array('author', 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments', 'custom-fields')
            )
        );

    }

    /**
     * Add custom html before submit button
     * @return false|string
     *
     * @since 1.0
     * @access public
     */
    public function addCustomHtmlBeforeSubmit( $html = '', $post_id = 0 )
    {
        ob_start();
        $grade_data = [];
        if( $post_id ){

            $grade_data = maybe_unserialize( get_post_meta( $post_id, 'grade_data', true ) );

        }
        ?>
        <div class="form-row">
            <div class="label-wrap"> &nbsp; </div>
            <div class="value-wrap">
                <ul class="grade-table">
                    <?php
                        if( !empty( $grade_data) && is_array($grade_data['grade_point']) ){
                            for($i = 0; $i < count($grade_data['grade_point']); $i ++ ){
                                ?>
                                <li>
                                    <?php echo EduPress::generateFormElement( 'number', 'range_start[]', array( 'value'=> $grade_data['range_start'][$i], 'placeholder'=>'Range starts', 'required'=> true, 'data' => array( 'step' => 'any', 'min' => 0, ) )); ?>
                                    <?php echo EduPress::generateFormElement( 'number', 'range_end[]', array( 'value'=> $grade_data['range_end'][$i], 'placeholder'=>'Range ends', 'required'=> true, 'data' => array( 'step' => 'any',  'min' => 0, ) )); ?>
                                    <?php echo EduPress::generateFormElement( 'number', 'grade_point[]', array( 'value'=> $grade_data['grade_point'][$i], 'placeholder'=>'Grade Point', 'required'=> true, 'data' => array( 'step' => 'any',  'min' => 0, ) )); ?>
                                    <?php echo EduPress::generateFormElement( 'text', 'grade[]', array( 'value'=> $grade_data['grade'][$i], 'placeholder'=>'Grade', 'required'=> true, 'data' => array( 'step' => 'any' ) )); ?>
                                    <div class="action">
                                        <a href="javascript:void(0)" class="copy-grade-table-row">+</a>
                                        <a href="javascript:void(0)" class="remove-grade-table-row">-</a>
                                    </div>
                                </li>
                                <?php
                            }
                        } else {
                            ?>
                            <li>
                                <?php echo EduPress::generateFormElement( 'number', 'range_start[]', array('placeholder'=>'Range starts', 'required'=> true, 'data' => array( 'step' => 'any', 'min' => 0, ) )); ?>
                                <?php echo EduPress::generateFormElement( 'number', 'range_end[]', array('placeholder'=>'Range ends', 'required'=> true, 'data' => array( 'step' => 'any',  'min' => 0, ) )); ?>
                                <?php echo EduPress::generateFormElement( 'number', 'grade_point[]', array('placeholder'=>'Grade Point', 'required'=> true, 'data' => array( 'step' => 'any',  'min' => 0, ) )); ?>
                                <?php echo EduPress::generateFormElement( 'text', 'grade[]', array('placeholder'=>'Grade', 'required'=> true, 'data' => array( 'step' => 'any' ) )); ?>
                                <div class="action">
                                    <a href="javascript:void(0)" class="copy-grade-table-row">+</a>
                                    <a href="javascript:void(0)" class="remove-grade-table-row">-</a>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Filter list fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     *
     */
    public function filterListFields( $fields )
    {
        unset($fields['post_content']);
        unset($fields['status']);

        $fields['grade_data'] = 'Grade Details';
        $fields['post_content'] = 'Description';
        $fields['status'] = 'Status';

        return $fields;

    }

    /**
     * Get grade data
     * return both grade point and grade
     *
     *
     * @param int $id
     * @param int $marks
     * @param int $total
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getGradeData($id, $marks, $total = 100)
    {

        $marks = round(floatval($marks));
        $total = floatval($total);

        if($marks < 0) $marks = 0;

        $grade_data = maybe_unserialize( get_post_meta( $id, 'grade_data', true ) );
        $rank_type =  floatval(reset($grade_data['range_start'])) > 0 ? 'DESC' : 'ASC';

        $data = [];
        for( $i = 0; $i < count($grade_data['range_start']); $i++ ){

            $range_start = floatval($grade_data['range_start'][$i]);
            $range_end = floatval($grade_data['range_end'][$i]);

            if( $rank_type == 'ASC' && $i > 0 ){
                $prev_end = $grade_data['range_end'][$i-1];
                if( $prev_end < $range_start ) $range_start = $prev_end;
            } else {
                $next_end = $grade_data['range_end'][$i+1] ?? [];
                if( $range_start > $next_end ) $range_start = $next_end;
            }

            if($range_start > 0) $range_start = ($range_start * $total) / 100;

            if($range_end > 0) $range_end = ($range_end * $total) / 100;

            if( $marks == 0){

                $data['point'] = 0;
                $data['grade'] = 'F';
                $data['marks'] = 0;
                break;

            } else if ( $marks > $range_start && $marks <= $range_end ) {

                $data['point'] = number_format(floatval($grade_data['grade_point'][$i]), 2);
                $data['grade'] = $grade_data['grade'][$i];
                $data['marks'] = $marks;
                break;

            }

        }

        if( !empty($data) ) return $data;

        $data['point'] = 0;
        $data['grade'] = 'F';
        $data['marks'] = $marks;

        return $data;

    }

    /**
     * Get max grade point
     *
     * @param int $id
     * @return float
     *
     * @since 1.0
     * @access public
     */
    public static function getMaxGradePoint( $id )
    {

        $grade_data = maybe_unserialize( get_post_meta( $id, 'grade_data', true ) );
        $points = $grade_data['grade_point'];
        if(!is_array($points)) return 0.00;
        $points = array_map( 'floatval', $points );
        ksort($points);
        return reset($points);

    }
    /**
     * Get data in a table
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function getTable( $id = 0 )
    {
        $data = maybe_unserialize( get_post_meta( $id, 'grade_data', true ) );
        if(empty($data)) return '';
        ob_start();
        ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table">
                <tr>
                    <th><?php _e( 'Interval', 'edupress' ); ?></th>
                    <th style="text-align:center;"><?php _e( 'Grade Point', 'edupress' ); ?></th>
                    <th style="text-align:center;"><?php _e( 'Letter Grade', 'edupress' ); ?></th>
                </tr>
                <?php
                    for( $i=0; $i < count($data['range_start']); $i++ ){
                        ?>
                        <tr>
                            <td><?php echo "{$data['range_start'][$i]} - {$data['range_end'][$i]}" ;?></td>
                            <td style="text-align:center;"><?php echo number_format($data['grade_point'][$i], 2);?></td>
                            <td style="text-align:center;"><?php echo $data['grade'][$i];?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /***
     * Get grade from grade point
     *
     * @param int $id
     * @param float $point
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function getGrade( $id, $point )
    {
        $data = maybe_unserialize( get_post_meta( $id, 'grade_data', true ) );

        $grade_points = $data['grade_point'];
        $grade_points = array_map( 'floatval', $grade_points );

        $type = reset($grade_points) > end($grade_points) ? 'DESC' : 'ASC';

        if($type == 'ASC'){
            for( $i = 0; $i < count($grade_points); $i++ ){
                $start = $grade_points[$i];
                $end = $grade_points[$i+1] ?? $start + 1;
                if( $point >= $start && $point < $end ) {
                    return $data['grade'][$i];
                }
            }
        } else {

            for( $i = count($grade_points) - 1; $i >= 0 ; $i-- ){
                $start = $grade_points[$i];
                $end = $grade_points[$i-1] ?? $start + 1;
                if( $point >= $start && $point < $end ) {
                    return $data['grade'][$i];
                }
            }

        }

    }



}

GradeTable::instance();