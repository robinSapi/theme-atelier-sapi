<?php
/**
 * Email Header - Atelier Sapi (surcharge charte)
 *
 * Surcharge de woocommerce/templates/emails/email-header.php (base v9.7.0).
 * Habillage a la charte : en-tete fond creme, logo PNG centre, titre du mail
 * ($email_heading) en Square Peg bois, puis un filet bois de 3px.
 *
 * Structure : ce fichier OUVRE le container + #body_content_inner ; c'est
 * email-footer.php (meme theme) qui referme tout. Les 2 fichiers vont ensemble.
 * Styles inline volontaires (l'email n'a pas de CSS externe fiable). Les
 * couleurs sont ecrites en dur pour un rendu previsible (charte).
 *
 * @package theme-sapi-maison
 */

defined( 'ABSPATH' ) || exit;

$sapi_logo_png = 'https://atelier-sapi.fr/wp-content/uploads/2024/12/logo_sapi.png';
$sapi_site_name = get_bloginfo( 'name', 'display' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $sapi_site_name ); ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&amp;family=Square+Peg&amp;display=swap" rel="stylesheet">
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;background-color:#F5EDE4;-webkit-text-size-adjust:100%;">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" id="outer_wrapper" style="background-color:#F5EDE4;">
		<tr>
			<td><!-- Deliberately empty to support consistent sizing across email clients. --></td>
			<td width="600">
				<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="margin:0 auto;padding:24px 12px;width:100%;max-width:600px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%" id="inner_wrapper">
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="max-width:600px;background-color:#FEFDFB;border-radius:4px;">
									<!-- Header : logo -->
									<tr>
										<td align="center" valign="top" style="background-color:#FBF6EA;padding:32px 24px 4px;">
											<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" style="text-decoration:none;">
												<img src="<?php echo esc_url( $sapi_logo_png ); ?>" alt="<?php echo esc_attr( $sapi_site_name ); ?>" width="128" style="display:block;width:128px;max-width:128px;height:auto;border:0;margin:0 auto;">
											</a>
										</td>
									</tr>
									<!-- Header : titre du mail -->
									<tr>
										<td id="header_wrapper" align="center" valign="top" style="background-color:#FBF6EA;padding:8px 24px 18px;">
											<h1 style="margin:0;padding:0;font-family:'Square Peg',Georgia,serif;font-size:38px;font-weight:400;color:#937D68;line-height:44px;text-align:center;text-shadow:none;"><?php echo esc_html( $email_heading ); ?></h1>
										</td>
									</tr>
									<!-- Header : filet bois -->
									<tr>
										<td style="padding:0;font-size:0;line-height:0;">
											<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:3px;background-color:#937D68;font-size:0;line-height:0;">&nbsp;</td></tr></table>
										</td>
									</tr>
									<!-- Body -->
									<tr>
										<td valign="top" id="body_content" style="background-color:#FEFDFB;">
											<table border="0" cellpadding="0" cellspacing="0" width="100%">
												<tr>
													<td valign="top" style="padding:32px 32px 24px;">
														<div id="body_content_inner" style="color:#323232;font-family:'Montserrat',Arial,Helvetica,sans-serif;font-size:15px;line-height:26px;text-align:<?php echo is_rtl() ? 'right' : 'left'; ?>;">
