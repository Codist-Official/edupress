<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Debug
{

    private static $_instance;

    /**
     * Initialize instance
     *
     * @return Debug
     * @since 1.0
     * @acccess public
     * @static
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    /**
     * Constructor
     */
    public function __construct()
    {

        add_shortcode('EduPress_Debug', [ $this, 'debug' ] );
        add_shortcode('edupress_debug', [ $this, 'debug' ] );

    }

    /**
     * Process debugging
     *
     * @return string
     * @access public
     * @since 1.0
     */
    public function debug()
    {
    
        // echo "<br><br><br><br><br><br><br><br>DEBUGINGGG";
        // $voice = Voice::send('01913919597', 'edupressbd_com_entry_test----');
        // var_dump($voice);
        // return; 
        // $users = count_users();
        // var_dump($users);
        // return;
        // var_dump(Voice::getBalance());
        // $id = Voice::getEntryVoiceId();
        // $mobile = '01913919597';
        // var_dump( Voice::send($mobile, $id) );

        // return;
        ob_start(); 
        // Attendance::sendAbsenceAttendanceSMS();
        // return ob_get_clean();
        ?> 
        <html>
            <head></head>
            <body class="print-card">
                <style>

                    body.print-card{
                        width: 290px; /* 54.61mm; */
                        height: 458px; /* 86.36mm; */
                        margin: 0;
                        padding: 0;
                        background-color: rgba(0, 255, 255. 0.2);
                        -webkit-print-color-adjust: exact !important;
                        color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        box-sizing: border-box;

                    }
                    .page-content{
                        position: relative !important;
                        width: 100% !important;
                        height: 100% !important;
                        background-color: #aaa;
                    }
                    .id-card-holder{
                        width: 290px; /* 54.61mm; */
                        height: 458px; /* 86.36mm; */
                        background-color: #fff;
                        border: none;
                        background-size: 100% 100%;
                        background-position: center;
                        background-repeat: no-repeat;
                        position: relative;
                    }
                    .id-card-inner{
                        width: 100%;
                        height: 100%;
                        background-color: transparent;
                        border: none;
                        padding: 5mm;
                        box-sizing: border-box;
                        /* top: 85mm;
                        left: 12mm; */
                        position: relative;
                        line-height: 1;
                        z-index: 9999;
                    }
                    .card-row{
                        display: inline-block;
                        width: 100%;
                    }
                    .pagebreak{
                        page-break-after: always;
                    }

                    .student-card-title{
                        margin: 0 auto;
                        background-color: #273a72;
                        color: white; 
                        padding: 5px 15px;
                        line-height: 1;
                        border-radius: 20px;
                        text-align: center;
                        font-size: 14px;
                        display: inline-block;
                    }
                    .stud-photo{
                        height: 100px;
                        width: auto;
                        box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                        border-radius: 10px;
                        overflow: hidden;
                    }
                </style>
                <?php 
                    $users = User::getAll(['role'=>'student', 'number'=>5]);
                    foreach($users as $user):
                        $section_id = get_user_meta($user->ID,'section_id', true);
                        if(in_array($section_id, [43,46,47])) continue;
                        $metadata = get_metadata('user', $user->ID);
                        $name = isset($metadata['first_name']) ? $metadata['first_name'][0] : '';
                        $data = [];
                        $data['roll'] = ['name' => "ID", 'value' =>  $metadata['roll'][0] ?? '' ];
                        $data['guardian'] = ['name' => "Guardian", 'value' =>  $metadata['guardian_name'][0] ?? '' ];
                        $data['mobile'] = ['name' => 'Mobile', 'value'=> $metadata['mobile'][0] ?? ''];
                        $data['blood_group'] = ['name' => 'Blood Group', 'value' => $metadata['blood_group'][0] ?? ''];
                        $avatar_id = get_user_meta($user->ID, 'avatar_id', true);
                        // $photo = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'full') : '';
                        // $photo_url = $photo;
                    ?>
                    <div class="id-card-holder">
                        <img src="<?php echo EDUPRESS_IMG_URL; ?>id-cards/fcghs.png" style="position: absolute; left: 0; top: 0; z-index: 1; width: 100%; height: 100%;">
                        <div class="id-card-inner">
                            <div class="card-row logo" style="display: flex; align-items: strech; margin: 0px 0 15px 0; gap: 5px; ">
                                <div class="logo-wrap" style="flex: 1; line-height: 0; align-items: center; justify-content: center; display: flex; "><img src="<?php echo EDUPRESS_IMG_URL; ?>id-cards/fcghs-logo.jpeg" class="top-logo" style=" width: 50px;"></div>
                                <div class="name-wrap" style="flex: 3; display: flex; align-items: center; background-color: #273a72; ;  font-size: 13px; color: #fff; font-weight:bold; padding: 10px; ">FOREST COLONY GIRLS' HIGH SCHOOL</div>
                            </div>
                            <div class="card-row card-title" style="text-align: center;">
                                <div class="dp-wrap" style="margin-bottom: 5px;">
                                    <?php echo wp_get_attachment_image($avatar_id, 'full', null, ['class'=>'stud-photo']); ?>
                                </div>
                            </div>
                            <div class="card-row" style="text-align:center; margin-top: 10px;">
                                <div class="student-card-title" style="margin-bottom: 10px;">Student ID Card</div>
                            </div>

                            <div class="card-row data-details" style="padding: 0 10px;">
                                <div class="card-value student-name" style="text-align: center; font-size: 15px; text-transform: uppercase; line-height: 1.25;font-weight:bold; color: #d41c15;"><?php echo strtoupper($name); ?></div>
                                <div style="font-size: 14px; line-height: 1.2; margin-top: 10px; min-height: 65px;">
                                    <?php 
                                        foreach($data as $k=>$v){
                                            if(empty($v['value'])) continue; 
                                            echo "<span class='label'>{$v['name']}</span>: <strong>{$v['value']}</strong><br>"; 
                                        }
                                    ?>
                                </div>
                            </div>
                            <div class="card-row" style="margin-top: 15px; text-align: center; ">
                                <img src="<?php echo EDUPRESS_IMG_URL; ?>id-cards/fcghs-hm.jpeg" style="height: 25px; width: auto;" ><br>
                                <span class="hm-sign" style="display: inline-block; font-size: 10px; font-weight: bold; border-top: 1px dashed #000;">Headmaster's Signature</span>
                            </div>
                            <div class="card-row" style="font-size:10px; text-align: center; line-height: 1; font-weight: 300; margin-top: 10px; color: #8ec532">
                                <?php $attendance_id = get_user_meta($user->ID, 'attendance_id', true); ?>
                                The card is generated by EduPress School Management Software [<?php echo $attendance_id; ?>]
                            </div>
                        </div>
                    </div>
                    <div class="pagebreak"></div>
                <?php endforeach; ?>
            </body>
        </html>
        <?php 
        $html = ob_get_clean();
        // echo $html;
        // return;

        $settings = [
            'page_width' => '54.61mm',
            'page_height' => '86.36mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ];
        $response = wp_remote_post('http://pdf.edupressbd.com/', [
            'method' => 'POST',
            'timeout' => 30,
            'body' => ['html' => $html, 'settings' => $settings],
        ]);
        if(is_wp_error($response)){
            var_dump(['status' => 0, 'data' => $response->get_error_message()]);
        }
        $data = json_decode($response['body'], true);
        var_dump($data);
        echo "<br><br>";
        echo $data['pdf'];
        echo "<br><br>";


    }

}

Debug::instance();