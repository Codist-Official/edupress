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

    public function getVideoList()
    {
        return [
            [
                'title' => 'How to set up Helix device, add users to device',
                'title_bn' => 'হেলিক্স ডিভাইস কিভাবে সেটআপ করতে হয়, কিভাবে ইউজার এড করতে হয় ইত্যাদি',
                'link' => 'https://www.youtube.com/watch?v=atGUaG56yWM'
            ],
            [
                'title' => 'How to add class & section',
                'title_bn' => 'কিভাবে ক্লাস এবং সেকশন ব্যবহার এড করতে হয়',
                'link' => 'https://www.youtube.com/watch?v=tNNAgxTEkUQ'
            ],
            [
                'title' => 'How to add and manage users',
                'title_bn' => 'কিভাবে ইউজার এড করতে হয় এবং ম্যানেজ করতে হয়',
                'link' => 'https://www.youtube.com/watch?v=cX2iaOZFXb4'
            ],
            [
                'title' => 'How to prepare exam result',
                'title_bn' => 'কিভাবে পরীক্ষার রেজাল্ট তৈরি করবেন',
                'link' => 'https://www.youtube.com/watch?v=WDx2zfF_LwU'
            ],
            [
                'title' => 'How to use accounting',
                'title_bn' => 'একাউন্টিং কিভাবে ব্যবহার করতে হয়',
                'link' => 'https://www.youtube.com/watch?v=Es957-OmAxg'
            ],
        ];
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
        $lang = Admin::getSetting('system_lang');

        ob_start();
        ?>
        <ul class="support-list">
            <li>
                <p><i class="fa-solid fa-phone"></i> 
                <?php ($lang == 'en') ? _t('To get support over phone, please call us at ') : _t('ফোনে সাহায্য পেতে এই নাম্বারে কল করুন '); ?>
                <a href="tel:+8801979001001"><strong>+8801979001001</strong></a></p>
            </li>
            <li>
                <p><i class="fa-solid fa-envelope"></i> 
                <?php ($lang == 'en') ? _t('To get support over email, please send an email to ') : _t('ইমেইলে সাহায্য পেতে ইমেইল করুন '); ?>
                <a href="mailto:support@edupressbd.com"><strong>support@edupressbd.com</strong></a></p>
            </li>
            <li>
                <p><i class="fa-brands fa-whatsapp"></i> 
                <?php ($lang == 'en') ? _t('To get support over whatsapp, please send a message to ') : _t('হোয়াটসএপে সাহায্য পেতে ম্যাসেজ পাঠান '); ?>

                 <a target="_blank" href="https://wa.me/+8801979001001?text=<?php echo $text; ?>"><strong>+8801979001001</strong></a></p>
            </li>
        </ul>
        <div class="yt-playlist">
            <h4><?php _t('Video Tutorials', 'edupress'); ?></h4>
            <?php 
                $videos = $this->getVideoList();
                if(!empty($videos)){
                    echo "<ul class='video-list'>";
                    foreach($videos as $video){
                        $title = $lang == 'en' ? $video['title'] : $video['title_bn'];
                        $query = parse_url($video['link'], PHP_URL_QUERY);
                        parse_str($query, $params);
                        $video_id = $params['v'] ?? null;
                        echo "<li><a class='yt-link' data-id='{$video_id}' data-link='{$video['link']}' href='javacript:void(0)'>{$title}</a></li>";
                    }
                    echo "</ul>";
                }
            
            ?>
         </div>
        <style>
            .yt-playlist{
                display: inline-block;
                height:auto;
                width: 100%;
                max-width: 1024px;
                margin: 30px 0 0;
                padding: 0;
            }

            .video-list{
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 600px;
            }
            .video-list li {
                list-style: square;
                list-style-position: inside;
                margin: 0;
                border-bottom: 1px solid #aaa;
            }
            .video-list li:nth-child(even){
                background-color: #efefef;
            }
            .video-list li:hover{
                background-color: #8ec532;
            }
            .video-list li:hover a{
                color: #fff; 
            }
            body .edupress-frontend-panel-wrap .content-wrap ul.video-list li a {
                font-size: 18px !important;
                line-height: 2 !important;
            }
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
        <script>
            jQuery(document).ready(function(e){
                
                jQuery(document).on('click', '.yt-link', function(e){
                    preventDefault(e);
                    let id = $j(this).data('id');
                    let html = `<iframe width="100%" height="100%" style="height: 100%; min-height: 500px;" src="https://www.youtube.com/embed/${id}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
                    showEduPressPopup(html);
                })
            })
        </script>
        <?php
        return ob_get_clean();
    }
     
}

Support::instance();