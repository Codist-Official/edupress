<?php
namespace EduPress;
//@ini_set('display_errors', 0 );

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
        // echo PrintMaterial::getBulkIdCardHtml(['print_type' => 'class_wise', 'class_id' => 44]);
        var_dump(Attendance::scheduleDeleteLog());
    }

}

Debug::instance();