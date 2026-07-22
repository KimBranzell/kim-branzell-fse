/**
 * Intro Accordion Module
 *
 * Progressive enhancement: all panels are visible server-side.
 * This script collapses panels 2–N on init and adds interactivity.
 *
 * @package Kim_Branzell
 */

(function() {
	'use strict';

	function initIntroAccordion() {
		var sections = document.querySelectorAll('.intro__cards');

		if (!sections.length) {
			return;
		}

		sections.forEach(function(section) {
			// Signal that JS is active — gates CSS animation overrides.
			section.classList.add('js-intro-accordion');

			var items = section.querySelectorAll('.intro__card');

			// Collapse all panels except the first on init.
			items.forEach(function(item, index) {
				var button = item.querySelector('.intro__card-toggle');
				var panel = item.querySelector('.intro__card-panel');

				if (!button || !panel) {
					return;
				}

				if (index !== 0) {
					button.setAttribute('aria-expanded', 'false');
					panel.hidden = true;
					item.setAttribute('data-expanded', 'false');
				}
			});

			// Attach event handlers.
			items.forEach(function(item) {
				var button = item.querySelector('.intro__card-toggle');
				var panel = item.querySelector('.intro__card-panel');

				if (!button || !panel) {
					return;
				}

				// Toggle expand/collapse on click.
				button.addEventListener('click', function() {
					var isExpanded = button.getAttribute('aria-expanded') === 'true';

					if (isExpanded) {
						// Collapse this item.
						button.setAttribute('aria-expanded', 'false');
						panel.hidden = true;
						item.setAttribute('data-expanded', 'false');
					} else {
						// Collapse all other items first.
						items.forEach(function(otherItem) {
							if (otherItem === item) {
								return;
							}
							var otherButton = otherItem.querySelector('.intro__card-toggle');
							var otherPanel = otherItem.querySelector('.intro__card-panel');
							if (otherButton && otherPanel) {
								otherButton.setAttribute('aria-expanded', 'false');
								otherPanel.hidden = true;
								otherItem.setAttribute('data-expanded', 'false');
							}
						});

						// Expand this item.
						button.setAttribute('aria-expanded', 'true');
						panel.hidden = false;
						item.setAttribute('data-expanded', 'true');
					}
				});

				// Keyboard navigation.
				button.addEventListener('keydown', function(event) {
					var currentIndex = Array.from(items).indexOf(item);
					var targetIndex = null;

					switch (event.key) {
						case 'ArrowDown':
							event.preventDefault();
							targetIndex = currentIndex + 1 < items.length ? currentIndex + 1 : 0;
							break;
						case 'ArrowUp':
							event.preventDefault();
							targetIndex = currentIndex - 1 >= 0 ? currentIndex - 1 : items.length - 1;
							break;
						case 'Home':
							event.preventDefault();
							targetIndex = 0;
							break;
						case 'End':
							event.preventDefault();
							targetIndex = items.length - 1;
							break;
						case 'Escape':
							if (button.getAttribute('aria-expanded') === 'true') {
								event.preventDefault();
								button.setAttribute('aria-expanded', 'false');
								panel.hidden = true;
								item.setAttribute('data-expanded', 'false');
							}
							return;
						default:
							return;
					}

					if (targetIndex !== null) {
						var targetButton = items[targetIndex].querySelector('.intro__card-toggle');
						if (targetButton) {
							targetButton.focus();
						}
					}
				});
			});
		});
	}

	// Initialize when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initIntroAccordion);
	} else {
		initIntroAccordion();
	}
})();
