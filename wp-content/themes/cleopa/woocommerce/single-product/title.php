<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @author     WooThemes
 * @package    WooCommerce/Templates
 * @version    4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$page_title = cleopa_get_options('show_title_section');
$pd_title = cleopa_get_options('nbcore_pd_details_title');

if($page_title) {
	if($pd_title) {
		the_title( '<h1 class="product_title entry-title">', '</h1>' );
	}
} else {
	the_title( '<h1 class="product_title entry-title">', '</h1>' );
}

