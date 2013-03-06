<?php
/*
Plugin Name: Intuitive Custom Post Order
Plugin URI: http://hijiriworld.com/web/plugins/intuitive-custom-post-order/
Description: Intuitively, Order posts(posts, any custom post types) using a Drag and Drop Sortable JavaScript.
Author: hijiri
Author URI: http://hijiriworld.com/web/
Version: 1.2.1
*/

/*  Copyright 2012 hijiri

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

	define

***************************************************************/

define( 'hicpo_URL', plugins_url('', __FILE__) );


/***************************************************************

	init

***************************************************************/

add_action( 'wp_loaded', 'hicpo_init' );
function hicpo_init() {
	global $custom_post_type_order, $userdata;
	$custom_post_type_order = new hicpo();
}


class hicpo {
	var $current_post_type = null;
	function hicpo()  {
		add_action( 'admin_init', array( &$this, 'regist_files' ), 11 );
		add_action( 'wp_ajax_update-menu-order', array( &$this, 'save_menu_order' ) );
	}
	
	function regist_files() {
		// Apply to Post and Custom Post Type, Exclude Page
		$post_list_url = substr($_SERVER["REQUEST_URI"], -18, 18);
		if ( (isset($_GET['post_type']) && $_GET['post_type'] != 'page') || $post_list_url == '/wp-admin/edit.php' ) {
			// load JavaScript
			wp_enqueue_script( 'jQuery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'hicpojs', hicpo_URL.'/js/hicpo.js', array(), null, true );
			
			// load CSS
			wp_enqueue_style( 'hicpo', hicpo_URL.'/css/hicpo.css', array(), null );
		}
	}
	
	function save_menu_order() {
		global $wpdb;
		// update 'menu_order' with serialize strings
		parse_str($_POST['order'], $data);
		if ( is_array($data) ) {
			foreach( $data as $key => $values ) {
				foreach( $values as $position => $id ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $position, 'post_parent' => 0 ), array( 'ID' => $id ) );
				}
			}
		}
	}
}

/***************************************************************

	edit posts per page hook

***************************************************************/

add_filter( 'edit_posts_per_page', 'hicpo_edit_post_per_page' );
function hicpo_edit_post_per_page( $per_page, $post_type = null ) {
	
	$post_list_url = substr($_SERVER["REQUEST_URI"], -18, 18);
	if ( (isset($_GET['post_type']) && $_GET['post_type'] != 'page') || $post_list_url == '/wp-admin/edit.php' ) {
		$per_page = 999;
	}
	return $per_page;
}

/***************************************************************

	output filter hook

***************************************************************/

add_filter( 'pre_get_posts', 'hicpo_pre_get_posts' );
function hicpo_pre_get_posts( $query ) {
	// get_postsの場合 suppress_filters=true となる為、フィルタリングを有効にする
	if ( isset($query->query['suppress_filters']) ) $query->query['suppress_filters'] = false;
	if ( isset($query->query_vars['suppress_filters']) ) $query->query_vars['suppress_filters'] = false;
	return $query;
}

add_filter( 'posts_orderby', 'hicpo_posts_orderby' );
function hicpo_posts_orderby( $orderby ) {
	global $wpdb;
	
	// for admin
	if ( is_admin() ) {
		$orderby = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
	// for site
	} else {
		// orderおよびorderby引数をユーザが指定していない場合のみ適用する
		if ( $orderby == "{$wpdb->posts}.post_date DESC" ) {
			$orderby = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
		}
	}
	return $orderby;
}
?>