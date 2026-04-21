<?php
/**
 * Snippet: Sapi Cookie Popup — Popup cookies custom + capture email promo
 *
 * Etape 1 : remplace visuellement la banniere Complianz (popup anime dans la
 * charte Atelier Sapi). La logique de consentement reste geree par Complianz.
 *
 * Etape 2 : apres le choix cookies (Accepter ou Refuser), transition vers un
 * ecran promo capturant l'email pour ajouter le contact a la liste Brevo #6
 * et afficher le code BIENVENUE10.
 *
 * A coller dans Code Snippets (frontend only). NE PAS mettre dans functions.php.
 */

// ==================================================================
// 1. AJAX handler : inscription Brevo (liste 6)
// ==================================================================

// Resolution de la cle API Brevo. Ordre :
//   1. Constantes wp-config.php (BREVO_API_KEY, SAPI_BREVO_API_KEY, SIB_API_KEY, SENDINBLUE_API_KEY)
//   2. Option du plugin officiel Brevo (sib_api_key_v3)
//   3. Option serialisee du plugin (mailin_options)
function sapi_get_brevo_api_key() {
	foreach ( [ 'BREVO_API_KEY', 'SAPI_BREVO_API_KEY', 'SIB_API_KEY', 'SENDINBLUE_API_KEY' ] as $const ) {
		if ( defined( $const ) && constant( $const ) ) {
			return constant( $const );
		}
	}
	$key = get_option( 'sib_api_key_v3' );
	if ( ! empty( $key ) ) {
		return $key;
	}
	$options = get_option( 'mailin_options' );
	if ( is_array( $options ) ) {
		foreach ( [ 'api_key_v3', 'api_key', 'access_key', 'apikey' ] as $k ) {
			if ( ! empty( $options[ $k ] ) ) {
				return $options[ $k ];
			}
		}
	}
	return null;
}

add_action( 'wp_ajax_nopriv_sapi_brevo_subscribe', 'sapi_brevo_subscribe' );
add_action( 'wp_ajax_sapi_brevo_subscribe', 'sapi_brevo_subscribe' );
function sapi_brevo_subscribe() {
	if ( ! check_ajax_referer( 'sapi_brevo_nonce', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => 'invalid_nonce' ], 403 );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => 'invalid_email' ], 400 );
	}

	$api_key = sapi_get_brevo_api_key();
	if ( empty( $api_key ) ) {
		wp_send_json_error( [ 'message' => 'no_api_key' ], 500 );
	}

	$response = wp_remote_post( 'https://api.brevo.com/v3/contacts', [
		'timeout' => 10,
		'headers' => [
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		],
		'body'    => wp_json_encode( [
			'email'         => $email,
			'listIds'       => [ 6 ],
			'updateEnabled' => true,
			'attributes'    => [
				'SOURCE' => 'popup',
			],
		] ),
	] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => 'http_error', 'details' => $response->get_error_message() ], 500 );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code >= 200 && $code < 300 ) {
		wp_send_json_success();
	}

	$body = wp_remote_retrieve_body( $response );
	wp_send_json_error( [ 'message' => 'brevo_error', 'code' => $code, 'body' => $body ], $code );
}

// ==================================================================
// 2. Injection frontend (CSS + HTML + JS)
// ==================================================================

if ( is_admin() ) {
	return;
}

add_action( 'wp_footer', function () {

	$nonce    = wp_create_nonce( 'sapi_brevo_nonce' );
	$ajax_url = admin_url( 'admin-ajax.php' );

	?>
	<style id="sapi-cookie-popup-css">
		/* Masque la banniere Complianz native */
		.cmplz-cookiebanner,
		#cmplz-cookiebanner-container,
		.cmplz-cookiebanner-container { display: none !important; }

		/* Overlay — cache par defaut, affiche par JS uniquement si pas de consentement */
		#sapi-cookie-overlay[hidden] { display: none !important; }
		#sapi-cookie-overlay {
			position: fixed;
			inset: 0;
			background: rgba(30, 25, 20, 0.55);
			z-index: 99999;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 1rem;
			opacity: 0;
			animation: sapiCookieOverlayIn 0.3s ease forwards;
		}
		#sapi-cookie-overlay.sapi-cookie-closing {
			animation: sapiCookieOverlayOut 0.35s ease forwards;
		}

		/* Popup */
		#sapi-cookie-popup {
			background: var(--color-warm, #FBF6EA);
			border-radius: 16px;
			padding: 2.5rem 2rem;
			max-width: 520px;
			width: 90%;
			box-shadow: 0 20px 60px rgba(0,0,0,0.25);
			text-align: center;
			box-sizing: border-box;
			position: relative;
		}

		/* Gestion des 3 ecrans via data-screen sur l'overlay */
		#sapi-cookie-overlay .sapi-screen {
			transition: opacity 0.35s ease;
		}
		#sapi-cookie-overlay[data-screen="cookie"] .sapi-screen:not(#sapi-screen-cookie),
		#sapi-cookie-overlay[data-screen="promo"] .sapi-screen:not(#sapi-screen-promo),
		#sapi-cookie-overlay[data-screen="confirm"] .sapi-screen:not(#sapi-screen-confirm) {
			display: none;
		}
		#sapi-cookie-overlay .sapi-screen.sapi-screen--fading { opacity: 0; }

		/* ========== ECRAN 1 : COOKIES ========== */
		/* Phase 1 — Phrase animee lettre par lettre */
		#sapi-cookie-phrase {
			font-family: 'Square Peg', cursive;
			font-size: 2.4rem;
			line-height: 1.15;
			color: var(--color-wood-dark, #6b5644);
			margin: 0;
		}
		#sapi-cookie-phrase .sapi-cookie-char {
			display: inline-block;
			opacity: 0;
			animation: sapiCookieCharIn 0.45s ease forwards;
			will-change: opacity;
		}
		#sapi-cookie-phrase .sapi-cookie-line-intro {
			font-size: 1.25em;
			display: inline-block;
		}

		/* Signature */
		#sapi-cookie-signature {
			font-family: 'Montserrat', sans-serif;
			font-weight: 600;
			letter-spacing: 0.1em;
			color: var(--color-orange, #E35B24);
			margin: 0.75rem 0 0 0;
			font-size: 0.75rem;
			text-transform: uppercase;
			text-align: right;
			opacity: 0;
			animation: sapiCookieFadeIn 0.6s ease forwards;
			animation-delay: 2.7s;
		}

		/* Phase 2 — Explication + boutons */
		#sapi-cookie-body {
			opacity: 0;
			animation: sapiCookieFadeIn 0.7s ease forwards;
			animation-delay: 3.4s;
			margin-top: 2rem;
		}

		#sapi-cookie-message {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.9rem;
			color: var(--color-wood-dark, #6b5644);
			opacity: 0.85;
			line-height: 1.5;
			margin: 0 0 1.5rem 0;
		}

		#sapi-cookie-buttons {
			display: flex;
			justify-content: center;
			gap: 1rem;
			flex-wrap: wrap;
		}

		#sapi-cookie-deny,
		#sapi-cookie-accept {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.85rem;
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			border-radius: 50px;
			padding: 0.65rem 1.5rem;
			cursor: pointer;
			transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease;
			min-width: 130px;
		}
		#sapi-cookie-deny {
			background: transparent;
			border: 1.5px solid var(--color-wood, #937D68);
			color: var(--color-wood, #937D68);
		}
		#sapi-cookie-deny:hover {
			background: var(--color-wood, #937D68);
			color: #fff;
		}
		#sapi-cookie-accept {
			background: var(--color-wood, #937D68);
			border: 1.5px solid var(--color-wood, #937D68);
			color: #fff;
		}
		#sapi-cookie-accept:hover {
			background: var(--color-wood-dark, #6b5644);
			border-color: var(--color-wood-dark, #6b5644);
			transform: translateY(-1px);
		}

		/* ========== ECRAN 2 : PROMO ========== */
		#sapi-promo-title {
			font-family: 'Montserrat', sans-serif;
			font-size: 1.3rem;
			font-weight: 700;
			color: var(--color-wood-dark, #6b5644);
			margin: 0 0 1rem 0;
		}
		#sapi-promo-text {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.95rem;
			color: var(--color-wood-dark, #6b5644);
			opacity: 0.85;
			line-height: 1.6;
			margin: 0 0 1.5rem 0;
		}
		#sapi-promo-form {
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
			margin: 0;
		}
		#sapi-promo-email {
			width: 100%;
			border: 1.5px solid var(--color-wood, #937D68);
			border-radius: 50px;
			padding: 0.65rem 1.25rem;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.9rem;
			background: #fff;
			color: var(--color-wood-dark, #6b5644);
			box-sizing: border-box;
			outline: none;
			transition: border-color 0.2s ease;
		}
		#sapi-promo-email:focus {
			border-color: var(--color-wood-dark, #6b5644);
		}
		#sapi-promo-email.sapi-input-error {
			border-color: var(--color-orange, #E35B24);
		}
		#sapi-promo-submit {
			width: 100%;
			background: var(--color-wood, #937D68);
			border: 1.5px solid var(--color-wood, #937D68);
			color: #fff;
			border-radius: 50px;
			padding: 0.65rem 1.5rem;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.85rem;
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			cursor: pointer;
			transition: background-color 0.2s ease, transform 0.2s ease;
		}
		#sapi-promo-submit:hover {
			background: var(--color-wood-dark, #6b5644);
			transform: translateY(-1px);
		}
		#sapi-promo-submit:disabled {
			opacity: 0.6;
			cursor: wait;
		}
		#sapi-promo-error {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.8rem;
			color: var(--color-orange, #E35B24);
			margin: 0.35rem 0 0 1rem;
			text-align: left;
			min-height: 1em;
		}
		#sapi-promo-disclaimer {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.72rem;
			color: var(--color-wood, #937D68);
			opacity: 0.65;
			text-align: center;
			margin: 0.75rem 0 0 0;
			line-height: 1.4;
		}
		#sapi-promo-skip {
			display: block;
			margin: 1rem auto 0;
			background: none;
			border: none;
			color: var(--color-wood, #937D68);
			opacity: 0.6;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.85rem;
			text-decoration: underline;
			cursor: pointer;
			padding: 0.25rem 0.5rem;
		}
		#sapi-promo-skip:hover { opacity: 1; }

		/* ========== ECRAN 3 : CONFIRMATION ========== */
		#sapi-promo-confirm-label {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.95rem;
			color: var(--color-wood-dark, #6b5644);
			margin: 0 0 0.75rem 0;
		}
		#sapi-promo-code {
			font-family: 'Montserrat', sans-serif;
			font-size: 1.6rem;
			font-weight: 700;
			color: var(--color-wood, #937D68);
			letter-spacing: 0.15em;
			text-align: center;
			background: rgba(147, 125, 104, 0.12);
			border-radius: 8px;
			padding: 0.75rem 1rem;
			margin: 0 0 1rem 0;
		}
		#sapi-promo-copy {
			background: transparent;
			border: 1.5px solid var(--color-wood, #937D68);
			color: var(--color-wood, #937D68);
			border-radius: 50px;
			padding: 0.45rem 1.25rem;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.8rem;
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			cursor: pointer;
			transition: background-color 0.2s ease, color 0.2s ease;
		}
		#sapi-promo-copy:hover {
			background: var(--color-wood, #937D68);
			color: #fff;
		}
		#sapi-promo-copy.sapi-copied {
			background: var(--color-wood, #937D68);
			color: #fff;
		}
		#sapi-promo-remember {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.9rem;
			color: var(--color-orange, #E35B24);
			line-height: 1.5;
			margin: 1.25rem 0 0 0;
		}
		#sapi-promo-remember strong {
			font-weight: 700;
			letter-spacing: 0.03em;
		}
		#sapi-promo-confirm-text {
			font-family: 'Montserrat', sans-serif;
			font-size: 0.85rem;
			color: var(--color-wood-dark, #6b5644);
			opacity: 0.75;
			margin: 0.5rem 0 1.5rem 0;
		}
		#sapi-promo-close {
			width: 100%;
			background: var(--color-wood, #937D68);
			border: 1.5px solid var(--color-wood, #937D68);
			color: #fff;
			border-radius: 50px;
			padding: 0.65rem 1.5rem;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.85rem;
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			cursor: pointer;
			transition: background-color 0.2s ease, transform 0.2s ease;
		}
		#sapi-promo-close:hover {
			background: var(--color-wood-dark, #6b5644);
			transform: translateY(-1px);
		}

		/* Mobile */
		@media (max-width: 600px) {
			#sapi-cookie-popup { padding: 2rem 1.25rem; }
			#sapi-cookie-phrase { font-size: 1.7rem; }
			#sapi-cookie-buttons { gap: 0.75rem; }
			#sapi-cookie-deny,
			#sapi-cookie-accept { min-width: 0; flex: 1; padding: 0.7rem 1rem; }
			#sapi-promo-code { font-size: 1.3rem; }
		}

		@keyframes sapiCookieFadeIn {
			from { opacity: 0; transform: translateY(6px); }
			to   { opacity: 1; transform: translateY(0); }
		}
		@keyframes sapiCookieCharIn {
			from { opacity: 0; }
			to   { opacity: 1; }
		}
		@keyframes sapiCookieOverlayIn {
			from { opacity: 0; }
			to   { opacity: 1; }
		}
		@keyframes sapiCookieOverlayOut {
			from { opacity: 1; }
			to   { opacity: 0; }
		}

		@media (prefers-reduced-motion: reduce) {
			#sapi-cookie-overlay,
			#sapi-cookie-phrase,
			#sapi-cookie-signature,
			#sapi-cookie-body,
			#sapi-cookie-overlay .sapi-screen {
				animation-duration: 0.01ms !important;
				animation-delay: 0ms !important;
				transition-duration: 0.01ms !important;
			}
		}
	</style>

	<div id="sapi-cookie-overlay" role="dialog" aria-modal="true" data-screen="cookie" hidden>
		<div id="sapi-cookie-popup">

			<!-- ECRAN 1 : Cookies -->
			<div id="sapi-screen-cookie" class="sapi-screen">
				<p id="sapi-cookie-phrase"><span class="sapi-cookie-line-intro">Bienvenue sur mon site&nbsp;!</span><br>Je fabrique des luminaires à la main.<br>Je respecte aussi le RGPD.</p>
				<p id="sapi-cookie-signature">Robin</p>
				<div id="sapi-cookie-body">
					<p id="sapi-cookie-message">
						J'utilise quelques cookies pour vérifier que le site fonctionne, et pour comprendre ce qui vous plaît.<br>Votre accord&nbsp;?
					</p>
					<div id="sapi-cookie-buttons">
						<button type="button" id="sapi-cookie-deny">Refuser</button>
						<button type="button" id="sapi-cookie-accept">Accepter</button>
					</div>
				</div>
			</div>

			<!-- ECRAN 2 : Promo email -->
			<div id="sapi-screen-promo" class="sapi-screen">
				<p id="sapi-promo-title">Puisque vous êtes là…</p>
				<p id="sapi-promo-text">
					Pour votre première commande, je vous offre <strong>10%</strong>.<br>
					Laissez votre email, je vous envoie le code.
				</p>
				<form id="sapi-promo-form" novalidate>
					<input type="email" id="sapi-promo-email" name="email" placeholder="votre@email.fr" autocomplete="email" required>
					<button type="submit" id="sapi-promo-submit">Je veux mon code →</button>
					<p id="sapi-promo-error" aria-live="polite"></p>
				</form>
				<p id="sapi-promo-disclaimer">
					En cliquant, vous acceptez de recevoir les actualités de l'Atelier Sâpi. Désinscription à tout moment.
				</p>
				<button type="button" id="sapi-promo-skip">Non merci</button>
			</div>

			<!-- ECRAN 3 : Confirmation code -->
			<div id="sapi-screen-confirm" class="sapi-screen">
				<p id="sapi-promo-confirm-label">Votre code&nbsp;:</p>
				<p id="sapi-promo-code">BIENVENUE10</p>
				<button type="button" id="sapi-promo-copy">Copier le code</button>
				<p id="sapi-promo-remember"><strong>Notez-le bien !</strong><br>Il ne s'affichera plus.</p>
				<p id="sapi-promo-confirm-text">Valable sur votre première commande 🎁</p>
				<button type="button" id="sapi-promo-close">J'ai noté mon code</button>
			</div>

		</div>
	</div>

	<script id="sapi-cookie-popup-js">
	(function () {
		var AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
		var NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
		var PROMO_CODE = 'BIENVENUE10';

		// ----------------------------------------------------------
		// Detection des cookies existants
		// ----------------------------------------------------------
		function hasComplianzConsent() {
			var c = document.cookie || '';
			if (/(?:^|;\s*)cmplz_banner-status[^=]*=dismissed/.test(c)) return true;
			if (/(?:^|;\s*)cmplz_consent_status[^=]*=(allow|deny)/.test(c)) return true;
			return false;
		}
		function hasPromoDismissed() {
			return /(?:^|;\s*)sapi_promo_dismissed=1/.test(document.cookie || '');
		}
		function setCookie(name, value, days) {
			try {
				var d = new Date();
				d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
				document.cookie = name + '=' + value + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
			} catch (e) {}
		}

		var overlay = document.getElementById('sapi-cookie-overlay');
		if (!overlay) return;

		var hasConsent = hasComplianzConsent();
		var hasPromo   = hasPromoDismissed();

		// Si tout est deja traite : ne rien afficher
		if (hasConsent && hasPromo) {
			overlay.parentNode && overlay.parentNode.removeChild(overlay);
			return;
		}

		// ----------------------------------------------------------
		// Determination de l'ecran initial
		// ----------------------------------------------------------
		// - Pas de consentement cookies : ecran 1 (cookie)
		// - Consentement deja donne mais promo jamais vue : ecran 2 (promo)
		var initialScreen = hasConsent ? 'promo' : 'cookie';
		overlay.setAttribute('data-screen', initialScreen);

		// ----------------------------------------------------------
		// Animation lettre par lettre (uniquement ecran 1)
		// ----------------------------------------------------------
		if (initialScreen === 'cookie') {
			var phrase = document.getElementById('sapi-cookie-phrase');
			if (phrase) {
				var state = { delay: 0.2, stagger: 0.015, pauseBetweenPhrases: 0.3 };
				function splitNode(node) {
					if (node.nodeType === 3) {
						var out = document.createDocumentFragment();
						var text = node.textContent;
						for (var i = 0; i < text.length; i++) {
							var ch = text[i];
							var span = document.createElement('span');
							span.className = 'sapi-cookie-char';
							span.textContent = ch === ' ' ? '\u00A0' : ch;
							span.style.animationDelay = state.delay.toFixed(3) + 's';
							out.appendChild(span);
							state.delay += state.stagger;
						}
						return out;
					}
					if (node.nodeName === 'BR') {
						state.delay += state.pauseBetweenPhrases;
						return node.cloneNode(true);
					}
					var clone = node.cloneNode(false);
					Array.prototype.forEach.call(node.childNodes, function (child) {
						clone.appendChild(splitNode(child));
					});
					return clone;
				}
				var frag = document.createDocumentFragment();
				Array.prototype.forEach.call(phrase.childNodes, function (node) {
					frag.appendChild(splitNode(node));
				});
				phrase.innerHTML = '';
				phrase.appendChild(frag);
			}
		}

		overlay.hidden = false;

		// ----------------------------------------------------------
		// Transition entre ecrans (fade)
		// ----------------------------------------------------------
		function switchScreen(to) {
			var from = overlay.getAttribute('data-screen');
			if (from === to) return;
			var elFrom = document.getElementById('sapi-screen-' + from);
			if (elFrom) elFrom.classList.add('sapi-screen--fading');
			setTimeout(function () {
				overlay.setAttribute('data-screen', to);
				if (elFrom) elFrom.classList.remove('sapi-screen--fading');
				var elTo = document.getElementById('sapi-screen-' + to);
				if (elTo) {
					elTo.classList.add('sapi-screen--fading');
					// force reflow
					void elTo.offsetHeight;
					elTo.classList.remove('sapi-screen--fading');
				}
			}, 350);
		}

		function closePopup() {
			overlay.classList.add('sapi-cookie-closing');
			setTimeout(function () {
				if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
			}, 400);
		}

		// ----------------------------------------------------------
		// Complianz : pose banner-status dismissed
		// ----------------------------------------------------------
		function setBannerDismissed() {
			try {
				if (typeof window.cmplz_set_banner_status === 'function') {
					window.cmplz_set_banner_status('dismissed');
				}
				if (typeof window.cmplz_set_cookie === 'function') {
					window.cmplz_set_cookie('banner-status', 'dismissed');
				}
			} catch (e) {}
			setCookie('cmplz_banner-status', 'dismissed', 365);
		}

		function callComplianz(action) {
			try {
				if (action === 'accept' && typeof window.cmplz_accept_all === 'function') {
					window.cmplz_accept_all();
					setBannerDismissed();
					return true;
				}
				if (action === 'deny' && typeof window.cmplz_deny_all === 'function') {
					window.cmplz_deny_all();
					setBannerDismissed();
					return true;
				}
				if (typeof window.cmplz_set_consent === 'function') {
					var value = action === 'accept' ? 'allow' : 'deny';
					window.cmplz_set_consent('marketing', value);
					window.cmplz_set_consent('statistics', value);
					window.cmplz_set_consent('preferences', value);
					window.cmplz_set_consent('functional', 'allow');
					setBannerDismissed();
					return true;
				}
			} catch (e) {
				if (window.console && console.warn) console.warn('[sapi-cookie-popup] Complianz API error:', e);
			}
			setBannerDismissed();
			return false;
		}

		// ----------------------------------------------------------
		// Ecran 1 : boutons Accepter / Refuser
		// ----------------------------------------------------------
		function handleCookieChoice(action) {
			callComplianz(action);
			if (hasPromoDismissed()) {
				closePopup();
			} else {
				switchScreen('promo');
			}
		}

		var btnAccept = document.getElementById('sapi-cookie-accept');
		var btnDeny   = document.getElementById('sapi-cookie-deny');
		if (btnAccept) btnAccept.addEventListener('click', function () { handleCookieChoice('accept'); });
		if (btnDeny)   btnDeny.addEventListener('click',   function () { handleCookieChoice('deny'); });

		// Clic/tap sur l'overlay = Refuser (uniquement ecran cookie)
		overlay.addEventListener('click', function (e) {
			if (e.target !== overlay) return;
			if (overlay.getAttribute('data-screen') !== 'cookie') return;
			handleCookieChoice('deny');
		});

		// ----------------------------------------------------------
		// Ecran 2 : formulaire promo
		// ----------------------------------------------------------
		function showError(msg) {
			var err = document.getElementById('sapi-promo-error');
			var input = document.getElementById('sapi-promo-email');
			if (err) err.textContent = msg || '';
			if (input) input.classList.toggle('sapi-input-error', !!msg);
		}
		function clearError() { showError(''); }

		function isValidEmail(v) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
		}

		function goToConfirm() {
			setCookie('sapi_promo_dismissed', '1', 365);
			switchScreen('confirm');
		}

		var form = document.getElementById('sapi-promo-form');
		var input = document.getElementById('sapi-promo-email');
		var submit = document.getElementById('sapi-promo-submit');
		if (input) input.addEventListener('input', clearError);

		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				clearError();
				var email = input ? input.value.trim() : '';
				if (!isValidEmail(email)) {
					showError('Email invalide');
					if (input) input.focus();
					return;
				}

				if (submit) submit.disabled = true;

				var body = new URLSearchParams();
				body.append('action', 'sapi_brevo_subscribe');
				body.append('nonce', NONCE);
				body.append('email', email);

				fetch(AJAX_URL, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				}).then(function (r) {
					return r.json().catch(function () { return null; });
				}).then(function (json) {
					// Meme en cas d'erreur API, on affiche le code pour ne pas bloquer l'utilisateur
					if (!json || !json.success) {
						if (window.console && console.warn) console.warn('[sapi-promo] Brevo error', json);
					}
					goToConfirm();
				}).catch(function (err) {
					if (window.console && console.warn) console.warn('[sapi-promo] Fetch error', err);
					goToConfirm();
				});
			});
		}

		var btnSkip = document.getElementById('sapi-promo-skip');
		if (btnSkip) btnSkip.addEventListener('click', function () {
			setCookie('sapi_promo_dismissed', '1', 365);
			closePopup();
		});

		// ----------------------------------------------------------
		// Ecran 3 : copier le code
		// ----------------------------------------------------------
		var btnClose = document.getElementById('sapi-promo-close');
		if (btnClose) btnClose.addEventListener('click', closePopup);

		var btnCopy = document.getElementById('sapi-promo-copy');
		if (btnCopy) btnCopy.addEventListener('click', function () {
			var done = function () {
				btnCopy.textContent = 'Copié ✓';
				btnCopy.classList.add('sapi-copied');
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(PROMO_CODE).then(done).catch(function () {
					// fallback
					var ta = document.createElement('textarea');
					ta.value = PROMO_CODE;
					document.body.appendChild(ta);
					ta.select();
					try { document.execCommand('copy'); done(); } catch (e) {}
					document.body.removeChild(ta);
				});
			} else {
				var ta = document.createElement('textarea');
				ta.value = PROMO_CODE;
				document.body.appendChild(ta);
				ta.select();
				try { document.execCommand('copy'); done(); } catch (e) {}
				document.body.removeChild(ta);
			}
		});

	})();
	</script>
	<?php
}, 100 );
