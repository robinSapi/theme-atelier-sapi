/**
 * Scroll Dots — Mobile slide indicators
 * Transforme les grilles verticales en sliders horizontaux avec dots sur mobile.
 * Activé uniquement sur mobile (max-width: 768px) via matchMedia.
 */
(function() {
  'use strict';

  var sections = [
    { container: '.collections-grid', child: '.collection-card', snap: 'center' },
    { container: '.advice-tips-grid', child: '.advice-tip', snap: 'center' },
    { container: '.testimonials-grid', child: '.testimonial-card', snap: 'center' },
    { container: '.artisan-values-grid', child: '.artisan-value-item', snap: 'center' },
    { container: '.process-inner', child: '.process-step', snap: 'center' },
    { container: '.surmesure-grid', child: '.surmesure-card', snap: 'center' },
    { container: '.surmesure-steps', child: '.surmesure-step', snap: 'center' },
    { container: '.use-cases-list', child: 'li', snap: 'center' },
    { container: '.guide-result-products-grid', child: '.guide-result-card', snap: 'start' }
  ];

  var instances = [];
  var mql = window.matchMedia('(max-width: 768px)');

  function createDots(container, children, config) {
    if (children.length < 2) return null;

    var dotsEl = document.createElement('div');
    dotsEl.className = 'scroll-dots';
    dotsEl.setAttribute('role', 'tablist');

    var dots = [];
    for (var i = 0; i < children.length; i++) {
      (function(index) {
        var dot = document.createElement('button');
        dot.className = 'scroll-dot' + (index === 0 ? ' active' : '');
        dot.setAttribute('role', 'tab');
        dot.setAttribute('aria-label', 'Slide ' + (index + 1));
        dot.setAttribute('aria-selected', index === 0 ? 'true' : 'false');

        dot.addEventListener('click', function() {
          children[index].scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: config.snap
          });
        });

        dotsEl.appendChild(dot);
        dots.push(dot);
      })(i);
    }

    container.parentNode.insertBefore(dotsEl, container.nextSibling);

    function onScroll() {
      var rect = container.getBoundingClientRect();
      var center = rect.left + rect.width / 2;
      var closest = 0;
      var minDist = Infinity;

      for (var j = 0; j < children.length; j++) {
        var childRect = children[j].getBoundingClientRect();
        var childCenter = childRect.left + childRect.width / 2;
        var dist = Math.abs(childCenter - center);
        if (dist < minDist) {
          minDist = dist;
          closest = j;
        }
      }

      for (var k = 0; k < dots.length; k++) {
        var isActive = k === closest;
        dots[k].classList.toggle('active', isActive);
        dots[k].setAttribute('aria-selected', isActive ? 'true' : 'false');
      }
    }

    container.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    return {
      destroy: function() {
        container.removeEventListener('scroll', onScroll);
        if (dotsEl.parentNode) dotsEl.parentNode.removeChild(dotsEl);
      }
    };
  }

  function activateAll() {
    sections.forEach(function(config) {
      var containers = document.querySelectorAll(config.container);
      containers.forEach(function(container) {
        var children = container.querySelectorAll(config.child);
        if (children.length < 2) return;
        var inst = createDots(container, children, config);
        if (inst) instances.push(inst);
      });
    });
  }

  function destroyAll() {
    instances.forEach(function(inst) { inst.destroy(); });
    instances = [];
  }

  function handleChange(e) {
    if (e.matches) {
      activateAll();
    } else {
      destroyAll();
    }
  }

  function init() {
    if (mql.matches) activateAll();
    mql.addEventListener('change', handleChange);
  }

  // Public API: allow dynamic content to trigger dot creation
  window.scrollDotsRefresh = function() {
    destroyAll();
    if (mql.matches) activateAll();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
