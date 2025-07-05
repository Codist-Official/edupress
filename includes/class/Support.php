<?php 
namespace EduPress;

defined('ABSPATH') or exit;

class Support extends Post{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $post_type
     */
    protected $post_type = 'support';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 1.1
     * @access public
     */
    public function __construct( $id = 0 )
    {
        parent::__construct( $id );

        // Filter search fields
        add_filter( "edupress_filter_{$this->post_type}_fields", function(){
            return [];
        });

        // Filter publish button
        add_filter( "edupress_publish_{$this->post_type}_button_html", function(){
            return '';
        });
    }

    /**
     * Summary of instance
     *
     * @return Support
     *
     * @since 1.0
     * @access public
     * @static
     */
    public static function instance()
    {
        if( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * List html
     *
     * @return string
     *
     * @since 1.1
     * @access public
     */
    public function getListHtml()
    {
        $text = "Hello, I need support for my website " . site_url() . ". I'm facing following issues: ";
        $text = urlencode($text);
        ob_start();
        ?>
        <ul class="support-list">
            <li>
                <p><i class="fa-solid fa-phone"></i> To get support over phone, please call us at <a href="tel:+8801979001001"><strong>+8801979001001</strong></a></p>
            </li>
            <li>
                <p><i class="fa-solid fa-envelope"></i> To get support over email, please send an email to <a href="mailto:support@edupressbd.com"><strong>support@edupressbd.com</strong></a></p>
            </li>
            <li>
                <p><i class="fa-brands fa-whatsapp"></i> To get support over whatsapp, please send a message to <a target="_blank" href="https://wa.me/+8801979001001?text=<?php echo $text; ?>"><strong>+8801979001001</strong></a></p>
            </li>
        </ul>
        <style>
            .support-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .support-list li {
                margin-bottom: 10px;
            }
            .support-list li p {
                margin: 0;
            }
            .support-list li p i {
                margin-right: 5px;
                font-size: 1.2em;
            }
        </style>
        <?php
        return ob_get_clean();
    }
     
}

Support::instance();