<?php

/**
 * Plugin Name: Dessky Responsive Slider
 * Plugin URI: http://dessky.com
 * Description: Dessky Responsive Slider is a simple and light-weight plugin for creating only one slideshow with a shortcode.
 * Version: 1.6
 * Author: Dessky.com
 * Author URI: http://dessky.com
 *
 * The Dessky Responsive Slider plugin allows users to create slides that consist of linked (to any url) images and titles.
 * The slider would then take those slides and present them as a jQuery-powered slideshow - at a chosen location within a theme, page, or post.
 *
 * @copyright 2015
 * @version 1.6
 * @author Dessky
 * @link http://dessky.com
 * @license http://www.gnu.org/licenses/gpl.html
 *
 * @package Dessky Responsive Slider
 */

/**
 * Holds the current version of the plugin.
 *
 * It is used by the dessky_update_checker() function to figure out if there is 
 * a difference between the installed plugin version number and the one saved 
 * in the database.
 */
global $dessky_version;

$dessky_version = 1.6;

add_action( 'plugins_loaded', 'dessky_update_checker' );

/* Setup the plugin. */
add_action( 'plugins_loaded', 'dessky_responsive_slider_setup' );

/* Register plugin activation hook. */
register_activation_hook( __FILE__, 'dessky_responsive_slider_activation' );

/* Register plugin activation hook. */
register_deactivation_hook( __FILE__, 'dessky_responsive_slider_deactivation' );

/* Register plugin activation hook. */
register_uninstall_hook( __FILE__, 'dessky_responsive_slider_uninstall' );

/**
 * Setup function.
 *
 */
function dessky_responsive_slider_setup() {
		
	/* Get the plugin directory URI. */
	define( 'DESSKY_RESPONSIVE_SLIDER_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

	/* Register the custom post types. */
	add_action( 'init', 'dessky_responsive_slider_register_cpt' );

	/* Register the shortcodes. */
	add_action( 'init', 'dessky_responsive_slider_register_shortcode' );

	/* Enqueue the stylesheet. */
	add_action( 'template_redirect', 'dessky_responsive_slider_enqueue_stylesheets' );

	/* Enqueue the admin stylesheet. */
	add_action( 'admin_enqueue_scripts', 'dessky_responsive_slider_enqueue_admin_stylesheets' );

	/* Enqueue the JavaScript. */
	add_action( 'template_redirect', 'dessky_responsive_slider_enqueue_scripts' );

	/* Custom post type icon. */
	add_action( 'admin_head', 'dessky_responsive_slider_cpt_icon' );

	/* Add image sizes */
	add_action( 'init', 'dessky_responsive_slider_image_sizes' );

	/* Add meta box for slides. */
	add_action( 'add_meta_boxes', 'dessky_responsive_slider_create_dessky_slide_metaboxes' );

	/* Save meta box data. */
	add_action( 'save_post', 'dessky_responsive_slider_save_meta', 1, 2 );

	/* Edit post editor meta boxes. */
	add_action('do_meta_boxes', 'dessky_responsive_slider_edit_metaboxes');

	/* Add 'Settings' submenu to 'Slides'.*/
	add_action('admin_menu', 'dessky_responsive_slider_settings');

	/* Register and define the slider settings. */
	add_action( 'admin_init', 'dessky_responsive_slider_settings_init' );

	/* Edit slide columns in 'all_items' view.  */
	add_filter( 'manage_edit-slides_columns', 'dessky_responsive_slider_columns' );

	/* Add slide-specific columns to the 'all_items' view. */
	add_action( 'manage_posts_custom_column', 'dessky_responsive_slider_add_columns' );
	
	/* Order the slides by the 'order' attribute in the 'all_items' column view. */
	add_filter( 'pre_get_posts', 'dessky_responsive_slider_column_order' );
	
	add_filter("mce_buttons", "dessky_responsive_slider_add_button");
	
	add_filter('mce_external_plugins', 'dessky_responsive_slider_add_plugin');
}

function dessky_responsive_slider_add_button($buttons) {
	array_push($buttons, "rs_code");
	return $buttons;
}
function dessky_responsive_slider_add_plugin($plugin_array) {
   $plugin_array['blist'] = DESSKY_RESPONSIVE_SLIDER_URI . 'dessky-responsive-slider_mc.js';
   return $plugin_array;
}

/**
 * Do things on plugin activation.
 *
 * @since 0.1
 */
function dessky_responsive_slider_activation() {
	/* Register the custom post type. */
    dessky_responsive_slider_register_cpt();

	/* Flush permalinks. */
    flush_rewrite_rules();

	/* Set default slider settings. */
	dessky_responsive_slider_default_settings();
}

/**
 * Flush permalinks on plugin deactivation.
 *
 * @since 0.1
 */
function dessky_responsive_slider_deactivation() {
    flush_rewrite_rules();
}

/**
 * Delete slider settings on plugin uninstall.
 *
 * @since 0.1
 */
 
function remove_mce_buttons($buttons) {
	$num = count($buttons);
	for($i = 0; $i < $num; $i++){
		if ($buttons[$i] == 'rs_code')
			unset($buttons[$i]);
	}
    return $buttons;
}
 
function dessky_responsive_slider_uninstall() {
	delete_option( 'dessky_responsive_slider_options' );
	
	add_filter('mce_buttons', 'remove_mce_buttons' );
}

/**
 * Register the 'Slides' custom post type.
 *
 * @since 0.1
 */
function dessky_responsive_slider_register_cpt() {
	$labels = array(
		'name'                 => __( 'Slides', 'dessky-responsive-slider' ),
		'singular_name'        => __( 'Slide', 'dessky-responsive-slider' ),
		'all_items'            => __( 'All Slides', 'dessky-responsive-slider' ),
		'add_new'              => __( 'Add New Slide', 'dessky-responsive-slider' ),
		'add_new_item'         => __( 'Add New Slide', 'dessky-responsive-slider' ),
		'edit_item'            => __( 'Edit Slide', 'dessky-responsive-slider' ),
		'new_item'             => __( 'New Slide', 'dessky-responsive-slider' ),
		'view_item'            => __( 'View Slide', 'dessky-responsive-slider' ),
		'search_items'         => __( 'Search Slides', 'dessky-responsive-slider' ),
		'not_found'            => __( 'No Slide found', 'dessky-responsive-slider' ),
		'not_found_in_trash'   => __( 'No Slide found in Trash', 'dessky-responsive-slider' ),
		'parent_item_colon'    => ''
	);
	$args = array(
		'labels'               => $labels,
		'public'               => true,
		'publicly_queryable'   => true,
		'_builtin'             => false,
		'show_ui'              => true, 
		'query_var'            => true,
		'rewrite'              => array( "slug" => "slides" ),
		'capability_type'      => 'post',
		'hierarchical'         => false,
		'menu_position'        => 20,
		'menu_icon'			   => '',
		'supports'             => array( 'title','thumbnail', 'page-attributes' ),
		'taxonomies'           => array(),
		'has_archive'          => true,
		'show_in_nav_menus'    => false
	);
	register_post_type( 'slides', $args );
}

/**
 * Enqueue the stylesheet.
 *
 * @since 0.1
 */
function dessky_responsive_slider_enqueue_stylesheets() {
	wp_enqueue_style( 'dessky-responsive-slider', DESSKY_RESPONSIVE_SLIDER_URI . 'css/dessky-responsive-slider.css', false, 0.1, 'all' );
}

/**
 * Enqueue the admin stylesheet.
 *
 * @since 0.1
 */
function dessky_responsive_slider_enqueue_admin_stylesheets() {
	global $post_type;
	if ( ( isset( $post_type ) && $post_type == 'slides' ) || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'slides' ) ) {
		wp_enqueue_style( 'responsive-slider_admin', DESSKY_RESPONSIVE_SLIDER_URI . 'css/dessky-responsive-slider-admin.css', false, 0.1, 'all' );
	}
	
	$should_include_tinymce_custom_css = false;

    // Get current screen and determine if we are using the editor
    $screen = get_current_screen();
 
    if ( $screen->id == 'page' || $screen->id == 'post' ) {
    	$should_include_tinymce_custom_css = true;
    }
     
	if ($should_include_tinymce_custom_css) {
		wp_enqueue_style( 'responsive-slider_admin', DESSKY_RESPONSIVE_SLIDER_URI . 'css/dessky-responsive-slider-admin-tinymce.css', false, 0.1, 'all' );
	}
}

/**
 * Enqueue the JavaScript.
 *
 * @since 0.1
 */
function dessky_responsive_slider_enqueue_scripts() {

	/* Enqueue script. */
	wp_enqueue_script( 'responsive-slider_flex-slider', DESSKY_RESPONSIVE_SLIDER_URI . 'dessky-responsive-slider.js', array( 'jquery' ), 0.1, true );

	/* Get slider settings. */
	$options = get_option( 'dessky_responsive_slider_options' );

	/* Prepare variables for JavaScript. */
	wp_localize_script( 'responsive-slider_flex-slider', 'slider', array(
		'effect'    => $options['dessky_slide_effect'],
		'delay'     => $options['dessky_slide_delay'],
		'direction' => $options['dessky_slide_direction'],
		'duration'  => $options['dessky_slide_duration'],
		'start'     => $options['dessky_slide_start'],
		'randomize' => $options['dessky_slide_randomize'],
		'controlNav' => $options['dessky_slide_controlNav'],
		'keyboard' => $options['dessky_slide_keyboard'],
		'mousewheel' => $options['dessky_slide_mousewheel']
	) );
}

/**
 * Custom post type icon.
 *
 * @since 0.1
 */
function dessky_responsive_slider_cpt_icon() {
	?>
	<style type="text/css" media="screen">
		#menu-posts-slides .wp-menu-image {
			background: url(<?php echo DESSKY_RESPONSIVE_SLIDER_URI . 'images/slides-icon.png'; ?>) no-repeat 9px -17px !important;
			height: 24px !important;
			margin-top: 1px !important;
		}
		#menu-posts-slides:hover .wp-menu-image {
			background-position: 9px 7px !important;
		}
	</style>
<?php }

/**
 * Output the slider.
 *
 * @since 0.1
 */
function dessky_responsive_slider() {

	$slides = new WP_Query( array( 'post_type' => 'slides', 'order' => 'ASC', 'orderby' => 'menu_order' ) );
	if ( $slides->have_posts() ) : ?>
	
		<div class="responsive-slider flexslider">
			<ul class="slides">
			<?php while ( $slides->have_posts() ) : $slides->the_post(); ?>
				<li>
					<div id="slide-<?php the_ID(); ?>" class="slide">
						<?php global $post; ?>
							<?php if ( has_post_thumbnail() ) : ?>
								<a href="<?php echo get_post_meta( $post->ID, "_dessky_slide_link_url", true ); ?>" title="<?php the_title_attribute(); ?>" >
									<?php the_post_thumbnail( 'slide-thumbnail', array( 'class'	=> 'slide-thumbnail' ) ); ?>
								</a>
							<?php endif; ?>
						<h2 class="slide-title">
							<?php
								if (get_post_meta( $post->ID, "_dessky_slide_caption", true ) == 1) :
							?>
								<a href="<?php echo get_post_meta( $post->ID, "_dessky_slide_link_url", true ); ?>" title="<?php get_post_meta( $post->ID, "_dessky_slide_caption", true ); ?>" ><?php the_title(); ?></a>
							<?php endif; ?>
						</h2>
					</div><!-- #slide-x -->
				</li>
			<?php endwhile; ?>
			</ul>
		</div><!-- #featured-content -->
	<?php endif;
}

/**
 * Register the slider shortcode.
 *
 * @since 0.1
 */
function dessky_responsive_slider_register_shortcode() {
	add_shortcode( 'dessky_responsive_slider', 'dessky_responsive_slider_shortcode' );
}

/**
 * Slider shortcode.
 *
 * @since 0.1
 */
function dessky_responsive_slider_shortcode() {
	$slider = dessky_responsive_slider();
	return $slider;
}

/**
 *  Add image sizes
 *
 * @since 0.1
 */
function dessky_responsive_slider_image_sizes() {
	$options = get_option( 'dessky_responsive_slider_options' );
	add_image_size( 'slide-thumbnail', $options['dessky_slide_width'], $options['dessky_slide_height'], true );
}

/**
 * Add meta box for slides.
 *
 * @since 0.1
 */
function dessky_responsive_slider_create_dessky_slide_metaboxes() {
    add_meta_box( 'dessky_responsive_slider_metabox_1', __( 'Slide Link', 'dessky-responsive-slider' ), 'dessky_responsive_slider_metabox_1', 'slides', 'normal', 'default' );
}

/**
 * Output the meta box #1.
 *
 * @since 0.1
 */
function dessky_responsive_slider_metabox_1() {
	global $post;	

	/* Retrieve the metadata values if they already exist. */
	$dessky_slide_link_url = get_post_meta( $post->ID, '_dessky_slide_link_url', true ); ?>

	<p>URL: <input type="text" style="width: 90%;" name="dessky_slide_link_url" value="<?php echo esc_attr( $dessky_slide_link_url ); ?>" /></p>
	<span class="description"><?php echo _e( 'The URL this slide should link to.', 'dessky-responsive-slider' ); ?></span>

<?php }

function dessky_slide_caption() {
	global $post;
	$dessky_slide_caption = get_post_meta( $post->ID, "_dessky_slide_caption", true ); ?>
	
	<p>Enable slide caption: <input type="checkbox" name="dessky_slide_caption" value="1" <?php echo ($dessky_slide_caption==1 ? 'checked' : ''); ?> /></p>
<?php }

/**
 * Save meta box data.
 *
 * @since 0.1
 */
function dessky_responsive_slider_save_meta( $post_id, $post ) {
	
	if ( isset( $_POST['dessky_slide_link_url'] ) ) {
		update_post_meta( $post_id, '_dessky_slide_link_url', strip_tags( $_POST['dessky_slide_link_url'] ) );
	}
	if ( isset( $_POST['dessky_slide_caption' ] ) ) {
		update_post_meta( $post_id, '_dessky_slide_caption', true);
	}
	else{
		update_post_meta( $post_id, '_dessky_slide_caption', false);
	}
}

/**
 * Edit post editor meta boxes.
 *
 * @since 0.1
 */
function dessky_responsive_slider_edit_metaboxes() {

	/* Remove metaboxes */
    remove_meta_box( 'postimagediv', 'slides', 'side' );
	remove_meta_box( 'pageparentdiv', 'slides', 'side' );
	remove_meta_box( 'hybrid-core-post-template', 'slides', 'side' );
	remove_meta_box( 'theme-layouts-post-meta-box', 'slides', 'side' );
	remove_meta_box( 'post-stylesheets', 'slides', 'side' );

	/* Add the previously removed meta boxes - with modified properties */
    add_meta_box('postimagediv', __('Slide Image', 'dessky-responsive-slider' ), 'post_thumbnail_meta_box', 'slides', 'side', 'low');
	add_meta_box('pageparentdiv', __('Slide Order', 'dessky-responsive-slider' ), 'page_attributes_meta_box', 'slides', 'side', 'low');
	
	add_meta_box('slidecaption', __('Slide Caption', 'dessky-responsive-slider' ), 'dessky_slide_caption', 'slides', 'side', 'low');
}


/**
 * Add 'Settings' submenu to 'Slides'.
 *
 * @since 0.1
 */
function dessky_responsive_slider_settings() {
	add_submenu_page( 'edit.php?post_type=slides', __( 'Slider Settings', 'dessky-responsive-slider' ), __( 'Settings', 'dessky-responsive-slider' ), 'manage_options', 'responsive-slider-settings', 'dessky_responsive_slider_settings_page' );
}

/**
 * Create the Slider Settings page.
 *
 * @since 0.1
 */
function dessky_responsive_slider_settings_page() { ?>
	<div class="wrap">
		<?php screen_icon( 'plugins' ); ?>
		<h2><?php _e( 'Dessky Responsive Slider Settings', 'dessky-responsive-slider' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'dessky_responsive_slider_options' ); ?>
			<?php do_settings_sections( 'responsive-slider-settings' ); ?>
			<br /><p><input type="submit" name="Submit" value="<?php _e( 'Update Settings', 'dessky-responsive-slider' ); ?>" class="button-primary" /></p>
			<br /><p class="description"><?php _e( 'Note: Whenever you change the Width and Height settings, it is a good idea to re-upload the Featured Images of your Slides. This would get them cropped to the new size.', 'dessky-responsive-slider' ); ?></p>
		</form>
	</div>
<?php }

/**
 * Register and define the slider settings.
 *
 * @since 0.1
 */
function dessky_responsive_slider_settings_init() {
	/* Register the slider settings. */
	register_setting( 'dessky_responsive_slider_options', 'dessky_responsive_slider_options', 'dessky_responsive_slider_validate_options' );
	
	/* Add settings section. */
	add_settings_section( 'dessky_responsive_slider_options_main', __( ' ', 'dessky-responsive-slider' ), 'dessky_responsive_slider_section_text', 'responsive-slider-settings' );

	/* Add settings fields. */
	add_settings_field( 'dessky_slide_width', __( 'Width:', 'dessky-responsive-slider' ), 'dessky_slide_width', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_height', __( 'Height:', 'dessky-responsive-slider' ), 'dessky_slide_height', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_effect', __( 'Transition Effect:', 'dessky-responsive-slider' ), 'dessky_slide_effect', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	
	add_settings_field( 'dessky_slide_direction', __( 'Direction:', 'dessky-responsive-slider' ), 'dessky_slide_direction', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_delay', __( 'Delay:', 'dessky-responsive-slider' ), 'dessky_slide_delay', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_duration', __( 'Animation Duration:', 'dessky-responsive-slider' ), 'dessky_slide_duration', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_start', __( 'Start Automatically:', 'dessky-responsive-slider' ), 'dessky_slide_start', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );		
	add_settings_field( 'dessky_slide_randomize', __( 'Randomize:', 'dessky-responsive-slider' ), 'dessky_slide_randomize', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );

	add_settings_field( 'dessky_slide_controlNav', __( 'Control Navigation:', 'dessky-responsive-slider' ), 'dessky_slide_controlNav', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_keyboard', __( 'Keyboard:', 'dessky-responsive-slider' ), 'dessky_slide_keyboard', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
	add_settings_field( 'dessky_slide_mousewheel', __( 'Mousewheel:', 'dessky-responsive-slider' ), 'dessky_slide_mousewheel', 'responsive-slider-settings', 'dessky_responsive_slider_options_main' );
}

/* Output the section header text. */
function dessky_responsive_slider_section_text() {
	echo '<p class="description">' . __( 'Make sure to set the desired slide width and height BEFORE creating your slides. Ideally, this would be the maximum size the slider container expands to.', 'dessky-responsive-slider' ) . '</p>';
}

/**
 * Display and fill the settings fields.
 *
 * @since 0.1
 */
function dessky_slide_width() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_width = $options['dessky_slide_width'];

	/* Echo the field. */ ?>
	<input type="text" id="dessky_slide_width" name="dessky_responsive_slider_options[dessky_slide_width]" value="<?php echo $dessky_slide_width; ?>" /> <span class="description"><?php _e( 'px', 'dessky-responsive-slider' ); ?></span>

<?php }



function dessky_slide_height() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_height = $options['dessky_slide_height'];

	/* Echo the field. */ ?>
	<input type="text" id="dessky_slide_height" name="dessky_responsive_slider_options[dessky_slide_height]" value="<?php echo $dessky_slide_height; ?>" /> <span class="description"><?php _e( 'px', 'dessky-responsive-slider' ); ?></span>

<?php }

function dessky_slide_effect() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_effect = $options['dessky_slide_effect'];
	
	/* Echo the field. */
	echo "<select id='dessky_slide_effect' name='dessky_responsive_slider_options[dessky_slide_effect]'>";
	echo '<option value="fade" ' . selected( $dessky_slide_effect, 'fade', false ) . ' >' . __( 'fade', 'dessky-responsive-slider' ) . '</option>';
	echo '<option value="slide" ' . selected( $dessky_slide_effect, 'slide', false ) . ' >' . __( 'slide', 'dessky-responsive-slider' ) . '</option>';
	echo '</select>';
}

function dessky_slide_direction(){
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_direction = $options['dessky_slide_direction'];
	
	echo "<select id='dessky_slide_direction' name='dessky_responsive_slider_options[dessky_slide_direction]'>";
	echo '<option value="horizontal" ' . selected( $dessky_slide_direction, 'horizontal', false ) . ' >' . __( 'horizontal', 'dessky-responsive-slider' ) . '</option>';
	echo '<option value="vertical" ' . selected( $dessky_slide_direction, 'vertical', false ) . ' >' . __( 'vertical', 'dessky-responsive-slider' ) . '</option>';
	echo '</select>';	
}

function dessky_slide_delay() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_delay = $options['dessky_slide_delay'];
	
	/* Echo the field. */ ?>
	<input type="text" id="dessky_slide_delay" name="dessky_responsive_slider_options[dessky_slide_delay]" value="<?php echo $dessky_slide_delay; ?>" /> <span class="description"><?php _e( 'milliseconds', 'dessky-responsive-slider' ); ?></span>
<?php }

function dessky_slide_duration() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_duration = $options['dessky_slide_duration'];

	/* Echo the field. */ ?>
	<input type="text" id="dessky_slide_duration" name="dessky_responsive_slider_options[dessky_slide_duration]" value="<?php echo $dessky_slide_duration; ?>" /> <span class="description"><?php _e( 'milliseconds', 'dessky-responsive-slider' ); ?></span>
<?php }

function dessky_slide_start() {
	/* Get the option value from the database. */
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_start = $options['dessky_slide_start'];

	/* Echo the field. */
	echo "<input type='checkbox' id='dessky_slide_start' name='dessky_responsive_slider_options[dessky_slide_start]' value='1' " . checked( $dessky_slide_start, 1, false ) . " />";	
}

function dessky_slide_randomize() {
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_randomize = $options['dessky_slide_randomize'];
	
	echo "<input type='checkbox' id='dessky_slide_randomize' name='dessky_responsive_slider_options[dessky_slide_randomize]' value='1' " . checked( $dessky_slide_randomize, 1, false ) . " />";
}

function dessky_slide_mousewheel() {
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_mousewheel = $options['dessky_slide_mousewheel'];

	echo "<input type='checkbox' id='dessky_slide_mousewheel' name='dessky_responsive_slider_options[dessky_slide_mousewheel]' value='1' " . checked( $dessky_slide_mousewheel, 1, false ) . " />";
}

function dessky_slide_keyboard() {
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_keyboard = $options['dessky_slide_keyboard'];

	echo "<input type='checkbox' id='dessky_slide_keyboard' name='dessky_responsive_slider_options[dessky_slide_keyboard]' value='1' " . checked( $dessky_slide_keyboard, 1, false ) . " />";
}

function dessky_slide_controlNav() {
	$options = get_option( 'dessky_responsive_slider_options' );
	$dessky_slide_controlNav = $options['dessky_slide_controlNav'];

	echo "<input type='checkbox' id='dessky_slide_controlNav' name='dessky_responsive_slider_options[dessky_slide_controlNav]' value='1' " . checked( $dessky_slide_controlNav, 1, false ) . " />";
}

/**
 * Validate and/or sanitize user input.
 *
 * @since 0.1
 */
function dessky_responsive_slider_validate_options( $input ) {
	$options = get_option( 'dessky_responsive_slider_options' );
	$options['dessky_slide_width'] = wp_filter_nohtml_kses( intval( $input['dessky_slide_width'] ) );
	$options['dessky_slide_height'] = wp_filter_nohtml_kses( intval( $input['dessky_slide_height'] ) );
	$options['dessky_slide_effect'] = wp_filter_nohtml_kses( $input['dessky_slide_effect'] );
	$options['dessky_slide_direction'] = wp_filter_nohtml_kses( $input['dessky_slide_direction'] );
	$options['dessky_slide_delay'] = wp_filter_nohtml_kses( intval( $input['dessky_slide_delay'] ) );
	$options['dessky_slide_duration'] = wp_filter_nohtml_kses( intval( $input['dessky_slide_duration'] ) );
	$options['dessky_slide_start'] = isset( $input['dessky_slide_start'] ) ? 1 : 0;
	$options['dessky_slide_randomize'] = isset( $input['dessky_slide_randomize'] ) ? 1 : 0;
	$options['dessky_slide_controlNav'] = isset( $input['dessky_slide_controlNav'] ) ? 1 : 0;
	$options['dessky_slide_keyboard'] = isset( $input['dessky_slide_keyboard'] ) ? 1 : 0;
	$options['dessky_slide_mousewheel'] = isset( $input['dessky_slide_mousewheel'] ) ? 1 : 0;

	return $options;
}

/**
 * Default slider settings.
 *
 * @since 0.1
 */
function dessky_responsive_slider_default_settings() {
	/* Retrieve exisitng options, if any. */

	/* Check if options are set. Add default values if not. */ 
	if ( !is_array( $ex_options ) || $ex_options['dessky_slide_duration'] == '' ) {
		$default_options = array(	
			'dessky_slide_width'     => '940',
			'dessky_slide_height'    => '400',
			'dessky_slide_effect'    => 'fade',
			'dessky_slide_direction' => 'horizontal',
			'dessky_slide_delay'     => '7000',
			'dessky_slide_duration'  => '600',
			'dessky_slide_start'     => 1,
			'dessky_slide_randomize' => 1,
			'dessky_slide_controlNav' => 1,
			'dessky_slide_keyboard' => 1,
			'dessky_slide_mousewheel' => 0
		);

		/* Set the default options. */
		update_option( 'dessky_responsive_slider_options', $default_options );
	}
}


/**
 * This function checks for plugin version differences and applies the correct
 * update scripts.
 *
 * For example, let's say we upgrade the plugin from 1.2 to 1.3 (by extracting 
 * the new plugin into wp-content/plugins/ directory). The function below will 
 * see that the version in the database is 1.2, while we currently have 1.3 
 * installed. This is a signal that the database version needs to be corrected 
 * and any applicable update scripts must be executed.
 */
function dessky_update_checker()
{
    global $dessky_version;

	$dessky_version_in_db = (float) get_site_option('dessky_version');

    if ($dessky_version_in_db != $dessky_version) {
    	if (!$dessky_version_in_db) {
			dessky_update_from_v12_to_v13();
    	} else if ($dessky_version_in_db == 1.2 && $dessky_version >= 1.3) {
			dessky_update_from_v12_to_v13();
    	}
    }

	update_option('dessky_version', $dessky_version);
}

/**
 * Three new settings were introduced at v1.3: controlNav, keyboard, mousewheel
 *
 * We setup their default values so when people upgrade the plugin, it wouldn't
 * have weird side-effects (control navigation disappearing for example due to 
 * wrong default option value).
 */
function dessky_update_from_v12_to_v13()
{
	$dessky_settings = get_option( 'dessky_responsive_slider_options' );

	if (!isset($dessky_settings['dessky_slide_controlNav']) || !$dessky_settings['dessky_slide_controlNav']) {
		$dessky_settings['dessky_slide_controlNav'] = 1;
	}

	if (!isset($dessky_settings['dessky_slide_keyboard']) || !$dessky_settings['dessky_slide_keyboard']) {
		$dessky_settings['dessky_slide_keyboard'] = 1;
	}

	if (!isset($dessky_settings['dessky_slide_mousewheel']) || !$dessky_settings['dessky_slide_mousewheel']) {
		$dessky_settings['dessky_slide_mousewheel'] = 0;
	}

	update_option( 'dessky_responsive_slider_options', $dessky_settings );
}

/**
 * Edit slide columns in 'all_items' view.
 *
 * @since 0.1
 */
function dessky_responsive_slider_columns( $columns ) {
	$columns = array(
		'cb'       => '<input type="checkbox" />',
		'image'    => __( 'Image', 'dessky-responsive-slider' ),
		'title'    => __( 'Title', 'dessky-responsive-slider' ),
		'order'    => __( 'Order', 'dessky-responsive-slider' ),
		'link'     => __( 'Link', 'dessky-responsive-slider' ),
		'date'     => __( 'Date', 'dessky-responsive-slider' )
	);
	
	return $columns;
}

/**
 * Add slide-specific columns to the 'all_items' view.
 *
 * @since 0.1
 */
function dessky_responsive_slider_add_columns( $column ) {
	global $post;

	/* Get the post edit link for the post. */
	$edit_link = get_edit_post_link( $post->ID );

	/* Add column 'Image'. */
	if ( $column == 'image' )		
		echo '<a href="' . $edit_link . '" title="' . $post->post_title . '">' . get_the_post_thumbnail( $post->ID, array( 60, 60 ), array( 'title' => trim( strip_tags(  $post->post_title ) ) ) ) . '</a>';

	/* Add column 'Order'. */	
	if ( $column == 'order' )		
		echo '<a href="' . $edit_link . '">' . $post->menu_order . '</a>';

	/* Add column 'Link'. */
	if ( $column == 'link' )
		echo '<a href="' . get_post_meta( $post->ID, "_dessky_slide_link_url", true ) . '" target="_blank" >' . get_post_meta( $post->ID, "_dessky_slide_link_url", true ) . '</a>';		
}

/**
 * Order the slides by the 'order' attribute in the 'all_items' column view.
 *
 * @since 0.1.2
 */
function dessky_responsive_slider_column_order($wp_query) {
	if( is_admin() ) {
		$post_type = $wp_query->query['post_type'];
		if( $post_type == 'slides' ) {
			$wp_query->set( 'orderby', 'menu_order' );
			$wp_query->set( 'order', 'ASC' );
		}
	}
}
?>
