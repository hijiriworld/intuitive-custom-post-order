# Intuitive Custom Post Order

## Description

Intuitively reorder Posts, Pages, Custom Post Types, Taxonomies, and Sites with a simple drag-and-drop interface.

Intuitive Custom Post Order lets you reorder items with simple drag and drop in the WordPress admin.  
You can sort Posts, Pages, Custom Post Types, Taxonomies, and (on Multisite) Sites.

Go to **Settings → Intuitive CPO** and select which content types you want to make sortable.  
Once enabled, just drag and drop items in the list tables—no extra setup is required.

If you create custom queries in your theme or plugins, set `orderby=menu_order` and `order=ASC` to respect the drag-and-drop order.  
To keep the default WordPress order (by date), explicitly set `orderby=date` and `order=DESC`.

Source code and development are available on [GitHub](https://github.com/hijiriworld/intuitive-custom-post-order).

## Installation

1. Upload the 'intuitive-custom-post-order' folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings → Intuitive CPO** and choose which post types or taxonomies you want to make sortable.
4. Simply drag and drop items in the list tables to reorder them.

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
