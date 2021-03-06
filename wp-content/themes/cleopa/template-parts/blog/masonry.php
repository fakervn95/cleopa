<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package cleopa
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php cleopa_featured_thumb(); ?>
	<div class="entry-content">
		<?php
		cleopa_posted_on();
		the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );
		?>
		<div class="entry-text">
			<?php cleopa_get_excerpt(); ?>
		</div>
		<?php
		echo '<div class="read-more-link"><a class="bt-4 nb-secondary-button" href="' . get_permalink() . '">' . esc_html__('View post', 'cleopa') . '<span> &rarr;</span></a></div>';
		?>
	</div>

</article><!-- #post-## -->
