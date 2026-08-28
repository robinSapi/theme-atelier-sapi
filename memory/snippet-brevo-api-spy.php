<?php
/**
 * Snippet TEMPORAIRE : Espion API Brevo
 *
 * Intercepte TOUTES les requêtes HTTP sortantes vers l'API Brevo
 * et les enregistre dans un fichier log lisible.
 *
 * INSTALLATION : Code Snippets → Ajouter → Coller → "Exécuter partout"
 * NOM : sapi-brevo-api-spy
 *
 * LECTURE DU LOG : aller sur /wp-admin/ puis ajouter ?sapi-brevo-log=1 à l'URL
 * Exemple : https://atelier-sapi.fr/wp-admin/?sapi-brevo-log=1
 *
 * ⚠️ SUPPRIMER APRÈS DIAGNOSTIC — ce snippet log des données sensibles (emails, clés API partielles)
 */

defined('ABSPATH') || exit;

// ─── 1. INTERCEPTER LES REQUÊTES SORTANTES VERS BREVO ─────────

add_filter('http_request_args', function ($args, $url) {

    // Ne logger que les requêtes vers l'API Brevo
    if (
        strpos($url, 'api.brevo.com') === false &&
        strpos($url, 'api.sendinblue.com') === false
    ) {
        return $args;
    }

    // Construire l'entrée de log
    $log_entry = [
        'time'       => gmdate('Y-m-d H:i:s') . ' UTC',
        'url'        => $url,
        'method'     => $args['method'] ?? 'GET',
        'body'       => null,
        'backtrace'  => [],
    ];

    // Décoder le body JSON si présent
    if (!empty($args['body'])) {
        $decoded = json_decode($args['body'], true);
        $log_entry['body'] = $decoded ?: $args['body'];
    }

    // Capturer la pile d'appels pour savoir D'OÙ vient la requête
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
    foreach ($backtrace as $frame) {
        $file = isset($frame['file']) ? basename($frame['file']) : '?';
        $line = $frame['line'] ?? '?';
        $class = $frame['class'] ?? '';
        $func = $frame['function'] ?? '';
        $caller = $class ? "{$class}::{$func}" : $func;
        $log_entry['backtrace'][] = "{$caller} ({$file}:{$line})";
    }

    // Sauvegarder dans le log
    $log_file = WP_CONTENT_DIR . '/sapi-brevo-api-spy.log';
    $existing = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) : [];
    if (!is_array($existing)) {
        $existing = [];
    }
    $existing[] = $log_entry;
    file_put_contents($log_file, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $args;
}, 10, 2);

// ─── 2. PAGE DE LECTURE DU LOG ─────────────────────────────────

add_action('admin_init', function () {

    if (empty($_GET['sapi-brevo-log'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }

    $log_file = WP_CONTENT_DIR . '/sapi-brevo-api-spy.log';

    // Action : vider le log
    if (isset($_GET['clear'])) {
        if (file_exists($log_file)) {
            unlink($log_file);
        }
        wp_redirect(admin_url('?sapi-brevo-log=1'));
        exit;
    }

    // Lire et afficher
    $entries = [];
    if (file_exists($log_file)) {
        $entries = json_decode(file_get_contents($log_file), true) ?: [];
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Brevo API Spy</title>';
    echo '<style>
        body { font-family: monospace; background: #1e1e2e; color: #cdd6f4; padding: 20px; }
        .entry { background: #313244; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .time { color: #a6adc8; }
        .method { font-weight: bold; }
        .method-POST { color: #fab387; }
        .method-GET { color: #89b4fa; }
        .method-PUT { color: #a6e3a1; }
        .method-DELETE { color: #f38ba8; }
        .url { color: #89dceb; word-break: break-all; }
        .body { background: #1e1e2e; padding: 12px; border-radius: 4px; margin-top: 8px; white-space: pre-wrap; }
        .backtrace { color: #a6adc8; font-size: 0.85em; margin-top: 8px; }
        .backtrace span { color: #f9e2af; }
        h1 { color: #cba6f7; }
        a { color: #89b4fa; }
        .alert { background: #f38ba8; color: #1e1e2e; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .empty { color: #a6adc8; font-style: italic; }
    </style></head><body>';

    echo '<h1>🕵️ Brevo API Spy — ' . count($entries) . ' requête(s)</h1>';
    echo '<p><a href="' . admin_url('?sapi-brevo-log=1&clear=1') . '">Vider le log</a></p>';

    if (empty($entries)) {
        echo '<p class="empty">Aucune requête enregistrée. Passe une commande pour voir les appels API.</p>';
    }

    foreach (array_reverse($entries) as $i => $entry) {
        $method = $entry['method'] ?? 'GET';
        $url = $entry['url'] ?? '';
        $body = $entry['body'] ?? null;

        // Détecter les appels dangereux
        $is_dangerous = false;
        if ($body) {
            $body_str = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
            if (
                strpos($body_str, 'blacklisted') !== false ||
                strpos($body_str, 'unlinkListIds') !== false ||
                strpos($body_str, 'NonSubscribers') !== false
            ) {
                $is_dangerous = true;
            }
        }

        echo '<div class="entry">';
        echo '<span class="time">' . ($entry['time'] ?? '?') . '</span> ';
        echo '<span class="method method-' . $method . '">' . $method . '</span> ';
        echo '<span class="url">' . htmlspecialchars($url) . '</span>';
        if ($is_dangerous) {
            echo ' <span class="alert">⚠️ BLOCKLIST / UNLINK</span>';
        }

        if ($body) {
            $formatted = is_string($body) ? $body : json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo '<div class="body">' . htmlspecialchars($formatted) . '</div>';
        }

        if (!empty($entry['backtrace'])) {
            echo '<div class="backtrace">';
            foreach (array_slice($entry['backtrace'], 0, 8) as $bt) {
                // Mettre en valeur les fichiers du plugin Brevo ou nos snippets
                if (strpos($bt, 'Sendinblue') !== false || strpos($bt, 'sendinblue') !== false || strpos($bt, 'sapi') !== false) {
                    echo '<span>' . htmlspecialchars($bt) . '</span><br>';
                } else {
                    echo htmlspecialchars($bt) . '<br>';
                }
            }
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</body></html>';
    exit;
});
