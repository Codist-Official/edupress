<?php
namespace EduPress;

defined('ABSPATH') || die();

class TransactionReport extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'transaction_report';

    /**
     * Initialize instance
     *
     * @return self
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

        // Filter Query
        add_filter( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterQueryFields' ] );

        // Add new
        add_filter( "edupress_publish_{$this->post_type}_button_html", function(){
            return '';
        });

        // After form html
        add_filter( "edupress_filter_{$this->post_type}_after_form_html", [ $this, "afterFilterFormHtml" ] );

        // After list html
        add_filter( "edupress_list_{$this->post_type}_before_html", function() { return '';} );
    }

    /**
     * Filter query fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterQueryFields( $fields = [] )
    {
        $fields = [];
        $branch = new Branch();
        $fields['branch_id'] = array(
            'type' => 'select',
            'name'  => 'branch_id',
            'settings' => array(
                'label' => 'Branch',
                'value' => sanitize_text_field($_REQUEST['branch_id'] ?? ''),
                'options' => $branch->getPosts( [], true ),
                'placeholder' => 'Select a branch',
                'required' => true,
            )
        );
        $fields['report_type'] = array(
            'type' => 'select',
            'name'  => 'report_type',
            'settings' => array(
                'label' => 'Report Type',
                'value' => sanitize_text_field($_REQUEST['report_type'] ?? ''),
                'options' => array(
                    'user' => 'User',
                    'class' => 'Class',
                    'duration' => 'Duration',
                ),
                'placeholder' => 'Select a report type',
                'required' => true,
            )
        );
        $fields['t_user'] = array(
            'type' => 'text',
            'name'  => 't_user',
            'settings' => array(
                'label' => 'Name',
                'value' => sanitize_text_field($_REQUEST['t_user'] ?? ''),
                'class' => 't_user',
            )
        );
        $fields['t_user_id'] = array(
            'type' => 'hidden',
            'name'  => 't_user_id',
            'settings' => array(
                'label' => 'User ID',
                'value' => sanitize_text_field($_REQUEST['t_user_id'] ?? ''),
                'class' => 't_user_id',
            )
        );
        if( Admin::getSetting('shift_active') == 'active'){
            $fields['shift_id'] = array(
                'type' => 'select',
                'name' => 'shift_id',
                'settings' => array(
                    'label' => 'Shift',
                    'placeholder' => 'Select',
                    'data' => array(
                        'data-group' => 'class'
                    )
                )
            );
        }
        if( Admin::getSetting('class_active') == 'active'){
            $fields['class_id'] = array(
                'type' => 'select',
                'name' => 'class_id',
                'settings' => array(
                    'label' => 'Class',
                    'placeholder' => 'Select',
                    'data' => array(
                        'data-group' => 'class'
                    )
                )
            );
        }
        if( Admin::getSetting('section_active') == 'active'){
            $fields['section_id'] = array(
                'type' => 'select',
                'name' => 'section_id',
                'settings' => array(
                    'label' => 'Section',
                    'placeholder' => 'Select',
                    'data' => array(
                        'data-group' => 'class'
                    )
                )
            );
        }
        $fields['duration'] = array(
            'type' => 'select',
            'name' => 'duration',
            'settings'=> array(
                'label' => 'Duration',
                'options' => array(
                    'this_week' => 'This Week',
                    'last_week'    => 'Last Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                    'last_year' => 'Last Year',
                ),
                'value' => sanitize_text_field($_REQUEST['duration'] ?? ''),
                'placeholder' => 'Select',
                'class'=>'report_duration'
            )
        );
        $fields['start_date'] = array(
            'type' => 'date',
            'name'  => 'start_date',
            'settings' => array(
                'label' => 'Start Date',
                'value' => sanitize_text_field($_REQUEST['start_date'] ?? ''),
                'required'=> false,
            )
        );
        $fields['end_date'] = array(
            'type' => 'date',
            'name'  => 'end_date',
            'settings' => array(
                'label' => 'End Date',
                'value' => sanitize_text_field($_REQUEST['end_date'] ?? ''),
                'required'=> false,
            )
        );

        return $fields;
    }


    /**
     * Get a class or section transaction report
     * @param int $class_id 
     * 
     * @return array 
     * @since 1.0
     * @access public
     * @static 
     */
    public static function getClassReport( $class_id = 0 )
    {
        $class_id = intval($class_id);
        $post = get_post( $class_id );
        $post_type = $post->post_type;
        $class_type = '';
        switch($post_type){
            case 'class':
                $class_type = 'class_id';
                break;
            case 'section':
                $class_type = 'section_id';
                break;
            case 'shift':
                $class_type = 'shift_id';
                break;
            case 'branch':
                $class_type = 'branch_id';
                break;
        }
        if( $class_id <= 0 ) return [];

        $students = User::getStudents( [ $class_type => $class_id ] );

        $data = [];
        foreach($students as $student){
            $user = new User($student);
            $tran_details = $user->getTransactionDetails();
            $tran_details['name'] = $user->getMeta('first_name') . ' ' . $user->getMeta('last_name');
            $tran_details['roll'] = $user->getMeta('roll');
            $tran_details['mobile'] = $user->getMeta('mobile');
            $data[$user->id] = $tran_details;
        }

        // echo "<pre>";
        // var_dump( $data );
        // echo "</pre>";


        $class_start_date = get_post_meta( $class_id, 'start_date', true );
        $class_end_date = get_post_meta( $class_id, 'end_date', true );
        $start_date = $end_date = '';
        if(empty($class_start_date) || empty($class_end_date)){
            $start_date = new \Datetime(current_time('mysql'));
            $end_date = new \Datetime(current_time('mysql'));
            $start_date->modify('first day of January');
            $end_date->modify('last day of December');
        } else {
            $start_date = new \Datetime($class_start_date);
            $end_date = new \Datetime($class_end_date);
        }
        $period = new \DatePeriod($start_date, new \DateInterval('P1M'), $end_date);

        // Format data
        $formtted_data = [];
        $formatted_data['user_data'] = [];
        $formatted_data['transaction_data'] = [];

        // Storing student data
        foreach($data as $k=>$v){
            $formatted_data['user_data'][$k] = array(
                'name' => $v['name'],
                'roll' => $v['roll'],
                'mobile' => $v['mobile'],
            );
        }

        // Storing period data
        foreach($period as $date){
            $formatted_data['months'][] = array(
                'month' => $date->format('m'),
                'year' => $date->format('Y'),
            );
        }

        // Storing transaction data
        foreach($period as $date){
            $month = $date->format('m');
            $year = $date->format('Y');
            foreach($formatted_data['user_data'] as $user_id=>$user){
                $formatted_data['transaction_data'][$user_id][$year][$month] = $data[$user_id]['details'][$year][$month] ?? [];
            }
        }
        return $formatted_data;

    }


    /**
     * Show class report
     * 
     * @return string
     * 
     * @param int $class_id
     * 
     * @since 1.0
     * @access public
     */
    public function showClassReport( $class_id = 0 )
    {
        $data = self::getClassReport( $class_id );
        if(empty($data['user_data'])) return __('No data found!', 'edupress');


        $user_total_data = [];
        $monthly_total_data = [];

        ob_start();
        ?>
        <div class="class-report-wrap">
            <div class="edupress-table-wrap">
                <h2 style="text-align: center; width: 100%;" class="text-center">Payment Report of <?php echo get_the_title( $class_id ); ?></h2>
                <br>
                <table class="edupress-table compact edupress-master-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'edupress'); ?></th>
                            <th><?php _e('Roll', 'edupress'); ?></th>
                            <?php foreach($data['months'] as $month){ ?>
                                <?php $date = \DateTime::createFromFormat('!m', $month['month']); ?>
                                <th><?php echo $date->format('M') . ' '. $month['year']; ?></th>
                            <?php } ?>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['user_data'] as $user_id=>$user){ ?>
                            <tr>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['roll']; ?></td>
                                <?php foreach($data['months'] as $month_data){ ?>
                                    <?php 
                                        $month = $month_data['month']; 
                                        $year = $month_data['year']; 
                                        $paid = $data['transaction_data'][$user_id][$year][$month]['paid'] ?? '-';
                                        $due = $data['transaction_data'][$user_id][$year][$month]['due'] ?? '-';
                                        $due_class = $due > 0 ? 'text-red' : '';
                                        $user_total_data[$user_id]['paid'] = isset( $user_total_data[$user_id]['paid'] ) ? $user_total_data[$user_id]['paid'] + intval($paid) : intval($paid);
                                        $user_total_data[$user_id]['due'] = isset( $user_total_data[$user_id]['due'] ) ? $user_total_data[$user_id]['due'] + intval($due) : intval($due);
                                        $monthly_key = "{$month}_{$year}";
                                        $monthly_total_data[$monthly_key]['paid'] = isset( $monthly_total_data[$monthly_key]['paid'] ) ? $monthly_total_data[$monthly_key]['paid'] + intval($paid) : intval($paid);
                                        $monthly_total_data[$monthly_key]['due'] = isset( $monthly_total_data[$monthly_key]['due'] ) ? $monthly_total_data[$monthly_key]['due'] + intval($due) : intval($due);
                                    ?>
                                    <td class="text-center">
                                        <div class="text-center text-green"><?php echo $paid; ?></div>
                                        <div class="text-center <?php echo $due_class; ?>"><?php echo $due; ?></div>
                                    </td>
                                <?php } ?>
                                <td class="text-center">
                                    <div class="text-center text-green"><?php echo number_format( $user_total_data[$user_id]['paid'], 0 ); ?></div>
                                    <div class="text-center text-red"><?php echo number_format( $user_total_data[$user_id]['due'], 0 ) ; ?></div>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <?php foreach($monthly_total_data as $month_key=>$month_data){ ?>
                                <td class="text-center">
                                    <div class="text-center text-green"><?php echo number_format( $month_data['paid'], 0 ); ?></div>
                                    <div class="text-center text-red"><?php echo number_format( $month_data['due'], 0 ); ?></div>
                                </td>
                            <?php } ?>
                            <td class="text-center">
                                <div class="text-center text-green"><?php echo number_format( array_sum( array_column( $monthly_total_data, 'paid' ) ), 0 ); ?></div>
                                <div class="text-center text-red"><?php echo number_format( array_sum( array_column( $monthly_total_data, 'due' ) ), 0 ); ?></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * After form html
     *
     * @retrun string
     *
     * @since 1.0
     * @access public
     */
    public function afterFilterFormHtml()
    {
        $types = [ 'this_week', 'last_week', 'this_month', 'last_month', 'last_3_months', 'last_6_months', 'this_year', 'last_year', 'today', 'yesterday' ];
        $options = [];
        foreach($types as $type){
            $options[$type] = self::getDateRange( $type );
        }
        ob_start();
        ?>
        <script>
            let dateRanges = <?php echo json_encode($options); ?>;
            if(typeof $j === 'undefined') var $j = jQuery();
            $j(document).ready(function(){

                const updateReportFields = () => {
                    var sels = [ 't_user', 'shift_id', 'class_id', 'section_id', 'duration', 'start_date', 'end_date', 'submit' ];
                    var activeSels = [];
                    sels.forEach( (k,v) => {
                        $j(`.edupress-filter-list div[data-name=${k}]`).hide();
                    })
                    $j(`.edupress-filter-list :input[name=start_date]`).val('');
                    $j(`.edupress-filter-list :input[name=end_date]`).val('');

                    var reportType = $j(":input[name=report_type]").val();
                    if(reportType !== ''){
                        if(reportType === 'user'){
                            activeSels = ['t_user'];
                        } else if( reportType === 'class' ){
                            activeSels = ['shift_id', 'class_id', 'section_id'];
                        } else if ( reportType === 'duration' ){
                            activeSels = ['duration'];
                        }
                        if(activeSels.length > 0){
                            activeSels.push('start_date');
                            activeSels.push('end_date');
                        }
                        activeSels.forEach( (k,v) => {
                            $j(`.edupress-filter-list div[data-name=${k}]`).show();
                        })
                        $j(`.edupress-filter-list div[data-name=submit`).show();
                    }
                }

                // trigger update fields
                $j(document).on('change', ':input[name=report_type]', function(){
                    updateReportFields();
                })

                updateReportFields();


                // Report duration change
                $j(document).on( 'change', '.report_duration', function(){
                    var v = $j(this).val();
                    var dates = dateRanges[v];
                    console.log(dates);
                    $j(`:input[name='start_date']`).val(dates['start']);
                    $j(`:input[name='end_date']`).val(dates['end']);
                })
            })
        </script>
        <?php
        return ob_get_clean();
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
        $report_type = sanitize_text_field($_REQUEST['report_type'] ?? '');
        $start_date = sanitize_text_field($_REQUEST['start_date'] ?? '');
        $end_date = sanitize_text_field($_REQUEST['end_date'] ?? '');
        $user_id = intval($_REQUEST['t_user_id'] ?? 0);
        
        ob_start(); ?>
        <div class="transaction-report-wrap">


            <style>
                h5{
                    margin-top: 0 !important
                }
            </style>


            <!-- Duration Report -->
            <?php if( $report_type === 'duration' ): ?>
                <?php 
                    global $wpdb;
                    $qry = "SELECT *, SUM(amount) AS total_amount, SUM(discount) AS total_discount FROM {$wpdb->prefix}transaction WHERE 1 = 1 ";
                    $qry .= " AND is_inflow = 1 ";
                    if(!empty($start_date)) $qry .= " AND DATE(t_time) >= '{$start_date}' ";
                    if(!empty($end_date)) $qry .= " AND DATE(t_time) <= '{$end_date}' ";
                    $qry .= " AND status = 'completed' ";
                    $qry .= "GROUP BY is_inflow, DATE(t_time) ORDER BY t_time DESC ";

                    $results = $wpdb->get_results($qry);
                    if(empty($results)) return __('No transactions found!', 'edupress');
                    $data = [] ;
                    foreach($results as $r){
                        $date = date('Y-m-d', strtotime($r->t_time));
                        $type = $r->is_inflow ? 'inflow' : 'outflow';
                        $data[$date] = array(
                            'amount' => $r->total_amount,
                            'discount' => $r->total_discount,
                        );
                    }
                ?>

                <div class="duration-report-wrap">
                    <div class="edupress-table-wrap">
                        <table class="edupress-table">
                            <tr>
                                <th style="text-align: left;">Date</th>
                                <th style="text-align: center">Inflow</th>
                                <th style="text-align: center">Discount</th>
                            </tr>
                            <?php foreach($data as $k=>$v){ ?>
                                <tr>
                                    <td><?php echo date('d M, y', strtotime($k)); ?></td>
                                    <td style="text-align: center;"><?php echo isset( $v['amount']) ? number_format( $v['amount'], 0) : 0; ?></td>
                                    <td style="text-align: center;"><?php echo $v['discount'] ?? 0; ?></td>
                                </tr>
                            <?php } ?>
                            <tfoot>
                                <tr>
                                    <td> </td>
                                    <td style="text-align: center;"><?php echo number_format(  array_sum( array_column( $data, 'amount') ), 0 );  ?></td>
                                    <td style="text-align: center;"><?php echo array_sum( array_column( $data, 'discount') );  ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Class Report -->
            <?php if($report_type === 'class'): ?>
                <div class="class-report-wrap">
                    <?php 
                        $cls_id = 0;
                        if(isset($_REQUEST['section_id'])) $cls_id = intval($_REQUEST['section_id']);
                        else if(isset($_REQUEST['class_id'])) $cls_id = intval($_REQUEST['class_id']);
                        else if(isset($_REQUEST['shift_id'])) $cls_id = intval($_REQUEST['shift_id']);
                        else if(isset($_REQUEST['branch_id'])) $cls_id = intval($_REQUEST['branch_id']);
                        echo self::showClassReport( $cls_id );
                    ?>
                </div>
            <?php endif; ?>

            <!-- User Report -->
            <?php if($report_type === 'user'): ?>
                <div class="user-report-wrap">    
                    <div class="user-intro-wrap">
                        <?php $user = new User($user_id); ?>
                        <h5 style="text-align: center;margin-top: 10px;">User Details</h5>
                        <div class="edupress-table-wrap">
                            <table class="edupress-table compact">
                                <tr>
                                    <th style="text-align: left;">Name</th>
                                    <th style="text-align: left;">Branch</th>
                                    <?php if( EduPress::isActive('shift')) : ?>
                                        <th style="text-align: left;">Shift</th>                                
                                    <?php endif; ?>
                                    <?php if( EduPress::isActive('class')) : ?>
                                        <th style="text-align: left;">Class</th>
                                    <?php endif; ?>
                                    <?php if( EduPress::isActive('section')) : ?>
                                        <th style="text-align: left;">Section</th>
                                    <?php endif; ?>
                                    <th style="text-align: left;">Roll</th>
                                    <th style="text-align: left;">Mobile</th>
                                </tr>
                                <tr>
                                    <td><?php echo $user->getMeta('first_name'); ?> <?php echo $user->getMeta('last_name'); ?></td>
                                    <td><?php echo get_the_title($user->getMeta('branch_id')); ?></td>
                                    <?php if( EduPress::isActive('shift')) : ?>
                                        <td><?php echo get_the_title($user->getMeta('shift_id')); ?></td>                                
                                    <?php endif; ?>
                                    <?php if( EduPress::isActive('class')) : ?>
                                        <td><?php echo get_the_title($user->getMeta('class_id')); ?></td>
                                    <?php endif; ?>
                                    <?php if( EduPress::isActive('section')) : ?>
                                        <td><?php echo get_the_title($user->getMeta('section_id')); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $user->getMeta('roll'); ?></td>
                                    <td><?php echo $user->getMeta('mobile'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="user-pay-details" style="margin: 25px 0; width: 100%; background-color: #fff; padding: 20px; box-sizing: border-box;">
                        <h5 style="text-align: center;">Payment Details</h5>
                        <?php echo $user->showTransactionDetails(); ?>
                    </div>

                    <div class="user-transaction-details">
                        <h5 style="text-align: center;">Transaction Activity</h5>
                        <?php echo $user->showTransactionActivity(); ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php return ob_get_clean();
    }
    /**
     * Get date range of a duration
     *
     * @return array
     *
     * @param string $name
     *
     * @param string $format
     *
     * @since 1.0
     * @access public
     * @static
     *
     */
    public static function getDateRange( $name, $format = 'Y-m-d' )
    {
        $response = [];
        switch (strtolower(trim($name))){
            case 'this_week':
                $startOfWeek = (new \DateTime())->modify('this week');
                $endOfWeek = (new \DateTime())->modify('this week +6 days');
                $response = array(
                    'start'     => $startOfWeek->format($format),
                    'end'     => $endOfWeek->format($format),
                );
                break;
            case 'last_week':
                $startOfLastWeek = (new \DateTime())->modify('last week');
                $endOfLastWeek = (new \DateTime())->modify('last week +6 days');
                $response = array(
                    'start' => $startOfLastWeek->format($format),
                    'end' => $endOfLastWeek->format($format),
                );
                break;
            case 'this_month':
                $startOfMonth = (new \DateTime('first day of this month'));
                $endOfMonth = (new \DateTime('last day of this month'));
                $response = array(
                    'start' => $startOfMonth->format($format),
                    'end' => $endOfMonth->format($format),
                );
                break;
            case 'last_month':
                $startOfLastMonth = (new \DateTime('first day of last month'));
                $endOfLastMonth = (new \DateTime('last day of last month'));
                $response = array(
                    'start' => $startOfLastMonth->format($format),
                    'end' => $endOfLastMonth->format($format),
                );
                break;
            case 'last_3_months':
                $startOfLast3Months = (new \DateTime('first day of -3 month'));
                $endOfLast3Months = (new \DateTime('last day of last month'));
                $response = array(
                    'start' => $startOfLast3Months->format($format),
                    'end' => $endOfLast3Months->format($format),
                );
                break;
            case 'last_6_months':
                $startOfLast6Months = (new \DateTime('first day of -6 month'));
                $endOfLast6Months = (new \DateTime('last day of last month'));
                $response = array(
                    'start' => $startOfLast6Months->format($format),
                    'end' => $endOfLast6Months->format($format),
                );
                break;
            case 'this_year':
                $startOfYear = (new \DateTime('first day of January this year'));
                $endOfYear = (new \DateTime('last day of December this year'));
                $response = array(
                    'start' => $startOfYear->format($format),
                    'end' => $endOfYear->format($format),
                );
                break;
            case 'last_year':
                $startOfLastYear = (new \DateTime('first day of January last year'));
                $endOfLastYear = (new \DateTime('last day of December last year'));
                $response = array(
                    'start' => $startOfLastYear->format($format),
                    'end' => $endOfLastYear->format($format),
                );
                break;
            case 'today':
                $today = (new \DateTime('today'));
                $response = array(
                    'start' => $today->format($format),
                    'end' => $today->format($format),
                );
                break;
            case 'yesterday':
                $yesterday = (new \DateTime('yesterday'));
                $response = array(
                    'start' => $yesterday->format($format),
                    'end' => $yesterday->format($format),
                );
                break;
        }

        return $response;
    }
}

TransactionReport::instance();