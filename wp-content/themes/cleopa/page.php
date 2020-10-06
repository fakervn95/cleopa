<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package cleopa
 */

get_header();
if(get_the_title(get_the_ID())) { 
    cleopa_page_title();
}
$page_sidebar = cleopa_get_options( 'nbcore_page_layout');
?>
	
	<div class="container">
		<div class="row">
			<div id="primary" class="content-area page-<?php echo esc_attr($page_sidebar); ?>">
				<main id="main" class="site-main">

					<?php
					while ( have_posts() ) : the_post();

						?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <?php
                            if('no-thumb' !== cleopa_get_options('page_thumb')) {
                                cleopa_featured_thumb();
                            }
                            ?>
                            <div class="entry-content">
                                <?php
                                the_content();

                                wp_link_pages( array(
                                    'before' => '<div class="page-links ' . cleopa_get_options('pagination_style') . '">' . esc_html__( 'Pages:', 'cleopa' ),
                                    'after'  => '</div>',
									'link_before' => '<span>',
									'link_after' => '</span>',
                                ) );
                                ?>
                            </div><!-- .entry-content -->
                        </article><!-- #post-## -->
                        <?php
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :
							comments_template();
						endif;

					endwhile; // End of the loop.
					?>

				</main><!-- #main -->
			</div><!-- #primary -->
			<?php
            if('full-width' !== $page_sidebar) {
                get_sidebar();
            }
			?>
		</div>
	</div>

<?php
get_footer();
