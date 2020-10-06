<div class="row">
	<div class="cart-left-section col-lg-8">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>
		<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
			<thead>
				<tr>
					<th class="product-thumbnail"><?php esc_html_e( 'Product', 'cleopa' ); ?></th>
					<th class="product-name">&nbsp;</th>
					<th class="product-price"><?php esc_html_e( 'Price', 'cleopa' ); ?></th>
					<th class="product-quantity"><?php esc_html_e( 'Quantity', 'cleopa' ); ?></th>
					<th class="product-subtotal"><?php esc_html_e( 'Total', 'cleopa' ); ?></th>
					<th class="product-remove">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php do_action( 'woocommerce_before_cart_contents' ); ?>

				<?php
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

					if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						?>
						<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

							<td class="product-thumbnail">
								<?php
									$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

									if ( ! $product_permalink ) {
										print($thumbnail);
									} else {
										printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
									}
								?>
							</td>

							<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'cleopa' ); ?>">
								<?php
									if ( ! $product_permalink ) {
										echo apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;';
									} else {
										echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key );
									}

									// Meta data
									if (version_compare( WC()->version, 3.3, "<" )){
										echo WC()->cart->get_item_data( $cart_item );
									} else {
										echo wc_get_formatted_cart_item_data( $cart_item );
									}

									// Backorder notification
									if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
										echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'cleopa' ) . '</p>';
									}
								?>
							</td>

							<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'cleopa' ); ?>">
								<?php
									echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
								?>
							</td>

							<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'cleopa' ); ?>">
								<?php
									if ( $_product->is_sold_individually() ) {
										$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
									} else {
										$product_quantity = woocommerce_quantity_input( array(
											'input_name'  => "cart[{$cart_item_key}][qty]",
											'input_value' => $cart_item['quantity'],
											'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
											'min_value'   => '0',
										), $_product, false );
									}

									echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
								?>
							</td>

							<td class="product-subtotal" data-title="<?php esc_attr_e( 'Total', 'cleopa' ); ?>">
								<?php
									echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
								?>
							</td>

							<td class="product-remove">
								<?php
								echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
									'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><i class="icon-garbage"></i></a>',
									(version_compare( WC()->version, 3.3, "<" ) ?
										WC()->cart->get_remove_url( $cart_item_key )
										: esc_url( wc_get_cart_remove_url( $cart_item_key ) )),
									__( 'Remove this item', 'cleopa' ),
									esc_attr( $product_id ),
									esc_attr( $cart_item_key ),
									esc_attr( $_product->get_sku() )
								), $cart_item_key );
								?>
							</td>
						</tr>
						<?php
					}
				}
				?>

						<?php do_action( 'woocommerce_cart_contents' ); ?>

						<tr>
							<td colspan="6" class="actions">
								<?php if(cleopa_get_options('nbcore_show_to_shop')): ?>
								<a class="bt-5 nb-secondary-button to-shop" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><i class="icon-angle-double-left"></i> <?php esc_html_e( 'Continue Shopping', 'cleopa' ) ?></a>
								<?php endif; ?>

								<input type="submit" class="nb-primary-button bt-5" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'cleopa' ); ?>" />

								<?php do_action( 'woocommerce_cart_actions' ); ?>

								<?php wp_nonce_field( 'woocommerce-cart' ); ?>
							</td>
						</tr>

						<?php do_action( 'woocommerce_after_cart_contents' ); ?>
			</tbody>
		</table>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</div>

	<div class="cart-right-section col-lg-4">
		<?php
		$button_class = '';
		if('cart-layout-1' === cleopa_get_options('nbcore_cart_layout')) {
			$button_class = 'nb-primary-button';
		} else {
			$button_class = 'nb-wide-button button';
		}
		if ( wc_coupons_enabled() ) { ?>
			<div class="coupon">
				<h3>Coupon</h3>
				<div class="coupon-wrap">
					<input type="text" name="coupon_code" class="input-text bt-5" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'cleopa' ); ?>" />
					<input type="submit" class="bt-5 <?php echo esc_attr($button_class); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'cleopa' ); ?>" />
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
			</div>
		<?php }
		woocommerce_cart_totals(); ?>
	</div>
</div>

<div class="cart-collaterals">
	<?php
	if(cleopa_get_options('nbcore_show_cross_sells')) {
		woocommerce_cross_sell_display();
	} ?>
</div>