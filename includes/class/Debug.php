<?php
namespace EduPress;


@ini_set('display_errors', 0 );

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
        var_dump(Voice::getBalance());
        $id = Voice::getAttendanceEntryId();
        $mobile = '01913919597';
        var_dump( Voice::send($mobile, $id) );

        return;
        ob_start(); 
        Attendance::sendAbsenceAttendanceSMS();
        return ob_get_clean();
        ?> 
        <html>
            <head></head>
            <body>
                <style>
                    html, body, *{
                        margin: 0;
                        padding: 0;
                    }
                    .id-card-holder{
                        height: 85.598mm;
                        width: 53.975mm;
                        height: 100%;
                        width: 100%;
                        background-color: #fff;
                        border: none;
                        background-image: url('<?php echo EDUPRESS_IMG_URL; ?>front-bg.png');
                        background-size: cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        position: relative;
                    }
                    .id-card-inner{
                        width: 100%;
                        height: 100%;
                        background-color: transparent;
                        border: none;
                        top: 85mm;
                        left: 12mm;
                        position: absolute;
                        line-height: 1.65;
                    }
                    .card-row{
                        display: inline-block;
                        width: 100%;
                    }

                    .card-label,
                    .card-value{
                        display: inline-block;
                        float:left;
                        font-size: 18px;
                        font-weight: bold;
                        font-family: Calibri, sans-serif;
                    }
                    .card-label{
                        width: 18mm;
                    }
                    .card-value{
                        width: 45mm;
                    }

                    .class_color{
                        color: darkgreen;
                    }
                    .batch_color{
                        color: darkblue;
                    }
                    .id_color{
                        color: darkred;
                    }
                    .pagebreak{
                        page-break-after: always;
                    }
                </style>
                <?php 
                    $users = User::getAll(['role'=>'student']);
                    foreach($users as $user):
                        $section_id = get_user_meta($user->ID,'section_id', true);
                        if(in_array($section_id, [43,46,47])) continue;
                        $class = get_the_title(get_user_meta($user->ID,'class_id', true));
                        $section = get_the_title($section_id);
                        $roll = get_user_meta($user->ID,'roll', true);
                    ?>
                    <div class="id-card-holder">
                        <div class="id-card-inner">
                            <div class="card-row">
                                <div class="card-label">Class</div>
                                <div class="card-value class_color">: <?php echo ucwords($class); ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Batch</div>
                                <div class="card-value batch_color">: <?php echo strtoupper($section); ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">ID No</div>
                                <div class="card-value id_color">: <?php echo strtoupper($roll); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="pagebreak"></div>
                <?php endforeach; ?>
            </body>
        </html>
        <?php 
        $html = ob_get_clean();

        $settings = [
            'page_width' => '54mm',
            'page_height' => '85.598mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ];
        $response = wp_remote_post('http://pdf.edupressbd.com/', [
            'method' => 'POST',
            'body' => ['html' => $html, 'settings' => $settings],
        ]);
        if(is_wp_error($response)){
            return ['status' => 0, 'data' => $response->get_error_message()];
        }
        $data = json_decode($response['body'], true);
        echo $data['pdf'];
        echo "<br><br>";
        var_dump($data);


    }

}

Debug::instance();