<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The virtual offline page at /offline/, which shows the offline message. The offline page slug
 * is reserved, and the login URL never redirects back to it.
 */
class LH_web_application_offline_page_class extends LH_web_application_plugin {

    private static $instance;

    var $fake_page_ran = false;

    public function fix_shortlink($url, $id, $context, $allow_slugs){

        return null;

    }

    /**
     * Create a fake page
     *
     * @param   object  $posts  Original posts object
     * @global  object  $wp     The main WordPress object
     * @global  object  $wp     The main WordPress query object
     * @return  object  $posts  Modified posts object
     */
    public function generate_fake_page( $posts,  $wp_query) {

    	global $wp, $wp_query;


    	if ( empty( $this->fake_page_ran ) && ( strtolower( $wp->request ) == self::return_offline_page_slug() ) ) {

    		// stop interferring with other $posts arrays on this page (only works if the sidebar is rendered *after* the main page)
    		$this->fake_page_ran = true;

    		// create a fake virtual page
    		$post = new stdClass;
    		$post->post_author      = 1;
    		$post->post_name        = self::return_offline_page_slug();
    		$post->guid             = self::return_offline_page_url();
    		$post->post_title       = __( 'Offline', self::return_plugin_text_domain() );
            $post->post_content     =  self::return_offline_html();


    		$post->post_excerpt   = __( 'You or the site are offline', self::return_plugin_text_domain() );
    		$post->ID               = -1;
    		$post->post_type        = self::return_plugin_namespace();
    		$post->post_status      = 'publish';
    		$post->comment_status   = 'closed';
    		$post->ping_status      = 'closed';
    		$post->comment_count    = 0;
    		$post->post_parent      = 0;
    		$post->post_date        = current_time( 'mysql' );
    		$post->post_date_gmt    = current_time( 'mysql', true );
    		$post->ancestors        = array();
    		$post->filter           = 'raw'; // important!
    		$posts                  = array( $post );

    		$wp_post = new WP_Post( $post );
    		// Add the fake post to the cache
            wp_cache_add( $post->ID , $wp_post, 'posts' );

    		// make wpQuery believe this is a real page too
            $wp_query->post = $wp_post;
            $wp_query->posts = array( $wp_post );
            $wp_query->queried_object = $wp_post;
            $wp_query->queried_object_id = $post->ID;
            $wp_query->found_posts = 1;
            $wp_query->post_count = 1;
            $wp_query->max_num_pages = 1;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->is_single = false;
            $wp_query->is_attachment = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            $wp_query->is_tag = false;
            $wp_query->is_tax = false;
            $wp_query->is_author = false;
            $wp_query->is_date = false;
            $wp_query->is_year = false;
            $wp_query->is_month = false;
            $wp_query->is_day = false;
            $wp_query->is_time = false;
            $wp_query->is_search = false;
            $wp_query->is_feed = false;
            $wp_query->is_comment_feed = false;
            $wp_query->is_trackback = false;
            $wp_query->is_home = false;
            $wp_query->is_embed = false;
            $wp_query->is_404 = false;
            $wp_query->is_paged = false;
            $wp_query->is_admin = false;
            $wp_query->is_preview = false;
            $wp_query->is_robots = false;
            $wp_query->is_posts_page = false;
            $wp_query->is_post_type_archive = false;


    		unset( $wp_query->query[ 'error' ] );
    		$wp_query->query_vars[ 'error' ] = '';
    		$wp_query->query_vars[ 'page' ] = 1;


    		$GLOBALS['wp_query'] = $wp_query;
            $wp->register_globals();
            setup_postdata($post);
            add_filter( 'pre_get_shortlink', array($this,'fix_shortlink'), 10 , 4);
            remove_action( 'wp_head', 'rel_canonical' );

    	}

    	return $posts;

    }

    public function no_comments_fake_page($comments, $query ){

        global $wp, $wp_query;

    	if ( ( strtolower( $wp->request ) == self::return_offline_page_slug() ) ) {

    	    $comments = array();

    	}

    	return $comments;

    }

    public function protect_the_slug($bad_slug, $slug, $post_type, $post_parent = null){

        if ( ($slug == self::return_offline_page_slug() ) && ($post_type == 'page')) {

            return true;

        }

        return $bad_slug;

    }

    public function filter_login_url( $login_url ){

        if (is_user_logged_in()){

            return $login_url;

        }

        if (!wp_http_validate_url($login_url)){

            return $login_url;

        }

        $parts = wp_parse_url($login_url);

        if (empty($parts['query'])){

            return $login_url;

        }

        parse_str($parts['query'], $query);

        if (!empty($query['redirect_to'])){

            $parsed = wp_parse_url($query['redirect_to']);

            $final_url = '';

            if (!empty($parsed['scheme']) && !empty($parsed['host'])){

                $final_url .= $parsed['scheme']. '://'. $parsed['host'];

            }

            if (!empty($parsed['path'])){

                $final_url .= $parsed['path'];

            }

            if ($final_url == self::return_offline_page_url()){

                $login_url = remove_query_arg( 'redirect_to', $login_url);

            }

        }

        return $login_url;

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        // generate a fake page and protect the slug
        add_filter( 'the_posts', array( $this, 'generate_fake_page' ), 100, 2 );
        add_filter( 'the_comments', array( $this, 'no_comments_fake_page' ), 100, 2 );
        add_filter( 'wp_unique_post_slug_is_bad_flat_slug', array( $this, 'protect_the_slug' ), 10, 3 );
        add_filter( 'wp_unique_post_slug_is_bad_hierarchical_slug', array( $this, 'protect_the_slug' ), 10, 4 );

        // remove the redirect from the login url if it contains the offline page
        add_filter( 'login_url', array( $this, 'filter_login_url' ), 10000000000, 1 );

    }

}

LH_web_application_offline_page_class::get_instance();
