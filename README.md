# Intuitive Custom Post Order

Intuitive Custom Post Order is WordPress Plugin that order items using a drag and drop sortable JavaScript.

## Description

Intuitively, order items (Posts, Pages, Custom Post Types, Custom Taxonomies, Sites) using a drag and drop sortable JavaScript.

Select sortable items from 'Intuitive CPO' menu of Setting menu in WordPress.

In addition, You can re-override the parameters of `orderby` and `order`, by using the `WP_Query` or `pre_get_posts` or `query_posts()` or `get_posts()`.

**Attention**: Only if you use `get_posts()` to re-overwrite to the default order (`orderby=date, order=DESC`), You need to use own custom parameter `orderby=default_date`.

## Installation

1. Upload 'intuitive-custom-post-order' folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Select sortable items from 'Intuitive CPO' menu of Setting menu in WordPress.

## Local development

To ensure following WordPress coding standards [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) is used for linting. Currently the CSS and JavaScript parts are not heavy so that there is no bundle process implemented, yet.

### Dependencies

* [Node](https://nodejs.org/en/) >= 18
* [Composer](https://getcomposer.org/download/) >= 2.0

### Installation

To use the plugin coding standards and linting navigate to the **plugin folder** and run the following commands in terminal:

```Shell
composer install
npm i
```

### Lint

After the installation is complete you can process linting with this command:

```Shell
npm run lint
```

### Create a zip file

When you are ready you can create a zip file, which excludes not necessary files with this command:

```Shell
npm run plugin-zip
```

## Documentation

* [WordPress Plugin Directory](https://wordpress.org/plugins/intuitive-custom-post-order/)

## License

GPLv2 or later
