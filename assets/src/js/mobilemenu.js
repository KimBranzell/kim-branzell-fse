/**
 * Multi-layer drill-down navigation for the mobile menu.
 *
 * Reads the existing menu HTML and builds a stack-based layer view so
 * mobile users can drill into submenus instead of seeing everything
 * expanded at once. Each layer is a slide-in/out panel with a back
 * button, proper focus management, and screen reader announcements.
 */
const mobileMenu = () => {
  var toggle = document.getElementById('MobileMenuToggle');
  var nav = document.getElementById('HeaderNav');

  if (!toggle || !nav) {
    return;
  }

  // ── State ─────────────────────────────────────────────────

  var layersContainer = null;
  var liveRegion = null;
  var layerStack = [];

  // Measure scrollbar width once for layout-shift compensation
  var scrollbarWidth = (function () {
    var el = document.createElement('div');
    el.style.cssText = 'width:100px;height:100px;overflow:scroll;position:absolute;top:-9999px';
    document.body.appendChild(el);
    var w = el.offsetWidth - el.clientWidth;
    document.body.removeChild(el);
    return w;
  })();
  document.documentElement.style.setProperty('--scrollbar-width', scrollbarWidth + 'px');

  // ── Live region for screen reader announcements ───────────

  function createLiveRegion() {
    liveRegion = document.createElement('div');
    liveRegion.setAttribute('role', 'status');
    liveRegion.className = 'screen-reader-text';
    nav.appendChild(liveRegion);
  }

  function announce(text) {
    if (!liveRegion) {
      return;
    }
    // Clear and re-set to force announcement even if text is the same
    liveRegion.textContent = '';
    var force = liveRegion.offsetWidth; // eslint-disable-line no-unused-vars
    liveRegion.textContent = text;
  }

  // ── Item extraction from DOM ──────────────────────────────

  /**
   * Extract item data from a regular submenu <ul>.
   * Each <li> is examined for its <a> and a possible .children block.
   * Descriptions are read from either data-description (set by PHP on
   * regular links) or .mega-menu__description (set in mega-menu HTML).
   * Mega-menu parent items also carry a layerDescription extracted
   * from .mega-menu__title-description.
   */
  function extractItemsFromList(ulElement) {
    var items = [];
    var liElements = ulElement.querySelectorAll(':scope > li');

    liElements.forEach(function (li) {
      var link = li.querySelector(':scope > a');
      if (!link) return;

      var url = link.getAttribute('href') || '#';

      var item = {
        title: link.textContent.trim(),
        url: url,
        description: link.getAttribute('data-description') || '',
        isActive: li.classList.contains('is-active') || link.getAttribute('aria-current') === 'page',
        children: [],
        layerDescription: '',  // set when a mega-menu parent is detected
        depth: null   // set by caller
      };

      // Check for submenu children
      var childrenDiv = li.querySelector(':scope > .children');
      if (childrenDiv) {
        // Try regular submenu <ul> first
        var childUl = childrenDiv.querySelector(':scope > ul');
        if (childUl) {
          item.children = extractItemsFromList(childUl);
        }
        // If no <ul>, check for mega-menu columns
        if (item.children.length === 0) {
          item.children = extractMegaMenuColumns(childrenDiv);

          // Pull the parent description from the mega-menu title-description block
          var titleDesc = childrenDiv.querySelector('.mega-menu__title-description > div');
          if (titleDesc) {
            // Clone to avoid mutating the live DOM; strip the "Read more" link
            var clone = titleDesc.cloneNode(true);
            var readMore = clone.querySelector('a');
            if (readMore) readMore.remove();
            var text = clone.textContent.trim();
            if (text) {
              item.layerDescription = text;
            }
          }
        }
      }

      items.push(item);
    });

    return items;
  }

  /**
   * Extract items from a mega-menu .children block.
   * Each .mega-menu__column becomes a drillable item carrying the
   * column's heading as its title and its description text.
   */
  function extractMegaMenuColumns(childrenDiv) {
    var columns = childrenDiv.querySelectorAll(':scope .mega-menu__column');
    var items = [];

    columns.forEach(function (col) {
      var heading = col.querySelector('.mega-menu__heading');
      var colTitle = heading ? heading.textContent.trim() : '';

      if (!colTitle) return;

      var descEl = col.querySelector('.mega-menu__description');
      var colDescription = descEl ? descEl.textContent.trim() : '';

      var colItem = {
        title: colTitle,
        url: '#',
        description: colDescription,
        isActive: false,
        children: [],
        depth: null
      };

      // Child links inside the column
      var colUl = col.querySelector(':scope > ul');
      if (colUl) {
        colItem.children = extractItemsFromList(colUl);
      }

      items.push(colItem);
    });

    return items;
  }

  // ── Label element accessor ────────────────────────────────

  function getLabelEl() {
    return toggle.querySelector('.site-header__mobile-menu-button__label');
  }

  // ── Layer element builders ────────────────────────────────

  /**
   * Create the outer shell of a layer: header (back + title) + item list.
   */
  function createLayerStructure(title) {
    var layer = document.createElement('div');
    layer.className = 'mobile-layer';
    layer.setAttribute('role', 'group');
    layer.setAttribute('aria-label', title);

    var header = document.createElement('div');
    header.className = 'mobile-layer__header';

    var titleEl = document.createElement('span');
    titleEl.className = 'mobile-layer__title';
    titleEl.textContent = title;

    header.appendChild(titleEl);
    layer.appendChild(header);

    var list = document.createElement('ul');
    list.className = 'mobile-layer__items';
    layer.appendChild(list);

    return { layer: layer, list: list, header: header, titleEl: titleEl };
  }

  /**
   * Build a single <li> from item data. Adds a drill button when
   * the item has children, and an optional description below the title.
   */
  function buildItemElement(itemData, parentTitle) {
    var li = document.createElement('li');
    li.className = 'mobile-layer__item';

    if (itemData.isActive) {
      li.classList.add('is-active');
    }

    var link = document.createElement('a');
    link.href = itemData.url;
    link.className = 'mobile-layer__link';

    // Title
    var titleSpan = document.createElement('span');
    titleSpan.className = 'mobile-layer__link-title';
    titleSpan.textContent = itemData.title;
    link.appendChild(titleSpan);

    // Description (present for mega-menu columns and items with data-description)
    if (itemData.description) {
      var descSpan = document.createElement('span');
      descSpan.className = 'mobile-layer__link-description';
      descSpan.textContent = itemData.description;
      link.appendChild(descSpan);
    }

    if (itemData.isActive) {
      link.setAttribute('aria-current', 'page');
    }

    li.appendChild(link);

    // Drill button for items with children
    if (itemData.children && itemData.children.length > 0) {
      var drill = document.createElement('button');
      drill.className = 'mobile-layer__drill';
      drill.setAttribute('aria-label', 'Open ' + itemData.title + ' menu');
      drill.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
          '<path fill="none" stroke="currentColor" stroke-width="2.5" d="M9 6l6 6-6 6"/>' +
        '</svg>';

      drill.addEventListener('click', function (event) {
        event.stopPropagation();
        pushLayer(itemData.title, itemData.children, link, itemData.layerDescription || '');
      });

      li.appendChild(drill);
    }

    return li;
  }

  /**
   * Build a complete layer <div> from a list of items and a title.
   * An optional description is rendered as an intro block above
   * the item list (useful for mega-menu parent descriptions).
   */
  function buildLayer(title, items, layerDescription) {
    var parts = createLayerStructure(title);
    var list = parts.list;

    // Layer-level description (e.g. from .mega-menu__title-description)
    if (layerDescription) {
      var intro = document.createElement('div');
      intro.className = 'mobile-layer__intro';
      intro.textContent = layerDescription;
      parts.layer.insertBefore(intro, list);
    }

    items.forEach(function (itemData) {
      var li = buildItemElement(itemData, title);
      list.appendChild(li);
    });

    return parts.layer;
  }

  // ── Layer navigation ──────────────────────────────────────

  function pushLayer(title, items, activatingLink, layerDescription) {
    // Depth-label items
    items.forEach(function (item) {
      item.depth = layerStack.length + 1;
    });

    var layerEl = buildLayer(title, items, layerDescription || '');

    // Back button — present on all layers except the root
    if (layerStack.length > 0) {
      var backBtn = document.createElement('button');
      backBtn.className = 'mobile-layer__back';
      backBtn.setAttribute('aria-label', 'Back to ' + layerStack[layerStack.length - 1].title);
      backBtn.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
          '<path fill="none" stroke="currentColor" stroke-width="2.5" d="M15 18l-6-6 6-6"/>' +
        '</svg> Back';

      backBtn.addEventListener('click', function () {
        popLayer();
      });

      layerEl.querySelector('.mobile-layer__header').insertBefore(
        backBtn,
        layerEl.querySelector('.mobile-layer__header').firstChild
      );
    }

    layersContainer.appendChild(layerEl);

    // Hide previous layer, show new one
    if (layerStack.length > 0) {
      var prev = layerStack[layerStack.length - 1];
      prev.element.classList.remove('is-active');
    }

    layerEl.classList.add('is-active');

    layerStack.push({
      element: layerEl,
      title: title,
      activatingLink: activatingLink
    });

    // Focus the first focusable element in the new layer
    var first = layerEl.querySelector('a, button');
    if (first) {
      first.focus();
    }

    announce('Öppnade ' + title);
  }

  function popLayer() {
    if (layerStack.length <= 1) return;

    var current = layerStack.pop();
    var parent = layerStack[layerStack.length - 1];

    // Hide current layer and show parent
    current.element.classList.remove('is-active');
    if (current.element.parentNode) {
      current.element.parentNode.removeChild(current.element);
    }
    parent.element.classList.add('is-active');

    // Return focus to the element that was clicked to enter this layer.
    // If we're at the root (no activatingLink), focus the first link
    // in the restored parent layer.
    if (current.activatingLink) {
      current.activatingLink.focus();
    } else if (parent.element) {
      var firstFocusable = parent.element.querySelector('a, button');
      if (firstFocusable) firstFocusable.focus();
    }

    announce('Tillbaka till ' + parent.title);
  }

  // ── Root layer ────────────────────────────────────────────

  function getRootItems() {
    var rootUl = nav.querySelector(':scope > ul');
    if (!rootUl) return [];
    return extractItemsFromList(rootUl);
  }

  function buildRootLayer() {
    var items = getRootItems();
    pushLayer('Menu', items, null);
  }

  function closeAllLayers() {
    if (layersContainer) {
      layersContainer.innerHTML = '';
    }
    layerStack = [];
  }

  // ── Open / Close / Toggle ─────────────────────────────────

  function openMenu() {
    toggle.setAttribute('aria-expanded', 'true');
    nav.classList.add('is-open');
    document.body.classList.add('menu-is-open');

    if (!layersContainer) {
      layersContainer = document.createElement('div');
      layersContainer.className = 'mobile-layers';
      nav.appendChild(layersContainer);
      createLiveRegion();
    }

    closeAllLayers();
    buildRootLayer();

    var labelEl = getLabelEl();
    if (labelEl) {
      labelEl.textContent = 'Close';
    }
  }

  function closeMenu() {
    toggle.setAttribute('aria-expanded', 'false');
    nav.classList.remove('is-open');
    document.body.classList.remove('menu-is-open');
    toggle.focus();

    closeAllLayers();

    // Collapse any desktop submenus that might have been opened via app.js
    var openSubmenus = nav.querySelectorAll('button[aria-controls][aria-expanded="true"]');
    openSubmenus.forEach(function (btn) {
      btn.setAttribute('aria-expanded', 'false');
    });

    var labelEl = getLabelEl();
    if (labelEl) {
      labelEl.textContent = 'Menu';
    }
  }

  function isOpen() {
    return toggle.getAttribute('aria-expanded') === 'true';
  }

  function toggleMenu() {
    if (isOpen()) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  // ── Event listeners ──────────────────────────────────────

  toggle.addEventListener('click', toggleMenu);

  // Escape: go back one layer, or close if at root
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape' || !isOpen()) return;
    event.stopPropagation();

    if (layerStack.length > 1) {
      popLayer();
    } else {
      closeMenu();
    }
  });

  // Close when tapping outside
  document.addEventListener('click', function (event) {
    if (!isOpen()) return;
    if (!nav.contains(event.target) && !toggle.contains(event.target)) {
      closeMenu();
    }
  });

  // Navigate a link: close menu; handle same-page anchors
  nav.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link || !isOpen()) return;

    var href = link.getAttribute('href');

    if (href && href.charAt(0) === '#') {
      event.preventDefault();
      var target = document.getElementById(href.substring(1));
      closeMenu();
      if (target) {
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({ behavior: prefersReduced ? 'auto' : 'smooth' });
      }
    } else {
      closeMenu();
    }
  });

  // Focus trap: keep Tab cycling within the current active layer
  nav.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab' || !isOpen()) return;

    var activeLayer = layerStack.length > 0
      ? layerStack[layerStack.length - 1].element
      : null;

    if (!activeLayer) return;

    var focusable = Array.prototype.slice.call(
      activeLayer.querySelectorAll(
        'a:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"]):not([disabled])'
      )
    ).filter(function (el) {
      return el.offsetParent !== null;
    });

    if (focusable.length === 0) return;

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey) {
      if (document.activeElement === first) {
        event.preventDefault();
        last.focus();
      }
    } else {
      if (document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });
};

document.addEventListener('DOMContentLoaded', mobileMenu);
