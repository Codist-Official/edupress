<?php
namespace EduPress;
defined( 'ABSPATH' ) || die();

class Printer
{

    /**
     * @param $_instance
     */
    private static $_instance;

    /**
     * Initialize instance
     *
     * @return Printer
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if( is_null( self::$_instance) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * @constructor
     *
     * @since 1.0
     * @access public
     */
    public function __construct()
    {

        // header and footer content
        add_action( 'wp_footer', [ $this, 'footerHtml' ] );

    }

    /**
     * Get printer header content
     *
     * @return string
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getHeader()
    {
        $font_family = Admin::getSetting('print_font_family');
        $font_family_escaped = str_replace(' ', '+', $font_family);
        $font_size = Admin::getSetting('print_font_size');
        $line_height = Admin::getSetting('print_line_height');

        $header_height = Admin::getSetting('print_header_height', 1.5);
        $logo_height = Admin::getSetting('print_logo_height', 0.5);
        $header_elements = Admin::getSetting('print_header_elements');
        if(!is_array($header_elements)) $header_elements = explode(',', $header_elements);

        $qr_code = Admin::getSetting('print_qr_code');
        $qr_code_size = Admin::getSetting('print_qr_code_size');
        $qr_position = Admin::getSetting('print_qr_code_position');

        $top_margin = Admin::getSetting('print_top_margin');
        $bottom_margin = Admin::getSetting('print_bottom_margin');
        $left_margin = Admin::getSetting('print_left_margin');
        $right_margin = Admin::getSetting('print_right_margin');

        $footer_height = Admin::getSetting('print_footer_height', 1);
        ob_start();
        ?>
        <link rel="stylesheet" type="text/css" media="screen, print" href="<?php echo EDUPRESS_CSS_URL; ?>print.css?v=<?php echo rand(1,10000); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=<?php echo $font_family_escaped; ?>:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
        <style type="text/css" media="screen,print">
            @page{
                size: <?php echo Admin::getSetting('print_paper_size'); ?>;
                margin: <?php echo "{$top_margin}in {$right_margin}in {$bottom_margin}in {$left_margin}in" ?>;
            }
            body, th, td, a, p, div, span, .edupress-table td, .edupress-table th{
                font-family: "<?php echo $font_family; ?>", sans-serif !important;
                font-size: <?php echo $font_size; ?>px !important;
                line-height: <?php echo $line_height; ?>px !important;
            }
            table.edupress-table td,
            table.edupress-table th{
                padding: 3px !important;
            }
            .edupress-print-header-wrap{
                height: <?php echo $header_height; ?>in;
                width: 100%;
            }
            .header-logo img{
                height: <?php echo $logo_height; ?>in;
                width: auto;
            }
            .print-qr-code{
                display: inline-block;
                position: fixed;
                text-align: center;
                <?php
                    $top_margin = Admin::getSetting('print_qr_code_top_margin');
                    $bottom_margin = Admin::getSetting('print_qr_code_bottom_margin');
                    $left_margin = Admin::getSetting('print_qr_code_left_margin');
                    $right_margin = Admin::getSetting('print_qr_code_right_margin');
                    switch (trim(strtolower($qr_position))){

                        case 'topleft':
                            echo "top: {$top_margin}in; left: {$left_margin}in;";
                            break;

                        case 'bottomleft':
                            echo "bottom: {$bottom_margin}in; left: {$left_margin}in;";
                            break;

                        case 'bottomright':
                            echo "bottom: {$bottom_margin}in; right: {$right_margin}in;";
                            break;

                        case 'topright':
                        default:
                            echo "top: {$top_margin}in; right: {$right_margin}in;";
                            break;

                    }
                ?>
            }
            .main{
                page-break-before: auto;
                page-break-inside: auto;
            }
        </style>
        <div class="edupress-print-header-wrap">
            <?php if( in_array( 'logo', $header_elements ) ): ?>
                <div class="header-logo"><?php echo wp_get_attachment_image( Admin::getSetting('institute_logo_id'), 'full') ?></div>
            <?php endif; ?>
            <?php if( in_array( 'institute_name', $header_elements ) ): ?>
                <div class="header-title"><?php echo Admin::getSetting('institute_name'); ?></div>
            <?php endif; ?>
            <?php if( in_array( 'address', $header_elements ) ): ?>
                <div class="header-address"><?php echo Admin::getSetting('institute_address'); ?></div>
            <?php endif; ?>
            <div class="header-metadata">
                <?php
                    $eiin = Admin::getSetting('institute_eiin');
                    $phone = Admin::getSetting('institute_phone');
                    $website = site_url();
                    $protocol = is_ssl() ? 'https://' : 'http://';
                    $website = str_replace( $protocol, '', $website );
                    $email = Admin::getSetting('institute_email');
                    $data = [];
                    if(!empty($eiin) && in_array('eiin', $header_elements)) $data[] =  "EIIN {$eiin}";
                    if(!empty($phone) && in_array('phone', $header_elements)) $data[] = EduPress::getIcon('mobile', array('print') ) . " {$phone}";
                    if(!empty($website) && in_array('website', $header_elements)) $data[] = EduPress::getIcon('website', array('print')) . " {$website}";
                    if(!empty($email) && in_array('email', $header_elements)) $data[] = EduPress::getIcon('email', array('print')) . " {$email}";
                    echo implode( ' ', $data );
                ?>
            </div>
        </div>
        <?php if($qr_code == 'active' ): ?>
            <div class="print-qr-code" style="height: <?php echo ($qr_code_size * 40) + 10; ?>px;width:<?php echo $qr_code_size * 40; ?>px;">
                <img style="width: 100%; height: auto;" src="<?php echo EduPress::createQrForCurrentUrl( '', $qr_code_size); ?>" alt="">
                <?php
                    $qr_text = Admin::getSetting('print_qr_code_text');
                    if(!empty($qr_text)):
                    ?>
                    <span style="font-size:7px !important; width: 100%; display: inline-block; line-height: 7px !important; margin: 0 auto; text-align: center;"><?php echo $qr_text; ?></span>
                    <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php
        $html =  ob_get_clean();
        return "<div class='header' id='pageHeader'>{$html}</div>";

    }

    /**
     * Print footer
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public static function getFooter()
    {
        global $post;
        if(!has_shortcode($post->post_content, 'edupress')) return '';
        ob_start();
        ?>
        <style media="screen,print">
            .footer{
                height: <?php echo Admin::getSetting('print_footer_height', 0.5); ?>in;
                width: 100%;
                overflow: hidden;
            }
        </style>
        <section class="edupress-print-footer-wrap">
            <p style="font-size:8px !important;line-height:8px !important;margin:0;padding:0;">
                This document was generated on <?php echo date('h:i:s a, d/m/y', strtotime(current_time('mysql'))); ?>
                using <strong>EduPress School Management Software </strong> | +880 1979 001 001 | www.edupressbd.com
            </p>
        </section>

        <?php
        $html = ob_get_clean();
        return "<div class='footer' id='pageFooter'>{$html}</div>";

    }

    /**
     * Custom html to be shown
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function footerHtml()
    {
        if( isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor') return;
        ob_start();
        ?>
        <script>

            if(typeof $j === 'undefined') var $j = jQuery;

            jQuery(document).ready(function(){
                // Global print button
                $j(document).on('click', '.printContent', function(e){
                    let o = $j(this).data('orientation');
                    let popupLen = $j('.edupress-popup').length;
                    let html = '';
                    if(popupLen > 0){
                        html += $j(".edupress-popup").html();
                    } else {
                        html += $j('.edupress-before-list-wrap').length > 0 ? $j('.edupress-before-list-wrap').parent().html() : '';
                        html += $j('.edupress-master-table').length > 0 ? $j('.edupress-master-table').parent().html() : '';
                        html += $j('.edupress-after-list-wrap').length > 0 ? $j('.edupress-after-list-wrap').parent().html() : '';
                    }

                    printContent(html, o === 'p');
                })

            })
            function printContent( content = '', portrait= true ) {

                let landscapeMode = !portrait ? `<style type="text/css">@page {size: <?php echo Admin::getSetting('print_paper_size', 'A4'); ?> landscape !important;}</style>` : '';

                // Custom content to be printed
                let customContent = `
                    <html>
                    <head>
                        <title>Print Preview</title>
                        ${landscapeMode}
                    </head>
                    <body>
                        <?php echo self::getHeader(); ?>
                        <div class='main'>
                            <div class="print-section">${content}</div>
                        </div>
                        <?php echo self::getFooter(); ?>
                    </body>
                    </html>
                `;

                let printWindow = window.open('', '_blank', 'height=500,width=800');
                printWindow.document.write(customContent);
                printWindow.document.close();

                printWindow.onload = function() {
                    printWindow.print();
                };
            }
        </script>
        <?php echo ob_get_clean();

    }

    /**
     * Print individual result
     *
     *
     * @param int $user_id
     * @param array $data
     * @param string $format
     *
     * @return string
     * @since 1.0
     * @access public
     */
    public static function printIndividualResult( $user_id = 0, $user_data = [], $extra_data = [] )
    {
        $term_id = (int) $extra_data['term_id'] ?? 0;
        $user_id = intval($user_id);
        $format = $extra_data['method'] ?? 'marks';
        $subject_order = $extra_data['subject_order'] ?? [];
        if(empty($user_data)) return "No results found for the user {$user_id}!";

        ob_start();
        ?>
        <section class="head"><h2 class="master-title">Academic Progress Report</h2></section>
        <section class="student-details">
            <div class="details-wrap">
                <h3 class="master-subtitle">Student Details</h3>
                <div class="edupress-table-wrap">
                    <table class="edupress-table">
                        <tr>
                            <th style="width: 100px;"><?php _e( 'Student\'s Name', 'edupress' ); ?></th>
                            <td><?php echo get_user_meta( $user_id, 'first_name', true ) ?? ''; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Roll', 'edupress' ); ?></th>
                            <td><?php echo get_user_meta( $user_id, 'roll', true ); ?></td>
                        </tr>
                        <?php if( EduPress::isActive('branch') ): ?>
                            <tr>
                                <th><?php _e( 'Branch', 'edupress' ); ?></th>
                                <td><?php echo get_the_title(get_user_meta($user_id, 'branch_id', true )); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if( EduPress::isActive('shift') ): ?>
                            <tr>
                                <th><?php _e( 'Shift', 'edupress' ); ?></th>
                                <td><?php echo get_the_title(get_user_meta($user_id, 'shift_id', true )); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if( EduPress::isActive('class') ): ?>
                            <tr>
                                <th><?php _e( 'Class', 'edupress' ); ?></th>
                                <td><?php echo get_the_title(get_user_meta($user_id, 'class_id', true )); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if( EduPress::isActive('section') ): ?>
                            <tr>
                                <th><?php _e( 'Section', 'edupress' ); ?></th>
                                <td><?php echo get_the_title(get_user_meta($user_id, 'section_id', true )); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th><?php _e( 'Exam Term', 'edupress' ); ?></th>
                            <td><?php echo get_the_title($term_id); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if(Admin::getSetting('attendance_active') == 'active' ): ?>
            <div class="attendance-wrap">
                <h3 class="master-subtitle"><?php _e('Attendance Report', 'edupress'); ?></h3>
                <div class="edupress-table-wrap">
                    <table class="edupress-table">
                        <?php
                            $term_start = $term_end = '';
                            if($term_id){
                                $term_start = get_post_meta( intval($extra_data['term_id']), 'start_date', true);
                                $term_end = get_post_meta( intval($extra_data['term_id']), 'end_date', true);
                            }
                            $start_date = sanitize_text_field($extra_data['start_date'] ?? '');
                            $end_date = sanitize_text_field($extra_data['end_date'] ?? '');
                            if(empty($start_date)) $start_date = $term_start;
                            if(empty($end_date)) $end_date = $term_end;
                            $user = new User($user_id);
                            $cal_data = $user->getAttendanceReport( $start_date, $end_date );
                        ?>
                        <tr>
                            <th><?php _e('Dates', 'edupress'); ?></th>
                            <td><?php echo date('d/m/y', strtotime($cal_data['start_date'])) . ' - ' . date('d/m/y', strtotime($cal_data['end_date'])); ?></td>
                            <th><?php _e('Total Days', 'edupress'); ?></th>
                            <td><?php echo $cal_data['total_days']; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Open', 'edupress'); ?></th>
                            <td><?php echo $cal_data['open']; ?></td>
                            <th><?php _e('Close', 'edupress'); ?></th>
                            <td><?php echo $cal_data['close']; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Present', 'edupress'); ?></th>
                            <td><?php echo $cal_data['present']; ?></td>
                            <th><?php _e('Absent', 'edupress'); ?></th>
                            <td><?php echo $cal_data['absent']; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Presence %', 'edupress'); ?></th>
                            <td><?php echo number_format($cal_data['present_percentage'], 2); ?>%</td>
                            <th><?php _e('Absence %', 'edupress'); ?></th>
                            <td><?php echo number_format(100 - $cal_data['present_percentage'], 2); ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="result-details">
            <!-- Rank method Marks -->
            <?php
                $all_marks_heads = [];
                $optional_subject_data = [];

                foreach($user_data['results'] as $k=>$v){
                    foreach($v['marks'] as $markk=>$markv){
                        if( !in_array( $markk, $all_marks_heads ) ) $all_marks_heads[] = $markk;
                    }
                    if( isset($v['is_optional']) && $v['is_optional'] == 1){
                        $optional_subject_id = $k;
                        $optional_subject_data = $v;
                    }
                }
                ?>
            <style>
                table.ind-result tr th,
                table.ind-result tr td{
                    text-align: center;
                }
            </style>
                <div class="edupress-table-wrap">
                    <h3 class="master-subtitle">Mark Details</h3>
                    <table class="edupress-table ind-result">
                        <thead>
                            <tr>
                            <th style="text-align:left;" rowspan="2"><?php _e('Subject', 'edupress'); ?></th>
                            <?php if($format == 'marks') : ?>
                                <th rowspan="2"><?php _e('Date<br>of Exam', 'edupress'); ?></th>
                            <?php endif; ?>
                            <th rowspan="2"><?php _e('Exam<br>Mark', 'edupress'); ?></th>
                            <th style="text-align: center" colspan="<?php echo count($all_marks_heads);?>"><?php _e('Obtained Marks', 'edupress'); ?></th>
                            <th rowspan="2"><?php _e('Obtained<br>Total', 'edupress'); ?></th>
                            <?php if($format == 'marks'){ ?>
                                <th rowspan="2"><?php _e('Highest<br>Total', 'edupress'); ?></th>
                                <th rowspan="2">Total </th>
                                <th rowspan="2">Merit<br>Pos.</th>
                            <?php } else { ?>
                                <th rowspan="2">Letter<br>Grade </th>
                                <th rowspan="2">Grade<br>Point </th>
                                <th rowspan="2" style="text-align:center;">GPA <br>Without Op. Sub.</th>
                                <th rowspan="2" style="text-align:center;">GPA <br>With Op. Sub.</th>
                                <th rowspan="2" style="text-align:center;">Grade</th>
                            <?php } ?>
                        </tr>
                        <tr>
                            <!-- Head wise marks -->
                            <?php foreach($all_marks_heads as $k=>$v){
                                ?>
                                <th><?php _e( $v, 'edupress' ); ?></th>
                                <?php
                            }?>
                        </tr>
                      </thead>
                      <?php
                      $i = 0;
                      foreach($subject_order as $subject_id){

                          $result = $user_data['results'][$subject_id];

                          if(empty($result)) continue;

                          // Skip if unregistered
                          if($result['unregistered'] == 1) continue;
                          if($format != 'marks' && $result['is_optional'] == 1) continue;

                          ?>
                            <tr data-subject-id="<?php echo $subject_id; ?>" data-connected-subject-id="<?php echo $user_data['results'][$subject_id]['connected_subject_id'] ?? ''; ?>">
                                <td style="text-align: left;">
                                    <?php
                                        if ($format == 'marks'){
                                            echo get_the_title($subject_id);
                                        } else {
                                            $subject_title = '';
                                           if ( isset($result['connected_subject_id']) && $result['connected_subject_id'] > 0 ) {
                                               $subject_title = get_post_meta( $result['connected_subject_id'], 'combined_name', true );
                                           } 
                                           if(empty($subject_title)) $subject_title = get_the_title($subject_id);
                                           echo $subject_title;
                                        }
                                    ?>
                                </td>

                                <?php if($format == 'marks'): ?>
                                    <td><?php echo date('d/m/y', strtotime($result['exam_date'] ?? 0)); ?></td>
                                <?php endif; ?>

                                <td><?php echo $result['exam_marks']; ?></td>

                                <!-- Head wise marks -->
                                <?php foreach($all_marks_heads as $k=>$v){
                                    ?>
                                    <td>
                                        <?php echo $result['marks'][$v]['obtained'] ?? '-'; ?>
                                        <?php echo isset($result['marks'][$v]['exam_marks']) ? "({$result['marks'][$v]['exam_marks']})" : ''; ?>
                                        <?php echo isset($result['marks'][$v]['absent']) && $result['marks'][$v]['absent'] == 1 ? "(A)" : ''; ?>
                                        <?php echo isset($result['marks'][$v]['failed']) && $result['marks'][$v]['failed'] == 1 ? "(F)" : ''; ?>
                                    </td>
                                    <?php
                                }?>

                                <td><?php echo $result['obtained']; ?></td>

                                <!-- marks based -->
                                <?php $rowspan = count($user_data['results']); ?>
                                <?php if($format == 'marks'){ ?>

                                    <td><?php echo $result['highest']; ?></td>

                                    <!-- other details -->
                                    <?php if( $i == 0 ){ ?>
                                        <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo $user_data['total_obtained_marks']; ?></strong></td>
                                        <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo Result::ordinal($user_data['merit']); ?></strong></td>
                                    <?php } ?>

                                <!-- CGPA based -->
                                <?php } else { ?>

                                    <?php if( !empty($optional_subject_data) ) $rowspan++; ?>
                                    <td><?php echo $result['grade'] ?? ''; ?></td>
                                    <td><?php echo number_format($result['grade_point'], 2); ?></td>
                                    <?php if( $i == 0 ){ ?>
                                        <td style="text-align:center" rowspan="<?php echo $rowspan; ?>"><strong><?php echo $user_data['grade_point_without_optional']; ?></strong></td>
                                        <td style="text-align:center;" rowspan="<?php echo $rowspan; ?>"><strong><?php echo $user_data['grade_point_with_optional']; ?></strong></td>
                                        <td style="text-align:center;" rowspan="<?php echo $rowspan; ?>"><strong><?php echo $user_data['grade']; ?></strong></td>
                                    <?php } ?>

                                <?php } ?>

                            </tr>
                          <?php
                          $i++;
                      }
                      ?>

                      <!-- Optional subject details -->
                      <?php if($format != 'marks' && !empty($optional_subject_data)) : ?>
                        <tr>
                            <td style="text-align: left;" colspan="<?php echo count($all_marks_heads) + 5; ?>"><strong><?php _e('Optional Subject', 'edupress'); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">
                                <?php 
                                    $subject_title = '';
                                    $subject_title = get_post_meta( $optional_subject_id, 'combined_name', true ); 
                                    echo empty($subject_title) ? get_the_title($optional_subject_id) : $subject_title; 
                                ?>
                            </td>
                            <td><?php echo $optional_subject_data['exam_marks'] ?? 0; ?></td>
                            <?php foreach($all_marks_heads as $k => $v): ?>
                                <td><?php echo isset($optional_subject_data['marks'][$v]['obtained']) ? $optional_subject_data['marks'][$v]['obtained'] . '('. $optional_subject_data['marks'][$v]['exam_marks'] . ')' : '-'; ?></td>
                            <?php endforeach; ?>
                            <td><?php echo $optional_subject_data['obtained'] ?? ''; ?></td>
                            <td><?php echo $optional_subject_data['grade'] ?? ''; ?></td>
                            <td><?php echo $optional_subject_data['grade_point'] ? number_format($optional_subject_data['grade_point'], 2) : ''; ?></td>
                        </tr>
                      <?php endif; ?>
                  </table>
                  <p class="legends" style="margin: 3px 0 10px 0;"> Legends: <strong>(A)</strong> - Absent, <strong>(F)</strong> - Failed, <strong>N/A</strong> - Not Applicable </p>
                </div>

            <?php if($format !== 'marks' ) : ?>
            <!-- Grade table data -->
            <style>
                .grade-table-wrap{ width: 100%; max-width: 200px; float: right; margin-bottom: 20px;}
                .grade-table-wrap table tr th,
                .grade-table-wrap table tr td { font-size: 8px !important; line-height: 8px !important;}
            </style>
            <div class="grade-table-wrap">
                <h3 class="master-subtitle"><?php _e( 'Grading System', 'edupress' ); ?></h3>
                <?php echo GradeTable::getTable($extra_data['method']); ?>
            </div>
            <?php endif; ?>
        </section>

        <?php echo Result::getEndorsementBox(); ?>

        <?php
        return ob_get_clean();
    }

}

Printer::instance();