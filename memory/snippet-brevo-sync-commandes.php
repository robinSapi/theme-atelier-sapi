<?php
/**
 * Snippet : Syncs Brevo unifiées déclenchées par une commande
 * Nom Code Snippets : sapi-brevo-sync-commandes
 *
 * Regroupe TOUTES les syncs Brevo liées à une commande, sorties de
 * functions.php pour être maintenues au même endroit, hors thème.
 *
 * GÈRE 2 LISTES :
 *  • #6  "Newsletter"        → ajout immédiat SI le client a coché l'opt-in
 *                              au checkout (case RGPD) ou au retry paiement,
 *                              PUIS re-sync retardée de 5 min qui lève le
 *                              blocklist posé entre-temps par Brevo.
 *  • #12 "Commande récente"  → ajout RETARDÉ de 5 min, systématique (file
 *                              d'attente de l'automation post-achat).
 *
 * POURQUOI TOUT EST RETARDÉ :
 *  Le plugin "Brevo for WooCommerce" (toujours activé pour la sync e-commerce)
 *  fait partir un événement `order_completed` depuis le tracker JS. Le serveur
 *  Brevo applique alors sa propre logique de sync contacts, ~1 min après la
 *  commande, et considère l'acheteur comme non-abonné : il le retire de #12,
 *  l'ajoute à #11 (NonSubscribers) et le passe en BLOCKLIST. Il ne voit pas
 *  notre case opt-in custom (le champ opt-in du plugin est décoché).
 *  Tout ajout immédiat est donc écrasé. On repasse APRÈS, via WP-Cron.
 *
 *  SÉQUENCE #12 :
 *    T+0s  : commande passée → on programme l'ajout (rien d'ajouté encore)
 *    T+1m  : sync serveur Brevo (sans effet, le contact n'est pas en #12)
 *    T+5m  : ce snippet ajoute à #12 → persiste ✅
 *
 *  SÉQUENCE #6 (corrigée le 28/08/2026 — cas Olivier Leibovici, cmd #10147) :
 *    T+0s  : opt-in coché → ajout immédiat à #6 (filet : si le tracker JS ne
 *            part pas, adblock par exemple, le contact est déjà correct)
 *    T+1m  : sync serveur Brevo → BLOCKLIST du contact (il reste dans #6 mais
 *            plus aucune campagne ne lui parvient)
 *    T+5m  : ce snippet repasse sur #6 avec emailBlacklisted:false → ✅
 *
 * INSTALLATION : Code Snippets → Ajouter → coller CE fichier SANS la ligne
 *   <?php du haut → portée "Exécuter partout" → Activer.
 *
 * DÉPEND DE : constante BREVO_API_KEY dans wp-config.php.
 * AUTONOME  : aucune dépendance à functions.php pour les syncs Brevo.
 *   (functions.php conserve seulement la sauvegarde de la meta opt-in et de
 *    la note client sur la page order-pay — hook before_pay_action prio 10.)
 *
 * ⚠️ #12 utilise WP-Cron (pseudo-cron déclenché par visite). Sur un site à
 *   faible trafic la nuit, le délai réel peut dépasser 5 min — sans impact
 *   fonctionnel.
 */

defined('ABSPATH') || exit;

/* ════════════════════════════════════════════════════════════════════
 * SECTION 1 — Checkout : champ opt-in newsletter (#6) + sauvegarde meta
 * ════════════════════════════════════════════════════════════════════ */

add_action('woocommerce_init', function () {
    if (!function_exists('woocommerce_register_additional_checkout_field')) return;

    woocommerce_register_additional_checkout_field([
        'id'       => 'sapi-maison/newsletter-optin',
        'label'    => 'Je souhaite recevoir des nouvelles de l\'atelier et de jolies idées pour m\'inspirer',
        'location' => 'order',
        'type'     => 'checkbox',
        'default'  => false,
    ]);
});

// Sauvegarde du choix opt-in comme meta de commande.
add_action('woocommerce_set_additional_field_value', function ($key, $value, $group, $wc_object) {
    if ($key !== 'sapi-maison/newsletter-optin') return;
    if (!($wc_object instanceof WC_Order)) return;
    $wc_object->update_meta_data('_sapi_newsletter_optin', wc_bool_to_string($value));
}, 10, 4);

/* ════════════════════════════════════════════════════════════════════
 * SECTION 2 — Hooks commande : scheduler #12 (toujours) + sync #6 (si opt-in)
 * ════════════════════════════════════════════════════════════════════ */

if (!function_exists('sapi_brevo_schedule_list12')) {
    function sapi_brevo_schedule_list12($order_id) {
        if (!wp_next_scheduled('sapi_brevo_delayed_list12_sync', [$order_id])) {
            wp_schedule_single_event(time() + 300, 'sapi_brevo_delayed_list12_sync', [$order_id]);
            error_log('[sapi-brevo-delayed-list12] Programmé réajout #12 dans 5 min pour commande #' . $order_id);
        }
    }
}

/**
 * Programme la re-sync #6 qui lève le blocklist posé par la sync serveur Brevo.
 * Ne programme QUE si le client a explicitement coché l'opt-in : sans acte
 * positif, on ne touche jamais au statut d'abonnement (RGPD).
 */
if (!function_exists('sapi_brevo_schedule_newsletter_recheck')) {
    function sapi_brevo_schedule_newsletter_recheck($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        if ($order->get_meta('_sapi_newsletter_optin') !== 'yes') return;

        if (!wp_next_scheduled('sapi_brevo_delayed_newsletter_sync', [$order_id])) {
            wp_schedule_single_event(time() + 330, 'sapi_brevo_delayed_newsletter_sync', [$order_id]);
            error_log('[sapi-brevo-newsletter-recheck] Programmé déblocage #6 dans 5 min 30 pour commande #' . $order_id);
        }
    }
}

// Checkout Blocks (Store API) — hook principal sur ce site.
add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
    if (!($order instanceof WC_Order)) return;
    $order_id = $order->get_id();
    sapi_brevo_schedule_list12($order_id);            // #12 : toujours
    sapi_brevo_sync_newsletter($order_id);            // #6  : si opt-in coché
    sapi_brevo_schedule_newsletter_recheck($order_id); // #6  : déblocage retardé
}, 25, 1);

// Checkout classique (fallback / compat).
add_action('woocommerce_checkout_order_processed', function ($order_id) {
    sapi_brevo_schedule_list12($order_id);
    sapi_brevo_sync_newsletter($order_id);
    sapi_brevo_schedule_newsletter_recheck($order_id);
}, 25, 1);

/* ════════════════════════════════════════════════════════════════════
 * SECTION 3 — Order-pay : sync #6 au retry paiement
 * Priorité 25 → s'exécute APRÈS le handler functions.php (prio 10) qui a
 * sauvegardé la meta _sapi_newsletter_optin.
 * ════════════════════════════════════════════════════════════════════ */

add_action('woocommerce_before_pay_action', function ($order) {
    if ($order instanceof WC_Order) {
        sapi_brevo_sync_newsletter($order->get_id());
        sapi_brevo_schedule_newsletter_recheck($order->get_id());
    }
}, 25, 1);

/* ════════════════════════════════════════════════════════════════════
 * SECTION 4 — Handler WP-Cron : ajout retardé à #12
 * ════════════════════════════════════════════════════════════════════ */

add_action('sapi_brevo_delayed_list12_sync', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log('[sapi-brevo-delayed-list12] Commande #' . $order_id . ' introuvable');
        return;
    }

    $email = $order->get_billing_email();
    if (!$email || !is_email($email)) {
        error_log('[sapi-brevo-delayed-list12] Email invalide pour commande #' . $order_id);
        return;
    }

    if (sapi_brevo_upsert_contact($email, 12, sapi_brevo_order_attributes($order), '[sapi-brevo-delayed-list12]')) {
        error_log('[sapi-brevo-delayed-list12] Ajout retardé à #12 réussi pour commande #' . $order_id . ' (' . $email . ')');
    }
});

/* ════════════════════════════════════════════════════════════════════
 * SECTION 4 bis — Handler WP-Cron : déblocage retardé de #6
 * Repasse sur la liste #6 APRÈS la sync serveur Brevo, en forçant
 * emailBlacklisted:false. Sans ça, un client qui a coché l'opt-in reste
 * dans #6 mais blocklisté, donc aucune campagne ne lui parvient.
 * ════════════════════════════════════════════════════════════════════ */

add_action('sapi_brevo_delayed_newsletter_sync', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log('[sapi-brevo-newsletter-recheck] Commande #' . $order_id . ' introuvable');
        return;
    }

    // Double garde : on ne déblocklist JAMAIS sans opt-in explicite.
    if ($order->get_meta('_sapi_newsletter_optin') !== 'yes') {
        error_log('[sapi-brevo-newsletter-recheck] Pas d\'opt-in sur commande #' . $order_id . ', abandon');
        return;
    }

    $email = $order->get_billing_email();
    if (!$email || !is_email($email)) {
        error_log('[sapi-brevo-newsletter-recheck] Email invalide pour commande #' . $order_id);
        return;
    }

    $attrs = sapi_brevo_order_attributes($order);
    $attrs['SOURCE'] = 'checkout';

    if (sapi_brevo_upsert_contact($email, 6, $attrs, '[sapi-brevo-newsletter-recheck]', false)) {
        $order->update_meta_data('_sapi_newsletter_brevo_unblocked', 'yes');
        $order->save();
        error_log('[sapi-brevo-newsletter-recheck] Déblocage #6 OK pour commande #' . $order_id . ' (' . $email . ')');
    }
});

/* ════════════════════════════════════════════════════════════════════
 * SECTION 5 — Sync newsletter #6 (opt-in checkout / retry)
 * ════════════════════════════════════════════════════════════════════ */

if (!function_exists('sapi_brevo_sync_newsletter')) {
    function sapi_brevo_sync_newsletter($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        if ($order->get_meta('_sapi_newsletter_optin') !== 'yes') return;        // pas d'opt-in
        if ($order->get_meta('_sapi_newsletter_brevo_synced') === 'yes') return; // idempotence

        $email = $order->get_billing_email();
        if (!$email || !is_email($email)) return;

        $attrs = sapi_brevo_order_attributes($order);
        $attrs['SOURCE'] = 'checkout';

        if (sapi_brevo_upsert_contact($email, 6, $attrs, '[sapi-brevo-newsletter]')) {
            $order->update_meta_data('_sapi_newsletter_brevo_synced', 'yes');
            $order->save();
            error_log('[sapi-brevo-newsletter] Opt-in #6 synchronisé pour commande #' . $order_id . ' (' . $email . ')');
        }
    }
}

/* ════════════════════════════════════════════════════════════════════
 * SECTION 6 — Helpers communs (POST Brevo + attributs commande)
 * ════════════════════════════════════════════════════════════════════ */

if (!function_exists('sapi_brevo_order_attributes')) {
    function sapi_brevo_order_attributes($order) {
        $attributes = [];
        $firstname = $order->get_billing_first_name();
        $lastname  = $order->get_billing_last_name();
        if ($firstname) $attributes['PRENOM'] = $firstname;
        if ($lastname)  $attributes['NOM']    = $lastname;
        return $attributes;
    }
}

if (!function_exists('sapi_brevo_upsert_contact')) {
    /**
     * Upsert d'un contact Brevo dans une liste donnée.
     *
     * @param bool|null $blacklisted null = on ne touche pas au statut (défaut),
     *                               false = on force la réinscription (opt-in
     *                               explicite uniquement).
     * @return bool true si Brevo a répondu 2xx.
     */
    function sapi_brevo_upsert_contact($email, $list_id, $attributes, $log_prefix, $blacklisted = null) {
        $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
        if (!$api_key) {
            error_log($log_prefix . ' BREVO_API_KEY manquante (liste #' . $list_id . ', ' . $email . ')');
            return false;
        }

        $payload = [
            'email'         => $email,
            'listIds'       => [(int) $list_id],
            'updateEnabled' => true,
        ];
        if (!empty($attributes)) {
            $payload['attributes'] = $attributes;
        }
        if ($blacklisted !== null) {
            $payload['emailBlacklisted'] = (bool) $blacklisted;
        }

        $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
            'timeout' => 10,
            'headers' => [
                'accept'       => 'application/json',
                'content-type' => 'application/json',
                'api-key'      => $api_key,
            ],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            error_log($log_prefix . ' Erreur HTTP (liste #' . $list_id . ') : ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            return true;
        }

        error_log($log_prefix . ' Brevo a répondu ' . $code . ' (liste #' . $list_id . ') : ' . wp_remote_retrieve_body($response));
        return false;
    }
}
