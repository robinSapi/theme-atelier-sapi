<?php
/**
 * Email Styles - Atelier Sapi (surcharge charte)
 *
 * Surcharge de woocommerce/templates/emails/email-styles.php (base v9.7.0).
 * WooCommerce capture la sortie de ce fichier comme feuille de style, puis
 * l'applique en inline (inliner CSS) sur le corps genere par WooCommerce
 * (tableau de commande, adresses, contenu additionnel).
 *
 * Couleurs charte ecrites en dur (rendu previsible, independant des reglages
 * WooCommerce > E-mails). Selecteurs volontairement mode-agnostiques : ils
 * marchent que le flag "email_improvements" soit actif ou non (on cible
 * thead th / tr.order_item / tr.order-totals-total / .address, toujours poses).
 *
 * @package theme-sapi-maison
 */

defined( 'ABSPATH' ) || exit;
?>
/* ===== Fond general ===== */
body {
	background-color: #F5EDE4;
	padding: 0;
	text-align: center;
	-webkit-text-size-adjust: none !important;
}

#outer_wrapper {
	background-color: #F5EDE4;
}

#wrapper {
	margin: 0 auto;
	padding: 24px 12px;
	width: 100%;
	max-width: 600px;
}

#inner_wrapper,
#template_container {
	background-color: #FEFDFB;
	border-radius: 4px;
}

#body_content {
	background-color: #FEFDFB;
}

/* ===== Typographie du corps (texte genere par WooCommerce) ===== */
#body_content_inner {
	color: #323232;
	font-family: 'Montserrat', Arial, Helvetica, sans-serif;
	font-size: 15px;
	line-height: 26px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

#body_content_inner p {
	margin: 0 0 16px;
}

#body_content_inner a,
#body_content a,
.link,
a.link {
	color: #937D68;
	text-decoration: underline;
}

/* ===== Titres (Square Peg, repli Georgia) ===== */
#body_content h1,
#body_content h2,
#body_content h3,
h2.email-order-detail-heading {
	color: #937D68;
	font-family: 'Square Peg', Georgia, serif;
	font-weight: 400;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	text-shadow: none;
}

#body_content h2,
h2.email-order-detail-heading {
	font-size: 26px;
	line-height: 30px;
	margin: 0 0 12px;
}

#body_content h3 {
	font-size: 22px;
	line-height: 26px;
	margin: 16px 0 8px;
}

h2.email-order-detail-heading span {
	color: #585858;
	font-family: 'Montserrat', Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: 400;
	display: block;
}

/* ===== Tableau de commande ===== */
.td {
	color: #323232;
	border: 0;
	vertical-align: middle;
	font-family: 'Montserrat', Arial, Helvetica, sans-serif;
}

#body_content table.td {
	border: 0;
	border-collapse: collapse;
}

/* En-tete du tableau : fond bois, texte creme */
#body_content thead th,
#body_content thead th.td {
	background-color: #937D68;
	color: #FEFDFB;
	font-family: 'Montserrat', Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 1px;
	padding: 10px 8px;
	border: 0;
}

/* Lignes produits : texte fonce, filet bas discret */
#body_content tr.order_item td,
#body_content tr.order_item td.td {
	color: #323232;
	font-size: 14px;
	padding: 14px 8px;
	border: 0;
	border-bottom: 1px solid #E8DCC4;
}

/* Meta produit (variations) : gris secondaire, sans bordure */
#body_content .order-item-data td {
	border: 0 !important;
	padding: 0 !important;
	color: #585858;
}

#body_content ul.wc-item-meta,
#body_content ul.wc-item-meta li {
	color: #585858;
	font-size: 13px;
	margin: 4px 0 0;
	padding: 0;
	list-style: none;
}

/* Totaux (sous-total, livraison, TVA...) : gris secondaire */
#body_content tr.order-totals td,
#body_content tr.order-totals th {
	color: #585858;
	font-size: 13px;
	font-weight: normal;
	padding: 6px 8px;
	border: 0;
}

/* Ligne Total : orange, gras, filet bois au-dessus */
#body_content tr.order-totals-total th {
	color: #323232;
	font-size: 15px;
	font-weight: 700;
	border: 0;
	border-top: 2px solid #937D68;
	padding: 8px;
}

#body_content tr.order-totals-total td {
	color: #E35B24;
	font-size: 16px;
	font-weight: 800;
	border: 0;
	border-top: 2px solid #937D68;
	padding: 8px;
}

/* ===== Adresses ===== */
#body_content .address,
.address {
	color: #585858;
	font-size: 13px;
	line-height: 20px;
	font-style: normal;
	border: 0;
	padding: 8px 0;
}

/* ===== Contenu additionnel (ex. encart mode vacances) ===== */
#body_content .email-additional-content {
	padding-top: 16px;
}

/* ===== Images ===== */
#body_content img {
	max-width: 100%;
	height: auto;
	border: 0;
}

/* ===== Pied (repli si WooCommerce injecte un texte de credit) ===== */
#template_footer td {
	border: 0;
	padding: 0;
}

#template_footer #credit,
#template_footer #credit a {
	color: #585858;
}

/* ===== Mobile ===== */
@media screen and (max-width: 600px) {
	#body_content_inner {
		font-size: 14px !important;
	}
	#body_content thead th {
		padding: 8px 6px !important;
	}
	#body_content tr.order_item td {
		padding: 12px 6px !important;
	}
}
<?php
