<?php
namespace EduPress;

defined('ABSPATH') || die();

class Voice extends CustomPost
{

    private static $_instance; 
    protected $table = 'voice_logs';
    protected $post_type = 'voice';
    protected static $voice_balance_key = 'edupress_voice_balance';
    protected static $voice_balance_history_key = 'edupress_voice_balance_history';

    private static function getApiToken()
    {
        return Admin::getSetting('voice_api_token');
    }
    public static function instance()
    {
        if(self::$_instance == null) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct($id=0)
    {
        parent::__construct($id);
        // Filter list query
        add_filter( "edupress_list_{$this->post_type}_query", [ $this, 'filterListQuery' ] );

        // Before list html
        add_filter( "edupress_list_{$this->post_type}_filter_form_before_html", [ $this, 'getComposeForm' ]  );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", function() {
            return [];
        });

        // Hide publish button
        add_filter( "edupress_publish_{$this->post_type}_button_html", function(){
            return '';
        });

    }

    /**
     * Filter list qry
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterListQuery ()
    {

        $status = sanitize_text_field( $_REQUEST['status'] ?? '' );

        $paged = max( get_query_var( 'paged' ), 1 );
        $page = max( get_query_var( 'page' ), 1 );

        $paged = max($paged, $page);

        $offset = $paged > 1 ? $this->posts_per_page * ($paged - 1) : 0;

        $qry = "SELECT * FROM {$this->table} WHERE 1 = 1 ORDER BY ID DESC LIMIT {$this->posts_per_page} ";

        if( $offset > 0 ) $qry .= " OFFSET {$offset} ";

        return $qry;

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

        if( !User::currentUserCan('read', $this->post_type ) ) return User::getCapabilityErrorMsg( 'see', $this->post_type . ' entries.' );

        global $wpdb;

        $results = $wpdb->get_results( $this->getListQuery() );
        if( empty( $results ) ) return __( "No {$this->post_type} found!", 'edupress' );
        ob_start();
        ?>
        <h3><?php _e( 'Current balance: ৳', 'edupress' ); ?> <span class="voice-current-balance"><?php echo number_format(self::getBalance(), 2); ?></span></h3>

        <div>
            <!-- View Balance History --> 
            <button data-success_callback="showPopupOnCallback" class="edupress-btn edupress-btn-primary edupress-ajax-link" data-ajax_action="viewBalanceHistoryHTML"><?php _e( 'View Balance History', 'edupress' ); ?></button>
            
            <?php if(current_user_can('manage_options')): ?>
                <!-- Add Balance --> 
                <button data-success_callback="showPopupOnCallback" class="edupress-btn edupress-btn-primary edupress-ajax-link" data-ajax_action="updateBalanceHTML"><?php _e( 'Add Balance', 'edupress' ); ?></button>
            <?php endif; ?>
        </div>


        <div class="edupress-table-wrap">
            <table class="edupress-table edupress-master-table tablesorter">

                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'edupress' ); ?></th>
                        <th><?php _e( 'Mobile', 'edupress' ); ?></th>
                        <th><?php _e( 'User', 'edupress' ); ?></th>
                        <th><?php _e( 'Cost', 'edupress' ); ?></th>
                        <th><?php _e( 'Status', 'edupress' ); ?></th>
                        <th><?php _e( 'Sent On', 'edupress' ); ?></th>
                    </tr>
                </thead>

                <?php
                    foreach($results as $result){
                        $error_class = !is_null($result->response_code) && $result->response_code != 202 ? "error" : "success";
                        ?>
                        <tr class="row-<?php echo $error_class; ?>">
                            <td><?php echo $result->id; ?></td>
                            <td><?php echo $result->mobile; ?></td>
                            <td>
                                <?php 
                                    if($result->user_id):
                                        $name = get_user_meta($result->user_id, 'first_name', true ) .  ' ' . get_user_meta($result->user_id, 'last_name', true);
                                        echo User::showProfileOnClick($result->user_id, $name);
                                    endif; 
                                ?>
                            </td>
                            <td><?php echo $result->rate; ?></td>
                            <td><?php echo $result->status; ?></td>
                            <td><?php echo date('h:i A, d/m/Y', strtotime($result->record_time)); ?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <?php echo $this->getPagination(); ?>
        <?php return ob_get_clean();

    }
    
    public function getComposeForm()
    {
        return '';
    }

    public static function getEntryVoiceId()
    {
        return Admin::getSetting('voice_entry_audio_id');
    }

    public static function getExitVoiceId()
    {
        return Admin::getSetting('voice_exit_audio_id');
    }

    public static function getCallRate()
    {
        return Admin::getSetting('voice_call_rate');
    }

    public static function getBalance()
    {
        return (float) get_option(self::$voice_balance_key, 0);
    }

    public static function AddBalance($amount=0)
    {
        $balance = self::getBalance();
        $balance = $balance + floatval($amount);
        update_option(self::$voice_balance_key, $balance);
        self::addBalanceHistory($amount, 'add');
        return $balance;
    }


    public static function removeBalance( $amount = 0 )
    {
        $balance = self::getBalance();
        $balance = $balance - floatval($amount);
        update_option(self::$voice_balance_key, $balance);
        self::addBalanceHistory($amount, 'remove');
        return $balance;
    }

  
    public static function addBalanceHistory($amount=0, $type='add')
    {
        $history = get_option(self::$voice_balance_history_key, []);
        $history = maybe_unserialize($history);
        $history[] = [
            'amount' => $amount,
            'type' => $type,
            'date' => current_time('mysql'),
        ];
        update_option(self::$voice_balance_history_key, $history);
    }

    public static function getBalanceHistoryHTML()
    {
        $history = get_option(self::$voice_balance_history_key, []);
        $history = maybe_unserialize($history);
        // reverse sort 
        $history = array_reverse($history);
        ob_start();
        ?>
        <h4><?php _e( 'Balance History', 'edupress' ); ?></h4>
        <?php if(empty($history)): echo __('No balance history found!', 'edupress'); return ob_get_clean(); endif; ?>
        <div class="edupress-table-wrap">
            <table class="edupress-table edupress-master-table tablesorter" style="max-width: 300px;">
                <thead>
                    <tr>
                        <th style="text-align:left;"><?php _e( 'Date', 'edupress' ); ?></th>
                        <th style="text-align:left;"><?php _e( 'Action', 'edupress' ); ?></th>
                        <th style="text-align:left;"><?php _e( 'Amount', 'edupress' ); ?></th>
                    </tr>
                </thead>
                <?php foreach($history as $item){?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($item['date'])); ?></td>
                        <td><?php echo $item['type'] == 'add' ? '+' : '-'; ?></td>
                        <td><?php echo $item['amount']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <?php return ob_get_clean();
    }


    public static function getBlanceModifyOptionHTML()
    {
        $fields = [];
        $fields['amount'] = array(
            'type' => 'text',
            'name' => 'amount',
            'settings' => array(
                'label' => __('Amount', 'edupress'),
                'value' => '',
                'required' => true,    
            )
        );
        $fields['balance_action'] = array(
            'type' => 'select',
            'name' => 'balance_action',
            'settings' => array(
                'label' => __('Action', 'edupress'),
                'required' => true,
                'options' => array(
                    'add' => __('Add', 'edupress'),
                    'remove' => __('Remove', 'edupress'),
                )
            )
        );
        $fields['submit'] = array(
            'type' => 'submit',
            'name' => '',
            'settings' => array(
                'value' => __('Submit', 'edupress'),
            )
        );
        ob_start();
        ?>
        <form action="" class="edupress-form edupress-ajax-form edupress-filter-list">
            <?php foreach($fields as $field){ ?>
                <div class="form-column">
                    <div class="label-wrap"><label for="<?php echo $field['name']; ?>"><?php echo $field['settings']['label']; ?></label></div>
                    <div class="value-wrap"><?php echo EduPress::generateFormElement( $field['type'], $field['name'], $field['settings'] ); ?></div>
                </div>
            <?php } ?>
            <?php 
                echo EduPress::generateFormElement('hidden', 'action', ['value'=>'edupress_admin_ajax']);
                echo EduPress::generateFormElement('hidden', 'ajax_action', ['value'=>'updateVoiceBalance']);
                echo EduPress::generateFormElement('hidden', '_wpnonce', ['value'=>wp_create_nonce('edupress')]);
            ?>
        </form>
        <?php 
        return ob_get_clean();
    }

    public static function send($mobile='', $audio_id=0, $user_id=null)
    {
        // check balance 
        // if balance available submit to api 
        // otherwise make it pending by making msg_id  = 0 
        // default all voice 0.29 
        global $wpdb; 
        $table = $wpdb->prefix .'voice_logs';
        $balance = self::getBalance();
        $rate = self::getCallRate();

        $insert_data = [
            'mobile' => $mobile, 
            'user_id' => $user_id,
            'rate' => $rate,
            'audio_id' => $audio_id,
            'record_time' => current_time('mysql')
        ];

        if( $rate > $balance ){
            // insufficient balance 
            $insert_data['msg_id'] = 0;
            $insert_data['status'] = 'INSUFFICIENT_BALANCE';

            $insert = $wpdb->insert($table, $insert_data);

            return ['status' => 0, 'data' => 'Insufficient balance'];
        }

        // make api reqest 
        $body = [
            'request_id' => uniqid('edupress',true),
            'voice' => $audio_id,
            'sender' => Admin::getSetting('voice_caller_id'),
            'phone_numbers' => !is_array($mobile) ? explode(',', $mobile) : $mobile,
        ];

        $args = array(
            'method'    => 'POST',
            'headers'   => array(
                'Authorization' => 'Bearer ' . self::getApiToken(),
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 20
        );

        var_dump($args);

        // make wordpress post request 
        $response = wp_remote_post('https://api.awajdigital.com/api/broadcasts', $args );

        if(is_wp_error($response)){
            $insert_data['msg_id'] = 0;
            $insert_data['status'] = 'PENDING';
            $wpdb->insert($table, $insert_data);
            return ['status' => 0, 'data'=>$response->get_error_message()];
        }


        $status = wp_remote_retrieve_response_code($response);

        $res_body = wp_remote_retrieve_body($response);
        $res_decoded = json_decode($res_body, true);

        if(!$res_decoded['success']){
            return ['status' => 0, 'data'=>$res_decoded['message']];
        }


        if(isset($res_decoded['success']) && $res_decoded['success'] ){
            $insert_data['msg_id'] = $res_decoded['broadcast']['id'];
            $insert_data['status'] = ucwords($res_decoded['broadcast']['status']);
            $wpdb->insert($table, $insert_data);
            self::removeBalance($rate);
            return['status' => 1, 'data'=>'Voice successfully submitted'];
        }

    }

}

Voice::instance();