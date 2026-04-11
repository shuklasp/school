/**
 * @file
 * Vshiksha theme behaviors.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.vshikshaAnimation = {
        attach: function (context, settings) {
            // Use core/once to ensure this only runs once per element
            const cards = once('vshiksha-animated-card', '.card', context);

            if (cards.length === 0) {
                return;
            }

            // Add subtle entrance animation to cards as they scroll into view
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Apply to all elements found
            cards.forEach(function (card) {
                card.style.opacity = '0'; // Initial state before animation class is added
                observer.observe(card);
            });
        }
    };

})(Drupal, once);
