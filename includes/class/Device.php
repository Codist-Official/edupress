<?php 
namespace EduPress;

class Device
{
    public static $_instance; 
    private static $base_api_url = 'http://api.edupressbd.com/wp-json/edupress_sync/v1/deviceManagement/';

    public static function instance()
    {
        if(is_null(self::$_instance))
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {

    }

    public static function getDevices()
    {
        $device_ids = Admin::getSetting('attendance_device_id');
        if(!empty($device_ids) && is_array($device_ids)){
            $device_ids = array_map( 'intval', $device_ids );
        }
        return $device_ids;
    }

    // we need to add additional params for api call 
    // system_uid, api_key, 
    public static function validateApiParams($params=[])
    {
        // merge params with api params in wordpress shortcode atts 
        return wp_parse_args($params, [
            'system_uid' => Admin::getSystemUid(),
            'api_key' => Admin::getSetting('attendance_api_key'),
        ]);
    }

    // methods to add 
    // addUser deleteUser getUser getDeviceLogStat getUserRfid remoteEnroll deleteLogs 
    public function addUser($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];

        $data['action'] = 'add_user_to_device';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        $response_data = [];
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = json_decode($response['body'], true);
        }
        return $response_data;
    }

    public function getUser($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'get_user_from_device';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                return ['success' => false, 'message' => $response->get_error_message()];
            }
            $response_data = json_decode($response['body'], true);
            return $response_data;
        }
    }


    public function getUserRfid($user_id, $check_database = true )
    {
        $data = ['user_id' => $user_id];
        $response = $this->getUser($data);
        return $response['success'] ? (int) $response['data']['cardNumber'] : '';
    }

    public function deleteUser($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'delete_user_from_device';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        $response_data = [];
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = json_decode($response['body'], true);
        }
        return $response_data;
    }

    public function remoteEnroll($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'remote_enroll';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        $response_data = [];
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = json_decode($response['body'], true);
        }
        return $response_data;
    }

    public function deleteLogs($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'delete_logs';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = json_decode($response['body'], true);
        }
        return $response_data;
    }

    public function getLogStats($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'get_log_stats';
        $data = self::validateApiParams($data);

        $response_data = [];

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            $res = json_decode($response['body'], true);
            $serial = isset($res['data']['DeviceSerialNo']) ? $res['data']['DeviceSerialNo'] : '';
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = $res['data'];
        }
        return $response_data;
    }


    public function pullRfidNumbers()
    {
        $key = "_pull_rfid_numbers";
        $value = (int) get_option($key, 0);
        $limit = 100; 

        // get users having id more than value 
        global $wpdb; 

        $qry = $wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID > %d ORDER BY ID ASC LIMIT %d", $value, $limit);
        $users = $wpdb->get_results($qry);
        if(empty($users)){
            return [];
        }

        $rfid_numbers = [];
        foreach($users as $user){
            $attendance_id = get_user_meta($user->ID, 'attendance_id', true);
            if(empty($attendance_id)){
                continue;
            }
            $rfid_number = $this->getUserRfid($attendance_id);
            echo "User ID: " . $user->ID . " RFID Number: " . $rfid_number . "\n";
            if($rfid_number > 0){
                $rfid_numbers[$attendance_id] = $rfid_number;
            }
        }

        update_option($key, $users[count($users) - 1]->ID, 'no');
        return $rfid_numbers;
        
    }

    public function deleteFace($data=[], $devices=[])
    {
        if(empty($devices)){
            $devices = self::getDevices();
        }
        if(empty($devices)) return ['success' => false, 'message' => 'No devices found'];
        
        $data['action'] = 'delete_face';
        $data = self::validateApiParams($data);

        // join url and data with ?
        $url = self::$base_api_url . '?' . http_build_query($data);
        $response_data = [];
        foreach($devices as $device_id){
            $response = wp_remote_get($url . '&device_id=' . $device_id);
            if(is_wp_error($response)){
                $response_data[$device_id]['success'] = false;
                $response_data[$device_id]['message'] = $response->get_error_message();
                continue;
            }
            $response_data[$device_id] = json_decode($response['body'], true);
        }
        return $response_data;
    }
}
