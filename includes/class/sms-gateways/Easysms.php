<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();
class Easysms
{
    /**
     * @param $base_url
     */
    private $base_url;

    /**
     * @param $username
     */
    private $username;

    /**
     * @param $password
     */
    private $password;

    /**
     * @param $api_key
     */
    private $api_key;

    /**
     * @param $sender
     */
    private $sender;


    /**
     * Constructor
     *
     * @since 1.0
     * @access public
     */
    public function __construct()
    {

        // $this->base_url = 'http://api.easysmsbd.com/api/';
        $this->base_url = 'http://bulksmsbd.net/api/';
        $this->username = Admin::getSetting('sms_username' );
        $this->password = Admin::getSetting('sms_password' );
        $this->api_key = Admin::getSetting('sms_api_key' );
        $this->sender = Admin::getSetting('sms_sender', 8809617614215 );

    }


    /**
     * Send sms
     *
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function send( $data = [] )
    {

        if ( !isset($data['mobile']) || empty($data['mobile'] ) ) return array('error_message' => 'Mobile empty', 'response_code' => 0);
        if ( !isset($data['sms']) || empty($data['sms'] ) ) return array('error_message' => 'SMS empty', 'response_code' => 0);

        $payload = [];
        $payload['number'] = $data['mobile'];
        $payload['message'] = $data['sms'];
        $payload['api_key'] = $this->api_key;
        $payload['senderid'] = $this->sender;
        $payload['type'] = 'text';

        $url = $this->base_url . 'smsapi/' . '?' . http_build_query( $payload );

        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );

        return !is_wp_error($response) ? $response['body'] : []  ;
    }

    /**
     * Get balance
     *
     * @return float
     *
     * @since 1.0
     * @access public
     */
    public function getBalance()
    {

        $payload = [];
        $payload['api_key'] = $this->api_key;

        $url = $this->base_url . 'getBalanceApi/' . '?' . http_build_query($payload);

        $response = wp_remote_get( $url );

        if( is_wp_error( $response ) ) return ' Unknown';

        $body = json_decode($response['body'], true);

        return isset($body['balance']) ? floatval($body['balance']) : 0;

    }

}