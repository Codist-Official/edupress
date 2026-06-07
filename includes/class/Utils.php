<?php 
namespace EduPress; 

defined('ABSPATH') || die();

class Util
{
    private static $_instance; 

    public function __construct()
    {
        // Show Principal Signature 
        add_shortcode( 'principal_sign', [$this, 'getPrincipalSign'] );
    }

    public static function instance()
    {
        if( null === self::$_instance ) self::$_instance = new self();

        return self::$_instance;
    }

    public function getPrincipalSign($atts)
    {
        $params = shortcode_atts(
            [
                'width' => 100,
            ], $atts
        );
        $key = 'principal_signature';
        $img = (int) admin::getSetting($key);
        if(!$img) return; 
        ob_start();
        ?>
        <style>
            .principalSignature{
                width: 100%;
                max-width: <?php echo $params['width'];?>px;
                height: auto;
            }
        </style>
        <?php 
        echo wp_get_attachment_image($img, 'full', null, ['class'=>'principalSignature']);
        return ob_get_clean();
    }

}

Util::instance();