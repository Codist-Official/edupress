<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class Frontend
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * Initialize instance
     *
     * @return Frontend
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ) self::$_instance = new self();
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

        // Add custom body class
        add_filter( 'body_class', [ $this, 'addCustomBodyClass' ] );


    }

    /**
     * Add custom body class
     * @return array
     *
     * @since 1.0
     * @access public
     */
    public function addCustomBodyClass( $classes )
    {
        global $post;

        // Check if the current post content contains your custom shortcode
        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'edupress' ) ) {

            $classes[] = 'edupress-panel';

        }
        $classes[] = has_shortcode( $post->post_content, 'edupress' ) ? 'pppp' : 'xxxxx';

        return $classes;

    }

    /**
     * Show panel
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getPanel()
    {

        ob_start();
        ?>
        <div class='edupress-frontend-panel-wrap'>
            <div class="topbar-wrap"><?php echo $this->getTopBar(); ?></div>
            <div class="sidebar-wrap"><?php echo $this->getSidebar(); ?></div>
            <div class="content-wrap"><?php echo $this->getContent(); ?></div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Get Top bar
     *
     * @return string
     *
     * @since 1.0
     * @acesss public
     */
    public function getTopBar()
    {
        ob_start();
        echo $this->getTopBarLogo();
        echo $this->getTopBarMenu();
        return ob_get_clean();

    }

    /**
     * get top bar logo
     *
     * @return string
     *
     * @since 1.0
     * @access pubic
     */
    public function getTopBarLogo()
    {

        $logo_id = apply_filters( 'edupress_panel_logo_id', Admin::getSetting( 'institute_logo_id' ) );

        $url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : EDUPRESS_IMG_URL . 'edupress-logo.png';

        ob_start();
        ?>

            <div class="logo-wrap">
                <a href="<?php echo get_permalink(get_the_ID()); ?>"><img src="<?php echo $url; ?>" alt="<?php echo Admin::getSetting('institute_name'); ?>"></a>
                <div class="institute-title"><?php echo Admin::getSetting('institute_name'); ?></div>
            </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Return sidebar
     *
     * @return string
     *
     * @since 1.0
     * @acecess public
     */
    public function getTopBarMenu()
    {

        ob_start();
        ?>
        <div class="top-menu-wrap">
            <ul class="menu-links">
                <li>
                    <?php
                    if( is_user_logged_in() ):

                        $user = new User(get_current_user_id());
                        $name = $user->getMeta('first_name');
                        if( empty($name) ) $name = ucwords( $user->getUser()->display_name );
                        $names = explode( ' ', $name );
                        $names = array_map( 'ucwords', $names );
                        echo _e('Welcome ', 'edupress') . reset($names) . '!';

                    endif;
                    ?>
                </li>
                <?php if(is_user_logged_in()) : ?>
                    <li><a data-user-id="<?php echo get_current_user_id(); ?>" data-action="edit" data-success_callback="showProfileUpdateFormCallback" href="javascript:void(0)" class="edupress-modify-user"><?php _e( 'Update Profile', 'edupress' ); ?></a></li>
                    <li><a href="<?php echo wp_logout_url( EduPress::getCurrentUrl() ); ?>"><?php _e( 'Logout', 'edupress' ); ?></a></li>
                <?php else: ?>
                    <li><a data-ajax_action="getLoginForm" data-success_callback="getLoginFormSuccessCallback" class="<?php echo EduPress::getClassNames('showEduPressLoginForm', 'link'); ?>" href="javascript:void(0)"><?php _e( 'Login', 'edupress' ); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Get menu
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getMenu()
    {

        $menus = [];

        $post_types = EduPress::getFeatureList();

        $post_types_always_active = [ 'user', 'setting', 'support' ];

        foreach( $post_types as $k => $v ){

            if( !in_array( $k, $post_types_always_active ) && !EduPress::isActive($k) ) continue;

            if( User::currentUserCan( 'read', $k ) ) $menus[$k] = $v;
            
        }

        ob_start();
        $active_panel = sanitize_text_field($_REQUEST['panel'] ?? '' ) ;
        $current_link = get_permalink( get_the_ID() );
        ?>
        <!-- Mobile menu -->
        <div class="mobile-nav-menu">
            <select name="mobile-menu" id="mobileMenu">
                <?php if(User::currentUserCan('delete', 'user')): ?>
                    <option value="<?php echo $current_link; ?>?panel=dashboard">Dashboard</option>
                <?php endif; ?>
                <?php
                foreach( $menus as $k => $v ):
                    $selected = $k === $active_panel ? " selected='selected' " : ''; ?>
                    <option value="<?php echo "{$current_link}?panel={$k}"; ?>" <?php echo $selected; ?>><?php _e( $v['title'], 'edupress' ) ; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <ul class="frontend-menu">
            <?php if(User::currentUserCan('delete', 'user')): ?>
                <li class="<?php echo empty($active_panel) ? 'active' : ''; ?>">
                    <a href="<?php echo $current_link; ?>">
                        <span class="menu-icon-wrap">
                            <?php echo EduPress::getIcon('dashboard'); ?>
                        </span>
                        <?php _e( 'Dashboard', 'edupress' ); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php
                foreach( $menus as $k => $v ):
                    $active_class = $k === $active_panel ? ' active ' : '';
                    ?>
                    <li class="<?php echo $active_class; ?>">
                        <a href="<?php echo $current_link; ?>?panel=<?php echo $k; ?>">
                            <span class="menu-icon-wrap">
                                <?php echo EduPress::getIcon($v['icon']); ?>
                            </span> 
                            <?php _e( $v['title'], 'edupress' ) ; ?>
                        </a>
                        <?php if($k == 'transaction'): ?>
                            <?php $active_class = $active_panel === 'transaction_report' ? ' active ' : ''; ?>
                            <ul class="submenu">
                                <li class="<?php echo $active_class; ?>">
                                    <a href="<?php echo $current_link; ?>?panel=transaction_report">
                                        <span class="menu-icon-wrap">
                                            <?php echo EduPress::getIcon('report'); ?>
                                        </span>
                                        <?php _e('Report', 'edupress' ); ?>
                                    </a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </li>
            <?php endforeach; ?>
        </ul>

        <?php return ob_get_clean();

    }

    /**
     * Return sidebar
     *
     * @since 1.0
     * @acecess public
     */
    public function getSidebar()
    {

        ob_start();
        ?>

        <div class='frontend-menu-wrap'>
            <?php echo $this->getMenu(); ?>
        </div>

        <?php
        return ob_get_clean();

    }

    /**
     * Get top breadcrumb bar
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getTopBreadcrumbBar()
    {
        $panel = strtoupper(str_replace('_', ' ', sanitize_text_field($_REQUEST['panel'] ?? '')));
        if(empty($panel)) $panel = 'Dashboard';
        $feature_list = EduPress::getFeatureList();
        $panel_details = $feature_list[strtolower($panel)] ?? null;
        $panel_icon = $panel_details['icon'] ?? 'dashboard';
        ob_start();
        ?>
        <ul class="top-breadcrumb-bar">
            <li class="dash"><?php echo EduPress::getIcon($panel_icon); ?></li>
            <li class="current"><?php echo $panel; ?></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Get page content
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getContent()
    {

        $panel = isset($_REQUEST['panel']) ? sanitize_text_field($_REQUEST['panel']) : '';

        $is_active = Admin::getSetting($panel.'_active') == 'active';

        $always_active_panels = [ 'user', 'setting', 'support' ];

        if( !in_array($panel, $always_active_panels) && !empty($panel) && !$is_active ) return __( "This feature is not active.", 'edupress' );

        $post = null;

        switch ( strtolower( $panel ) ){

            case 'class':
            case 'klass':
                $post = new Klass();
                break;

            case 'shift':
                $post = new Shift();
                break;

            case 'branch':
                $post = new Branch();
                break;

            case 'section':
                $post = new Section();
                break;

            case 'subject':
                $post = new Subject();
                break;

            case 'term':
                $post = new Term();
                break;

            case 'exam':
                $post = new Exam();
                break;

            case 'user':
                $post = new User();
                break;

            case 'setting':
                $post = new Admin();
                break;

            case 'grade_table':
                $post = new GradeTable();
                break;

            case 'sms':
                $post = new SMS();
                break;

            case 'attendance':
                $post = new Attendance();
                break;

            case 'transaction':
                $post = new Transaction();
                break;

            case 'result':
                $post = new Result();
                break;

            case 'calendar':
                $post = new Calendar();
                break;

            case 'transaction_report':
                $post = new TransactionReport();
                break;

            case 'notice':
                $post = new Notice();
                break;

            case 'support':
                $post = new Support();
                break;

            default:
                break;
        }

        if( !is_user_logged_in() && empty($panel) ) return __( "Please select a menu item.", 'edupress' );

        if( empty($panel) || $panel == 'dashboard' ){

            $html = Statistics::getDashboardStats();

        } else if ( $panel == 'setting' ){

            $html = $post->getSettingsPanel();

        }
        else {

            $html = !is_null($post) ? $post->getList() : '';

        }

        return $this->getTopBreadcrumbBar() . $html;

    }



}
Frontend::instance();