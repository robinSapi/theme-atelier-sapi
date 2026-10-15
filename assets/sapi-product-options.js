/**
 * Options de la fiche produit regroupées dans une seule carte (maquette validée
 * par Robin le 15/10/2026, « variante A ») : une ligne par option, étiquette à
 * gauche, choix à droite. Matériau, taille, add-ons (câble) et ampoule.
 *
 * On ne recrée aucun champ : on DÉPLACE ceux des plugins dans la carte, sans
 * jamais les sortir de form.cart.
 *   - table.variations reste une table .variations entière (WooCommerce cherche
 *     `.variations select`, Variation Swatches ses `ul` dedans) ;
 *   - les add-ons (.wc-pao-addon) gardent leurs classes et leurs champs, Product
 *     Add-Ons les retrouve dans le formulaire ; leur sous-total reste à sa place ;
 *   - la ligne ampoule vient de inc/sapi-ampoule-optin.php.
 * La classe .sapi-opts-ready sur le formulaire active le nouveau CSS. Si ce
 * script ne tourne pas, la fiche garde son ancienne présentation, entière.
 *
 * Pastille : orange tant que l'option n'a pas de valeur, verte dès qu'elle en a
 * une (classe .is-done sur la ligne). L'ampoule reste orange tant qu'elle n'est
 * pas cochée.
 *
 * Câble : si un add-on liste déroulante obligatoire est vide et propose « Noir »,
 * « Noir » est sélectionné d'office (demande de Robin).
 */
(function ($) {
  'use strict';

  var form = document.querySelector('.product-form-v2 form.cart');
  if (!form) return;
  var table = form.querySelector('table.variations');
  var addons = form.querySelectorAll('.wc-pao-addon');
  var ampoule = form.querySelector('.sapi-ampoule-addon');
  if (!table && !addons.length && !ampoule) return;

  // Libellés trop longs pour la colonne de gauche (le nom complet reste en title).
  var SHORT_LABELS = {
    'couleur du cable et du pavillon': 'Câble'
  };
  function norm(t) {
    return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[*\s]+/g, ' ').trim();
  }

  // 1. La carte
  var card = document.createElement('div');
  card.className = 'sapi-opts';
  var anchor = table || form.querySelector('.single_variation_wrap') || form.firstElementChild;
  anchor.parentNode.insertBefore(card, anchor);
  if (table) card.appendChild(table);

  addons.forEach(function (addon) {
    var name = addon.querySelector('.wc-pao-addon-name');
    if (name) {
      // Le texte du libellé est un nœud texte à côté de l'astérisque.
      var full = name.textContent.replace(/\*|Obligatoire/g, '').trim();
      var short = SHORT_LABELS[norm(full)];
      if (short) {
        name.setAttribute('title', full);
        Array.prototype.forEach.call(name.childNodes, function (n) {
          if (n.nodeType === 3 && n.nodeValue.trim()) n.nodeValue = short;
        });
      }
    }
    card.appendChild(addon);
  });
  if (ampoule) card.appendChild(ampoule);

  // Nom de l'essence choisie, affiché à côté des pastilles de bois.
  if (table) {
    table.querySelectorAll('ul.image-variable-items-wrapper').forEach(function (ul) {
      var picked = document.createElement('span');
      picked.className = 'sapi-opt-picked';
      ul.parentNode.insertBefore(picked, ul.nextSibling);
    });
  }

  // 2. Câble noir par défaut
  addons.forEach(function (addon) {
    var sel = addon.querySelector('select.wc-pao-addon-field');
    if (!sel || sel.value) return;
    for (var i = 0; i < sel.options.length; i++) {
      if (norm(sel.options[i].text) === 'noir') {
        sel.selectedIndex = i;
        if ($) $(sel).trigger('change'); else sel.dispatchEvent(new Event('change', { bubbles: true }));
        break;
      }
    }
  });

  // 3. Pastilles
  function hasValue(row) {
    var cb = row.querySelector('input[type="checkbox"]');
    if (row.classList.contains('sapi-ampoule-addon')) return !!(cb && cb.checked);
    var fields = row.querySelectorAll('select, input[type="radio"]:checked, input[type="checkbox"]:checked, input[type="text"], input[type="number"], textarea');
    for (var i = 0; i < fields.length; i++) {
      var f = fields[i];
      if (f.type === 'radio' || f.type === 'checkbox') return true;
      if (f.value && f.value !== '') return true;
    }
    return false;
  }
  function refresh() {
    if (table) {
      table.querySelectorAll('tr').forEach(function (tr) {
        var sel = tr.querySelector('select');
        tr.classList.toggle('is-done', !!(sel && sel.value));
        var picked = tr.querySelector('.sapi-opt-picked');
        if (picked) picked.textContent = sel && sel.value ? sel.options[sel.selectedIndex].text : '';
      });
    }
    card.querySelectorAll('.wc-pao-addon, .sapi-ampoule-addon').forEach(function (row) {
      row.classList.toggle('is-done', hasValue(row));
    });
  }
  var t = null;
  function schedule() { clearTimeout(t); t = setTimeout(refresh, 0); }
  form.addEventListener('change', schedule);
  form.addEventListener('click', schedule);
  if ($) $(form).on('woocommerce_variation_has_changed reset_data show_variation hide_variation updated_addons', schedule);

  form.classList.add('sapi-opts-ready');
  refresh();
})(window.jQuery);
