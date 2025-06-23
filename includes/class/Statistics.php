<?php
namespace EduPress;

defined('ABSPATH') || die();

class Statistics
{

    /**
     * @param $_instance
     */
    private static $_instance;

    /**
     * Initializes instance
     *
     * @return Statistics
     *
     * @since 1.0
     * @acesss public
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ){

            self::$_instance = new self();

        }

        return self::$_instance;

    }

    /**
     * Return dashboard statistics
     *
     * @return string
     *
     * @since 1.0
     * @acess public
     * @static
     */
    public static function getDashboardStats()
    {

        $types = array(
            'branch' => 'Branch',
            'shift'  => 'Shift',
            'class'  => 'Class',
            'section'=> 'Section',
            'subject' => 'Subject',
            'grade_table' => 'Grade Table',
            'calendar' => 'Calendar',
            'term' => 'Exam Term',
            'exam' => 'Exam',
            'user' => 'User',
            'sms' => 'SMS',
            'attendance' => 'Attendance',
            'transaction' => 'Transaction',
        ) ;
        $types_always_active = [ 'grade_table', 'user', 'setting', 'result' ];

        $menus = [];

        foreach( $types as $k => $v ){

            if( !in_array( $k, $types_always_active ) && Admin::getSetting(strtolower($k).'_active') === 'inactive' ) continue;

            if( User::currentUserCan( 'read', $k ) ){

                $menus[$k] = $v;

            }
        }

        if( empty($menus) ) return '';

        ob_start();
        ?>
        <ul class="dashboard-stats-wrap">
            <?php
                $cur_page = get_permalink(get_the_ID());
                foreach($menus as $k => $v ){
                    ?>
                    <li>
                        <a href="<?php echo $cur_page; ?>?panel=<?php echo $k; ?>">
                            <div class="icon-wrap"><?php echo EduPress::getIcon($k, '', '3x'); ?></div>
                            <div class="title-wrap"><?php echo $v; ?></div>
                            <div class="count-wrap"><?php echo self::countPosts($k  ); ?></div>
                        </a>
                    </li>
                    <?php
                }
            ?>
        </ul>

        <?php
        return ob_get_clean();


    }

    /**
     * count total posts
     *
     * @param string $post_type
     * @param array $data
     * @return int
     * @since 1.0
     *
     */
    public static function countPosts( $post_type, $data = [] )
    {
        global $wpdb;
        $count = 0;

        switch($post_type){

            case 'post':
            case 'branch':
            case 'shift':
            case 'class':
            case 'section':
            case 'subject':
            case 'term':
            case 'exam':
            case 'grade_table':

                $args = array(
                    'post_type'      => $post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1 // Retrieve all posts
                );

                $query = new \WP_Query($args);
                $count = (int) $query->found_posts;
                wp_reset_query();
                break;
            case 'calendar':
                $qry = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'academic_calendar' ";
                $count = $wpdb->get_var($qry);
                break;
            case 'sms':
            case 'attendance':
            case 'transaction':
                $table = $wpdb->prefix. $post_type;
                if($post_type == 'sms') $table .= '_logs';
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $wpdb->flush();
                break;
            case 'user':
                $user_count = count_users();
                $count = $user_count['total_users'];
                break;
        }

        return $count;

    }
}

Statistics::instance();