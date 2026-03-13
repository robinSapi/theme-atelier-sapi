/**
 * Guide Personalize — Swap product card images based on guide preferences
 *
 * Reads sapiGuidePrefs from localStorage (set by mon-projet.js)
 * and replaces default product thumbnails with the preferred essence image.
 */
(function () {
  var raw = localStorage.getItem('sapiGuidePrefs');
  if (!raw) return;

  var prefs;
  try { prefs = JSON.parse(raw); } catch (e) { return; }
  if (!prefs || !prefs.essence) return;

  var essence = prefs.essence; // 'peuplier' or 'okoume'

  function swapCard(card) {
    var data = card.getAttribute('data-variation-imgs');
    if (!data) return;

    try {
      var imgs = JSON.parse(data);
      if (!imgs[essence]) return;

      var mainImg = card.querySelector('.product-image-main img');
      if (!mainImg) return;

      mainImg.src = imgs[essence];
      mainImg.srcset = ''; // OBLIGATOIRE — évite que le navigateur garde l'ancien srcset
    } catch (e) { /* silently skip */ }
  }

  function swapAll() {
    var cards = document.querySelectorAll('.product-card-cinetique[data-variation-imgs]');
    for (var i = 0; i < cards.length; i++) {
      swapCard(cards[i]);
    }
  }

  // Initial swap
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', swapAll);
  } else {
    swapAll();
  }

  // MutationObserver for infinite scroll / dynamically loaded products
  var observer = new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      var nodes = mutations[i].addedNodes;
      for (var j = 0; j < nodes.length; j++) {
        var node = nodes[j];
        if (node.nodeType !== 1) continue;
        if (node.matches && node.matches('.product-card-cinetique[data-variation-imgs]')) {
          swapCard(node);
        }
        if (node.querySelectorAll) {
          var inner = node.querySelectorAll('.product-card-cinetique[data-variation-imgs]');
          for (var k = 0; k < inner.length; k++) {
            swapCard(inner[k]);
          }
        }
      }
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });
})();
