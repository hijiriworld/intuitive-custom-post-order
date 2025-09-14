<?php
/*
 * Plugin Name: Intuitive Custom Post Order
 * Plugin URI:  http://hijiriworld.com/web/plugins/intuitive-custom-post-order/
 * Description: Intuitively, Order Items (Posts, Pages, ,Custom Post Types, Custom Taxonomies, Sites) using a Drag and Drop Sortable JavaScript.
 * Version:     3.1.5.1
 * Author:      hijiri
 * Author URI:  http://hijiriworld.com/web/
 * Text Domain: intuitive-custom-post-order
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Define
 */
define( 'HICPO_URL', plugins_url( '', __FILE__ ) );
define( 'HICPO_DIR', plugin_dir_path( __FILE__ ) );

$plugin_file = file_get_contents( __FILE__ );
preg_match( '/Version:\s*([^\s]+)/i', $plugin_file, $version_matches );
$plugin_version = $version_matches[1];
define( 'HICPO_VER', $plugin_version );

/**
 * Uninstall hook
 */
register_uninstall_hook( __FILE__, 'hicpo_uninstall' );
function hicpo_uninstall() {
	global $wpdb;
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blogids as $blog_id ) {
			switch_to_blog( $blog_id );
			hicpo_uninstall_db_terms();
		}
		restore_current_blog();
		hicpo_uninstall_db_blogs();
	} else {
		hicpo_uninstall_db_terms();
	}
	delete_option( 'hicpo_activation' ); // old version before than 3.1.0
	delete_option( 'hicpo_ver' );
}

// drop term_order COLUMN to $wpdb->terms TABLE
function hicpo_uninstall_db_terms() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- its ok.
	$result = $wpdb->query( "DESCRIBE  $wpdb->terms `term_order`" );
	if ( $result ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- its ok.
		$result = $wpdb->query( "ALTER TABLE $wpdb->terms DROP `term_order`" );
	}
}

// drop menu_order COLUMN to $wpdb->blogs TABLE
function hicpo_uninstall_db_blogs() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- its ok.
	$result = $wpdb->query( "DESCRIBE $wpdb->blogs `menu_order`" );
	if ( $result ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- its ok.
		$result = $wpdb->query( "ALTER TABLE $wpdb->blogs DROP `menu_order`" );
	}
}

/**
 * Class & Method
 */

$hicpo = new Hicpo();

class Hicpo {

	/**
	 * Construct
	 */
	public function __construct() {
		 // activation
		$hicpo_ver = get_option( 'hicpo_ver' );
		if ( version_compare( $hicpo_ver, HICPO_VER ) < 0 ) {
			$this->hicpo_activation();
		}

		// textdomain
		add_action( 'plugins_loaded', [ $this, 'hicpo_load_plugin_textdomain' ] );

		// add menu
		add_action( 'admin_menu', [ $this, 'hicpo_admin_menu' ] );

		// admin init
		if ( empty( $_GET ) ) {
			add_action( 'admin_init', [ $this, 'hicpo_refresh' ] );
		}
		add_action( 'admin_init', [ $this, 'hicpo_add_capabilities' ] );
		add_action( 'admin_init', [ $this, 'hicpo_update_options' ] );
		add_action( 'admin_init', [ $this, 'hicpo_load_script_css' ] );

		// sortable ajax action
		add_action( 'wp_ajax_update-menu-order', [ $this, 'hicpo_update_menu_order' ] );
		add_action( 'wp_ajax_update-menu-order-tags', [ $this, 'hicpo_update_menu_order_tags' ] );

		// reorder post types
		add_action( 'pre_get_posts', [ $this, 'hicpo_pre_get_posts' ] );

		add_filter( 'get_previous_post_where', [ $this, 'hicpo_previous_post_where' ] );
		add_filter( 'get_previous_post_sort', [ $this, 'hicpo_previous_post_sort' ] );
		add_filter( 'get_next_post_where', [ $this, 'hicpo_next_post_where' ] );
		add_filter( 'get_next_post_sort', [ $this, 'hicpo_next_post_sort' ] );

		// reorder taxonomies
		add_filter( 'get_terms_orderby', [ $this, 'hicpo_get_terms_orderby' ], 10, 3 );
		add_filter( 'wp_get_object_terms', [ $this, 'hicpo_get_object_terms' ], 10, 3 );
		add_filter( 'get_terms', [ $this, 'hicpo_get_object_terms' ], 10, 3 );

		// reorder sites
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'hicpo_network_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'hicpo_update_network_options' ] );
			add_action( 'wp_ajax_update-menu-order-sites', [ $this, 'hicpo_update_menu_order_sites' ] );

			// networkadmin サイト削除時はサイト並び替え除外
			if (
				empty( $_SERVER['QUERY_STRING'] ) ||
				( ! empty( $_SERVER['QUERY_STRING'] ) &&
					'action=deleteblog' !== $_SERVER['QUERY_STRING'] && // delete
					'action=allblogs' !== $_SERVER['QUERY_STRING']         // delete all
				)
			) {

				// call from 'get_sites'
				add_filter( 'sites_clauses', [ $this, 'hicpo_sites_clauses' ], 10, 1 );

				add_action( 'admin_init', [ $this, 'hicpo_refresh_network' ] );

				// adminbar sites reorder
				add_filter( 'get_blogs_of_user', [ $this, 'hicpo_get_blogs_of_user' ], 10, 3 );
			}

			// before wp v4.6.0 * wp_get_sites
			add_action( 'init', [ $this, 'hicpo_refresh_front_network' ] );
		}
	}

	/**
	 * Method
	 */
	public function hicpo_activation() {
		global $wpdb;

		// add term_order COLUMN to $wpdb->terms TABLE
		$result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
		if ( ! $result ) {
			$query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- it is ok.
			$result = $wpdb->query( $query );
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// add menu_order COLUMN to $wpdb->blogs TABLE
			$result = $wpdb->query( "DESCRIBE $wpdb->blogs `menu_order`" );
			if ( ! $result ) {
				$query = "ALTER TABLE $wpdb->blogs ADD `menu_order` INT( 4 ) NULL DEFAULT '0'";
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- it is ok.
				$result = $wpdb->query( $query );
			}
		}
		update_option( 'hicpo_ver', HICPO_VER );
	}

	public function hicpo_load_plugin_textdomain() {
		load_plugin_textdomain(
			'intuitive-custom-post-order',
			false,
			basename( __DIR__ ) . '/languages/'
		);
	}

	public function hicpo_admin_menu() {
		add_options_page(
			__( 'Intuitive CPO', 'intuitive-custom-post-order' ),
			__( 'Intuitive CPO', 'intuitive-custom-post-order' ),
			'manage_options',
			'hicpo-settings',
			[ $this, 'hicpo_admin_page' ]
		);
	}

	public function hicpo_admin_page() {
		require HICPO_DIR . 'admin/settings.php';
	}

	public function hicpo_network_admin_menu() {
		add_submenu_page(
			'settings.php',
			__( 'Intuitive CPO', 'hicpo' ),
			__( 'Intuitive CPO', 'hicpo' ),
			'manage_options',
			'hicpo-network-settings',
			[ $this, 'hicpo_network_admin_page' ]
		);
	}

	public function hicpo_network_admin_page() {
		require HICPO_DIR . 'admin/settings-network.php';
	}

	private function _hicpo_check_load_script_css() {
		global $pagenow, $typenow;

		$active = false;

		if ( ! current_user_can( 'hicpo_load_script_css' ) ) {
			return false;
		}

		// multisite > sites
		if (
			function_exists( 'is_multisite' )
			&& is_multisite()
			&& 'sites.php' == $pagenow
			&& get_option( 'hicpo_network_sites' )
		) {
			return true;
		}

		$objects = $this->hicpo_get_options_objects();
		$tags = $this->hicpo_get_options_tags();

		if ( empty( $objects ) && empty( $tags ) ) {
			return false;
		}

		// exclude when orderby is set or at add new or edit page
		$is_orderby_set = isset( $_GET['orderby'] );
		$is_edit_action_or_new_post = (
			isset( $_SERVER['REQUEST_URI'] ) &&
			(
				strstr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'action=edit' ) ||
				strstr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp-admin/post-new.php' )
			)
		);
		if ( $is_orderby_set || $is_edit_action_or_new_post ) {
			return false;
		}

		if ( ! empty( $objects ) ) {
			// page or custom post type
			if ( isset( $_GET['post_type'] ) && ! isset( $_GET['taxonomy'] ) && in_array( $_GET['post_type'], $objects ) ) {
				$active = true;
			}

			// post
			if (
				! isset( $_GET['post_type'] ) &&
				strstr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp-admin/edit.php' ) &&
				in_array( 'post', $objects )
			) {
				$active = true;
			}
		}

		if ( ! empty( $tags ) ) {
			if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], $tags ) ) {
				$active = true;
			}
		}

		return $active;
	}

	public function hicpo_load_script_css() {
		if ( $this->_hicpo_check_load_script_css() ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script( 'hicpojs', HICPO_URL . '/js/hicpo.js', [ 'jquery' ], HICPO_VER, true );
			wp_localize_script( 'hicpojs', 'hicpojs_ajax_vars', [ 'nonce' => wp_create_nonce( 'hicpojs-ajax-nonce' ) ] );

			wp_enqueue_style( 'hicpo', HICPO_URL . '/css/hicpo.css', [], HICPO_VER );
		}
	}

	public function hicpo_refresh() {
		global $wpdb;
		$objects = $this->hicpo_get_options_objects();
		$tags = $this->hicpo_get_options_tags();

		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				$query = $wpdb->prepare(
					"
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					",
					$object
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$result = $wpdb->get_results( $query );
				if ( 0 == $result[0]->cnt || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				$query = $wpdb->prepare(
					"
					SELECT ID
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					ORDER BY menu_order ASC
				",
					$object
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$results = $wpdb->get_results( $query );

				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, [ 'menu_order' => $key + 1 ], [ 'ID' => $result->ID ] );
				}
			}
		}

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $taxonomy ) {
				$query = $wpdb->prepare(
					"
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
				",
					$taxonomy
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$result = $wpdb->get_results( $query );

				if ( 0 == $result[0]->cnt || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				$query = $wpdb->prepare(
					"
					SELECT terms.term_id
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
					ORDER BY term_order ASC
				",
					$taxonomy
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$results = $wpdb->get_results( $query );
				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->terms, [ 'term_order' => $key + 1 ], [ 'term_id' => $result->term_id ] );
				}
			}
		}
	}

	public function hicpo_refresh_network() {
		global $pagenow;
		if ( 'sites.php' === $pagenow && ! isset( $_GET['orderby'] ) ) {
			add_filter( 'query', [ $this, 'hicpo_refresh_network_2' ] );
		}
	}

	public function hicpo_refresh_network_2( $query ) {
		global $wpdb, $wp_version, $blog_id;

		/**
		 * after wp4.7.0
		 * ブログのステータスが公開以外の際、$blog_id=1以外のoptionを取得しようとする処理のSQLを除外する
		 * eq.) SELECT option_name, option_value FROM wp_11_options WHERE autoload = 'yes'
		 */

		// $wpdb->get_varやswitch_to_blog(1)からのget_optionでは処理が止まるため、$blog_idで判別
		if ( version_compare( $wp_version, '4.7.0' ) >= 0 ) {
			if ( 1 !== $blog_id ) {
				return $query;
			}
		}

		$hicpo_network_sites = get_option( 'hicpo_network_sites' );
		if ( ! $hicpo_network_sites ) {
			return $query;
		}

		if (
			false !== strpos( $query, "SELECT * FROM $wpdb->blogs WHERE site_id = '1'" ) ||
			false !== strpos( $query, "SQL_CALC_FOUND_ROWS blog_id FROM $wpdb->blogs  WHERE site_id = 1" )
		) {
			if ( false !== strpos( $query, ' LIMIT ' ) ) {
				$query = preg_replace( '/^(.*) LIMIT(.*)$/', '$1 ORDER BY menu_order ASC LIMIT $2', $query );
			} else {
				$query .= ' ORDER BY menu_order ASC';
			}
		}
		return $query;
	}

	public function hicpo_update_menu_order() {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'hicpojs-ajax-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'hicpo_update_menu_order' ) ) {
			return;
		}

		if ( ! isset( $_POST['order'] ) ) {
			return;
		}

		$order = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		parse_str( $order, $data );

		if ( ! is_array( $data ) ) {
			return false;
		}

		// get objects per now page
		$id_arr = [];
		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}

		global $wpdb;

		// get menu_order of objects per now page
		$menu_order_arr = [];
		foreach ( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( "SELECT menu_order FROM $wpdb->posts WHERE ID = " . intval( $id ) );
			foreach ( $results as $result ) {
				$menu_order_arr[] = $result->menu_order;
			}
		}

		// maintains key association = no
		sort( $menu_order_arr );

		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$wpdb->update( $wpdb->posts, [ 'menu_order' => $menu_order_arr[ $position ] ], [ 'ID' => intval( $id ) ] );
			}
		}

		// same number check
		$post_type = get_post_type( $id );
		$query = $wpdb->prepare(
			"
			SELECT COUNT(menu_order) AS mo_count, post_type, menu_order FROM $wpdb->posts
			WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
			AND menu_order > 0 GROUP BY post_type, menu_order HAVING (mo_count) > 1
			",
			$post_type
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
		$results = $wpdb->get_results( $query );
		if ( count( $results ) > 0 ) {
			// menu_order refresh
			$query = $wpdb->prepare(
				"
			SELECT ID, menu_order FROM $wpdb->posts
			WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
			AND menu_order > 0 ORDER BY menu_order
			",
				$post_type
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
			$results = $wpdb->get_results( $query );
			foreach ( $results as $key => $result ) {
				$view_posi = array_search( $result->ID, $id_arr, true );
				if ( false === $view_posi ) {
					$view_posi = 999;
				}
				$sort_key = ( $result->menu_order * 1000 ) + $view_posi;
				$sort_ids[ $sort_key ] = $result->ID;
			}
			ksort( $sort_ids );
			$oreder_no = 0;
			foreach ( $sort_ids as $key => $id ) {
				$oreder_no = ++$oreder_no;
				$wpdb->update( $wpdb->posts, [ 'menu_order' => $oreder_no ], [ 'ID' => intval( $id ) ] );
			}
		}
	}

	public function hicpo_update_menu_order_tags() {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'hicpojs-ajax-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'hicpo_update_menu_order' ) ) {
			return;
		}

		if ( ! isset( $_POST['order'] ) ) {
			return;
		}

		$order = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		parse_str( $order, $data );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$id_arr = [];
		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}

		global $wpdb;

		$menu_order_arr = [];
		foreach ( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( "SELECT term_order FROM $wpdb->terms WHERE term_id = " . intval( $id ) );
			foreach ( $results as $result ) {
				$menu_order_arr[] = $result->term_order;
			}
		}
		sort( $menu_order_arr );

		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$wpdb->update( $wpdb->terms, [ 'term_order' => $menu_order_arr[ $position ] ], [ 'term_id' => intval( $id ) ] );
			}
		}

		// same number check
		$term = get_term( $id );
		$taxonomy = $term->taxonomy;
		$query = $wpdb->prepare(
			"
			SELECT COUNT(term_order) AS to_count, term_order
			FROM $wpdb->terms AS terms
			INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
			WHERE term_taxonomy.taxonomy = %s GROUP BY taxonomy, term_order HAVING (to_count) > 1
			",
			$taxonomy
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
		$results = $wpdb->get_results( $query );

		if ( count( $results ) > 0 ) {
			// term_order refresh
			$query = $wpdb->prepare(
				"
				SELECT terms.term_id, term_order
				FROM $wpdb->terms AS terms
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
				WHERE term_taxonomy.taxonomy = %s
				ORDER BY term_order ASC
				",
				$taxonomy
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
			$results = $wpdb->get_results( $query );

			foreach ( $results as $key => $result ) {
				$view_posi = array_search( $result->term_id, $id_arr, true );
				if ( false === $view_posi ) {
					$view_posi = 999;
				}
				$sort_key = ( $result->term_order * 1000 ) + $view_posi;
				$sort_ids[ $sort_key ] = $result->term_id;
			}
			ksort( $sort_ids );
			$oreder_no = 0;
			foreach ( $sort_ids as $key => $id ) {
				$oreder_no = ++$oreder_no;
				$wpdb->update( $wpdb->terms, [ 'term_order' => $oreder_no ], [ 'term_id' => $id ] );
			}
		}
	}

	public function hicpo_update_menu_order_sites() {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'hicpojs-ajax-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'hicpo_update_menu_order_sites' ) ) {
			return;
		}

		if ( ! isset( $_POST['order'] ) ) {
			return;
		}

		$order = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		parse_str( $order, $data );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$id_arr = [];
		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}

		global $wpdb;

		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$wpdb->update( $wpdb->blogs, [ 'menu_order' => $position + 1 ], [ 'blog_id' => intval( $id ) ] );
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

	public function hicpo_update_options() {
		global $wpdb;

		if ( ! isset( $_POST['hicpo_submit'] ) ) {
			return false;
		}

		check_admin_referer( 'nonce_hicpo' );

		$input_options = [];
		$input_options['objects'] = isset( $_POST['objects'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['objects'] ) ) : [];
		$input_options['tags'] = isset( $_POST['tags'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['tags'] ) ) : [];

		update_option( 'hicpo_options', $input_options );

		$objects = $this->hicpo_get_options_objects();
		$tags = $this->hicpo_get_options_tags();

		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				$query = $wpdb->prepare(
					"
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
				",
					$object
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$result = $wpdb->get_results( $query );
				if ( 0 == $result[0]->cnt || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				if ( 'page' == $object ) {
					$query = $wpdb->prepare(
						"
						SELECT ID
						FROM $wpdb->posts
						WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						ORDER BY menu_order, post_title ASC
					",
						$object
					);
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
					$results = $wpdb->get_results( $query );
				} else {
					$query = $wpdb->prepare(
						"
						SELECT ID
						FROM $wpdb->posts
						WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						ORDER BY post_date DESC
					",
						$object
					);
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
					$results = $wpdb->get_results( $query );
				}
				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, [ 'menu_order' => $key + 1 ], [ 'ID' => $result->ID ] );
				}
			}
		}

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $taxonomy ) {
				$query = $wpdb->prepare(
					"
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
				",
					$taxonomy
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$result = $wpdb->get_results( $query );
				if ( 0 == $result[0]->cnt || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				$query = $wpdb->prepare(
					"
					SELECT terms.term_id
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
					ORDER BY name ASC
				",
					$taxonomy
				);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
				$results = $wpdb->get_results( $query );
				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->terms, [ 'term_order' => $key + 1 ], [ 'term_id' => $result->term_id ] );
				}
			}
		}

		wp_redirect( 'admin.php?page=hicpo-settings&msg=update' );
	}

	public function hicpo_update_network_options() {
		global $wpdb;

		if ( ! isset( $_POST['hicpo_network_submit'] ) ) {
			return false;
		}

		check_admin_referer( 'nonce_hicpo' );

		$hicpo_network_sites = isset( $_POST['sites'] ) ? sanitize_text_field( wp_unslash( $_POST['sites'] ) ) : 0;

		update_option( 'hicpo_network_sites', $hicpo_network_sites );

		// Initial
		$result = $wpdb->get_results(
			"
			SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
			FROM $wpdb->blogs
			"
		);
		if ( 0 != $result[0]->cnt && $result[0]->cnt != $result[0]->max ) {
			$results = $wpdb->get_results(
				"
				SELECT blog_id
				FROM $wpdb->blogs
				ORDER BY blog_id ASC
			"
			);
			foreach ( $results as $key => $result ) {
				$wpdb->update( $wpdb->blogs, [ 'menu_order' => $key + 1 ], [ 'blog_id' => $result->blog_id ] );
			}
		}

		wp_redirect( 'settings.php?page=hicpo-network-settings&msg=update' );
	}

	public function hicpo_previous_post_where( $where ) {
		global $post;

		$objects = $this->hicpo_get_options_objects();
		if ( empty( $objects ) ) {
			return $where;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = str_replace( "p.post_date < '" . $post->post_date . "'", "p.menu_order > '" . $current_menu_order . "'", $where );
		}
		return $where;
	}

	public function hicpo_previous_post_sort( $orderby ) {
		global $post;

		$objects = $this->hicpo_get_options_objects();
		if ( empty( $objects ) ) {
			return $orderby;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
		}
		return $orderby;
	}

	public function hicpo_next_post_where( $where ) {
		global $post;

		$objects = $this->hicpo_get_options_objects();
		if ( empty( $objects ) ) {
			return $where;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$current_menu_order = $post->menu_order;
			$where = str_replace( "p.post_date > '" . $post->post_date . "'", "p.menu_order < '" . $current_menu_order . "'", $where );
		}
		return $where;
	}

	public function hicpo_next_post_sort( $orderby ) {
		global $post;

		$objects = $this->hicpo_get_options_objects();
		if ( empty( $objects ) ) {
			return $orderby;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects ) ) {
			$orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
		}
		return $orderby;
	}

	public function hicpo_pre_get_posts( $wp_query ) {
		$objects = $this->hicpo_get_options_objects();
		if ( empty( $objects ) ) {
			return false;
		}

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
			if ( isset( $wp_query->query['post_type'] ) && ! $wp_query->get( 'orderby' ) ) {
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
				if ( ! is_array( $wp_query->query['post_type'] ) ) {
					if ( in_array( $wp_query->query['post_type'], $objects ) ) {
						$active = true;
					}
				}
				// post
			} elseif ( in_array( 'post', $objects ) ) {
					$active = true;
			}

			if ( ! $active ) {
				return false;
			}

			// get_posts()
			if ( isset( $wp_query->query['suppress_filters'] ) ) {
				if ( $wp_query->get( 'orderby' ) == 'date' || $wp_query->get( 'orderby' ) == 'menu_order' ) {
					$wp_query->set( 'orderby', 'menu_order' );
					$wp_query->set( 'order', 'ASC' );
				} elseif ( $wp_query->get( 'orderby' ) == 'default_date' ) {
					$wp_query->set( 'orderby', 'date' );
				}
				// WP_Query( contain main_query )
			} else {
				if ( ! $wp_query->get( 'orderby' ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
				}
				if ( ! $wp_query->get( 'order' ) ) {
					$wp_query->set( 'order', 'ASC' );
				}
			}
		}
	}

	public function hicpo_get_terms_orderby( $orderby, $args ) {
		if ( is_admin() ) {
			return $orderby;
		}

		$tags = $this->hicpo_get_options_tags();

		if ( ! isset( $args['taxonomy'] ) ) {
			return $orderby;
		}

		$taxonomy = $args['taxonomy'];
		if ( ! in_array( $taxonomy, $tags ) ) {
			return $orderby;
		}

		$orderby = 't.term_order';
		return $orderby;
	}

	public function hicpo_get_object_terms( $terms ) {
		$tags = $this->hicpo_get_options_tags();

		if ( is_admin() && isset( $_GET['orderby'] ) ) {
			return $terms;
		}

		foreach ( $terms as $key => $term ) {
			if ( is_object( $term ) && isset( $term->taxonomy ) ) {
				$taxonomy = $term->taxonomy;
				if ( ! in_array( $taxonomy, $tags ) ) {
					return $terms;
				}
			} else {
				return $terms;
			}
		}

		usort( $terms, [ $this, 'hicpo_taxcmp' ] );
		return $terms;
	}

	private function hicpo_taxcmp( $a, $b ) {
		if ( $a->term_order == $b->term_order ) {
			return 0;
		}
		return ( $a->term_order < $b->term_order ) ? -1 : 1;
	}

	public function hicpo_sites_clauses( $pieces = [] ) {
		global $blog_id;

		if ( is_admin() ) {
			return $pieces;
		}
		if ( 1 != $blog_id ) {
			switch_to_blog( 1 );
			$hicpo_network_sites = get_option( 'hicpo_network_sites' );
			restore_current_blog();
			if ( ! $hicpo_network_sites ) {
				return $pieces;
			}
		} elseif ( ! get_option( 'hicpo_network_sites' ) ) {
				return $pieces;
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.6.0' ) >= 0 ) {
			// サイト並び替え指定がデフォルトの場合のみ並び替え
			if ( 'blog_id ASC' === $pieces['orderby'] ) {
				$pieces['orderby'] = 'menu_order ASC';
			}
		}
		return $pieces;
	}

	public function hicpo_get_blogs_of_user( $blogs ) {
		global $blog_id;
		if ( 1 != $blog_id ) {
			switch_to_blog( 1 );
			$hicpo_network_sites = get_option( 'hicpo_network_sites' );
			restore_current_blog();
			if ( ! $hicpo_network_sites ) {
				return $blogs;
			}
		} elseif ( ! get_option( 'hicpo_network_sites' ) ) {
				return $blogs;
		}
		global $wpdb, $wp_version;

		if ( version_compare( $wp_version, '4.6.0' ) >= 0 ) {
			$sites = get_sites( [] );
			$sort_keys = [];
			foreach ( $sites as $k => $v ) {
				$sort_keys[] = $v->menu_order;
			}
			array_multisort( $sort_keys, SORT_ASC, $sites );

			$blog_list = [];
			foreach ( $blogs as $k => $v ) {
				$blog_list[ $v->userblog_id ] = $v;
			}

			$new = [];
			foreach ( $sites as $k => $v ) {
				if (
					isset( $v->blog_id ) &&
					isset( $blog_list[ $v->blog_id ] ) &&
					is_object( $blog_list[ $v->blog_id ] )
				) {
					$new[] = $blog_list[ $v->blog_id ];
				}
			}
		} else {
			$sites = get_sites( [ 'limit' => 9999 ] );
			$sort_keys = [];
			foreach ( $sites as $k => $v ) {
				$sort_keys[] = $v['menu_order'];
			}
			array_multisort( $sort_keys, SORT_ASC, $sites );

			$blog_list = [];
			foreach ( $blogs as $k => $v ) {
				$blog_list[ $v->userblog_id ] = $v;
			}

			$new = [];
			foreach ( $sites as $k => $v ) {
				if (
					isset( $v['blog_id'] ) &&
					isset( $blog_list[ $v['blog_id'] ] ) &&
					is_object( $blog_list[ $v['blog_id'] ] )
				) {
					$new[] = $blog_list[ $v['blog_id'] ];
				}
			}
		}
		return $new;
	}

	/* before wp v4.6.0 */
	public function hicpo_refresh_front_network() {
		 global $wp_version;
		if ( version_compare( $wp_version, '4.6.0' ) < 0 ) {
			global $blog_id;
			if ( 1 != $blog_id ) {
				$hicpo_network_sites = get_option( 'hicpo_network_sites' );
				restore_current_blog();
				if ( ! $hicpo_network_sites ) {
					return;
				}
			} elseif ( ! get_option( 'hicpo_network_sites' ) ) {
					return;
			}
			add_filter( 'query', [ $this, 'hicpo_refresh_front_network_2' ] );
		}
	}
	public function hicpo_refresh_front_network_2( $query ) {
		global $wpdb;
		if ( false !== strpos( $query, "SELECT  blog_id FROM $wpdb->blogs    ORDER BY blog_id ASC" ) ) {
			$query = str_replace( 'ORDER BY blog_id ASC', '', $query );
			if ( false !== strpos( $query, ' LIMIT ' ) ) {
				$query = preg_replace( '/^(.*) LIMIT(.*)$/', '$1 ORDER BY menu_order ASC LIMIT $2', $query );
			} else {
				$query .= ' ORDER BY menu_order ASC';
			}
		} elseif ( false !== strpos( $query, "SELECT * FROM $wpdb->blogs WHERE 1=1 AND site_id IN (1)" ) ) {
			if ( false !== strpos( $query, ' LIMIT ' ) ) {
				$query = preg_replace( '/^(.*) LIMIT(.*)$/', '$1 ORDER BY menu_order ASC LIMIT $2', $query );
			} else {
				$query .= ' ORDER BY menu_order ASC';
			}
		}
		return $query;
	}

	public function hicpo_get_options_objects() {
		$hicpo_options = get_option( 'hicpo_options' ) ? get_option( 'hicpo_options' ) : [];
		$objects = isset( $hicpo_options['objects'] ) && is_array( $hicpo_options['objects'] ) ? $hicpo_options['objects'] : [];
		return $objects;
	}
	public function hicpo_get_options_tags() {
		$hicpo_options = get_option( 'hicpo_options' ) ? get_option( 'hicpo_options' ) : [];
		$tags = isset( $hicpo_options['tags'] ) && is_array( $hicpo_options['tags'] ) ? $hicpo_options['tags'] : [];
		return $tags;
	}

	public function hicpo_add_capabilities() {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			$administrator->add_cap( 'hicpo_load_script_css' );
			$administrator->add_cap( 'hicpo_update_menu_order' );
			$administrator->add_cap( 'hicpo_update_menu_order_tags' );
			$administrator->add_cap( 'hicpo_update_menu_order_sites' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'hicpo_load_script_css' );
			$editor->add_cap( 'hicpo_update_menu_order' );
			$editor->add_cap( 'hicpo_update_menu_order_tags' );
		}
	}
}
