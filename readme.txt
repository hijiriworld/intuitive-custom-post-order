=== Intuitive Custom Post Order ===
Contributors: hijiri
Tags: post order, posts order, order post, order posts, custom post type order
Requires at least: 3.5.0
Tested up to: 3.8.1
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Intuitively, Order Items (Posts, Pages, and Custom Post Types) using a Drag and Drop Sortable JavaScript.

== Description ==

Intuitively, Order Items (Posts, Pages, and Custom Post Types) using a Drag and Drop Sortable JavaScript.
Configuration is unnecessary.
You can do directly on default WordPress administration.

Custom Query which uses 'order' or 'orderby' parameters is preferred. ( 'WP_Query()', 'get_posts()', 'query_posts()' )

== Installation ==

1. Upload 'intuitive-custom-post-order' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. (Optional) Select Sortable Objects from Intuitive CPT Menu

== Screenshots ==

1. Order items

== Changelog ==

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