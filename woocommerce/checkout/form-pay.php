<?php
/**
 * Pay for order form — Atelier Sâpi custom template
 * Reproduit le layout "Résumé de la commande" du checkout Blocks
 *
 * Override of: woocommerce/templates/checkout/form-pay.php
 * @version 8.2.0
 */

defined( 'ABSPATH' ) || exit;

$totals = $order->get_order_item_totals();
?>
<form id="order_review" method="post">

	<div class="sapi-order-pay-recap">
		<h2 class="sapi-order-pay-recap__title"><?php esc_html_e( 'Résumé de la commande', 'theme-sapi-maison' ); ?></h2>

		<?php if ( count( $order->get_items() ) > 0 ) : ?>
			<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
				<?php
				if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					continue;
				}
				$product   = $item->get_product();
				$image_id  = $product ? $product->get_image_id() : 0;
				$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$qty       = $item->get_quantity();

				// Nom du produit parent (sans les variations dans le titre)
				$parent_product = $product;
				if ( $product && $product->is_type( 'variation' ) ) {
					$parent_product = wc_get_product( $product->get_parent_id() );
				}
				$display_name = $parent_product ? $parent_product->get_name() : $item->get_name();
				?>
				<div class="sapi-order-pay-item <?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
					<div class="sapi-order-pay-item__image">
						<?php if ( $qty > 1 ) : ?>
							<span class="sapi-order-pay-item__qty"><?php echo esc_html( $qty ); ?></span>
						<?php endif; ?>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" />
					</div>
					<div class="sapi-order-pay-item__details">
						<h3 class="sapi-order-pay-item__name product-card-title"><?php echo wp_kses_post( $display_name ); ?></h3>
						<?php
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

						// Attributs de variation (matériau, taille…)
						if ( $product && $product->is_type( 'variation' ) ) {
							$variation_attrs = $product->get_variation_attributes();
							if ( ! empty( $variation_attrs ) ) {
								echo '<ul class="wc-item-meta">';
								foreach ( $variation_attrs as $attr_key => $attr_value ) {
									if ( empty( $attr_value ) ) {
										continue;
									}
									$taxonomy = str_replace( 'attribute_', '', $attr_key );
									$label    = wc_attribute_label( $taxonomy, $product );
									$value    = taxonomy_exists( $taxonomy )
										? get_term_by( 'slug', $attr_value, $taxonomy )->name ?? $attr_value
										: $attr_value;
									echo '<li><strong>' . esc_html( $label ) . ':</strong> <p>' . esc_html( $value ) . '</p></li>';
								}
								echo '</ul>';
							}
						}
						// Meta de commande (add-ons : couleur câble/pavillon, etc.)
						wc_display_item_meta( $item );

						do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
						?>
						<p class="sapi-order-pay-item__price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if ( $totals ) : ?>
			<div class="sapi-order-pay-totals">
				<?php foreach ( $totals as $key => $total ) : ?>
					<div class="sapi-order-pay-totals__row <?php echo esc_attr( $key ); ?>">
						<span class="sapi-order-pay-totals__label"><?php echo wp_kses_post( $total['label'] ); ?></span>
						<span class="sapi-order-pay-totals__value"><?php echo wp_kses_post( $total['value'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php do_action( 'woocommerce_pay_order_before_payment' ); ?>

	<div id="payment">
		<?php if ( $order->needs_payment() ) : ?>
			<ul class="wc_payment_methods payment_methods methods">
				<?php
				if ( ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				} else {
					echo '<li>';
					wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' );
					echo '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
		<div class="form-row">
			<input type="hidden" name="woocommerce_pay" value="1" />

			<?php wc_get_template( 'checkout/terms.php' ); ?>

			<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

			<div class="sapi-order-pay-actions">
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="sapi-order-pay-back">&larr; <?php esc_html_e( 'Retour au panier', 'theme-sapi-maison' ); ?></a>

				<?php echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'woocommerce_pay_order_button_html',
					'<button type="submit" class="button alt' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>'
				); ?>
			</div>

			<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

			<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
		</div>
	</div>
</form>
