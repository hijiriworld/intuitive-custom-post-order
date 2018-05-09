(function ($) {

  // #the-list is the id associated to any ".wp-list-table tbody" element,

  var variations = {
    posts: {
      selector: ".wp-list-table.pages tbody, .wp-list-table.posts tbody",
      beforeSortable: undefined,
      update: function (e, ui) {
        $.post(ajaxurl, {
          action: 'update-menu-order',
          order: $("#the-list").sortable('serialize')
        });
      }
    },
    taxonomies: {
      selector: ".wp-list-table.tags tbody",
      beforeSortable: undefined,
      update: function (e, ui) {
        $.post(ajaxurl, {
          action: 'update-menu-order-tags',
          order: $("#the-list").sortable('serialize')
        });
      }
    },
    sites: {
      selector: ".wp-list-table.sites tbody",
      beforeSortable: function () {
        // add number
        var site_table_tr = $('table.sites #the-list tr');
        site_table_tr.each(function () {
          var ret = null;
          var url = $(this).find('td.blogname a').attr('href');
          var parameters = url.split('?');
          if (parameters.length > 1) {
            var params = parameters[1].split('&');
            var paramsArray = [];
            for (var i = 0; i < params.length; i++) {
              var neet = params[i].split('=');
              paramsArray.push(neet[0]);
              paramsArray[neet[0]] = neet[1];
            }
            ret = paramsArray['id'];
          }
          $(this).attr('id', 'site-' + ret);
        });
      },
      update: function (e, ui) {
        $.post(ajaxurl, {
          action: 'update-menu-order-sites',
          order: $("#the-list").sortable('serialize')
        });
      }
    }
  };

  $.each(variations, function (type, options) {
    var sortable_table = $(options.selector);
    if (!sortable_table || !sortable_table.length) {
      // skip to next iteration
      return true;
    }

    if (options.beforeSortable) {
      options.beforeSortable();
    }

    sortable_table.sortable({
      items: '> tr',
      cursor: 'move',
      axis: 'y',
      containment: 'table.widefat',
      cancel: 'input, textarea, button, select, option, .inline-edit-row',
      distance: 2,
      opacity: .8,
      tolerance: 'pointer',
      create: function () {
        $(document).keydown(function (e) {
          var key = e.key || e.keyCode;
          if ('Escape' === key || 'Esc' === key || 27 === key) {
            sortable_table.sortable('option', 'preventUpdate', true);
            sortable_table.sortable('cancel');
          }
        });
      },
      start: function (e, ui) {
        if (typeof(inlineEditPost) !== 'undefined') {
          inlineEditPost.revert();
        }
        ui.placeholder.height(ui.item.height());
        ui.placeholder.empty();
      },
      helper: function (e, ui) {
        var children = ui.children();
        for (var i = 0; i < children.length; i++) {
          var selector = $(children[i]);
          selector.width(selector.width());
        }
        return ui;
      },
      stop: function (e, ui) {
        if (sortable_table.sortable('option', 'preventUpdate')) {
          sortable_table.sortable('option', 'preventUpdate', false);
        }

        // remove fixed widths
        ui.item.children().css('width', '');
      },
      update: options.update
    });
  });

})(jQuery)
