<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package cleopa
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php cleopa_featured_thumb(); ?>
	<div class="entry-content">
		<?php cleopa_posted_on();
		the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );
		cleopa_get_categories();
		if(cleopa_get_options('nbcore_blog_archive_summary')):
			?>
			<div class="entry-text">
				<?php
				if(cleopa_get_options('nbcore_excerpt_only')) {
					cleopa_get_excerpt();
					echo '<div class="read-more-link"><a class="bt-4 nb-secondary-button" href="' . get_permalink() . '">' . esc_html__('View post', 'cleopa') . '<span>&rarr;</span></a></div>';
				} else {
					the_excerpt();
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</article><!-- #post-## -->
