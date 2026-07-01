/**
 * FAQ Accordion Module
 *
 * @package Kim_Branzell
 */

(function() {
	'use strict';

	function initFaqAccordion() {
		var faqSections = document.querySelectorAll('.kb-faq-section');

		if (!faqSections.length) {
			return;
		}

		faqSections.forEach(function(section) {
			var items = section.querySelectorAll('.kb-faq-item');

			items.forEach(function(item) {
				var button = item.querySelector('.kb-faq-item__question');
				var answer = item.querySelector('.kb-faq-item__answer');

				if (!button || !answer) {
					return;
				}

				// Toggle expand/collapse on click
				button.addEventListener('click', function() {
					var isExpanded = button.getAttribute('aria-expanded') === 'true';

					if (isExpanded) {
						// Collapse this item
						button.setAttribute('aria-expanded', 'false');
						answer.hidden = true;
						item.setAttribute('data-expanded', 'false');
					} else {
						// Collapse all other items first
						items.forEach(function(otherItem) {
							if (otherItem === item) {
								return;
							}
							var otherButton = otherItem.querySelector('.kb-faq-item__question');
							var otherAnswer = otherItem.querySelector('.kb-faq-item__answer');
							if (otherButton && otherAnswer) {
								otherButton.setAttribute('aria-expanded', 'false');
								otherAnswer.hidden = true;
								otherItem.setAttribute('data-expanded', 'false');
							}
						});

						// Expand this item
						button.setAttribute('aria-expanded', 'true');
						answer.hidden = false;
						item.setAttribute('data-expanded', 'true');
					}
				});

				// Keyboard navigation
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
						default:
							return;
					}

					if (targetIndex !== null) {
						var targetButton = items[targetIndex].querySelector('.kb-faq-item__question');
						if (targetButton) {
							targetButton.focus();
						}
					}
				});
			});
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initFaqAccordion);
	} else {
		initFaqAccordion();
	}
})();
