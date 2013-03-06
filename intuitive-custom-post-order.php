<?php
/*
Plugin Name: Intuitive Custom Post Order
Plugin URI: http://hijiriworld.com/web/plugins/intuitive-custom-post-order/
Description: Intuitively, Order Items (Posts, Pages, and Custom Post Types) using a Drag and Drop Sortable JavaScript.
Version: 2.0.4
Author: hijiri
Author URI: http://hijiriworld.com/web/
*/

/*  Copyright 2013 hijiri

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/***************************************************************

	Define

***************************************************************/

define( 'HICPO_URL', plugins_url('', __FILE__) );

define( 'HICPO_DIR', plugin_dir_path(__FILE__) );

load_plugin_textdomain( 'hicpo', false, basename(dirname(__FILE__)).'/lang' );

/***************************************************************

	Class & Method

***************************************************************/

$hicpo = new Hicpo();

class Hicpo
{
	function __construct()
	{
		if ( !get_option('hicpo_options') ) $this->hicpo_install();
		
		add_action( 'admin_menu', array( &$this, 'admin_menu') );
		
		add_action( 'admin_init', array( &$this, 'refresh' ) );
		add_action( 'admin_init', array( &$this, 'update_options') );
		add_action( 'init', array( &$this, 'enable_objects' ) );
		
		add_action( 'wp_ajax_update-menu-order', array( &$this, 'update_menu_order' ) );
	}
	
	function hicpo_install()
	{
		global $wpdb;
		
		// Initialize : hicpo_options
		
		$post_types = get_post_types( array (
			'public' => true
			), 'objects' );
		
		foreach ($post_types as $post_type ) {
			$init_objects[] = $post_type->name;
		}
		$input_options = array( 'objects' => $init_objects );
		
		update_option( 'hicpo_options', $input_options );
		
		
		// Initialize : menu_order from date_post
		
		$hicpo_options = get_option( 'hicpo_options' );
		$objects = $hicpo_options['objects'];
		
		foreach( $objects as $object) {
			$sql = "SELECT
						ID
					FROM
						$wpdb->posts
					WHERE
						post_type = '".$object."'
						AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					ORDER BY
						post_date DESC
					";
				
			$results = $wpdb->get_results($sql);
			
			foreach( $results as $key => $result ) {
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
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
	
	function enable_objects()
	{
		$hicpo_options = get_option( 'hicpo_options' );
		$objects = $hicpo_options['objects'];
		
		if ( is_array( $objects ) ) {
			$active = false;
			
			// for Pages or Custom Post Types
			if ( isset($_GET['post_type']) ) {
				if ( in_array( $_GET['post_type'], $objects ) ) {
					$active = true;
				}
			// for Posts
			} else {
				$post_list = strstr( $_SERVER["REQUEST_URI"], 'wp-admin/edit.php' );
				if ( $post_list && in_array( 'post', $objects ) ) {
					$active = true;
				}
			}
			
			if ( $active ) {
				$this->load_script_css();	
			}
		}
	}
	
	function load_script_css() {
		// load JavaScript
		wp_enqueue_script( 'jQuery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'hicpojs', HICPO_URL.'/js/hicpo.js', array( 'jquery' ), null, true );
		// load CSS
		wp_enqueue_style( 'hicpo', HICPO_URL.'/css/hicpo.css', array(), null );
	}
	
	function refresh()
	{
		// menu_orderを再構築する
		global $wpdb;
		
		$hicpo_options = get_option( 'hicpo_options' );
		$objects = $hicpo_options['objects'];
		
		if ( is_array( $objects ) ) {
			foreach( $objects as $object) {
				$sql = "SELECT
							ID
						FROM
							$wpdb->posts
						WHERE
							post_type = '".$object."'
							AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						ORDER BY
							menu_order ASC
						";
						
				$results = $wpdb->get_results($sql);
				
				foreach( $results as $key => $result ) {
					// 新規追加した場合「menu_order=0」で登録されるため、常に1からはじまるように振っておく
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key+1 ), array( 'ID' => $result->ID ) );
				}
			}
		}
	}
	
	function update_menu_order()
	{
		global $wpdb;
		
		parse_str($_POST['order'], $data);
		
		if ( is_array($data) ) {
			
			// ページに含まれる記事のIDをすべて取得
			$id_arr = array();
			foreach( $data as $key => $values ) {
				foreach( $values as $position => $id ) {
					$id_arr[] = $id;
				}
			}
			
			// ページに含まれる記事のmenu_orderをすべて取得
			$menu_order_arr = array();
			foreach( $id_arr as $key => $id ) {
				$results = $wpdb->get_results("SELECT menu_order FROM $wpdb->posts WHERE ID = ".$id);
				foreach( $results as $result ) {
					$menu_order_arr[] = $result->menu_order;
				}
			}
			// menu_order配列をソート（キーと値の相関関係は維持しない）
			sort($menu_order_arr);
			
			foreach( $data as $key => $values ) {
				foreach( $values as $position => $id ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[$position] ), array( 'ID' => $id ) );
				}
			}
		}
	}
	

	
	function update_options()
	{
		if ( isset( $_POST['hicpo_submit'] ) ) {
			
			check_admin_referer( 'nonce_hicpo' );
			
			if ( isset( $_POST['objects'] ) ) {
				$input_options = array( 'objects' => $_POST['objects'] );
			} else {
				$input_options = array( 'objects' => '' );
			}
			
			update_option( 'hicpo_options', $input_options );
			wp_redirect( 'admin.php?page=hicpo-settings&msg=update' );
		}
	}
}

/***************************************************************

	output filter hook

***************************************************************/

add_filter( 'pre_get_posts', 'hicpo_filter_active' );

function hicpo_filter_active( $wp_query )
{
	// get_postsの場合 suppress_filters=true となる為、フィルタリングを有効にする
	if ( isset($wp_query->query['suppress_filters']) ) $wp_query->query['suppress_filters'] = false;
	if ( isset($wp_query->query_vars['suppress_filters']) ) $wp_query->query_vars['suppress_filters'] = false;
	return $wp_query;
}

add_filter( 'pre_get_posts', 'hicpo_pre_get_posts' );

function hicpo_pre_get_posts( $wp_query )
{
	$hicpo_options = get_option('hicpo_options');
	$objects = $hicpo_options['objects'];
	
	if ( is_array( $objects ) ) {
	
		// for Admin ---------------------------------------------------------------
		
		if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
		
			// post_type=post or page or custom post type
			// adminの場合、post_tyope=postも渡される
			if ( isset( $wp_query->query['post_type'] ) ) {
				if ( in_array( $wp_query->query['post_type'], $objects ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
					$wp_query->set( 'order', 'ASC' );
				}
			}
		
		// for Template ------------------------------------------------------------
		
		} else {
			
			$active = false;
			
			// postsのWordpressループ ----------------
			
			// $wp_query->queryが空配列の場合
			// WordPressループでもposts以外はpost_typeが渡される
			
			if ( empty( $wp_query->query ) ) {
				if ( in_array( 'post', $objects ) ) {
					$active = true;
				}
			} else {
			
				// get_posts() ----------------------
			
				// 完全な判別ではないが、suppress_filtersパラメータの有無で判別
				// get_posts()の場合、post_type, orderby, orderパラメータは必ず渡される
			
				if ( isset($wp_query->query['suppress_filters']) ) {
					
					// post_type判定
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
						
				// query_posts() or WP_Query()
				} else {
					
					// post_typeが指定されている場合
					if ( isset( $wp_query->query['post_type'] ) ) {
						
						// post_type判定
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
					// post_typeが指定されてい場合はpost_type=post
					} else {
						if ( in_array( 'post', $objects ) ) {
							$active = true;
						}
					}
				}	
			}
			
			if ( $active ) {
				if ( !isset( $wp_query->query['orderby'] ) || $wp_query->query['orderby'] == 'post_date' ) $wp_query->set( 'orderby', 'menu_order' );
				if ( !isset( $wp_query->query['order'] ) || $wp_query->query['order'] == 'DESC' ) $wp_query->set( 'order', 'ASC' );
			}				
		}
	}
}
?>