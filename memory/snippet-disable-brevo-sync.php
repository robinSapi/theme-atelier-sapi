<?php
/**
 * Snippet : Désactiver la sync contacts du plugin Brevo WC
 *
 * PROBLÈME : Le plugin "Brevo - WooCommerce Email Marketing" (v4.0.53)
 * sync les contacts à chaque commande. Quand "Import as subscribers" est OFF,
 * il BLOCKLISTE les contacts et les retire des listes custom (#12, #6...).
 * Ça casse notre architecture custom (hook → liste #12 → automation → #7).
 *
 * SOLUTION : On retire chirurgicalement les hooks de sync contact du plugin,
 * tout en gardant intacts : tracking panier abandonné, sync produits/commandes.
 *
 * INSTALLATION : Code Snippets → Ajouter → Coller → "Exécuter partout"
 *
 * SI LE PLUGIN BREVO EST MIS À JOUR : vérifier que les noms de méthodes
 * n'ont pas changé. Chercher dans api-manager.php::add_hooks().
 *
 * @see memory/project_brevo_architecture.md
 */

defined('ABSPATH') || exit;

/**
 * Retire un callback d'un hook WordPress en cherchant par nom de classe et méthode.
 * Nécessaire car le plugin Brevo crée des instances locales dans add_hooks(),
 * donc on n'a pas de référence directe à l'objet.
 */
function sapi_remove_hook_by_class_method($hook_name, $class_name, $method_name, $is_filter = false) {
    global $wp_filter;

    if (!isset($wp_filter[$hook_name])) {
        return false;
    }

    $removed = false;

    foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $key => $callback) {
            if (
                is_array($callback['function']) &&
                is_object($callback['function'][0]) &&
                get_class($callback['function'][0]) === $class_name &&
                $callback['function'][1] === $method_name
            ) {
                unset($wp_filter[$hook_name]->callbacks[$priority][$key]);
                $removed = true;
            }
        }
    }

    return $removed;
}

/**
 * Hook sur plugins_loaded (priorité tardive) pour s'assurer que le plugin Brevo
 * a déjà enregistré ses hooks via add_hooks().
 *
 * Le plugin Brevo enregistre ses hooks dans :
 * - woocommerce-sendinblue.php (fichier principal, hook plugins_loaded priorité 10)
 *   → appelle ApiManager::add_hooks() et ApiManager::add_conditional_hooks()
 */
add_action('plugins_loaded', function () {

    // Namespace complet des classes du plugin Brevo WC
    $cart_class = 'SendinblueWoocommerce\\Managers\\CartEventsManagers';
    $api_class  = 'SendinblueWoocommerce\\Managers\\ApiManager';

    // ─── HOOKS À RETIRER ──────────────────────────────────────────

    // 1. woocommerce_thankyou → ws_checkout_completed
    //    C'est le hook principal qui sync le contact à Brevo au checkout.
    //    Il crée/met à jour le contact avec le mauvais statut (blocklisted).
    sapi_remove_hook_by_class_method(
        'woocommerce_thankyou',
        $cart_class,
        'ws_checkout_completed'
    );

    // 2. wp_ajax_nopriv_the_ajax_hook → save_anonymous_user_as_blacklisted
    //    Blockliste explicitement les utilisateurs anonymes.
    sapi_remove_hook_by_class_method(
        'wp_ajax_nopriv_the_ajax_hook',
        $cart_class,
        'save_anonymous_user_as_blacklisted'
    );

    // 3. woocommerce_created_customer → on_new_customer_creation
    //    Crée le contact dans Brevo à la création d'un compte WP.
    //    On désactive car on gère la création via nos propres hooks.
    sapi_remove_hook_by_class_method(
        'woocommerce_created_customer',
        $api_class,
        'on_new_customer_creation'
    );

    // 4. woocommerce_checkout_update_order_meta → add_optin_order
    //    Le plugin traite son propre champ opt-in au checkout.
    //    Robin a son propre champ opt-in (sapi-maison/newsletter-optin).
    //    On retire pour éviter tout conflit.
    sapi_remove_hook_by_class_method(
        'woocommerce_checkout_update_order_meta',
        $cart_class,
        'add_optin_order'
    );

    // 5. woocommerce_checkout_after_terms_and_conditions → add_optin_terms
    //    Affiche la case opt-in du plugin au checkout (position "terms").
    //    Robin a sa propre case → on cache celle du plugin.
    sapi_remove_hook_by_class_method(
        'woocommerce_checkout_after_terms_and_conditions',
        $cart_class,
        'add_optin_terms'
    );

    // 6. woocommerce_checkout_fields → add_optin_billing
    //    Affiche la case opt-in du plugin au checkout (position "billing").
    sapi_remove_hook_by_class_method(
        'woocommerce_checkout_fields',
        $cart_class,
        'add_optin_billing',
        true // c'est un filter, pas une action
    );

    // ─── HOOKS CONSERVÉS (ne pas toucher) ─────────────────────────
    //
    // Cart tracking / paniers abandonnés :
    //   wp_login → wp_login_action
    //   wp_footer → ws_cart_custom_fragment_load
    //   woocommerce_add_to_cart_fragments → ws_cart_custom_fragment
    //   wp_ajax_the_ajax_hook → the_action_function
    //   woocommerce_update_cart_action_cart_updated → handle_cart_update_event
    //   woocommerce_add_to_cart → handle_cart_update_event
    //   woocommerce_cart_item_removed → handle_cart_update_event
    //
    // Product & category sync :
    //   save_post_product → product_events
    //   before_delete_post → product_deleted
    //   created_term / edit_term / delete_term → category events
    //   woocommerce_product_set_stock_status, etc.
    //
    // Order sync (e-commerce Brevo) :
    //   woocommerce_order_status_changed → order_events (OrdersManager)
    //   woocommerce_new_order → order_created
    //   woocommerce_order_refunded → order_created
    //
    // Order status sync (ApiManager) :
    //   woocommerce_order_status_changed → on_order_status_changed
    //   woocommerce_order_status_refunded → on_order_status_refunded
    //   woocommerce_order_note_added → on_new_customer_note

}, 20); // priorité 20 = après le plugin Brevo (priorité 10)
