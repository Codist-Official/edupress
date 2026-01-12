<?php
namespace EduPress;

class AdminShortcode
{

    /**
     * @param $_instance
     */
    private static $_instance;

    /**
     * Initialize Instance
     *
     * @return AdminShortcode
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance  = new self();
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
    public function __construct()
    {

        add_shortcode( 'edupress', [ $this,'process' ] );

    }

    /**
     * Process shortcode
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function process( $atts )
    {

        $params = shortcode_atts(
            array(
                'action'    => '',
            ),
            $atts
        );

        $method = $params['action'];

        if( method_exists( $this, $method ) ) return $this->$method();
        return $this->showFrontendPanel();
        
    }

    /**
     * Show frontend panel
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function showFrontendPanel()
    {

        $frontend = new Frontend();
        return $frontend->getPanel();

    }

    /**
     * Show settings panel
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function show_settings_panel()
    {

        $admin = new Admin();
        return $admin->getSettingsPanel();

    }

}

AdminShortcode::instance();