<?php
namespace EduPress;


@ini_set('display_errors', 1 );

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
        // Set path to wkhtmltopdf
        require_once EDUPRESS_LIB_DIR .'/wkhtmltopdf/autoload.php';
        $pdf = new \mikehaertl\wkhtmlto\Pdf([
            'binary' => '/usr/local/bin/wkhtmltopdf', // macOS (Intel)
            'encoding' => 'UTF-8',
            'page-width'  => '54mm',
            'page-height' => '86mm',
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,
            'disable-smart-shrinking',
            'print-media-type',
        ]);

        // Your HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
        <style>
        body { margin:0; padding:0; }
        .card {
        width: 54mm;
        height: 86mm;
        background: #f4ffd6;
        position: relative;
        font-family: Georgia;
        }
        .class { position:absolute; top:54mm; left:6mm; }
        .batch { position:absolute; top:62mm; left:6mm; }
        </style>
        </head>
        <body>
        <div class="card">
        <div class="class">Class: 10</div>
        <div class="batch">Batch: A</div>
        </div>
        </body>
        </html>
        ';

        // Add HTML as page
        $pdf->addPage($html);

        // Save to file
        if (!$pdf->saveAs(__DIR__.'/output.pdf')) {
            echo $pdf->getError();
        } else {
            echo "PDF saved successfully!";
        }



    }

}

Debug::instance();