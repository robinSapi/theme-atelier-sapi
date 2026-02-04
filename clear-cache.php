<?php
// Fichier temporaire pour vider le cache OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache vidé!";
} else {
    echo "OPcache non disponible";
}

// Vider le cache WooCommerce
if (function_exists('wc_delete_product_transients')) {
    wc_delete_product_transients();
    echo " + Cache WooCommerce vidé!";
}

echo "\n\nCache vidé à " . date('H:i:s');
