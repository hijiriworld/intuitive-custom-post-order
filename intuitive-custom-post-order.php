<?php
/*
 * Plugin Name: Intuitive Custom Post Order
 * Plugin URI: http://hijiriworld.com/web/plugins/intuitive-custom-post-order/
 * Description: Intuitively, Order Items (Posts, Pages, and Custom Post Types and Custom Taxonomies) using a Drag and Drop Sortable JavaScript.
 * Version: 3.0.7
 * Author: hijiri
 * Author URI: http://hijiriworld.com/web/
 * Text Domain: intuitive-custom-post-order
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
* Define
*/

define( 'HICPO_URL', plugins_url( '', __FILE__ ) );
define( 'HICPO_DIR', plugin_dir_path( __FILE__ ) );

/**
* Uninstall hook
*/

register_uninstall_hook( __FILE__, 'hicpo_uninstall' );
function hicpo_uninstall()
{
	global $wpdb;
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		$curr_blog = $wpdb->blogid;
		$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach( $blogids as $blog_id ) {
			switch_to_blog( $blog_id );
			hicpo_uninstall_db();
		}
		switch_to_blog( $curr_blog );
	} else {
		hicpo_uninstall_db();
	}
}
function hicpo_uninstall_db()
{
	global $wpdb;
	$result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
	if ( $result ){
		$query = "ALTER TABLE $wpdb->terms DROP `term_order`";
		$result = $wpdb->query( $query );
	}
	delete_option( 'hicpo_activation' );
}

/**
* Class & Method
*/

$hicpo = new Hicpo();

class Hicpo
{
	function __construct()
	{
		if ( !get_option( 'hicpo_activation' ) ) $this->hicpo_activation();
		
		add_action( 'plugins_loaded', array( $this, 'my_plugin_load_plugin_textdomain' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu') );
		
		add_action( 'admin_init', array( $this, 'refresh' ) );
		add_action( 'admin_init', array( $this, 'update_options') );
		add_action( 'admin_init', array( $this, 'load_script_css' ) );
		
		// sortable ajax action
		add_action( 'wp_ajax_update-menu-order', array( $this, 'update_menu_order' ) );
		add_action( 'wp_ajax_update-menu-order-tags', array( $this, 'update_menu_order_tags' ) );
		
		// reorder post types
		add_action( 'pre_get_posts', array( $this, 'hicpo_pre_get_posts' ) );
		
		add_filter( 'get_previous_post_where', array( $this, 'hicpo_previous_post_where' ) );
		add_filter( 'get_previous_post_sort', array( $this, 'hicpo_previous_post_sort' ) );
		add_filter( 'get_next_post_where', array( $this, 'hocpo_next_post_where' ) );
		add_filter( 'get_next_post_sort', array( $this, 'hicpo_next_post_sort' ) );
		
		// reorder taxonomies
		add_filter( 'get_terms_orderby', array( $this, 'hicpo_get_terms_orderby' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, 'hicpo_get_object_terms' ), 10, 3 );
		add_filter( 'get_terms', array( $this, 'hicpo_get_object_terms' ), 10, 3 );
	}
	
	function hicpo_activation()
	{
		global $wpdb;
		$result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
		if ( !$result ) {
			$query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
			$result = $wpdb->query( $query );
		}
		update_option( 'hicpo_activation', 1 );
	}

	function my_plugin_load_plugin_textdomain()
	{
		load_plugin_textdomain( 'intuitive-custom-post-order', false, basename( dirname( __FILE__ ) ).'/languages/' );
	}
	function admin_menu()
	{
		add_options_page( __( 'Intuitive CPO', 'intuitive-custom-post-order' ), __( 'Intuitive CPO', 'intuitive-custom-post-order' ), 'manage_options', 'hicpo-settings', array( $this,'admin_page' ) );
	}
	
	function admin_page()
	{
		require HICPO_DIR.'admin/settings.php';
	}

	function _check_load_script_css()
	{
		$active = false;
		
		$objects = $this->get_hicpo_options_objects();
		$tags = $this->get_hicpo_options_tags();
		
		if ( empty( $objects ) && empty( $tags ) ) return false;
		
		// exclude (sorting, addnew page, edit page)
		if ( isset( $_GET['orderby'] ) || strstr( $_SERVER['REQUEST_URI'], 'action=edit' ) || strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' ) ) return false;
		
		if ( !empty( $objects ) ) {
			if ( isset( $_GET['post_type'] ) && !isset( $_GET['taxonomy'] ) && in_array( $_GET['post_type'], $objects ) ) { // if page or custom post types
				$active = true;
			}
			if ( !isset( $_GET['post_type'] ) && strstr( $_SERVER['REQUEST_URI'], 'wp-admin/edit.php' ) && in_array( 'post', $objects ) ) { // if post
				$active = true;
			}
		}
		
		if ( !empty( $tags ) ) {
			if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], $tags ) ) {
				$active = true;
			}
		}
		
		return $active;
	}

	function load_script_css()
	{
		if ( $this->_check_load_script_css() ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'hicpojs', HICPO_URL.'/js/hicpo.js', array( 'jquery' ), null, true );
			
			wp_enqueue_style( 'hicpo', HICPO_URL.'/css/hicpo.css', array(), null );
		}
	}
			
	function refresh()
	{
		global $wpdb;
		$objects = $this->get_hicpo_options_objects();
		$tags = $this->get_hicpo_options_tags();
		
		if ( !empty( $objects ) ) {
			foreach( $objects as $object) {
				$result = $wpdb->get_results( "
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min 
					FROM $wpdb->posts 
					WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
				" );
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) continue;
				
				$results = $wpdb->get_results( "
					SELECT ID 
					FROM $wpdb->posts 
					WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') 
					ORDER BY menu_order ASC
				" );
				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
				}
			}
		}

		if ( !empty( $tags ) ) {
			foreach( $tags as $taxonomy ) {
				$result = $wpdb->get_results( "
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min 
					FROM $wpdb->terms AS terms 
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
					WHERE term_taxonomy.taxonomy = '".$taxonomy."'
				" );
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) continue;
				
				$results = $wpdb->get_results( "
					SELECT terms.term_id 
					FROM $wpdb->terms AS terms 
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
					WHERE term_taxonomy.taxonomy = '".$taxonomy."' 
					ORDER BY term_order ASC
				" );
				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->terms, array( 'term_order' => $key+1 ), array( 'term_id' => $result->term_id ) );
				}
			}
		}
	}
	
	function update_menu_order()
	{
		global $wpdb;

		parse_str( $_POST['order'], $data );
		
		if ( !is_array( $data ) ) return false;
			
		// get objects per now page
		$id_arr = array();
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}
		
		// get menu_order of objects per now page
		$menu_order_arr = array();
		foreach( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( "SELECT menu_order FROM $wpdb->posts WHERE ID = ".intval( $id ) );
			foreach( $results as $result ) {
				$menu_order_arr[] = $result->menu_order;
			}
		}
		
		// maintains key association = no
		sort( $menu_order_arr );
		
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[$position] ), array( 'ID' => intval( $id ) ) );
			}
		}
	}
	
	function update_menu_order_tags()
	{
		global $wpdb;
		
		parse_str( $_POST['order'], $data );
		
		if ( !is_array( $data ) ) return false;
		
		$id_arr = array();
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}
		
		$menu_order_arr = array();
		foreach( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( "SELECT term_order FROM $wpdb->terms WHERE term_id = ".intval( $id ) );
			foreach( $results as $result ) {
				$menu_order_arr[] = $result->term_order;
			}
		}
		sort( $menu_order_arr );
		
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$wpdb->update( $wpdb->terms, array( 'term_order' => $menu_order_arr[$position] ), array( 'term_id' => intval( $id ) ) );
			}
		}
	}
	
	/**
	* はじめて有効化されたオブジェクトは、ディフォルトの order に従って menu_order セットする
	*
	* post_type: orderby=post_date, order=DESC
	* page: orderby=menu_order, post_title, order=ASC
	* taxonomy: orderby=name, order=ASC
	* 
	* 判定は: アイテム数が 0 以上で menu_order の最大値とアイテム数が同じではないオブジェクト
	*/
	
	function update_options()
	{
		global $wpdb;
		
		if ( !isset( $_POST['hicpo_submit'] ) ) return false;
			
		check_admin_referer( 'nonce_hicpo' );
			
		$input_options = array();
		$input_options['objects'] = isset( $_POST['objects'] ) ? $_POST['objects'] : '';
		$input_options['tags'] = isset( $_POST['tags'] ) ? $_POST['tags'] : '';
		
		update_option( 'hicpo_options', $input_options );
		
		$objects = $this->get_hicpo_options_objects();
		$tags = $this->get_hicpo_options_tags();
		
		if ( !empty( $objects ) ) {
			foreach( $objects as $object ) {
				$result = $wpdb->get_results( "
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min 
					FROM $wpdb->posts 
					WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
				" );
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) continue;
				
				if ( $object == 'page' ) {
					$results = $wpdb->get_results( "
						SELECT ID 
						FROM $wpdb->posts 
						WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') 
						ORDER BY menu_order, post_title ASC
					" );
				} else {
					$results = $wpdb->get_results( "
						SELECT ID 
						FROM $wpdb->posts 
						WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') 
						ORDER BY post_date DESC
					" );
				}
				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
				}
			}
		}
		
		if ( !empty( $tags ) ) {
			foreach( $tags as $taxonomy ) {
				$result = $wpdb->get_results( "
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min 
					FROM $wpdb->terms AS terms 
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
					WHERE term_taxonomy.taxonomy = '".$taxonomy."'
				" );
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) continue;
				
				$results = $wpdb->get_results( "
					SELECT terms.term_id 
					FROM $wpdb->terms AS terms 
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
					WHERE term_taxonomy.taxonomy = '".$taxonomy."' 
					ORDER BY name ASC
				" );
				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->terms, array( 'term_order' => $key+1 ), array( 'term_id' => $result->term_id ) );
				}
			}
		}
		
		wp_redirect( 'admin.php?page=hicpo-settings&msg=update' );
	}
	
	function hicpo_previous_post_where( $where )
	{
		global $post;

		$objects = $this->get_hicpo_options_objects();
		if ( empty( $objects ) ) return $where;
		
		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = str_replace( "p.post_date < '".$post->post_date."'", "p.menu_order > '".$current_menu_order."'", $where );
		}
		return $where;
	}
	
	function hicpo_previous_post_sort( $orderby )
	{
		global $post;
		
		$objects = $this->get_hicpo_options_objects();
		if ( empty( $objects ) ) return $orderby;
		
		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
		}
		return $orderby;
	}
	
	function hocpo_next_post_where( $where )
	{
		global $post;

		$objects = $this->get_hicpo_options_objects();
		if ( empty( $objects ) ) return $where;
		
		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = str_replace( "p.post_date > '".$post->post_date."'", "p.menu_order < '".$current_menu_order."'", $where );
		}
		return $where;
	}
	
	function hicpo_next_post_sort( $orderby )
	{
		global $post;
		
		$objects = $this->get_hicpo_options_objects();
		if ( empty( $objects ) ) return $orderby;
		
		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
		}
		return $orderby;
	}
	
	function hicpo_pre_get_posts( $wp_query )
	{
		$objects = $this->get_hicpo_options_objects();
		if ( empty( $objects ) ) return false;
		
		/**
		* for Admin
		*
		* @default
		* post cpt: [order] => null(desc) [orderby] => null(date)
		* page: [order] => asc [orderby] => menu_order title
		* 
		*/
		
		if ( is_admin() ) {
			
			// adminの場合 $wp_query->query['post_type']=post も渡される
			if ( isset( $wp_query->query['post_type'] ) && !isset( $_GET['orderby'] ) ) {
				if ( in_array( $wp_query->query['post_type'], $objects ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
					$wp_query->set( 'order', 'ASC' );
				}
			}
		
		/**
		* for Front End
		*/
		
		} else {
			
			$active = false;
			
			// page or custom post types
			if ( isset( $wp_query->query['post_type'] ) ) {
				// exclude array()
				if ( !is_array( $wp_query->query['post_type'] ) ) {
					if ( in_array( $wp_query->query['post_type'], $objects ) ) {
						$active = true;
					}
				}
			// post
			} else {
				if ( in_array( 'post', $objects ) ) {
					$active = true;
				}
			}
			
			if ( !$active ) return false;
			
			// get_posts()
			if ( isset( $wp_query->query['suppress_filters'] ) ) {
				if ( $wp_query->get( 'orderby' ) == 'date' )  $wp_query->set( 'orderby', 'menu_order' );
				if ( $wp_query->get( 'order' ) == 'DESC' ) $wp_query->set( 'order', 'ASC' );
			// WP_Query( contain main_query )
			} else {
				if ( !$wp_query->get( 'orderby' ) )  $wp_query->set( 'orderby', 'menu_order' );
				if ( !$wp_query->get( 'order' ) ) $wp_query->set( 'order', 'ASC' );
			}
		}
	}
	
	function hicpo_get_terms_orderby( $orderby, $args )
	{
		if ( is_admin() ) return $orderby;
		
		$tags = $this->get_hicpo_options_tags();
		
		if( !isset( $args['taxonomy'] ) ) return $orderby;
		
		$taxonomy = $args['taxonomy'];
		if ( !in_array( $taxonomy, $tags ) ) return $orderby;
		
		$orderby = 't.term_order';
		return $orderby;
	}

	function hicpo_get_object_terms( $terms )
	{
		$tags = $this->get_hicpo_options_tags();
		
		if ( is_admin() && isset( $_GET['orderby'] ) ) return $terms;
		
		foreach( $terms as $key => $term ) {
			if ( is_object( $term ) && isset( $term->taxonomy ) ) {
				$taxonomy = $term->taxonomy;
				if ( !in_array( $taxonomy, $tags ) ) return $terms;
			} else {
				return $terms;
			}
		}
		
		usort( $terms, array( $this, 'taxcmp' ) );
		return $terms;
	}
	
	function taxcmp( $a, $b )
	{
		if ( $a->term_order ==  $b->term_order ) return 0;
		return ( $a->term_order < $b->term_order ) ? -1 : 1;
	}
	
	function get_hicpo_options_objects()
	{
		$hicpo_options = get_option( 'hicpo_options' ) ? get_option( 'hicpo_options' ) : array();
		$objects = isset( $hicpo_options['objects'] ) && is_array( $hicpo_options['objects'] ) ? $hicpo_options['objects'] : array();
		return $objects;
	}
	function get_hicpo_options_tags()
	{
		$hicpo_options = get_option( 'hicpo_options' ) ? get_option( 'hicpo_options' ) : array();
		$tags = isset( $hicpo_options['tags'] ) && is_array( $hicpo_options['tags'] ) ? $hicpo_options['tags'] : array();
		return $tags;
	}
	
}

?>