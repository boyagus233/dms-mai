/**
 * DMS Dropdown Helper:
 * 1. Forces all Select2 dropdown panels to open strictly BELOW the field & search bar.
 * 2. Smoothly scrolls viewport downward and fits height if near the bottom edge so it never flips above.
 * 3. Enhances long form <select> controls with searchable Select2 and bridges native DOM change events.
 */
(function () {
  function getJQuery() {
    return window.jQuery || window.$ || null;
  }

  function patchSelect2() {
    var $ = getJQuery();
    if (!$ || !$.fn || !$.fn.select2 || !$.fn.select2.amd) {
      return false;
    }

    try {
      $.fn.select2.amd.require(['select2/dropdown/attachBody'], function (AttachBody) {
        if (!AttachBody || !AttachBody.prototype) return;
        if (AttachBody.prototype._positionDropdown && AttachBody.prototype._positionDropdown.__dmsBelowOnly) {
          return;
        }

        var belowOnlyPosition = function () {
          var offset = this.$container.offset();
          if (!offset) return;

          var containerHeight = this.$container.outerHeight(false);
          var containerBottom = offset.top + containerHeight;

          var $offsetParent = this.$dropdownParent;
          if ($offsetParent && $offsetParent.css('position') === 'static') {
            $offsetParent = $offsetParent.offsetParent();
          }
          var parentOffset = ($offsetParent && $offsetParent.offset()) || { top: 0, left: 0 };

          var css = {
            left: offset.left - parentOffset.left,
            top: containerBottom - parentOffset.top
          };

          this.$dropdown
            .removeClass('select2-dropdown--above')
            .addClass('select2-dropdown--below');
          this.$container
            .removeClass('select2-container--above')
            .addClass('select2-container--below');

          this.$dropdownContainer.css(css);
        };

        belowOnlyPosition.__dmsBelowOnly = true;
        AttachBody.prototype._positionDropdown = belowOnlyPosition;
      });
    } catch (e) {
      // Ignore if Select2 AMD module differs
    }

    return true;
  }

  function ensureOpenBehaviorBound() {
    var $ = getJQuery();
    if (!$ || window.__dmsSelect2GlobalEventsBound) return;
    window.__dmsSelect2GlobalEventsBound = true;

    $(document).on('select2:open.dmsBelow', function (e) {
      patchSelect2();

      var searchField = document.querySelector('.select2-container--open .select2-search__field');
      if (searchField) {
        if (!searchField.getAttribute('placeholder')) {
          searchField.setAttribute('placeholder', 'Ketik untuk mencari...');
        }
        window.setTimeout(function () {
          try {
            searchField.focus({ preventScroll: true });
          } catch (err) {
            searchField.focus();
          }
        }, 10);
      }

      // Gently scroll downward if the below-dropdown extends past the bottom viewport edge
      window.requestAnimationFrame(function () {
        var selectEl = e && e.target;
        var $container = selectEl ? $(selectEl).next('.select2-container') : $('.select2-container--open').first();
        var dropdownEl = document.querySelector('.select2-container--open .select2-dropdown');
        if (!$container.length || !dropdownEl) return;

        var resultsEl = dropdownEl.querySelector('.select2-results__options');
        if (resultsEl) {
          resultsEl.style.maxHeight = '230px';
        }

        var containerRect = $container[0].getBoundingClientRect();
        var dropdownRect = dropdownEl.getBoundingClientRect();
        var marginBottom = 16;

        if (dropdownRect.bottom > window.innerHeight - marginBottom) {
          var overflow = dropdownRect.bottom - (window.innerHeight - marginBottom);
          var keepAboveNavbar = Math.max(0, containerRect.top - 96);
          var maxPageScroll = Math.max(
            0,
            document.documentElement.scrollHeight - (window.scrollY + window.innerHeight)
          );
          var scrollByY = Math.min(overflow, keepAboveNavbar, maxPageScroll);
          if (scrollByY > 0) {
            window.scrollBy({ top: scrollByY, behavior: 'smooth' });
          }

          // Cap result list height cleanly if very little room remains below scroll limit
          var remainingViewportBelow = window.innerHeight - (containerRect.bottom - scrollByY) - marginBottom;
          var searchHeight = (dropdownEl.querySelector('.select2-search--dropdown')?.offsetHeight || 0);
          var maxResultsH = Math.max(130, Math.min(230, remainingViewportBelow - searchHeight - 8));
          if (resultsEl && maxResultsH < 230) {
            resultsEl.style.maxHeight = maxResultsH + 'px';
          }
        }
      });
    });
  }

  function shouldAutoEnhance(el) {
    if (!el || el.tagName !== 'SELECT') return false;
    if (el.disabled) return false;
    if (el.classList.contains('select2-hidden-accessible')) return false;
    if (el.hasAttribute('data-no-select2')) return false;
    if (el.closest('.dataTables_wrapper, .dt-container')) return false;

    var inlineOnChange = el.getAttribute('onchange') || '';
    if (inlineOnChange.indexOf('submit(') !== -1) return false;

    if (el.classList.contains('searchable-select') || el.classList.contains('signer-select') || el.multiple) {
      return true;
    }

    var nonEmptyCount = 0;
    for (var i = 0; i < el.options.length; i++) {
      if (el.options[i].value !== '') {
        nonEmptyCount++;
        if (nonEmptyCount >= 7) return true;
      }
    }
    return false;
  }

  function bindNativeChangeBridge($, el) {
    if (el.dataset.dmsSelect2Bound === '1') return;
    el.dataset.dmsSelect2Bound = '1';

    $(el).on('change.dmsNativeBridge', function (evt) {
      $(el).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
      el.classList.remove('is-invalid');

      if (!evt.originalEvent && !el._dmsDispatchingNativeChange) {
        el._dmsDispatchingNativeChange = true;
        try {
          el.dispatchEvent(new Event('change', { bubbles: true }));
        } finally {
          el._dmsDispatchingNativeChange = false;
        }
      }
    });

    el.addEventListener('invalid', function () {
      el.classList.add('is-invalid');
      var containerEl = el.nextElementSibling;
      if (containerEl && containerEl.classList.contains('select2-container')) {
        containerEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  function initSelect(target, customOptions) {
    var $ = getJQuery();
    if (!$ || !$.fn || !$.fn.select2) return;

    patchSelect2();
    ensureOpenBehaviorBound();

    var elements = [];
    if (!target) return;
    if (typeof target === 'string') {
      elements = Array.prototype.slice.call(document.querySelectorAll(target));
    } else if (target.jquery) {
      elements = target.toArray();
    } else if (target.tagName === 'SELECT') {
      elements = [target];
    }

    elements.forEach(function (el) {
      if (!el || el.tagName !== 'SELECT' || el.disabled) return;
      if ($(el).hasClass('select2-hidden-accessible')) {
        bindNativeChangeBridge($, el);
        return;
      }

      var emptyOpt = null;
      for (var i = 0; i < el.options.length; i++) {
        if (el.options[i].value === '') {
          emptyOpt = el.options[i];
          break;
        }
      }

      var defaultPlaceholder = el.getAttribute('data-placeholder')
        || (emptyOpt ? emptyOpt.textContent.trim() : '')
        || '-- Pilih --';

      var modalParent = el.closest('.modal');
      var opts = $.extend({
        width: '100%',
        placeholder: defaultPlaceholder,
        allowClear: !el.multiple && !!emptyOpt,
        language: {
          noResults: function () {
            return 'Data tidak ditemukan';
          },
          searching: function () {
            return 'Mencari...';
          }
        }
      }, customOptions || {});

      if (modalParent && !opts.dropdownParent) {
        opts.dropdownParent = $(modalParent);
      }

      $(el).select2(opts);
      bindNativeChangeBridge($, el);
    });
  }

  function initAll(root) {
    var $ = getJQuery();
    if (!$ || !$.fn || !$.fn.select2) return;

    patchSelect2();
    ensureOpenBehaviorBound();

    var scope = root || document;
    var selects = scope.querySelectorAll('select.form-select');
    Array.prototype.forEach.call(selects, function (el) {
      if ($(el).hasClass('select2-hidden-accessible')) {
        bindNativeChangeBridge($, el);
        return;
      }
      if (shouldAutoEnhance(el)) {
        initSelect(el);
      }
    });
  }

  window.DMSDropdownHelper = {
    patchSelect2: patchSelect2,
    init: initSelect,
    initAll: initAll
  };

  patchSelect2();
  ensureOpenBehaviorBound();

  // Defer sweep until after page-level $(document).ready handlers finish their custom Select2 setups
  var scheduleSweep = function () {
    window.setTimeout(function () {
      initAll();
    }, 0);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleSweep);
  } else {
    scheduleSweep();
  }
})();
