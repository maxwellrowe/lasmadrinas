(function($) {
  var activeClass = 'lm-gf-button-spinner-active';
  var hiddenLabelClass = 'lm-gf-button-spinner-hidden-label';
  var wrapClass = 'lm-gf-button-spinner-wrap';

  function getSubmitButton($form) {
    return $form.find('.gform_footer input[type="submit"], .gform_page_footer input[type="submit"], button.gform_button').first();
  }

  function ensureWrap($button) {
    if (!$button.parent().hasClass(wrapClass)) {
      $button.wrap('<span class="' + wrapClass + '"></span>');
    }

    return $button.parent();
  }

  function setSubmitting($form) {
    var $button = getSubmitButton($form);
    var $wrap = ensureWrap($button);

    if (!$button.length || $wrap.hasClass(activeClass)) {
      return;
    }

    $wrap.addClass(activeClass);
    $button.prop('disabled', true);

    if ($button.is('input')) {
      $button.data('lmOriginalValue', $button.val());
      $button.val('');
      $button.addClass(hiddenLabelClass);
    }
  }

  function clearSubmitting(formId) {
    var selector = formId ? '#gform_' + formId : '.gform_wrapper form';

    $(selector).each(function() {
      var $form = $(this);
      var $button = getSubmitButton($form);
      var $wrap = $button.parent('.' + wrapClass);

      if (!$button.length) {
        return;
      }

      $wrap.removeClass(activeClass);
      $button.prop('disabled', false);

      if ($button.is('input')) {
        if ($button.data('lmOriginalValue')) {
          $button.val($button.data('lmOriginalValue'));
        }

        $button.removeClass(hiddenLabelClass);
      }
    });
  }

  $(document).on('click', '.gform_wrapper .gform_footer input[type="submit"], .gform_wrapper .gform_page_footer input[type="submit"], .gform_wrapper button.gform_button', function() {
    setSubmitting($(this).closest('form'));
  });

  $(document).on('submit', '.gform_wrapper form', function() {
    setSubmitting($(this));
  });

  $(document).on('gform_post_render', function(event, formId) {
    clearSubmitting(formId);
  });

  $(document).on('gform_page_loaded', function(event, formId) {
    clearSubmitting(formId);
  });

  $(document).on('gform_confirmation_loaded', function(event, formId) {
    clearSubmitting(formId);
  });
})(jQuery);
