import '@scss/app.scss';

// ── Prevent flash of incorrect theme: apply saved preference before any CSS paints. This runs synchronously before wp_head() output. ──────────
(function(){var t;try{t=localStorage.getItem('kb-theme-preference')}catch(e){}if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t)}})();


// ── FAQ Accordion (self-initializing module) ───────────────
import './modules/faq.js';

// ── Theme switcher ─────────────────────────────────────────
// Three-option selector: Light / System (follow OS) / Dark.
// Progressive enhancement: inert without JS — theme follows OS
// via CSS. With JS, the user can pick any option and the choice
// persists in localStorage.

var STORAGE_KEY = 'kb-theme-preference';

/**
 * Get the user's saved theme preference.
 * @returns {'dark'|'light'|null} — null means auto / follow OS
 */
function getSavedTheme() {
  try {
    var stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') return stored;
  } catch (_) { /* localStorage unavailable */ }
  return null;
}

/**
 * Save or clear theme preference.
 * @param {'dark'|'light'|null} theme — null removes the key
 */
function saveTheme(theme) {
  try {
    if (theme === 'dark' || theme === 'light') {
      localStorage.setItem(STORAGE_KEY, theme);
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  } catch (_) { /* localStorage unavailable */ }
}

/**
 * Detect the OS-level colour scheme.
 * @returns {'dark'|'light'}
 */
function getOSPreference() {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Apply a theme: set data-theme attribute on <html>,
 * persist to localStorage, and update the switcher UI.
 * @param {'dark'|'light'|'auto'} mode
 */
function selectTheme(mode) {
  var html = document.documentElement;

  if (mode === 'dark') {
    html.setAttribute('data-theme', 'dark');
    saveTheme('dark');
  } else if (mode === 'light') {
    html.setAttribute('data-theme', 'light');
    saveTheme('light');
  } else {
    // auto — remove override, CSS handles via prefers-color-scheme
    html.removeAttribute('data-theme');
    saveTheme(null);
  }

  updateSwitcher(mode);
}

/**
 * Mark the active option in the switcher and update aria-checked.
 * @param {'dark'|'light'|'auto'} mode
 */
function updateSwitcher(mode) {
  var group = document.querySelector('.theme-switcher');
  if (!group) return;

  var options = group.querySelectorAll('.theme-switcher__option');
  options.forEach(function (opt) {
    var isActive = opt.getAttribute('data-value') === mode;
    opt.setAttribute('aria-checked', isActive ? 'true' : 'false');
  });

  // Embed OS context into the System option's accessible name.
  // This tells screen reader users what "System" resolves to
  // (e.g. "System (dark mode)") without needing a separate live region.
  var autoOpt = group.querySelector('[data-value="auto"]');
  if (autoOpt) {
    var os = getOSPreference();
    autoOpt.setAttribute('aria-label', 'System (' + os + ' mode)');
  }

  // Sync tabindex — only the active option is reachable via Tab.
  options.forEach(function (opt) {
    opt.setAttribute('tabindex', opt.getAttribute('aria-checked') === 'true' ? '0' : '-1');
  });
}

/**
 * Handle option click.
 */
function handleSwitcherClick() {
  var value = this.getAttribute('data-value');
  selectTheme(value);
}

/**
 * Handle keyboard navigation within the radio group.
 */
function handleSwitcherKeydown(event) {
  var group = document.querySelector('.theme-switcher');
  if (!group) return;

  var options = Array.prototype.slice.call(group.querySelectorAll('.theme-switcher__option'));
  var currentIndex = options.indexOf(this);

  // Space — select the focused option.
  if (event.key === ' ') {
    event.preventDefault();
    var value = this.getAttribute('data-value');
    selectTheme(value);
    return;
  }

  // Arrow keys — move focus and selection.
  var dir;
  if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
    dir = 1;
  } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
    dir = -1;
  } else {
    return;
  }

  event.preventDefault();

  var nextIndex = (currentIndex + dir + options.length) % options.length;
  var nextOption = options[nextIndex];

  // Select and focus the next option.
  var value = nextOption.getAttribute('data-value');
  selectTheme(value);
  nextOption.focus();
}

/**
 * Initialise the theme switcher.
 */
function bootThemeSwitcher() {
  var group = document.querySelector('.theme-switcher');
  if (!group) return;

  // Mark that JS is active.
  document.documentElement.classList.add('js');

  var options = group.querySelectorAll('.theme-switcher__option');

  // Restore saved preference or default to auto.
  var saved = getSavedTheme();
  var initial = saved || 'auto';
  selectTheme(initial);

  // Attach event listeners.
  options.forEach(function (opt) {
    opt.addEventListener('click', handleSwitcherClick);
    opt.addEventListener('keydown', handleSwitcherKeydown);
  });
}

// ── Desktop sub-menu disclosure ───────────────────────────

const boot = () => {
var headerNav = document.getElementById("HeaderNav");
if ( ! headerNav ) {
  return;
}
var buttons = Array.prototype.slice.call(headerNav.querySelectorAll('button[aria-controls]'));
var subMenus = Array.prototype.slice.call(headerNav.querySelectorAll('button[aria-controls] + ul, button[aria-controls] + div'));

// Flag to suppress focusout handling during programmatic focus moves
// inside openSubNav. Prevents the submenu from closing when
// firstFocusable.focus() triggers a synchronous focusout event.
var openingSubNav = false;

function openSubNav(buttonEl) {
  var navId = buttonEl.getAttribute('aria-controls');
  var navEl = document.getElementById(navId);

  if (navEl) {
    buttonEl.setAttribute('aria-expanded', 'true');

    // Move focus into the submenu so keyboard users don't have
    // to tab through the rest of the top-level items first.
    var firstFocusable = navEl.querySelector('a, button');
    if (firstFocusable) {
      openingSubNav = true;
      firstFocusable.focus();
      openingSubNav = false;
    }
  }
}

function closeSubNav(buttonEl) {
  var navId = buttonEl.getAttribute('aria-controls');
  var navEl = document.getElementById(navId);

  if (navEl) {
    buttonEl.setAttribute('aria-expanded', 'false');
  }
}

function closeAllSubNavs() {
  buttons.forEach(function(button) {
    closeSubNav(button);
  });
}

// event handlers
function handleButtonClick(event) {
  var button = this;
  var isOpen = button.getAttribute('aria-expanded') === 'true';
  if (isOpen) {
    closeSubNav(button);
    // Return focus to the toggle since the user clicked to close.
    button.focus();
  } else {
    closeAllSubNavs();
    openSubNav(button);
  }
}

function handleButtonKeyDown(event) {
  if (event.key === 'Escape' || event.key === 'Esc') {
    closeSubNav(this);
    // Focus stays on the toggle button — it already has it.
  }
}

function handleNavKeyDown(event) {
  if (event.key === 'Escape' || event.key === 'Esc') {
    var button = document.querySelector('button[aria-controls=' + this.id + ']');
    closeSubNav(button);
    // Return focus to the toggle when closing from within the submenu.
    button.focus();
  }
}

function handleNavFocusOut(event) {
  // Suppress during programmatic focus moves inside openSubNav
  // to avoid a race where the submenu closes immediately after opening.
  if (openingSubNav) return;

  // Guard against browsers that don't set relatedTarget on focusout.
  // When relatedTarget is null, focus may have moved to a non-focusable
  // element or the document itself — we should not close in that case.
  if (!event.relatedTarget) return;

  var navContainsFocus = this.contains(event.relatedTarget);
  if (!navContainsFocus) {
    var button = this.querySelector('button[aria-controls]');
    // Close the submenu but do NOT steal focus — the user is
    // intentionally tabbing away to the next element.
    button && closeSubNav(button);
  }
}

// attach event listeners
buttons.forEach(function(button) {
  button.addEventListener('click', handleButtonClick);
  button.addEventListener('keydown', handleButtonKeyDown);
});

subMenus.forEach(function(subMenu) {
  subMenu.addEventListener('keydown', handleNavKeyDown);
});

// attach focusout listener to the parent of both the disclosure button and the menu
var subNavContainers = Array.prototype.slice.call(document.querySelectorAll('#HeaderNav > ul > li'));
subNavContainers.forEach(function(navContainer) {
  navContainer.addEventListener('focusout', handleNavFocusOut);
});
};

document.addEventListener('DOMContentLoaded', function () {
  boot();
  bootThemeSwitcher();
});
