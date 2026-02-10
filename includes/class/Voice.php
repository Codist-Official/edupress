<?php
namespace EduPress;

defined('ABSPATH') || die();

class Voice extends CustomPost
{

    private static $_instance; 
    protected $table = 'voice_logs';
    protected $post_type = 'voice';

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
        <script>
            jQuery(document).ready(function(){
                if( typeof smsGetCurBal != 'undefined' ){

                    smsGetCurBal();
                    setTimeout( smsGetCurBal, edupress.sms_balance_refresh_sec * 1000 );

                }
            })
        </script>
        <h3 style="margin-top: 50px;">Current balance: ৳ <span class="sms-current-balance">0</span></h3>

        <div class="edupress-table-wrap" style="margin-top: 50px;">
            <table class="edupress-table edupress-master-table tablesorter">

                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'edupress' ); ?></th>
                        <th><?php _e( 'Mobile', 'edupress' ); ?></th>
                        <th><?php _e( 'Duration', 'edupress' ); ?></th>
                        <th><?php _e( 'Rate', 'edupress' ); ?></th>
                        <th><?php _e( 'Cost', 'edupress' ); ?></th>
                        <th><?php _e( 'Sent By', 'edupress' ); ?></th>
                        <th><?php _e( 'Record Time', 'edupress' ); ?></th>
                    </tr>
                </thead>

                <?php
                    foreach($results as $result){
                        $error_class = !is_null($result->response_code) && $result->response_code != 202 ? "error" : "success";
                        ?>
                        <tr class="row-<?php echo $error_class; ?>">
                            <td><?php echo $result->id; ?></td>
                            <td><?php echo $result->mobile; ?></td>
                            <td><?php echo $result->duration; ?></td>
                            <td><?php echo $result->rate; ?></td>
                            <td><?php echo $result->cost; ?></td>
                            <td><?php
                                if($result->user_id){
                                    $user = get_user_by( 'id', $result->user_id ); 
                                    if( $user ) echo User::showProfileOnClick( $result->user_id, $user->first_name ); 
                                } else {
                                    echo "System";
                                }
                                ?></td>
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

    public static function getAttendanceEntryId()
    {
        return Admin::getSetting('voice_entry_audio_id');
    }

    public static function getAttenanceExitId()
    {
        return Admin::getSetting('voice_exit_audio_id');
    }

    public static function getBalance()
    {
        $response = wp_remote_get(
            'https://voicesms.softcents.com/api/account/balance',
            array(
                'method' => 'GET',
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer '. Admin::getSetting('voice_api_token'),
                )
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( $response->get_error_message() );
            return ['status' => 0, 'error'=>$response->get_error_message(), 'response'=>$response];
        } else {
            $body = json_decode($response['body'], true);
            $pulse_rate = $body['data']['pulse_rate'] ?? 0;
            $balance = $body['data']['balance'] ?? 0;
            return ['status' => 1, 'balance' => $balance, 'pulse_rate' => $pulse_rate ];
        }

    }

    public static function send($mobile='', $audio_id=0, $user_id=null)
    {
        if(empty($mobile)) return ['status'=>0, 'errors'=>'Mobile blank'];
        if(empty($audio_id) || $audio_id == 0) return ['status'=>0, 'errors'=>'Audio ID cannot be blank'];

        $response = wp_remote_post(
            'https://voicesms.softcents.com/api/calls',
            array(
                'method'  => 'POST',
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer '. Admin::getSetting('voice_api_token'),
                ),
                'body'    => array(
                    'caller_id'    => Admin::getSetting('voice_caller_id'),
                    'audio_id'     => $audio_id,
                    'phone_number' => $mobile,
                    'scheduled_at' => current_time('mysql'),
                ),
                'timeout' => 30,
            )
        );
        
        if ( is_wp_error( $response ) ) {
            error_log( $response->get_error_message() );
            return ['status' => 0, 'error'=>$response->get_error_message(), 'response'=>$response];
        } else {
            $reponse = [];
            $status_code = wp_remote_retrieve_response_code( $response );
            if($status_code != 201){
                return ['status'=> 0, 'error'=> 'Call not sent'];
            }

            $reponse['status'] = 1;
            $response['message'] = 'Voice call submitted';

            $body        = wp_remote_retrieve_body( $response );
        
            // Optional: decode JSON response
            $data = json_decode( $body, true );

            $reponse['response'] = $data;

            $id = $data['id'] ?? null; 
            $delivered = isset($data['status']) && $data['status']  == 'answered' ? 1 : 0;
            $duration = $data['duration'] ?? 0;
            $cost = $data['cost'] ?? 0;

            // inserting into database 
            global $wpdb; 
            $insert = $wpdb->insert(
                $wpdb->prefix . 'voice_calls',
                array(
                    'mobile' => $mobile, 
                    'user_id' => $user_id,
                    'rate' => Admin::getSetting('voice_sms_rate'),
                    'msg_id' => $id, 
                    'delivered' => $delivered,
                    'duration' => $duration,
                    'cost' => $cost
                )
            );
            if(!empty($wpdb->last_error)) $reponse['db_error'] = $wpdb->last_error;
            else $reponse['db_id'] = $wpdb->last_id;
            return $response;
        }
        
    }

}

Voice::instance();