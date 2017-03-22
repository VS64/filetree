(function($, Drupal) {
  Drupal.behaviors.filetree = {
    attach: function(context, drupalSettings) {

      // Collapse the sub-folders.
      $('.filetree .files ul').hide();

      // Expand/collapse sub-folder when clicking parent folder.
      $('.filetree .files li:has(ul)', context).click(function(event) {
        event.preventDefault();
        // A link was clicked, so don't mess with the folders.
        if ($(event.target).is('a')) {
          return;
        }
        // If multiple folders are not allowed, collapse non-parent folders.
        if (!$(this).parents('.filetree').hasClass('multi')) {
          $(this).parents('.files').find('li:has(ul)').not($(this).parents()).not($(this)).removeClass('expanded').find('ul:first').hide('fast');
        }
        // Expand.
        if (!$(this).hasClass('expanded')) {
          $(this).addClass('expanded').find('ul:first').show('fast');
        }
        // Collapse.
        else {
          $(this).removeClass('expanded').find('ul:first').hide('fast');
        }
        // Prevent collapsing parent folders.
        return false;
      });

      // Expand/collapse all when clicking controls.
      $('.filetree .controls a').click(function(event) {
        event.preventDefault();
        if ($(this).hasClass('expand')) {
          $(this).parents('.filetree').find('.files li:has(ul)').addClass('expanded').find('ul:first').show('fast');
        } else {
          $(this).parents('.filetree').find('.files li:has(ul)').removeClass('expanded').find('ul:first').hide('fast');
        }
        return false;
      });

      // Click on file links. Overrides click on expanded folder area.
      $('.filetree .item-list li a').click(function(event) {
        event.preventDefault();
        window.open($(this).attr('href'), '_blank');
      });

    }
  };
})(jQuery, Drupal);
