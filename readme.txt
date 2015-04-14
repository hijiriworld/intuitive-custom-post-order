=== Intuitive Custom Post Order ===
Contributors: hijiri
Tags: post order, posts order, order post, order posts, custom post type order, custom taxonomy order
Requires at least: 3.5.0
Tested up to: 4.1.0
Stable tag: 3.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Intuitively, Order Items (Posts, Pages, and Custom Post Types, and Custom Taxonomies) using a Drag and Drop Sortable JavaScript.

== Description ==

Intuitively, Order Items (Posts, Pages, and Custom Post Types, and Custom Taxonomies) using a Drag and Drop Sortable JavaScript.
Configuration is unnecessary.
You can do directly on default WordPress administration.

You can re-override the parameters of 'orderby' and 'order'.
In order to re-override the parameters, You must use the 'WP_Query' or 'pre_get_posts' or 'query_posts'.
The 'get_posts()' is excluded.

<a href="https://github.com/hijiriworld/intuitive-custom-post-order">This Plugin published on GitHub.</a>

== Installation ==

1. Upload 'intuitive-custom-post-order' folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Select Sortable Objects from Intuitive CPT Menu.

== Screenshots ==

1. Reorder post
2. Reorder taxonomy
3. Settings

== Frequently Asked Questions ==

= How to re-override the parameters of 'orderby' and 'order' =

Sub query

Use the 'WP_Query', you can re-override the parameters.

`
<?php $query = new WP_Query( array(
	'orderby' => 'date',
	'order' => 'DESC',
) ) ?>
`

Main query

Use the 'pre_get_posts' action hook or 'query_posts', you can re-override the parameters.

pre_get_posts

`
function my_filter( $query )
{
	if ( is_admin() || !$query->is_main_query() ) return;
	if ( is_home() ) {
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
		return;
	}
}
add_action( 'pre_get_posts', 'my_filter' );
`

query_posts

`
<?php query_posts( array(
	'orderby' => 'rand'
) ); ?>
`

== Changelog ==


= 3.0.4 =

* Your Query which uses the 'order' or 'orderby' parameters is preferred.
  In order to prefer the parameters of your query, You must use the 'WP_Query()' or 'query_posts()'.
  Excluded 'get_posts()'.
* Fixed bug
  - Decision of Enabling Sortable JavaScript.
  - Initialize of menu_order of pages.( orderby=post_title, order=asc )

= 3.0.3 =

* Performance improvement for Activation.
* Add Initialize of Custom Taxonomy Order.
* Fixed bug of refresh method.
* Overwirting orderby, order improved.(Thanks @newash and @anriettec)

= 3.0.1 & 3.0.2 =

* Fixed bug

= 3.0.0 =

* Support the Custom Taxonomy Order!!
  ( wp_list_categories, get_categories, the_terms, the_tags, get_terms, get_the_terms, get_the_term_list, the_category, wp_dropdown_categories, the_taxonomies )
* Suuport the sorting in admin UI.
  While having sorted, Drag and Drop Sortable Javascript don't run.
* Support non public objects( show_ui=true, show_in_menu=true )
* Add Japanese Translations.

= 2.1.0 =

* Fixed bug: Custom Query which uses 'order' or 'orderby' parameters is preferred.
* It does not depend on the designation manner of arguments( Parameters ).
  ( $args = 'orderby=&order=' or $args = array( 'orderby' => '', 'order' => '' ) )
* The trouble which exists in 2.0.7, 2.0.8, 2.0.9 was improved!
* From 2.0.6 please update in 2.1.0.

= 2.0.9 =

* Performance improvement for Admin.
  Fatal performance problem was improved dramatically.
* Fixed bug: Attachment objects are not broken.
* Fixed bug: Alert warning on the multisite was solved.
* Fixed bug: First when enabling items, 'menu order' of items are not broken.
* Custom Query which uses 'order' or 'orderby' parameters is preferred.

= 2.0.8 =

* Performance improvement for Admin.
  Refresh method( re-constructing all menu order) run on only active object's List page.

= 2.0.7 =

* Fixed bug: for WordPress 3.8
* Add Swedish Translations.(by Thomas)

= 2.0.6 =

* ver.2.0.5 fixed.

= 2.0.5 =

* Support 'next_post_link()' and 'previous_post_link()'(single to single).

= 2.0.4 =

* Fixed bug

= 2.0.3 =

* Intuitive CPO Settings Page was moved to Settings menu.

= 2.0.2 =

* Fixed bug

= 2.0.0 =

* Select Sortable Objects. (Posts, Pages, and Custom Post Types)
* Support Pages and hierarchical Custom Post Types.
* Sortable Item's status is not only 'publish' but also other all status('pending', 'draft', 'private', 'future').
* In Paging, it's all activated normaly. So, 'screen-per-page' is User like.
* In Lists which sorted any category(Category, Tag, Taxonomy), it's all activated normaly.
* Support Child posts and Child pages. When you sort any item, Relation of parent item between it's child items is maintained.

= 1.2.1 =

* Bug fixed

= 1.2.0 =

* Sortable UI that Visually cleared. (Change cursor, and so on.)
* Sortable items can be dragged only vertically.
* Quick Edit Menu was enabled.
* It is not collapse of the cell widths any more whenever dragging any items.

= 1.1.1 =

* Fixed bug

= 1.1.0 =

* screen-per-page is configurated to '999' automatically to prevent the trouble due to not setting it.
* Excluding custom query which uses 'order' or 'orderby' parameters, in 'get_posts' or 'query_posts' and so on.

= 1.0.0 =

Initial Release

== Upgrade Notice ==

= 3.0.3 =

Expand Database Table: wp_terms.
