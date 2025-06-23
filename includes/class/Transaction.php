<?php
namespace EduPress;

defined('ABSPATH') || exit;

class Transaction extends CustomPost
{
    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $id
     */
    public $id;

    /**
     * @var $table
     */
    protected $table = 'transaction';

    /**
     * @var $post_type
     */
    protected $post_type = 'transaction';

    /**
     * Initiate instance
     *
     * @return Transaction
     *
     * @since 1.0
     * @access public
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
     *
     * @return void
     *
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct( $id );

        // Filter Publish Fields
        add_filter( "edupress_publish_{$this->post_type}_fields", [ $this, 'filterPublishFields' ] );

        // Filter list query
        add_filter( "edupress_list_{$this->post_type}_query", [ $this, 'filterListQuery' ] );

        // Filter list html
        // add_filter( "edupress_list_{$this->post_type}_html", [ $this, 'filterListHtml' ] );

        // Filter search query
        add_filter ( "edupress_filter_{$this->post_type}_fields", [ $this, 'filterListFields' ] );


    }

    /**
     * Filter publish fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterPublishFields( $fields = [] )
    {
        $fields = [];
        $branch = new Branch();
        $fields['branch_id'] = array(
            'name' => 'branch_id',
            'type' => 'select',
            'settings' => array(
                'options'   => $branch->getPosts( [], true ) ,
                'label' => 'Branch',
                'required' => true,
                'id' => 'branch_id'
            )
        );
        $fields['is_inflow'] = array(
            'name' => 'is_inflow',
            'type' => 'select',
            'settings' => array(
                'options'   => array(
                    1 => 'Inflow',
                    0 => 'Outflow',
                ),
                'label' => 'Type',
                'placeholder' => 'Type',
                'required' => true,
            )
        );
        $fields['account'] = array(
            'name'  => 'account',
            'type'  => 'select',
            'settings' => array(
                'options' => array_combine(array_values(self::getAccounts()), array_values(self::getAccounts())),
                'label' => 'Account',
                'placeholder' => 'Select account',
                'required'=> true,
            )
        );
        $fields['user_search'] = array(
            'name' => 'user_search',
            'type'  => 'text',
            'settings' => array(
                'label' => 'User',
                'class' => 'user_search',
                'placeholder' => 'Type a user name',
                'required' => true,
                'after' => "<div class='transaction-user-details' style='width: 100%;'></div>",
            )
        );
        $fields['user_id'] = array(
            'name' => 'user_id',
            'type'  => 'hidden',
            'settings' => array(
                'class' => 'user_id',
            )
        );
        $fields['t_time'] = array(
            'name'  => 't_time',
            'type'  => 'datetime-local',
            'settings' => array(
                'placeholder' => 'Select input time',
                'required' => true,
                'label' => 'Time',
                'value' => current_time('mysql')
            )
        );
        $fields['shift_id'] = array(
            'type'      => 'hidden',
            'name'      => 'shift_id',
            'settings'  => array(
                'class' => 'shift_id'
            )
        );
        $fields['class_id'] = array(
            'type'      => 'hidden',
            'name'      => 'class_id',
            'settings'  => array(
                'class' => 'class_id'
            )
        );
        $fields['section_id'] = array(
            'type'      => 'hidden',
            'name'      => 'section_id',
            'settings'  => array(
                'class' => 'section_id'
            )
        );

        $fee_names = explode( ',', Admin::getSetting('transaction_fee_names') );
        if(!empty($fee_names)){
            $fee_names = array_map( 'trim', $fee_names );
        }
        $months = array( 1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
        $transaction_months = EduPress::generateFormElement( 'select', 't_month[]', array( 'options' => $months, 'value' => current_time('m')) );
        $current_year = current_time('Y');
        $min_year = $current_year - 3;
        $max_year = $current_year + 3;
        $years = range( $min_year, $max_year );
        $years = array_combine( $years, $years );
        $transaction_years = EduPress::generateFormElement( 'select', 't_year[]', array( 'options' => $years, 'value' => current_time('Y')) );
        $fee_html = EduPress::generateFormElement( 'select', 'fee_type[]', array( 'options' => array_combine( $fee_names, $fee_names ), 'required'=>true ) );
        $transaction_html = "
        <ul class='transaction-details'>
            <li>
                <div class='row-transaction-fee'>". __( 'Fee Name', 'edupress') . "</div>
                <div class='row-transaction-amount'>". __( 'Amount', 'edupress') . "</div> 
                <div class='row-transaction-due'>". __( 'Due', 'edupress') . "</div> 
                <div class='row-transaction-month'>". __( 'Month', 'edupress') . "</div>   
                <div class='row-transaction-year'>". __( 'Year', 'edupress') . "</div>   
                <div class='action-wrap'> </div> 
            </li>
            <li>
                <div class='row-transaction-fee'>{$fee_html}</div>
                <div class='row-transaction-amount'><input type='number' name='fee_amount[]' class='fee_amount' value='' placeholder='Fee amount'  step='any' required='required' aria-required='true'></div> 
                <div class='row-transaction-due'><input type='number' name='fee_due[]' value='0' placeholder='Due'  step='any'></div> 
                <div class='row-transaction-month'>{$transaction_months}</div>   
                <div class='row-transaction-year'>{$transaction_years}</div>   
                <div class='action-wrap'>
                    <a href='javascript:void(0)' class='duplicate copy-transaction-row'>+</a> 
                    <a href='javascript:void(0)' class='remove remove-transaction-row'>-</a>
                </div> 
            </li>
        </ul>
        ";

        $fields['html'] = array(
            'name'  => 'transaction_details',
            'type'  => 'html',
            'settings' => array(
                'html' => $transaction_html,
                'label' => 'Details'
            )
        );
        $fields['gross_amount'] = array(
            'name'  => 'gross_amount',
            'type' => 'text',
            'settings'  => array(
                'class' => 'gross_amount',
                'readonly'=>true,
                'label' => 'Gross Total',
                'value' => 0
            )
        );
        $fields['discount_type'] = array(
            'name'  => 'discount_type',
            'type' => 'select',
            'settings'  => array(
                'class' => 'discount_type',
                'label' => 'Discount Type',
                'options' => array(
                    'percentage' => 'Percentage',
                    'fixed' => 'Fixed',
                ),
                'value' => 'percentage',
                'after' => EduPress::generateFormElement( 'number', 'discount_amount', array(
                    'placeholder' => 'Discount Amount',
                    'class' => 'discount_amount',
                    'value' => 0,
                    'data' => array(
                        'min' => 0,
                    )
                ))
            )
        );

        $fields['amount'] = array(
            'name'  => 'amount',
            'type' => 'text',
            'settings'  => array(
                'class' => 't_amount',
                'readonly'=>true,
                'label' => 'Net Total',
                'value' => 0,
            )
        );
        $fields['method'] = array(
            'name'  => 'method',
            'type' => 'hidden',
            'settings'  => array(
                'value' => 'Offline',
            )
        );
        $fields['status'] = array(
            'name'  => 'status',
            'type' => 'hidden',
            'settings'  => array(
                'value' => 'completed',
            )
        );
        return $fields;
    }

    /**
     * Get transaction accounts
     *
     * @return array
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function getAccounts()
    {

        $accounts_text = Admin::getSetting('transaction_accounts');
        if( empty($accounts_text) ) return [];

        $accounts = explode(",", $accounts_text);
        return array_map( 'trim', $accounts );
    }

    /**
     * Publish a post
     *
     * @return int
     *
     * @since 1.0
     * @acecess public
     */
    public function publish( $data = [] )
    {
        $insert_data = [];
        $insert_data['branch_id'] = intval( $data['branch_id'] ??  0 );
        $insert_data['shift_id'] = intval( $data['shift_id'] ??  0 );
        $insert_data['class_id'] = intval( $data['class_id'] ??  0 );
        $insert_data['section_id'] = intval( $data['section_id'] ??  0 );
        $insert_data['is_inflow'] = intval( $data['is_inflow'] ??  0 );
        $insert_data['amount'] = floatval( $data['amount'] ??  '' );
        $insert_data['user_id'] = intval( $data['user_id'] ??  '' );
        $insert_data['account'] = sanitize_text_field( $data['account'] ??  '' );
        $insert_data['method'] = sanitize_text_field( $data['method'] ??  '' );
        $insert_data['input_by'] = get_current_user_id();
        $insert_data['status'] = sanitize_text_field( $data['status'] ??  '' );
        $insert_data['record_time'] = current_time('mysql');

        $t_time = sanitize_text_field($data['t_time'] ?? '');
        
        if(!empty($t_time)){
            $time = date('Y-m-d H:i:s', strtotime($t_time) );
        } else {
            $time = current_time('mysql');
        }
        $insert_data['t_time'] = $time;

        global $wpdb;
        $insert = $wpdb->insert(
            $this->table,
            $insert_data
        );

        $id = 0;

        if( $insert ){
            $id = $wpdb->insert_id;

            // Inserting item details
            for( $i = 0; $i < count($data['fee_type']); $i++ ){

                $item_data = array(
                    'transaction_id' => $id,
                    'item_name' => $data['fee_type'][$i],
                    'item_amount' => $data['fee_amount'][$i],
                    'item_due' => $data['fee_due'][$i],
                    'item_month' => $data['t_month'][$i],
                    'item_year' => $data['t_year'][$i],
                );
                $wpdb->insert(
                    $wpdb->prefix.'transaction_items',
                    $item_data
                );

            }
        }

        return $id;

    }

    /**
     * Filter list html
     *
     * @return string
     * @since 1.0
     * @access public
     */
    public function getListHtml()
    {

        global $wpdb;

        $results = $wpdb->get_results( $this->getListQuery() );

        if(empty($results)) return __( 'No transactions found', 'edupress' );

        ob_start();
        ?>
        <style>
            body .edupress-frontend-panel-wrap .content-wrap table.transaction-details tr th,
            body .edupress-frontend-panel-wrap .content-wrap table.transaction-details tr td{
                padding: 3px !important;
                font-size: 12px !important;
                color: #555 !important;
                text-align: left !important;
            }
        </style>
        <div class="edupress-table-wrap edupress-master-table">
            <table class="edupress-table tablesorter">

                <thead>
                    <tr>
                        <th><?php _e( 'Branch', 'edupress' ); ?></th>
                        <th><?php _e( 'Type', 'edupress' ); ?></th>
                        <th><?php _e( 'Amount', 'edupress' ); ?></th>
                        <th><?php _e( 'Discount', 'edupress' ); ?></th>
                        <th><?php _e( 'Paid By', 'edupress' ); ?></th>
                        <th><?php _e( 'Tran. Time', 'edupress' ); ?></th>
                        <th><?php _e( 'Details', 'edupress' ); ?></th>
                        <th><?php _e( 'Note', 'edupress' ); ?></th>
                        <th><?php _e( 'Entry By', 'edupress' ); ?></th>
                        <th><?php _e( 'Entry Time', 'edupress' ); ?></th>
                    </tr>
                </thead>

                <?php
                    $first = reset($results);
                    $branch_title = get_the_title($first->branch_id);
                    foreach($results as $r){
                        $name = get_user_meta( $r->user_id, 'first_name', true );
                        $details_qry = "SELECT * FROM {$wpdb->prefix}transaction_items WHERE transaction_id = {$r->id}";
                        $details = $wpdb->get_results( $details_qry );
                        ?>
                        <tr>
                            <td><?php echo $branch_title ; ?></td>
                            <td><?php echo $r->is_inflow  ? 'Inflow' : 'Outflow'; ?></td>
                            <td><?php echo $r->amount; ; ?></td>
                            <td><?php echo intval($r->discount); ; ?></td>
                            <td><a data-user-id="<?php echo $r->user_id; ?>" data-success_callback="showUserProfileSuccessCallback" data-error_callback="" data-action="edupress_admin_ajax" data-ajax_action="showUserProfile" href="javascript:void(0)" class="showUserProfile edupress-ajax edupress-ajax-link"><?php echo $name; ?></a></td>
                            <td><?php echo date( 'h:i a, d/m/y', strtotime($r->t_time) ) ; ?></td>
                            <td>
                                <table class="edupress-table transaction-details">

                                    <thead>
                                        <tr>
                                            <th><?php _e( 'Item', 'edupress' ); ?></th>
                                            <th><?php _e( 'Paid', 'edupress' ); ?></th>
                                            <th><?php _e( 'Due', 'edupress' ); ?></th>
                                            <th><?php _e( 'For', 'edupress' ); ?></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach($details as $d): ?>
                                            <?php 
                                                $time = $d->item_year . '-' . $d->item_month . '-01';
                                                $time = date('M, Y', strtotime($time));
                                                ?>
                                            <tr>
                                                <td><?php echo $d->item_name; ?></td>
                                                <td><?php echo $d->item_amount; ?></td>
                                                <td><?php echo $d->item_due; ?></td>
                                                <td><?php echo $time; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                            <td><?php echo $r->t_note; ?></td>
                            <td><?php echo get_user_meta( $r->input_by, 'first_name', true ); ?></td>
                            <td><?php echo date( 'h:i a, d/m/y', strtotime($r->record_time) ) ; ?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <?php echo $this->getPagination(); ?>
        <?php return ob_get_clean();
    }

    /**
     * Filter list query
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterListQuery()
    {

        $paged = max( get_query_var('paged'), 1 );
        $page = max( get_query_var('page'), 1 );
        $paged = max( $paged, $page );

        $offset = ($paged - 1) * $this->posts_per_page;

        $user_id = intval($_REQUEST['t_user_id'] ?? '');
        $branch_id = intval($_REQUEST['branch_id'] ?? '');
        $date = sanitize_text_field($_REQUEST['t_time'] ?? '');
        $date_formatted = date('Y-m-d', strtotime($date));

        $qry = "SELECT * FROM {$this->table} WHERE 1 = 1 ";

        if( !empty($user_id) ) $qry .= " AND user_id = {$user_id} ";
        if( !empty($branch_id) ) $qry .= " AND branch_id = {$branch_id} ";
        if( !empty($date) ) $qry .= " AND DATE(t_time) = '{$date_formatted}' ";

        $qry .= " ORDER BY id DESC ";
        $qry .= " LIMIT {$this->posts_per_page} ";
        
        if( $offset > 0 ) $qry .= " OFFSET {$offset} ";

        return $qry;

    }

    /**
     * Filter query fields
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function filterListFields( $fields = [] )
    {

        $fields = [];

        $branch = new Branch();
        $fields['branch_id'] = array(
            'type'      => 'select',
            'name'      => 'branch_id',
            'settings'  => array(
                'options' => $branch->getPosts( [], true ),
                'label' => 'Branch',
                'placeholder' => 'Select a branch',
                'value' => intval($_REQUEST['branch_id'] ?? 0)
            )
        );
        $fields['t_time'] = array(
            'type'      => 'date',
            'name'      => 't_time',
            'settings'  => array(
                'label' => 'Date',
                'value' => esc_attr($_REQUEST['t_time'] ?? ''),
            )
        );
        $fields['t_user'] = array(
            'type'      => 'text',
            'name'      => 't_user',
            'settings'  => array(
                'label' => 'User',
                'placeholder' => 'Type a name...',
                'class'=>'t_user',
                'value' => sanitize_text_field($_REQUEST['t_user'] ?? '')
            )
        );
        $fields['paged'] = array(
            'type'      => 'hidden',
            'name'      => 'paged',
            'settings'  => array(
                'value' => 0,
            )
        );
        $fields['t_user_id'] = array(
            'type'      => 'hidden',
            'name'      => 't_user_id',
            'settings'  => array(
                'value' => intval($_REQUEST['t_user_id'] ?? '' ),
                'class' => 't_user_id'
            )
        );

        return $fields;
    }

    /**
     * Search users for transaction
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public static function searchUsers( $conds = [] )
    {

        $term = esc_attr($conds['term'] ?? '');
        $role = esc_attr($conds['role'] ?? '');

        if( !empty($role) && !is_array($role) ) $role = explode(',', $role);

        $args = [];
        $args['role__in'] = !empty($role) ? $role  : array_values(User::getRoles());

        if ( !empty($term) ){

            if(!is_numeric($term)){
                $args['search'] = "*{$term}*";
                $args['search_columns'] = array( 'user_email', 'first_name', 'display_name' );
            }

            if(is_numeric($term)){
                $num_qry = [];
                $num_qry[] = array(
                    'key'   => 'roll',
                    'value' => $term,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                );
                if( strlen($term) >= 5 ){
                    $num_qry[] = array(
                        'key'   => 'mobile',
                        'value' => $term,
                        'compare' => 'LIKE',
                        'type' => 'CHAR',
                    );
                }
                if(count($num_qry) > 1) $num_qry['relation'] = 'OR';
                if(count($num_qry) > 0){
                    $args['meta_query'][] = $num_qry;
                }
            }
        }



        if(!empty($conds['branch_id'])){
            $args['meta_query'][] = array(
                'key'   => 'branch_id',
                'value' => (int) $conds['branch_id'],
                'compare' => '='
            );
        }

        if( isset($args['meta_query']) && count($args['meta_query']) > 1 ) $args['meta_query']['relation'] = 'AND';

        $user_qry = new \WP_User_Query($args);
        $results = $user_qry->get_results();
        if(empty($results)) return [];
        $branch_active = Admin::getSetting('branch_active') == 'active';
        $shift_active = Admin::getSetting('shift_active') == 'active';
        $class_active = Admin::getSetting('class_active') == 'active';
        $section_active = Admin::getSetting('section_active') == 'active';

        $users = [];

        foreach($results as $user){

            $e_user = new User($user->ID);

            $user_details = "Name: " . $e_user->getMeta('first_name') . " | Role: " . ucwords($e_user->getRole()) . " | Roll: " . $e_user->getMeta('roll') . " | Mobile: " . $e_user->getMeta('mobile') . "<br>";

            if( $branch_active ){
                $user_details .= "Branch: ". get_the_title( $e_user->getMeta('branch_id') );
            }

            if ( $shift_active && !empty( $e_user->getMeta('shift_id') ) ){
                $user_details .= " | Shift: ". get_the_title( $e_user->getMeta('shift_id') );
            }

            if ( $class_active && !empty( $e_user->getMeta('class_id') ) ){
                $user_details .= " | Class: ". get_the_title( $e_user->getMeta('class_id') );
            }

            if ( $section_active && !empty( $e_user->getMeta('section_id') ) ){
                $user_details .= " | Section: ". get_the_title( $e_user->getMeta('section_id') );
            }
            $user_details .= " | Date of Join: " . date('d/m/Y', strtotime($e_user->getUser()->user_registered));

            $payment_type = $e_user->getMeta('payment_type', true );
            $payment_amount = $e_user->getMeta('payment_amount', true );
            // if(!empty($payment_type))  $user_details .= "<br>Type: {$payment_type} ";
            // if(!empty($payment_amount)) $user_details .= " | Amount: " . $payment_amount;
            // $user_details .= " | Total Paid: " . $e_user->getTotalPaid();
            // $user_details .= " | Total Due: " . $e_user->getTotalDue();
            
            $user_details .= $e_user->showTransactionDetails();

            $name = $e_user->getMeta('first_name');
            $role = $e_user->getRole();
            $roll = $e_user->getMeta('roll');
            $mobile = $e_user->getMeta('mobile');

            $user_value = $name;
            if(!empty($role)) $user_value .= " | Role: " . ucwords($role);
            if(!empty($roll)) $user_value .= " | Roll: " . $roll;
            if(strtolower($role) == 'student'){
                if($class_active && !empty($e_user->getMeta('class_id'))) $user_value .= " | Class: " . get_the_title($e_user->getMeta('class_id'));
                if($section_active && !empty($e_user->getMeta('section_id'))) $user_value .= " | Section: " . get_the_title($e_user->getMeta('section_id'));
            }
            if(!empty($mobile)) $user_value .= " | Mobile: " . $mobile;

            $users[] = array(
                'key'   => $user->ID,
                'value' => $user_value,
                'details' => $user_details,
                'branch_id' => $e_user->getMeta('branch_id'),
                'shift_id' => $e_user->getMeta('shift_id'),
                'class_id' => $e_user->getMeta('class_id'),
                'section_id' => $e_user->getMeta('section_id'),
            );
        }

        return $users;
    }
}

Transaction::instance();