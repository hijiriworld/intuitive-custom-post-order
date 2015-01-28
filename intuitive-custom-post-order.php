<?php
/*
Plugin Name: Intuitive Custom Post Order
Plugin URI: http://hijiriworld.com/web/plugins/intuitive-custom-post-order/
Description: Intuitively, Order Items (Posts, Pages, and Custom Post Types and Custom Taxonomies) using a Drag and Drop Sortable JavaScript.
Version: 3.0.1
Author: hijiri
Author URI: http://hijiriworld.com/web/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/***********************************************************************************

	Define

***********************************************************************************/

define( 'HICPO_URL', plugins_url( '', __FILE__ ) );
define( 'HICPO_DIR', plugin_dir_path( __FILE__ ) );
load_plugin_textdomain( 'hicpo', false, basename( dirname( __FILE__ ) ).'/lang' );

/***********************************************************************************

	Activation

***********************************************************************************/

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
	$query = "SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'";
	$result = $wpdb->query($query);
	if ( $result ){
		$query = "ALTER TABLE $wpdb->terms DROP `term_order`";
		$result = $wpdb->query( $query );
	}
}

/***********************************************************************************

	Class & Method

***********************************************************************************/

$hicpo = new Hicpo();

class Hicpo
{
	function __construct()
	{	
		$this->hicpo_activation();
		
		//if ( !get_option('hicpo_options') ) $this->hicpo_install();
		
		add_action( 'admin_menu', array( $this, 'admin_menu') );
		
		add_action( 'admin_init', array( $this, 'refresh' ) );
		add_action( 'admin_init', array( $this, 'tags_refresh' ) );
		
		add_action( 'admin_init', array( $this, 'update_options') );
		add_action( 'admin_init', array( $this, 'load_script_css' ) );
		
		// sortable ajax action
		add_action( 'wp_ajax_update-menu-order', array( $this, 'update_menu_order' ) );
		add_action( 'wp_ajax_update-menu-order-tags', array( $this, 'update_menu_order_tags' ) );
		
		// post_type reorder - pre_get_posts
		add_filter( 'pre_get_posts', array( $this, 'hicpo_filter_active' ) );
		add_filter( 'pre_get_posts', array( $this, 'hicpo_pre_get_posts' ) );
		
		// post_type reorder - previous_post(s)_link, next_post(s)_link
		add_filter( 'get_previous_post_where', array( $this, 'hicpo_previous_post_where' ) );
		add_filter( 'get_previous_post_sort', array( $this, 'hicpo_previous_post_sort' ) );
		add_filter( 'get_next_post_where', array( $this, 'hocpo_next_post_where' ) );
		add_filter( 'get_next_post_sort', array( $this, 'hicpo_next_post_sort' ) );
		
		// term reorder
		add_filter( 'get_terms_orderby', array( $this, 'hicpo_get_terms_orderby' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, 'hicpo_get_object_terms' ), 10, 3 );
		add_filter( 'get_terms', array( $this, 'hicpo_get_object_terms' ), 10, 3 );
		//add_filter( 'tag_cloud_sort', array( $this, 'hicpo_get_object_terms' ), 10, 3 );
	}

	function hicpo_activation()
	{
		global $wpdb;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$curr_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				hicpo_activation_db();
			}
			switch_to_blog( $curr_blog );
		} else {
			$this->hicpo_activation_db();
		}
	}
	function hicpo_activation_db()
	{
		global $wpdb;
		$query = "SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'";
		$result = $wpdb->query($query);
		if ( $result == 0 ){
			$query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
			$result = $wpdb->query( $query );
		}
	}
	
	function hicpo_install()
	{
		global $wpdb;
		
		// Initialize : hicpo_options
		
		$init_objects = array();
		
		$post_types = get_post_types( array (
			'show_ui' => true,
			'show_in_menu' => true,
		), 'objects' );
		
		foreach ($post_types as $post_type ) {
			if ( $post_type->name != 'attachment' ) {
				$init_objects[] = $post_type->name;
			}
		}
		
		$input_options = array();
		$input_options['objects'] = $init_objects;
		
		update_option( 'hicpo_options', $input_options );
		
		// Initialize : menu_order
		
		$objects = $this->get_hicpo_options_objects();
		
		if ( !empty( $objects ) ) {
			foreach( $objects as $object) {
				$results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') ORDER BY post_date DESC" );
				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
				}
			}
		}
	}
	
	function admin_menu()
	{
		add_options_page( __('Intuitive CPO', 'hicpo'), __('Intuitive CPO', 'hicpo'), 'manage_options', 'hicpo-settings', array( &$this,'admin_page' ));
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
		
		// exclude sorting in admin ui
		if ( isset( $_GET['orderby'] ) ) return false;
		
		// exlude addnew Page and edit Page
		if ( strstr( $_SERVER["REQUEST_URI"], 'action=edit' ) || strstr( $_SERVER["REQUEST_URI"], 'wp-admin/post-new.php' ) ) return false;
		
		// post_types
		if ( is_array( $objects ) ) {
			// if Custom Post Types( include 'Page')
			if ( isset( $_GET['post_type'] ) ) {
				if ( in_array( $_GET['post_type'], $objects ) ) {
					$active = true;
				}
			// if Post
			} else if ( strstr( $_SERVER["REQUEST_URI"], 'wp-admin/edit.php' ) ) {
				if ( in_array( 'post', $objects ) ) {
					$active = true;
				}
			}
		}
		
		// Taxonomies
		if ( is_array( $tags ) ) {
			if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], $tags ) ) {
				$active = true;
			}
		}
		
		return $active;
	}

	function load_script_css()
	{
		if ( $this->_check_load_script_css() ) {
			
			// load JavaScript
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'hicpojs', HICPO_URL.'/js/hicpo.js', array( 'jquery' ), null, true );
	
			// load CSS
			wp_enqueue_style( 'hicpo', HICPO_URL.'/css/hicpo.css', array(), null );
		}
	}
	
	function refresh()
	{
		global $wpdb;
		
		$objects = $this->get_hicpo_options_objects();
		
		if ( is_array( $objects ) ) {
			foreach( $objects as $object) {
				
				// menu_order の max とレコード数が一致して、かつ min が 0 じゃない時は再構築処理をスキップする
				
				$result = $wpdb->get_results( "SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min FROM $wpdb->posts WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')" );
				if ( count( $result ) > 0 && $result[0]->cnt == $result[0]->max && $result[0]->min != 0 ) continue;
				
				$results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') ORDER BY menu_order ASC" );
				
				// 新規追加した場合 menu_order=0 で登録されるため、常に1からはじまるように振っておく

				foreach( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
				}
			}
		}
	}
	
	function tags_refresh()
	{
		global $wpdb;
		
		$tags = $this->get_hicpo_options_tags();
		
		foreach( $tags as $taxonomy ) {
		
			$sql = "SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min 
				FROM $wpdb->terms AS terms 
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
				WHERE term_taxonomy.taxonomy = '".$taxonomy."'
			";
			$result = $wpdb->get_results( $sql );
			if ( count( $result ) > 0 && $result[0]->cnt == $result[0]->max && $result[0]->min != 0 ) continue;
			
			$sql = "SELECT terms.term_id 
				FROM $wpdb->terms AS terms 
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
				WHERE term_taxonomy.taxonomy = '".$taxonomy."' 
				ORDER BY term_order ASC
			";
			$results = $wpdb->get_results( $sql );
			
			foreach( $results as $key => $result ) {
				$wpdb->update( $wpdb->terms, array( 'term_order' => $key+1 ), array( 'term_id' => $result->term_id ) );
			}
		}
	}
	function update_menu_order()
	{
		global $wpdb;
		
		parse_str( $_POST['order'], $data );
		
		if ( !is_array( $data ) ) return false;
			
		// 1ページに表示されているオブジェクトのIDをすべて取得
		$id_arr = array();
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}
		
		// 1ページに表示されているオブジェクトのmenu_orderをすべて取得
		$menu_order_arr = array();
		foreach( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( "SELECT menu_order FROM $wpdb->posts WHERE ID = ".$id );
			foreach( $results as $result ) {
				$menu_order_arr[] = $result->menu_order;
			}
		}
		// menu_order配列をソート（キーと値の相関関係は維持しない）
		sort( $menu_order_arr );
		
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[$position] ), array( 'ID' => $id ) );
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
			$results = $wpdb->get_results( "SELECT term_order FROM $wpdb->terms WHERE term_id = ".$id );
			foreach( $results as $result ) {
				$menu_order_arr[] = $result->term_order;
			}
		}
		sort( $menu_order_arr );
		
		foreach( $data as $key => $values ) {
			foreach( $values as $position => $id ) {
				$wpdb->update( $wpdb->terms, array( 'term_order' => $menu_order_arr[$position] ), array( 'term_id' => $id ) );
			}
		}
	}
	
	function update_options()
	{
		global $wpdb;
		
		if ( isset( $_POST['hicpo_submit'] ) ) {
			
			check_admin_referer( 'nonce_hicpo' );
			
			$input_options = array();
			$input_options['objects'] = isset( $_POST['objects'] ) ? $_POST['objects'] : '';
			$input_options['tags'] = isset( $_POST['tags'] ) ? $_POST['tags'] : '';
			
			update_option( 'hicpo_options', $input_options );
			
			// はじめて有効化されたオブジェクトの場合、ディフォルト状態 order=post_date に従って menu_order を振っておく
			
			$objects = $this->get_hicpo_options_objects();
			
			if ( !empty( $objects ) ) {
				foreach( $objects as $object ) {
					// 記事が1つ以上存在し、menu_oredr の最大値が 0 のオブジェクトが該当
					$result = $wpdb->get_results( "SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min FROM $wpdb->posts WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')" );
					if ( count( $result ) > 0 && $result[0]->max == 0 ) {
						$results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = '".$object."' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') ORDER BY post_date DESC" );
						foreach( $results as $key => $result ) {
							$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
						}
					}
				}
			}
			
			// タクソノミーはもともと orderby=name, order=asc なので初期化は不要
			
			wp_redirect( 'admin.php?page=hicpo-settings&msg=update' );
		}
	}
	
	function hicpo_previous_post_where( $where )
	{
		global $post;

		$objects = $this->get_hicpo_options_objects();
		
		if ( in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = "WHERE p.menu_order > '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish'";
		}
		return $where;
	}
	
	function hicpo_previous_post_sort( $orderby )
	{
		global $post;
		
		$objects = $this->get_hicpo_options_objects();
		
		if ( in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
		}
		return $orderby;
	}
	
	function hocpo_next_post_where( $where )
	{
		global $post;

		$objects = $this->get_hicpo_options_objects();
		
		if ( in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = "WHERE p.menu_order < '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish'";
		}
		return $where;
	}
	
	function hicpo_next_post_sort( $orderby )
	{
		global $post;
		
		$objects = $this->get_hicpo_options_objects();

		if ( in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
		}
		return $orderby;
	}
	
	function hicpo_filter_active( $wp_query )
	{
		// get_postsの場合 suppress_filters=true となる為、フィルタリングを有効にする
		if ( isset($wp_query->query['suppress_filters']) ) $wp_query->query['suppress_filters'] = false;
		if ( isset($wp_query->query_vars['suppress_filters']) ) $wp_query->query_vars['suppress_filters'] = false;
		return $wp_query;
	}
	
	function hicpo_pre_get_posts( $wp_query )
	{
		global $args;
		
		$objects = $this->get_hicpo_options_objects();
		
		
		if ( is_array( $objects ) ) {
		
			// for Admin >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
			
			if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
				
				/*
				post_type=post or page or custom post type
				adminの場合、post_type=postも渡される
				ゆえに $_GET['post_type] での判定はダメ
				・タイトル並び替えを行っている場合（$_GET['orderby']）は除外 
				*/

				if ( isset( $wp_query->query['post_type'] ) && !isset( $_GET['orderby'] ) ) {
					if ( in_array( $wp_query->query['post_type'], $objects ) ) {
						$wp_query->set( 'orderby', 'menu_order' );
						$wp_query->set( 'order', 'ASC' );
					}
				}
			
			// for Template >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
			
			} else {
				
				/*
				post_type が有効オブジェクトかどうかを判別
				$wp_query->query['post_type'] で判別
				ディフォルトのクエリの場合、$args指定がないため
				*/
					
				$active = false;
					
				// post_typeが指定されている場合
				
				if ( isset( $wp_query->query['post_type'] ) ) {
					// 複数指定の場合は、いずれかひとつでも該当すれば有効
					if ( is_array( $wp_query->query['post_type'] ) ) {
						$post_types = $wp_query->query['post_type'];
						foreach( $post_types as $post_type ) {
							if ( in_array( $post_type, $objects ) ) {
								$active = true;
							}
						}
					} else {
						if ( in_array( $wp_query->query['post_type'], $objects ) ) {
							$active = true;
						}
					}
				// post_typeが指定されていなければpost
				} else {
					if ( in_array( 'post', $objects ) ) {
						$active = true;
					}
				}

				/*
				
				$args が存在した場合はそちらを優先する
				
				*/
				
				if ( $active ) {
					
					if ( isset( $args ) ) {
						// args = array( 'orderby' => 'date', 'order' => 'DESC' );
						if ( is_array( $args ) ) {
							if ( !isset( $args['orderby'] ) ) {
								$wp_query->set( 'orderby', 'menu_order' );
							}
							if ( !isset( $args['order'] ) ) {
								$wp_query->set( 'order', 'ASC' );
							}
						// args = 'orderby=date&order=DESC';
						} else {
							if ( !strstr( $args, 'orderby=' ) ) {
								$wp_query->set( 'orderby', 'menu_order' );
							}
							if ( !strstr( $args, 'order=' ) ) {
								$wp_query->set( 'order', 'ASC' );
								
							}
						}
					} else {
						$wp_query->set( 'orderby', 'menu_order' );
						$wp_query->set( 'order', 'ASC' );
					}
				}
			}
		}
	}
	
	function hicpo_get_terms_orderby( $orderby, $args )
	{
		if ( is_admin() ) return $orderby;
		
		$tags = $this->get_hicpo_options_tags();
		
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