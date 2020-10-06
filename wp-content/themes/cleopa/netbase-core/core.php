<?php

define('Cleopa_VER', '2.0.0');

class Cleopa_Core
{
    /**
     * Class prefix for autoload
     *
     * @var string
     */
    protected static $prefix = 'Cleopa_';

    /**
     * Variable hold the page options
     *
     * @var array
     */
    protected static $page_options = array();

    public static $plugins;

   public function __construct()
    {
        require_once get_template_directory() . '/netbase-core/vendor/tgmpa/class-tgm-plugin-activation.php';

        spl_autoload_register(array($this, 'autoload'));

        new Cleopa_Customize();

        Cleopa_Helper::include_template_tags();

        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            new Cleopa_Extensions_Woocommerce();
        }

        require_once get_template_directory() . '/netbase-core/extensions/cleopa_option.php';
        //add_action('wp', array(__CLASS__, 'get_options'));
        //add_action('customize_register', array('Cleopa_Customize', 'register'));

        add_action('after_setup_theme', array($this, 'theme_setup'));
        add_action('widgets_init', array($this, 'default_sidebars'));

        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_scripts_enqueue'));
        add_action('customize_controls_enqueue_scripts', array('Cleopa_Customize', 'customize_control_js'));
        add_action('customize_preview_init', array('Cleopa_Customize', 'customize_preview_js'));
        add_action('customize_controls_print_styles', array('Cleopa_Customize', 'customize_style'));

        //TODO make inline style below woocommerce.css.
        add_action('wp_enqueue_scripts', array($this, 'core_scripts_enqueue'), 9998);
        add_action('wp_enqueue_scripts', array($this, 'print_embed_style'), 9999);
        add_action('wp_enqueue_scripts', array($this, 'google_fonts_url'));

        add_filter('body_class', array('Cleopa_Helper', 'nbcore_body_classes'));
        add_action('wp_head', array('Cleopa_Helper', 'nbcore_pingback_header'));

        add_filter('show_recent_comments_widget_style', '__return_false');

         add_filter('upload_mimes', array($this, 'upload_mimes'));

        add_action( 'tgmpa_register', array(__CLASS__, 'register_required_plugins') );
        
        add_action( 'admin_init', array(__CLASS__, 'add_editor_styles') );
        add_filter( 'pt-ocdi/import_files', array(__CLASS__, 'ocdi_import_files') );
        add_action( 'pt-ocdi/after_import', array(__CLASS__, 'after_import') );
        $content_width = 1170;

        if(empty(get_transient('current_load_css')) && $this->is_plugin_active_byme('nb-fw/nb-fw.php')) {
            set_transient('current_load_css', NBT_LOAD_CUSTOMIZE_FROM_HEAD, NBT_TIMEOUT_TRANSIENT_CUSTOMIZE);
        }

    }

    public static function autoload($class_name)
    {
        // Verify class prefix.
        if (0 !== strpos($class_name, self::$prefix)) {
            return false;
        }

        // Generate file path from class name.
        $base = get_template_directory() . '/netbase-core/';
        $path = strtolower(str_replace('_', '/', substr($class_name, strlen(self::$prefix))));

        // Check if class file exists.
        $standard = $path . '.php';
        $alternative = $path . '/' . current(array_slice(explode('/', str_replace('\\', '/', $path)), -1)) . '.php';

        while (true) {
            // Check if file exists in standard path.
            if (@is_file($base . $standard)) {
                $exists = $standard;

                break;
            }

            // Check if file exists in alternative path.
            if (@is_file($base . $alternative)) {
                $exists = $alternative;

                break;
            }

            // If there is no more alternative file, quit the loop.
            if (false === strrpos($standard, '/') || 0 === strrpos($standard, '/')) {
                break;
            }

            // Generate more alternative files.
            $standard = preg_replace('#/([^/]+)$#', '-\\1', $standard);
            $alternative = implode('/', array_slice(explode('/', str_replace('\\', '/', $standard)), 0, -1)) . '/' . substr(current(array_slice(explode('/', str_replace('\\', '/', $standard)), -1)), 0, -4) . '/' . current(array_slice(explode('/', str_replace('\\', '/', $standard)), -1));
        }

        // Include class declaration file if exists.
        if (isset($exists)) {
            return include_once $base . $exists;
        }

        return false;
    }

    public static function theme_setup()
    {
        /*
         * Make theme available for translation.
         * Translations can be filed in the /languages/ directory.
         * If you're building a theme based on cleopa, use a find and replace
         * to change 'cleopa' to the name of your theme in all the template files.
         */
        load_theme_textdomain('cleopa', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         *
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support('post-thumbnails');

        // A theme must have at least one navbar, right?
        register_nav_menus(array(
            'primary' => esc_html__('Primary', 'cleopa'),
            'footer' => esc_html__('Footer menu', 'cleopa'),
        ));
		
		add_theme_support('post-formats', array(
            'standard',
            'image',
            'video',
            'gallery',
            'audio',
            'aside',
            'chat',
            'link',
            'quote',
            'status'
        ));

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ));

        /*
         * Enable support for Post Formats.
         * See https://developer.wordpress.org/themes/functionality/post-formats/
         */

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        add_image_size('cleopa-masonry', 450, 450, true);
		
		add_theme_support( 'woocommerce' );
		add_theme_support( 'custom-header' );
		add_theme_support( 'custom-background' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );
    }

    /**
     * Theme default sidebar.
     *
     * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
     */
    public static function default_sidebars()
    {
        register_sidebar(array(
            'name' => esc_html__('Default Sidebar', 'cleopa'),
            'id' => 'default-sidebar',
            'description' => esc_html__('Add widgets here.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Shop Sidebar', 'cleopa'),
            'id' => 'shop-sidebar',
            'description' => esc_html__('Add widgets for category page.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Product Sidebar', 'cleopa'),
            'id' => 'product-sidebar',
            'description' => esc_html__('Add widgets for product details page', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Header Top #1', 'cleopa'),
            'id' => 'header-top-1',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Header Top #2', 'cleopa'),
            'id' => 'header-top-2',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Top #1', 'cleopa'),
            'id' => 'footer-top-1',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Top #2', 'cleopa'),
            'id' => 'footer-top-2',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Top #3', 'cleopa'),
            'id' => 'footer-top-3',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Top #4', 'cleopa'),
            'id' => 'footer-top-4',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Bottom #1', 'cleopa'),
            'id' => 'footer-bot-1',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Bottom #2', 'cleopa'),
            'id' => 'footer-bot-2',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Bottom #3', 'cleopa'),
            'id' => 'footer-bot-3',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));

        register_sidebar(array(
            'name' => esc_html__('Footer Bottom #4', 'cleopa'),
            'id' => 'footer-bot-4',
            'description' => esc_html__('For best display, please assign only one widget in this section.', 'cleopa'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ));
    }

    // Todo change to minified version and load conditional. Example: isotope is now always load
    public static function core_scripts_enqueue()
    {
        //TODO Remember this
        // wp_dequeue_script('wc-cart');
        wp_enqueue_style('cleopa_fontello', get_template_directory_uri() . '/assets/vendor/fontello/fontello.css', array(), Cleopa_VER);
		wp_enqueue_style( 'dashicons' );

        //wp_enqueue_style('cleopa_front_style', get_stylesheet_uri());
        wp_enqueue_style('cleopa_front_style', get_template_directory_uri() . '/assets/netbase/css/main.css', array(), Cleopa_VER);
		wp_enqueue_style('cleopa_front_font', get_template_directory_uri() . '/assets/font/font.css', array(), Cleopa_VER);

        wp_enqueue_script('isotope', get_template_directory_uri() . '/assets/vendor/isotope/isotope.pkdg.min.js', array('jquery'), '3.0.3', true);

        wp_enqueue_style('magnific-popup', get_template_directory_uri() . '/assets/vendor/magnific-popup/magnific-popup.css', array(), '2.0.5');
        wp_enqueue_script('magnific-popup', get_template_directory_uri() . '/assets/vendor/magnific-popup/jquery.magnific-popup.min.js', array('jquery'), '2.0.5', true);
        
		if (!class_exists("WPBakeryShortCode")) {
			wp_register_style( 'prettyphoto', get_template_directory_uri() . '/assets/vendor/prettyphoto/css/prettyPhoto.min.css', array(), Cleopa_VER );
			wp_register_script( 'prettyphoto', get_template_directory_uri() . '/assets/vendor/prettyphoto/js/jquery.prettyPhoto.min.js', array( 'jquery' ), Cleopa_VER, true );
		}
		// wp_enqueue_script( 'prettyphoto' );
		// wp_enqueue_style( 'prettyphoto' );

        wp_enqueue_style('swiper', get_template_directory_uri() . '/assets/vendor/swiper/swiper.min.css', array(), '3.4.2');
		wp_enqueue_script('swiper', get_template_directory_uri() . '/assets/vendor/swiper/swiper.jquery.min.js', array('jquery'), '3.4.2', true);

        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }

        if (function_exists('is_product') && is_product() && 'accordion-tabs' == cleopa_get_options('nbcore_info_style')) {
            wp_enqueue_script('jquery-ui-accordion');
        }

        if (cleopa_get_options('nbcore_header_fixed')) {
            wp_enqueue_script('theme_waypoints', get_template_directory_uri() . '/assets/vendor/waypoints/jquery.waypoints.min.js', array('jquery'), '4.0.1', true);
        }

        if (cleopa_get_options('nbcore_blog_sticky_sidebar') || cleopa_get_options('shop_sticky_sidebar') || cleopa_get_options('product_sticky_sidebar')) {
            wp_enqueue_script('sticky-kit', get_template_directory_uri() . '/assets/vendor/sticky-kit/jquery.sticky-kit.min.js', array('jquery'), '1.1.2', true);
        }

        wp_enqueue_script('cleopa_front_script', get_template_directory_uri() . '/assets/netbase/js/main.js', array('jquery'), Cleopa_VER, true);

        // wp_enqueue_script('cleopa_cart_script', get_template_directory_uri() . '/assets/netbase/js/cart.js', array('jquery'), Cleopa_VER, true);

        $localize_array = array(
            'ajaxurl'           => admin_url( 'admin-ajax.php', 'relative' ),
            'upsells_columns' => cleopa_get_options('nbcore_pd_upsells_columns'),
            'related_columns' => cleopa_get_options('nbcore_pd_related_columns'),
            'cross_sells_columns' => cleopa_get_options('nbcore_cross_sells_per_row'),
            'thumb_pos' => cleopa_get_options('nbcore_pd_thumb_pos'),
            'menu_resp' => cleopa_get_options('nbcore_menu_resp'),
        );
		
		if (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) )) ) {
            $version = version_compare( preg_replace( '/-beta-([0-9]+)/', '', WC()->version ), '2.3.0', '<' );   
            $localize_array['is2_2'] = $version;
        }
		
        wp_localize_script('cleopa_front_script', 'cleopa', $localize_array);

        wp_dequeue_script('yith-wcqv-frontend');
        wp_dequeue_style('yith-quick-view');
    }
	
	/**
	 *  Editor Style
	 */
	public static function add_editor_styles() {
		add_editor_style( 'css/editor-style.css' );
	}

    public static function admin_scripts_enqueue()
    {
        wp_enqueue_style('cleopa_fontello', get_template_directory_uri() . '/assets/vendor/fontello/fontello.css', array(), Cleopa_VER);
		//wp_enqueue_script('cleopa_admin_inline_script', get_template_directory_uri() . '/assets/netbase/js/admin/admin-inline.min.js', array('jquery'), Cleopa_VER, true);
    }

    

    //TODO optimize this(grouping and bring to css if can)
    //TODO early esc_
    public static function get_embed_style()
    {


        $bg_color = cleopa_get_options('nbcore_background_color');
        $inner_bg = cleopa_get_options('nbcore_inner_background');

        $top_padding = cleopa_get_options('nbcore_top_section_padding');
        $top_bg = cleopa_get_options('nbcore_header_top_bg');
        $top_color = cleopa_get_options('nbcore_header_top_color');
        $top_hover_color = cleopa_get_options('nbcore_header_top_hover_color');
        $middle_padding = cleopa_get_options('nbcore_middle_section_padding');
        $middle_bg = cleopa_get_options('nbcore_header_middle_bg');
        $middle_color = cleopa_get_options('nbcore_header_middle_color');
        $middle_hover_color = cleopa_get_options('nbcore_header_middle_hover_color');
        $bot_padding = cleopa_get_options('nbcore_bot_section_padding');
        $bot_bg = cleopa_get_options('nbcore_header_bot_bg');
        $bot_color = cleopa_get_options('nbcore_header_bot_color');
		$bot_hover_color = cleopa_get_options('nbcore_header_bot_hover_color');
        $menu_bg = cleopa_get_options('nbcore_header_mainmn_bg');
        $menu_color = cleopa_get_options('nbcore_header_mainmn_color');
        $menu_bor = cleopa_get_options('nbcore_header_mainmn_bor');
        $menu_bg2 = cleopa_get_options('nbcore_header_mainmnhover_bg');
        $menu_color2 = cleopa_get_options('nbcore_header_mainmnhover_color');
        $menu_bor2 = cleopa_get_options('nbcore_header_mainmnhover_bor');

        $logo_area_width = cleopa_get_options('nbcore_logo_width');
        $blog_width = cleopa_get_options('nbcore_blog_width');
        $primary_color = cleopa_get_options('nbcore_primary_color');
        $secondary_color = cleopa_get_options('nbcore_secondary_color');
        $divider_color = cleopa_get_options('nbcore_divider_color');
 
        $heading_font_array = explode(",", cleopa_get_options('heading_font_family'));
        $heading_family = end($heading_font_array);
        $heading_font_style = explode(",", cleopa_get_options('heading_font_style'));
        $heading_weight = end($heading_font_style);
        $heading_color = cleopa_get_options('nbcore_heading_color');

        $heading_base_size = cleopa_get_options('heading_base_size');

        $body_family_array = explode(",", cleopa_get_options('body_font_family'));
        $body_family = end($body_family_array);
        $body_style_array = explode(",", cleopa_get_options('body_font_style'));
        $body_weight = end($body_style_array);
        $body_color = cleopa_get_options('nbcore_body_color');
        $meta_color = cleopa_get_options('nbcore_meta_color');
        $body_size = cleopa_get_options('body_font_size');

        $link_color = cleopa_get_options('nbcore_link_color');
        $link_hover_color = cleopa_get_options('nbcore_link_hover_color');

        $blog_sidebar = cleopa_get_options('nbcore_blog_sidebar');
        $page_title_padding = cleopa_get_options('nbcore_page_title_padding');
        $page_title_color = cleopa_get_options('nbcore_page_title_color');
        $page_title_img = cleopa_get_options('nbcore_page_title_image');
		if ($page_title_img != '') {
			$page_title_img = wp_get_attachment_image_src( $page_title_img, 'full', false );
			$page_title_img = $page_title_img[0];
		}

        $wc_content_width = cleopa_get_options('nbcore_shop_content_width');
        $shop_sidebar = cleopa_get_options('nbcore_shop_sidebar');
        $loop_columns = cleopa_get_options('nbcore_loop_columns');
        $pd_details_sidebar = cleopa_get_options('nbcore_pd_details_sidebar');
        $pd_details_width = cleopa_get_options('nbcore_pd_details_width');
        $pd_images_width = cleopa_get_options('nbcore_pd_images_width');

        $pb_bg = cleopa_get_options('nbcore_pb_background');
        $pb_bg_hover = cleopa_get_options('nbcore_pb_background_hover');
        $pb_text = cleopa_get_options('nbcore_pb_text');
        $pb_text_hover = cleopa_get_options('nbcore_pb_text_hover');
        $pb_border = cleopa_get_options('nbcore_pb_border');
        $pb_border_hover = cleopa_get_options('nbcore_pb_border_hover');
        $sb_bg = cleopa_get_options('nbcore_sb_background');
        $sb_bg_hover = cleopa_get_options('nbcore_sb_background_hover');
        $sb_text = cleopa_get_options('nbcore_sb_text');
        $sb_text_hover = cleopa_get_options('nbcore_sb_text_hover');
        $sb_border = cleopa_get_options('nbcore_sb_border');
        $sb_border_hover = cleopa_get_options('nbcore_sb_border_hover');
        $button_padding = cleopa_get_options('nbcore_button_padding');
        $button_border_radius = cleopa_get_options('nbcore_button_border_radius');
        $button_border_width = cleopa_get_options('nbcore_button_border_width');

        $footer_top_heading = cleopa_get_options('nbcore_footer_top_heading');
        $footer_top_color = cleopa_get_options('nbcore_footer_top_color');
        $footer_top_hover_color = cleopa_get_options('nbcore_footer_top_hover_color');
        $footer_top_bg = cleopa_get_options('nbcore_footer_top_bg');
        $footer_bot_heading = cleopa_get_options('nbcore_footer_bot_heading');
        $footer_bot_color = cleopa_get_options('nbcore_footer_bot_color');
        $footer_bot_hover_color = cleopa_get_options('nbcore_footer_bot_hover_color');
        $footer_bot_bg = cleopa_get_options('nbcore_footer_bot_bg');
        $footer_abs_bg = cleopa_get_options('nbcore_footer_abs_bg');
        $footer_abs_color = cleopa_get_options('nbcore_footer_abs_color');
        $footer_abs_hover_color = cleopa_get_options('nbcore_footer_abs_hover_color');

        $blog_title_size = cleopa_get_options('nbcore_blog_single_title_size');
        $page_title_size = cleopa_get_options('nbcore_page_title_size');

        $footer_abs_padding = cleopa_get_options('nbcore_footer_abs_padding');

        $page_content_width = cleopa_get_options('page_content_width');
        $page_sidebar = cleopa_get_options('page_sidebar');
        $page_bg = wp_get_attachment_image_src(get_post_meta(get_the_ID(), 'page_bg_image', true), 'full');
        $page_bg_color = get_post_meta(get_the_ID(), 'page_bg_color', true);


        $css = "";

        if($body_family_array[0] === 'custom') {
            $body_custom_font_url = array_slice($body_family_array, 1, -1);
            $css .= "
            @font-face {
                font-family: '" . end($body_family_array) . "';            
            ";

            foreach($body_custom_font_url as $url) {
                $css .= "
                src: url('" . $url . "');
                ";
            }

            $css .= "
            }
            ";
        }
        if($heading_font_array[0] === 'custom') {
            $heading_custom_font_url = array_slice($heading_font_array, 1, -1);
            $css .= "
            @font-face {
                font-family: '" . end($heading_font_array) . "';            
            ";

            foreach($heading_custom_font_url as $url) {
                $css .= "
                src: url('" . $url . "');
                ";
            }

            $css .= "
            }
            ";
        }
        $css .= "
            body {
                background: " . esc_attr($bg_color) . ";
                font-family: " . esc_attr($body_family) . "; 
                font-weight: " . esc_attr($body_weight) . ";
                font-size: " . esc_attr($body_size) . "px;
        ";
        if (in_array("italic", $body_style_array)) {
            $css .= "
                font-style: italic;
            ";
        }
        if (in_array("underline", $body_style_array)) {
            $css .= "
                text-decoration: underline;
            ";
        }
        if (in_array("uppercase", $body_style_array)) {
            $css .= "
                text-transform: uppercase;
            ";
        }
        $css .= "
            }
            .nb-page-title-wrap,
            .single-blog .entry-author,
            .products .list-type-wrap,
            .shop-main.accordion-tabs .accordion-title-wrap,
            .woocommerce .woocommerce-message,
            .woocommerce .woocommerce-info,
            .woocommerce .woocommerce-error,
            .woocommerce-page .woocommerce-message,
            .woocommerce-page .woocommerce-info,
            .woocommerce-page .woocommerce-error,
            .cart-layout-2 .cart-totals-wrap,
            .blog.style-2 .post .entry-content,
            .comments-area,
            .blog .post .entry-cat a
            {
                background-color: " . esc_attr($inner_bg) . ";
            }
            .products.list-type .product .list-type-wrap .product-image:before {
                border-right-color: " . esc_attr($inner_bg) . ";
            }
            .main-logo {
                width: " . esc_attr($logo_area_width) . "px;
            }
            a,
            .footer-top-section a:hover,
            .footer-top-section .widget ul li a:hover,
            .footer-bot-section a:hover,
            .footer-bot-section .widget ul li a:hover{
                color: " . esc_attr($link_color) . ";
            }
            a:hover, a:focus, a:active,
			h1 > a:hover, h2 > a:hover, h3 > a:hover, h4 > a:hover, h5 > a:hover, h6 > a:hover,
			.widget ul li a:hover, .woocommerce-breadcrumb a:hover, .nb-social-icons > a:hover, .wc-tabs > li:not(.active) a:hover, 
			.shop-main.accordion-tabs .accordion-title-wrap:not(.ui-state-active) a:hover, .nb-account-dropdown a:hover,
			.entry-meta .byline a:hover, .comments-link a:hover,
			.nb-page-title-wrap a:hover,
            .widget ul li a:hover,
			.main-desktop-navigation .nb-navbar .menu-item:hover > a,
			.main-desktop-navigation .nb-navbar .menu-item > a:hover,
			.site-header .icon-header-wrap .nb-account-dropdown a:hover,
			.site-header .mini-cart-wrap .mini_cart_item a:hover,
			.woocommerce-MyAccount-navigation-link a:hover,
			.woocommerce-MyAccount-navigation-link.is-active a{
                color: " . esc_attr($link_hover_color) . ";
            }
            .button, .nb-primary-button, .post-password-form input[type='submit'],.type-post .entry-block:before {
                color: " . esc_attr($pb_text) . " !important;
                background-color: " . esc_attr($pb_bg) . ";
                border-color: " . esc_attr($pb_border) . ";
            }
            .button:hover, .nb-primary-button:hover, .post-password-form input[type='submit']:hover, .button:focus, .nb-primary-button:focus {
                color: " . esc_attr($pb_text_hover) . ";
                background-color: " . esc_attr($pb_bg_hover) . ";
                border-color: " . esc_attr($pb_border_hover) . ";
            }
			.type-post:not(.sticky) .entry-block:after{
				border-color: " . esc_attr($pb_text) . ";
			}
            .nb-secondary-button {
                color: " . esc_attr($sb_text) . ";
                background-color: " . esc_attr($sb_bg) . ";
                border-color: " . esc_attr($sb_border) . ";
            }
            .nb-secondary-button:hover, .nb-secondary-button:focus {
                color: " . esc_attr($sb_text_hover) . ";
                background-color: " . esc_attr($sb_bg_hover) . ";
                border-color: " . esc_attr($sb_border_hover) . ";
            }
            .list-type .add_to_cart_button, .nb-primary-button, .nb-secondary-button, .single_add_to_cart_button, .post-password-form input[type='submit']{
                padding-left: " . esc_attr($button_padding) . "px;
                padding-right: " . esc_attr($button_padding) . "px;
                border-width: " . esc_attr($button_border_width) . "px;
            ";
        if ($button_border_radius) {
            $css .= "
                border-radius: " . esc_attr($button_border_radius) . "px;
            ";
        } else {
            $css .= "
                border-radius: 0px;
            ";
        }
        $css .= "
            }
            body,
			.main-desktop-navigation .nb-navbar .menu-item > a,
			.site-header .icon-header-wrap .nb-account-dropdown a,
			.site-header .mini-cart-wrap .mini_cart_item a,
            .widget ul li a,
            .woocommerce-breadcrumb a,
			.woocommerce-MyAccount-navigation-link a,
            .nb-social-icons > a,
            .wc-tabs > li:not(.active) a,
            .shop-main.accordion-tabs .accordion-title-wrap:not(.ui-state-active) a,
            .nb-account-dropdown a,
            .header-account-wrap .not-logged-in,
            .mid-inline .nb-account-dropdown a, 
            .mid-inline .mini-cart-section span, 
            .mid-inline .mini-cart-section a, 
            .mid-inline .mini-cart-section strong{
                color: " . esc_attr($body_color) . ";
            }
            .entry-meta,
            .entry-meta .byline a,
            .comments-link a,
			.entry-cat{
                color: " . esc_attr($meta_color) . ";
            }
            h1 {
                font-size: " . esc_attr(intval($heading_base_size * 2.074)) . "px;
            }
            h2 {
                font-size: " . esc_attr(intval($heading_base_size * 1.728)) . "px;
            }
            h3 {
                font-size: " . esc_attr(intval($heading_base_size * 1.44)) . "px;
            }
            h4 {
                font-size: " . esc_attr(intval($heading_base_size * 1.2)) . "px;
            }
            h5 {
                font-size: " . esc_attr(intval($heading_base_size * 1)) . "px;
            }
            h6 {
                font-size: " . esc_attr(intval($heading_base_size * 0.833)) . "px;
            }
            h1, h2, h3, h4, h5, h6,
            h1 > a, h2 > a, h3 > a, h4 > a, h5 > a, h6 > a,
            .entry-title > a,
            .woocommerce-Reviews .comment-reply-title {
                font-family: " . esc_attr($heading_family) . "; 
                font-weight: " . esc_attr($heading_weight) . ";
                color: " . esc_attr($heading_color) . ";
        ";
        if (in_array("italic", $heading_font_style)) {
            $css .= "
                font-style: italic;
            ";
        }
        if (in_array("underline", $heading_font_style)) {
            $css .= "
                text-decoration: underline;
            ";
        }
        if (in_array("uppercase", $heading_font_style)) {
            $css .= "
                text-transform: uppercase;
            ";
        }
        //TODO after make inline below woocommerce.css remove these !important
        //TODO postMessage font-size .header-top-bar a
        $css .= "
            }
            .site-header .top-section-wrap {
                padding-top: " . esc_attr($top_padding) . "px;
                padding-bottom: " . esc_attr($top_padding) . "px;
                background-color: " . esc_attr($top_bg) . ";
            }
			.site-header .top-section-wrap, .site-header .top-section-wrap a{
				color: " . esc_attr($top_color) . ";
			}
			.site-header .top-section-wrap a:hover{
				color: " . esc_attr($top_hover_color) . ";
			}
            .top-section-wrap .nb-header-sub-menu a {
                color: " . esc_attr($top_color) . ";
            }
            .top-section-wrap .nb-header-sub-menu .sub-menu {
                background-color: " . esc_attr($top_bg) . ";
            }
            .site-header .middle-section-wrap {
                padding-top: " . esc_attr($middle_padding) . "px;
                padding-bottom: " . esc_attr($middle_padding) . "px;
                background-color: " . esc_attr($middle_bg) . ";
            }
			.site-header .middle-section-wrap, .site-header .middle-section-wrap a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-navbar > .menu-item > a,
			.site-header.creative.header-mobile .bot-section-wrap .bot-section a.mobile-toggle-button,
			.site-header.plain.header-mobile .bot-section-wrap .bot-section a.mobile-toggle-button{
				color: " . esc_attr($middle_color) . ";
			}
			.site-header .middle-section-wrap a:hover,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-navbar > .menu-item:hover > a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-navbar > .menu-item.current-menu-parent > a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-navbar > .menu-item.current-menu-item > a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item:hover > a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item.current-menu-parent > a,
			.site-header .middle-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item.current-menu-item > a,
			.site-header .middle-section-wrap .header-account-wrap:hover > i
			.site-header .middle-section-wrap .header-cart-wrap:hover .nb-cart-section,
            .site-header .middle-section-wrap .header-cart-wrap .nb-cart-section:hover,
            .functional-food-home .site-header.site-header-customize.header-desktop .middle-section-wrap .nb-navbar > li.menu-item.current_page_item > a,
			.site-header.creative.header-mobile .bot-section-wrap .bot-section a.mobile-toggle-button:hover,
			.site-header.plain.header-mobile .bot-section-wrap .bot-section a.mobile-toggle-button:hover{
				color: " . esc_attr($middle_hover_color) . ";
			}
			.site-header .middle-section-wrap .header-cart-wrap .nb-cart-section .counter{
				background-color: " . esc_attr($middle_hover_color) . ";
				color: " . esc_attr($middle_bg) . ";
			}
			.site-header:not(.header-mobile) .middle-section-wrap .middle-section .nb-navbar, .site-header .middle-section-wrap .middle-section .header-account-wrap, .site-header .middle-section-wrap .middle-section .header-cart-wrap {
				margin-top: -" . esc_attr($middle_padding) . "px;
				margin-bottom: -" . esc_attr($middle_padding) . "px;
			}
			.site-header .middle-section-wrap .middle-section .nb-navbar > li > a, .site-header .middle-section-wrap .middle-section .header-account-wrap, .site-header .middle-section-wrap .middle-section .header-cart-wrap {
				padding-top: " . esc_attr($middle_padding) . "px;
				padding-bottom: " . esc_attr($middle_padding) . "px;
			}
            .site-header .bot-section-wrap {
                padding-top: " . esc_attr($bot_padding) . "px;
                padding-bottom: " . esc_attr($bot_padding) . "px;
                background-color: " . esc_attr($bot_bg) . ";           
            }
			.site-header .bot-section-wrap, .site-header .bot-section-wrap a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-navbar > .menu-item > a{
				color: " . esc_attr($bot_color) . ";
			}
			.site-header .bot-section-wrap a:hover,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-navbar > .menu-item:hover > a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-navbar > .menu-item.current-menu-parent > a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-navbar > .menu-item.current-menu-item > a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item:hover > a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item.current-menu-parent > a,
			.site-header .bot-section-wrap .main-desktop-navigation .nb-header-sub-menu > .menu-item.current-menu-item > a,
			.site-header .bot-section-wrap .header-account-wrap:hover > i,
			.site-header .bot-section-wrap .header-cart-wrap:hover .nb-cart-section,
			.site-header .bot-section-wrap .header-cart-wrap .nb-cart-section:hover{
				color: " . esc_attr($bot_hover_color) . ";
			}
			.site-header .bot-section-wrap .header-cart-wrap .nb-cart-section .counter{
				background-color: " . esc_attr($bot_hover_color) . ";
				color: " . esc_attr($bot_bg) . ";
			}
			.site-header:not(.header-mobile) .bot-section-wrap .bot-section .nb-navbar, .site-header .bot-section-wrap .bot-section .header-account-wrap, .site-header .bot-section-wrap .bot-section .header-cart-wrap {
				margin-top: -" . esc_attr($bot_padding) . "px;
				margin-bottom: -" . esc_attr($bot_padding) . "px;
			}
			.site-header .bot-section-wrap .bot-section .nb-navbar > li > a, .site-header .bot-section-wrap .bot-section .header-account-wrap, .site-header .bot-section-wrap .bot-section .header-cart-wrap {
				padding-top: " . esc_attr($bot_padding) . "px;
				padding-bottom: " . esc_attr($bot_padding) . "px;
			}
            .nb-navbar .menu-item-has-children > a span:after,
            .icon-header-section .nb-cart-section,
            .nb-navbar .menu-item a,
            .nb-navbar .sub-menu > .menu-item:not(:last-child),
            .nb-header-sub-menu .sub-menu > .menu-item:not(:last-child),
            .widget .widget-title,
            .blog .classic .post .entry-footer,
            .single-post .single-blog .entry-footer,
            .nb-social-icons > a,
            .single-blog .entry-author-wrap,
            .shop-main:not(.wide) .single-product-wrap .product_meta,
            .shop-main.accordion-tabs .accordion-item .accordion-title-wrap,
            .shop-main.horizontal-tabs .wc-tabs-wrapper,
            .shop_table thead th,
            .shop_table th,
            .shop_table td,
            .mini-cart-wrap .total,
            .icon-header-wrap .nb-account-dropdown ul li:not(:last-of-type) a,
            .widget tbody th, .widget tbody td,
            .widget ul > li:not(:last-of-type),
			.widget .sub-menu,
            .blog .post .entry-image .entry-cat,
            .comment-list .comment,
            .paging-navigation.pagination-style-1 .page-numbers.current,
            .woocommerce-pagination.pagination-style-1 .page-numbers.current,
			.page-links.pagination-style-1 > span,
			.page-links.pagination-style-1 > a:hover,
			.blog .classic .post:not(.sticky) .entry-content,
			.blog .classic .post:not(.sticky) .entry-image,
			.single-blog .entry-content,
            .single-blog .entry-image,
            .loading.demo7 #loading-center #loading-center-absolute .object,
            .loading.demo3 #loading-center #loading-center-absolute .object,
			.woocommerce-account .woocommerce .woocommerce-MyAccount-navigation .woocommerce-MyAccount-navigation-link,
			.woocommerce-account .woocommerce .woocommerce-MyAccount-content,
			.woocommerce-account .woocommerce .woocommerce-MyAccount-content:before{
                border-color: " . esc_attr($divider_color) . ";
            }
            .loading.demo14 #loading-center #loading-center-absolute .object{
                border-left-color: " . esc_attr($primary_color) . ";
                border-right-color: " . esc_attr($primary_color) . ";
            }
            .loading.demo15 #loading-center #loading-center-absolute .object{
                border-left-color: " . esc_attr($primary_color) . ";
                border-top-color: " . esc_attr($primary_color) . ";
            }
            @media (max-width: 767px) {
                .shop_table.cart {
                    border: 1px solid " . esc_attr($divider_color) . ";
                }
                .shop_table.cart td {
                    border-bottom: 1px solid " . esc_attr($divider_color) . ";
                }
            }
			article.sticky .entry-content,
            .product .product-image .onsale,
            .wc-tabs > li.active,
            .product .onsale.sale-style-2 .percent,
            .wc-tabs-wrapper .woocommerce-Reviews #review_form_wrapper .comment-respond,
            .site-header.mid-stack .main-navigation .nb-navbar > .menu-item:hover,
            .shop-main.accordion-tabs .accordion-item .accordion-title-wrap.ui-accordion-header-active,
            .widget .tagcloud a,
            .footer-top-section .widget .tagcloud a,
            .footer-bot-section .widget .tagcloud a,.mini-cart-wrap .buttons .button,
            .nbt-brands .aio-icon:hover,.nb-input-group .search-field:focus,.widget .nb-input-group .search-field:focus,
            .cart-notice-wrap .cart-notice{
                border-color: " . esc_attr($primary_color) . ";
            }
            .nbt-product.grid-type-wrap2 .product-action a.button,.nbt-product.grid-type-wrap2 .product-action .button a{
                border: 1px solid " . esc_attr($primary_color) . " !important;
            }
            .widget .widget-title:before,
            .loading #loading-center #loading-center-absolute #object,
            .loading #loading-center #loading-center-absolute .object,
            .loading #loading-center .object-one,
            .loading #loading-center .object-two,
            .paging-navigation.pagination-style-2 .current,
            .product .onsale.sale-style-1,
            .woocommerce-pagination.pagination-style-2 span.current,
			.page-links.pagination-style-2 > span,
			.page-links.pagination-style-2 > a:hover,
            .shop-main.right-dots .flickity-page-dots .dot,
            .wc-tabs-wrapper .form-submit input,
            .nb-input-group .search-button button,
            .widget .tagcloud a:hover,
            .nb-back-to-top-wrap a:hover,
            .single-product-wrap .yith-wcwl-add-to-wishlist,
            .swiper-pagination-bullet.swiper-pagination-bullet-active,
            .nbt-product.grid-type-wrap2 .product-action .button a:hover,.faq-form input[type='submit'],#secondary .tagcloud a:hover,
            .nbt-product.grid-type-wrap2 .product-action a.button:hover,.mini-cart-wrap .buttons .button,.nb-back-to-top-wrap:hover a.light, .nb-back-to-top-wrap:hover a.dark,
			.filters-button-group .filter-btn.is-checked, .filters-button-group .filter-btn:hover{
                background-color: " . esc_attr($primary_color) . ";
            }
            .nbt-product.grid-type-wrap2 .product-action .button a,.nbt-product.grid-type-wrap2 .product-action a.button,.nbt-brands .aio-icon:hover{
                color: " . esc_attr($primary_color) . " !important;            
            }
            .product .star-rating:before,
            .product .star-rating span,
            .single-product-wrap .price ins,
            .single-product-wrap .price > span.amount,
            .wc-tabs > li.active a,
            .wc-tabs > li.active a:hover,
            .wc-tabs > li.active a:focus,
            .wc-tabs .ui-accordion-header-active a,
            .wc-tabs .ui-accordion-header-active a:focus,
            .wc-tabs .ui-accordion-header-active a:hover,
            .shop-main.accordion-tabs .ui-accordion-header-active:after,
            .shop_table .cart_item td .amount,
            .cart_totals .order-total .amount,
            .shop_table.woocommerce-checkout-review-order-table .order-total .amount,
            .woocommerce-order .woocommerce-thankyou-order-received,
            .woocommerce-order .woocommerce-table--order-details .amount,
            .paging-navigation.pagination-style-1 .current,
            .woocommerce-pagination.pagination-style-1 .page-numbers.current,
			.page-links.pagination-style-1 > span,.nbt-product .product-content .price .amount,.nb_testimonials .nb_testimonial-item .nb_testimonial-box2:before,
			.page-links.pagination-style-1 > a:hover,.product_list_widget span.amount,.widget .nb-input-group .search-button button:hover,
			.post a.more-link,.product .onsale.sale-style-1,.nb-input-group .search-button button:hover,
			.type-post.sticky .entry-content:before{
                color: " . esc_attr($primary_color) . ";                
            }
			.post a.more-link:hover{
                color: " . esc_attr($secondary_color) . ";                
            }
            .nb-page-title-wrap {
                padding-top: " . esc_attr($page_title_padding) . "px;
                padding-bottom: " . esc_attr($page_title_padding) . "px;"
				. ($page_title_img != '' ? "background-image: url(" . esc_url($page_title_img) . ");
				background-size: cover;
				background-position: 50% 50%;" : "")
            . "}
            .nb-page-title-wrap a, .nb-page-title-wrap h2, .nb-page-title-wrap nav {
                color: " . esc_attr($page_title_color) . ";
            }            
            .nb-page-title-wrap h2 {
                font-size: " . esc_attr($page_title_size) . "px;
            }
            .woocommerce-page.wc-no-sidebar #primary {
                width: 100%;
            }
            .shop-main .products.grid-type .product:nth-child(" . esc_attr($loop_columns) . "n + 1) {
                clear: both;
            }                                   
        ";
        $css .= "
            .footer-top-section {                
                background-color: " . esc_attr($footer_top_bg) . ";
            }
            .footer-top-section h1,
            .footer-top-section h2,
            .footer-top-section h3,
            .footer-top-section h4,
            .footer-top-section h5,
            .footer-top-section h6,
            .footer-top-section .widget-title a{
                color: " . esc_attr($footer_top_heading) . ";
            }
            .footer-top-section,
            .footer-top-section a,
            .footer-top-section .widget ul li a{
                color: " . esc_attr($footer_top_color) . ";
            }
            .footer-top-section a:hover,
            .footer-top-section .widget ul li a:hover{
                color: " . esc_attr($footer_top_hover_color) . ";
            }
            .footer-top-section .widget .tagcloud a{
                border-color: " . esc_attr($footer_top_color) . ";
            }
            .footer-top-section .widget .tagcloud a:hover{
                border-color: " . esc_attr($footer_top_hover_color) . ";
            }
            .footer-bot-section{
                background-color: " . esc_attr($footer_bot_bg) . ";
            }
            .footer-bot-section h1,
            .footer-bot-section h2,
            .footer-bot-section h3,
            .footer-bot-section h4,
            .footer-bot-section h5,
            .footer-bot-section h6,
            .footer-bot-section .widget-title a{
                color: " . esc_attr($footer_bot_heading) . ";
            }
            .footer-bot-section,
            .footer-bot-section a,
            .footer-bot-section .widget ul li a{
                color: " . esc_attr($footer_bot_color) . ";
            }
            .footer-bot-section a:hover,
            .footer-bot-section .widget ul li a:hover{
                color: " . esc_attr($footer_bot_hover_color) . ";
            }
            .footer-bot-section .widget .tagcloud a{
                border-color: " . esc_attr($footer_bot_color) . ";
            }
            .footer-bot-section .widget .tagcloud a:hover{
                border-color: " . esc_attr($footer_bot_hover_color) . ";
            }
            .footer-abs-section{
                color: " . esc_attr($footer_abs_color) . ";
                background-color: " . esc_attr($footer_abs_bg) . ";
                padding-top: " . esc_attr($footer_abs_padding) . "px;
                padding-bottom: " . esc_attr($footer_abs_padding) . "px;
            }
            .footer-abs-section, .footer-abs-section a, .footer-abs-section p {
                color: " . esc_attr($footer_abs_color) . ";
            }
			.footer-abs-section a:hover,.footer-abs-section .footer-abs-middle a {
                color: " . esc_attr($footer_abs_hover_color) . ";
            }
            .single-blog .nb-page-title .entry-title,
            .single-blog .entry-title{
                font-size: " . esc_attr($blog_title_size) . "px;
            }
            ";
        if ($page_bg_color) {
            $css .= "
                .page #site-wrapper {
                    background-color: " . esc_attr($page_bg_color) . ";
                }
                ";
        }
        if ($page_bg[0]) {
            $css .= "
                .page #site-wrapper {
                    background: url(" . esc_url($page_bg[0]) . ") repeat center center / cover; 
                }
            ";
        }
        $css .= "
            @media (min-width: 768px) {
                .shop-main:not(.wide) .single-product-wrap .product-image {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 " . esc_attr($pd_images_width) . "%;
                    flex: 0 0 " . esc_attr($pd_images_width) . "%;                   
                    max-width: " . esc_attr($pd_images_width) . "%;
                }
                .shop-main:not(.wide) .single-product-wrap .entry-summary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 calc(100% - " . esc_attr($pd_images_width) . "%);
                    flex: 0 0 calc(100% - " . esc_attr($pd_images_width) . "%);                   
                    max-width: calc(100% - " . esc_attr($pd_images_width) . "%);
                }
            }
            @media (min-width: 992px) {
        ";

        if ('no-sidebar' !== $blog_sidebar) {
            $css .= "            
                .site-content .blog #primary,
                .site-content .single-blog #primary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 " . esc_attr($blog_width) . "%;
                    flex: 0 0 " . esc_attr($blog_width) . "%;
                    max-width: " . esc_attr($blog_width) . "%;
                } 
                .site-content .blog #secondary,
                .site-content .single-blog #secondary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 calc(100% - " . esc_attr($blog_width) . "%);
                    flex: 0 0 calc(100% - " . esc_attr($blog_width) . "%);
                    max-width: calc(100% - " . esc_attr($blog_width) . "%);
                }                                  
            ";
        }
        if ('left-sidebar' == $blog_sidebar) {
            $css .= "
                .single-blog #primary, .blog #primary {
                    order: 2;
                }
                .single-blog #secondary, .blog #secondary {
                    padding-right: 15px;
                }
            ";
        } elseif ('right-sidebar' == $blog_sidebar) {
            $css .= "
                .single-blog #secondary, .blog #secondary {
                    padding-left: 15px;
                }
            ";
        }
        if ('left-sidebar' == $shop_sidebar) {
            $css .= "
                .archive.woocommerce .shop-main {
                    order: 2;
                }
                .archive.woocommerce #secondary {
                    padding-right: 15px;
                    padding-left: 15px;
                }
            ";
        } elseif('right-sidebar' == $shop_sidebar) {
            $css .= "
                .archive.woocommerce #secondary {
                    padding-left: 15px;
                    padding-right: 15px;
                }
            ";
        }

        if ('left-sidebar' == $pd_details_sidebar) {
            $css .= "
                .single-product .shop-main {
                    order: 2;
                }
                .single-product #secondary {
                    padding-right: 15px;
                }
            ";
        } elseif('right-sidebar' == $shop_sidebar) {
            $css .= "
                .single-product #secondary {
                    padding-left: 15px;
                }
            ";
        }
        if ('no-sidebar' !== $pd_details_sidebar) {
            $css .= "
                .single-product.wc-pd-has-sidebar .shop-main {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 " . esc_attr($pd_details_width) . "%;
                    flex: 0 0 " . esc_attr($pd_details_width) . "%;
                    max-width: " . esc_attr($pd_details_width) . "%;
                }
                .single-product #secondary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 calc(100% - " . esc_attr($pd_details_width) . "%);
                    flex: 0 0 calc(100% - " . esc_attr($pd_details_width) . "%);
                    max-width: calc(100% - " . esc_attr($pd_details_width) . "%);
                }
            ";
        }
        // TODO check this for tag ... ?
        if ('no-sidebar' !== $shop_sidebar) {
            $css .= "
                .archive.woocommerce.wc-has-sidebar .shop-main{
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 " . esc_attr($wc_content_width) . "%;
                    flex: 0 0 " . esc_attr($wc_content_width) . "%;
                    max-width: " . esc_attr($wc_content_width) . "%;
                }
                .archive.woocommerce.wc-has-sidebar #secondary{
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 calc(100% - " . esc_attr($wc_content_width) . "%);
                    flex: 0 0 calc(100% - " . esc_attr($wc_content_width) . "%);
                    max-width: calc(100% - " . esc_attr($wc_content_width) . "%);
                }
            ";
        } else {
            $css .= "
                .site-content .shop-main {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 100%;
                    flex: 0 0 100%;
                    max-width: 100%;
                }
            ";
        }
        if ('full-width' !== $page_sidebar) {
            $css .= "            
                .page #primary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 " . esc_attr($page_content_width) . "%;
                    flex: 0 0 " . esc_attr($page_content_width) . "%;                 
                    max-width: " . esc_attr($page_content_width) . "%;
                }
                .page #secondary {
                    -webkit-box-flex: 0;
                    -ms-flex: 0 0 calc(100% - " . esc_attr($page_content_width) . "%);
                    flex: 0 0 calc(100% - " . esc_attr($page_content_width) . "%);              
                    max-width: calc(100% - " . esc_attr($page_content_width) . "%);
                }
            ";
        }

        $css .= "
            }
        ";

        return $css;
    }

    public function print_embed_style()
    {
        if (class_exists( 'NetbaseCustomizeClass', false ) ) {
            if(!empty(get_transient('change_customize_css'))) {
                if(get_transient('change_customize_css')==1) {
                    set_transient('change_customize_css', 0, NBT_TIMEOUT_TRANSIENT_CUSTOMIZE);
                    $style = $this->get_embed_style();
                    $style = preg_replace('#/\*.*?\*/#s', '', $style);
                    $style = preg_replace('/\s*([{}|:;,])\s+/', '$1', $style);
                    $style = preg_replace('/\s\s+(.*)/', '$1', $style);
                    if(get_option('customize_save_css')==false) {
                        add_option( 'customize_save_css', $style, '', 'yes' );
                    } else {
                        update_option( 'customize_save_css', $style );
                    }
                    $cp = new NetbaseCustomizeClass();
                    $cp->save_css_customize();
                }
            }
        }
        $my_transient = get_transient('current_load_css');
        if(empty($my_transient) || !$this->is_plugin_active_byme('nb-fw/nb-fw.php')) {
            $this->print_style_head();
        } else {
            if($my_transient==NBT_LOAD_CUSTOMIZE_FROM_HEAD) {
                $this->print_style_head();
            } else {
                if($my_transient==NBT_LOAD_CUSTOMIZE_FROM_CSS_FILE && file_exists(NBT_REAL_PATH_TEMPLATE . NBT_CSS_CUSTOMIZE_PATH . NBT_CSS_CUSTOMIZE_NAME)) {
                    wp_enqueue_style('customize', get_template_directory_uri() . NBT_CSS_CUSTOMIZE_PATH . NBT_CSS_CUSTOMIZE_NAME, array(), Cleopa_VER);
                } else {
                    set_transient('current_load_css', NBT_LOAD_CUSTOMIZE_FROM_HEAD, NBT_TIMEOUT_TRANSIENT_CUSTOMIZE);
                    $this->print_style_head();
                }
            }
        }
    }
    public function print_style_head() {
        $style = $this->get_embed_style();
    
        $style = preg_replace('#/\*.*?\*/#s', '', $style);
        $style = preg_replace('/\s*([{}|:;,])\s+/', '$1', $style);
        $style = preg_replace('/\s\s+(.*)/', '$1', $style);
    
        wp_add_inline_style('cleopa_front_style', $style);
    }
    function is_plugin_active_byme( $plugin ) {
        return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
    }

    public static function filter_fonts($font)
    {
        $font_args = explode(",", cleopa_get_options($font));
        if($font_args[0] === 'google') {
            self::handle_google_font($font_args[1]);
        } elseif($font_args[0] === 'custom') {
            self::handle_custom_font($font_args[1]);
        } elseif($font_args[0] === 'standard') {
            self::handle_standard_font($font_args[1]);
        }
    }

    public static function handle_google_font($font_name)
    {
        $font_subset = 'latin,latin-ext';
        $font_families = array();
        $google_fonts = Cleopa_Helper::google_fonts();
        $font_parse = array();


        $font_weight = $google_fonts[$font_name];
        $font_families[$font_name] = isset($font_families[$font_name]) ? array_unique(array_merge($font_families[$font_name], $font_weight)) : $font_weight;

        foreach ($font_families as $font => $font_weight) {
            $font_parse[] = $font . ':' . implode(',', $font_weight);
        }

        if (cleopa_get_options('subset_cyrillic')) {
            $font_subset .= ',cyrillic,cyrillic-ext';
        }
        if (cleopa_get_options('subset_greek')) {
            $font_subset .= ',greek,greek-ext';
        }
        if (cleopa_get_options('subset_vietnamese')) {
            $font_subset .= ',vietnamese';
        }

        $query_args = array(
            'family' => urldecode(implode('|', $font_parse)),
            'subset' => urldecode($font_subset),
        );

        $font_url = add_query_arg($query_args, 'https://fonts.googleapis.com/css');

        $enqueue = esc_url_raw($font_url);

        wp_enqueue_style('cleopa-google-fonts', $enqueue);
    }

    public static function google_fonts_url()
    {
        $gg_font_arr = array();
        $gg_font_parse = array();
        $google_fonts = Cleopa_Helper::google_fonts();
        $gg_subset = 'latin,latin-ext';

        $body_font = explode(',', cleopa_get_options('body_font_family'));
        $heading_font = explode(',', cleopa_get_options('heading_font_family'));

        if($body_font[0] === 'google') {
            $body_name = $body_font[1];
            $body_weight = $google_fonts[$body_name];
            $gg_font_arr[$body_name] = isset($gg_font_arr[$body_name]) ? array_unique(array_merge($gg_font_arr[$body_name], $body_weight)) : $body_weight;
        }

        if($heading_font[0] === 'google') {
            $heading_name = $heading_font[1];
            $heading_weight = $google_fonts[$heading_name];
            $gg_font_arr[$heading_name] = isset($gg_font_arr[$heading_name]) ? array_unique(array_merge($gg_font_arr[$heading_name], $heading_weight)) : $heading_weight;
        }

        if(!empty($gg_font_arr)) {
            foreach ($gg_font_arr as $gg_font_name => $gg_font_weight) {
                $gg_font_parse[] = $gg_font_name . ':' . implode(',', $gg_font_weight);
            }

            if (cleopa_get_options('subset_cyrillic')) {
                $gg_subset .= ',cyrillic,cyrillic-ext';
            }
            if (cleopa_get_options('subset_greek')) {
                $gg_subset .= ',greek,greek-ext';
            }
            if (cleopa_get_options('subset_vietnamese')) {
                $gg_subset .= ',vietnamese';
            }

            $query_args = array(
                'family' => urldecode(implode('|', $gg_font_parse)),
                'subset' => urldecode($gg_subset),
            );

            $font_url = add_query_arg($query_args, 'https://fonts.googleapis.com/css');

            $enqueue = esc_url_raw($font_url);

            wp_enqueue_style('cleopa-google-fonts', $enqueue);
        }
    }

    public static function upload_mimes($t)
    {
        // Add supported font extensions and MIME types.
        $t['eot'] = 'application/vnd.ms-fontobject';
        $t['otf'] = 'application/x-font-opentype';
        $t['ttf'] = 'application/x-font-ttf';
        $t['woff'] = 'application/font-woff';
        $t['woff2'] = 'application/font-woff2';

        return $t;
    }

    public static function register_required_plugins()
    {
        if(!isset(self::$plugins)) {
            self::$plugins = array(

                array(
                    'name' => 'Netbase Framework',
                    'slug' => 'nb-fw',
                    'required' => true,
                    'version' => '1.4.1',
                    'source' 	=>esc_url('http://demo9.cmsmart.net/plugins/cleopa/nb-fw.zip'),
                ),

            );
        }

        $config = array(
            'id'           => 'cleopa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '',                      // Default absolute path to bundled plugins.
            'menu'         => 'tgmpa-install-plugins', // Menu slug.
            'has_notices'  => true,                    // Show admin notices or not.
            'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
            'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => false,                   // Automatically activate plugins after installation or not.
            'message'      => '',                      // Message to output right before the plugins table.
        );

        tgmpa( self::$plugins, $config );
    }
 
    
}

new Cleopa_Core();