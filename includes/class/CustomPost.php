<?php
namespace EduPress;

defined( 'ABSPATH' ) || die();

class CustomPost extends Post
{

    /**
     * @var $_instance
     */
    private static $_instance;

    /**
     * @var $table
     */
    protected $table = '';
    /**
     * Initialize instance
     *
     * @since 1.0
     * @access public
     */
    public static function instance()
    {

        if( is_null( self::$_instance ) ){

            self::$_instance = new self();

        }

        return self::$_instance;

    }



    /**
     * Constructor
     *
     * @since 1.0
     * @access public
     */
    public function __construct( $id = 0 )
    {

        parent::__construct($id);

        global $wpdb;

        $this->table = $wpdb->prefix . $this->table;

        // Filter list query
        add_filter( "edupress_list_{$this->post_type}_query", [ $this, 'filterListQuery' ] );

    }


    /**
     * Filter list qry
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function filterListQuery ()
    {

        $paged = max( get_query_var( 'paged' ), 1 );
        $page = max( get_query_var( 'page' ), 1 );

        $paged = max($paged, $page);

        $offset = $paged > 1 ? $this->posts_per_page * ($paged - 1) : 0;

        $qry = "SELECT * FROM {$this->table} WHERE 1 = 1 ";
        $qry .= " ORDER BY ID DESC LIMIT {$this->posts_per_page} ";

        if( $offset > 0 ) $qry .= " OFFSET {$offset} ";

        return $qry;

    }

    /**
     * Count total rows
     *
     * @return int
     *
     * @sinec 1.0
     * @acesss public
     */
    public function countRows()
    {

        global $wpdb;
        $qry = $this->getListQuery();
        $qry = preg_replace('/\sORDER\sBY\s.*/i', '', $qry);
        if($this->post_type !== 'attendance'){
            $qry = str_replace('*', 'COUNT(*)', $qry);
        } else {
            $qry = str_replace('t1.*', 'COUNT(*)', $qry);
        }

        $count = (int) $wpdb->get_var( $qry );
        $wpdb->flush();
        return $count;

    }

    /**
     * Get pagination
     *
     * @return string
     *
     * @since 1.0
     * @access public
     */
    public function getPagination()
    {

        $posts_per_page = is_numeric($this->posts_per_page) ? $this->posts_per_page : 10;
        $pages = $posts_per_page > 0 ? ceil( $this->countRows() / $posts_per_page ) : 0;
        return $pages > 1 ? EduPress::getPagination( $pages ) : '';

    }

}

CustomPost::instance();