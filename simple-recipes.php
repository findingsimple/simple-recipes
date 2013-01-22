<?php
/*
Plugin Name: Simple Recipes
Plugin URI: http://plugins.findingsimple.com
Description: Build a library of recipes that can be used by a theme or within content.
Version: 1.0
Author: Finding Simple
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Finding Simple  (email : plugins@findingsimple.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once dirname( __FILE__ ) . '/simple-recipes-admin.php';

if ( ! class_exists( 'Simple_Recipes' ) ) :

/**
 * So that themes and other plugins can customise the text domain, the Simple_Recipes
 * should not be initialized until after the plugins_loaded and after_setup_theme hooks.
 * However, it also needs to run early on the init hook.
 *
 * @author Jason Conroy <jason@findingsimple.com>
 * @package Simple Recipes
 * @since 1.0
 */
function initialize_recipes(){
	Simple_Recipes::init();
}
add_action( 'init', 'initialize_recipes', -1 );

/**
 * Plugin Main Class.
 *
 * @package Simple Recipes
 * @author Jason Conroy <jason@findingsimple.com>
 * @since 1.0
 */
class Simple_Recipes {

	static $text_domain;

	static $post_type_name;

	static $admin_screen_id;
	
	static $defaults;
	
	static $add_scripts;
	
	/**
	 * Initialise
	 */
	public static function init() {
	
		global $wp_version;
		
		self::$defaults = array(
			'x' => '',
			'y' => array()
		);
		
		self::$text_domain = apply_filters( 'simple_recipes_text_domain', 'Simple_Recipes' );

		self::$post_type_name = apply_filters( 'simple_recipes_post_type_name', 'simple_recipe' );

		self::$admin_screen_id = apply_filters( 'simple_recipes_admin_screen_id', 'simple_recipes' );

		self::$defaults = apply_filters( 'simple_recipes_defaults', self::$defaults );
		
		add_action( 'init', array( __CLASS__, 'register' ) );
		
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
		
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
						
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		
		add_action( 'save_post', array( __CLASS__, 'save_instruction_meta' ), 10, 1 );

		add_action( 'save_post', array( __CLASS__, 'save_nutrition_meta' ), 10, 1 );
		
		add_action( 'save_post', array( __CLASS__, 'save_information_meta' ), 10, 1 );
		
		add_action( 'save_post', array( __CLASS__, 'save_ingredients_meta' ), 10, 1 );
		
		add_action( 'init', __CLASS__ . '::register_image_sizes' , 99 );
				
		add_action( 'init', array( __CLASS__, 'add_styles_and_scripts') );
		
		add_action( 'wp_footer', array(__CLASS__, 'print_footer_scripts') );
		
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles_and_scripts'), 100 );
		
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles_and_scripts' ) );
		
		add_shortcode( 'recipe', array( __CLASS__, 'shortcode_recipe') );
										
		add_filter( 'enter_title_here' , __CLASS__ . '::change_default_title' );
				
		add_filter("manage_" . self::$post_type_name . "_posts_columns", __CLASS__ . '::manage_columns');
		
		add_action("manage_" . self::$post_type_name . "_posts_custom_column", __CLASS__ . '::manage_columns_values', 10, 2);
		
		add_filter("manage_" . self::$post_type_name . "_posts_custom_column", __CLASS__ . '::manage_columns_values', 10, 2);
						
	}

	/**
	 * Register the post type
	 */
	public static function register() {
		
		$labels = array(
			'name' => _x('Recipes', 'post type general name', self::$text_domain ),
			'singular_name' => _x('Recipe', 'post type singular name', self::$text_domain ),
			'all_items' => __( 'All Recipes', self::$text_domain ),
			'add_new_item' => __('Add New Recipe', self::$text_domain ),
			'edit_item' => __('Edit Recipe', self::$text_domain ),
			'new_item' => __('New Recipe', self::$text_domain ),
			'view_item' => __('View Recipe', self::$text_domain ),
			'search_items' => __('Search Recipes', self::$text_domain ),
			'not_found' =>  __('No recipes found', self::$text_domain ),
			'not_found_in_trash' => __('No recipes found in Trash', self::$text_domain ),
			'parent_item_colon' => ''
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true, 
			'query_var' => true,
			'has_archive' => true,
			'rewrite' => array( 'slug' => 'recipes', 'with_front' => false ),
			'capability_type' => 'post',
			'hierarchical' => true, //allows use of wp_dropdown_pages
			'menu_position' => null,
			'taxonomies' => array(''),
			'supports' => array( 'title', 'editor', 'thumbnail','revisions', 'excerpt', 'author', 'comments' )
		); 
		
		$args = apply_filters( self::$post_type_name . '_cpt_args' , $args );

		register_post_type( self::$post_type_name , $args );
		
	}	

	/**
	 * Filter the "post updated" messages
	 *
	 * @param array $messages
	 * @return array
	 */
	public static function updated_messages( $messages ) {
		global $post;

		$messages[ self::$post_type_name ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Recipe updated.', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			2 => __('Custom field updated.', self::$text_domain ),
			3 => __('Custom field deleted.', self::$text_domain ),
			4 => __('Recipe updated.', self::$text_domain ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Recipe restored to revision from %s', self::$text_domain ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Recipe published.', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			7 => __('Recipe saved.', self::$text_domain ),
			8 => sprintf( __('Recipe submitted.', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
			9 => sprintf( __('Recipe scheduled for: <strong>%1$s</strong>.', self::$text_domain ),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post->ID) ) ),
			10 => sprintf( __('Recipe draft updated.', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
		);

		return $messages;
	}
	
	public static function register_taxonomies() {

		/**
		 * Recipe Category (schema.org recipeCategory) - "The category of the recipe—for example, appetizer, entree, etc."
		 *
		 **/
		$labels = array(
			'name' => _x( 'Recipe Categories', 'Recipe category taxonomy' ),
			'singular_name' => _x( 'Category', 'Recipe category taxonomy' ),
			'search_items' =>  __( 'Search Categories' ),
			'all_items' => __( 'All Categories' ),
   			'parent_item' => __( 'Parent Category' ),
   			'parent_item_colon' => __( 'Parent Category:' ),
			'edit_item' => __( 'Edit Category' ), 
			'update_item' => __( 'Update Category' ),
			'add_new_item' => __( 'Add New Category' ),
			'new_item_name' => __( 'New Category' ),
			'menu_name' => __( 'Categories' ),
		); 	
		
		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'recipe-category' )		
		);
		
		register_taxonomy( self::$post_type_name . '_category' , array( self::$post_type_name ), $args );

		/**
		 * Recipe Cuisine (schema.org recipeCuisine) - "The cuisine of the recipe (for example, French or Ethopian)."
		 *
		 **/
		$labels = array(
			'name' => _x( 'Recipe Cuisines', 'Cuisine taxonomy' ),
			'singular_name' => _x( 'Cuisine', 'Cuisine taxonomy' ),
			'search_items' =>  __( 'Search Cuisine' ),
			'all_items' => __( 'All Cuisine' ),
   			'parent_item' => __( 'Parent Cuisine' ),
   			'parent_item_colon' => __( 'Parent Cuisine:' ),
			'edit_item' => __( 'Edit Cuisine' ), 
			'update_item' => __( 'Update Cuisine' ),
			'add_new_item' => __( 'Add New Cuisine' ),
			'new_item_name' => __( 'New Cuisine' ),
			'menu_name' => __( 'Cuisines' ),
		); 	

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true ,
			'rewrite' => array( 'slug' => 'cuisine' ),
		);
		
		register_taxonomy( self::$post_type_name . '_cuisine' , array( self::$post_type_name ), $args );
	
	}
	
	/**
	 * Add scripts and styles
	 *
	 * @since 1.0
	 */
	public static function add_styles_and_scripts(){
	
		wp_register_script( 'simple-recipes', self::get_url( '/js/simple-recipes.js', __FILE__ ) , 'jquery' , '1', true );
		
	}

	/**
	 * Conditional print some scripts in the footer when required
	 *
	 * @since 1.0
	 */
	public static function print_footer_scripts() {
	
		if ( ! self::$add_scripts )
			return;

		wp_print_scripts('simple-recipes');
		
	}

	/**
	 * Add styles and scripts
	 *
	 * @since 1.0
	 */
	public static function enqueue_styles_and_scripts(){
		
		if ( !is_admin() ) {
		
			//if ( get_option('simple_recipes_toggle_js_include') != 1 )
				//stuff here
							
			//if ( get_option('simple_recipes_toggle_css_include') != 1 )
				wp_enqueue_style( 'simple-recipes', self::get_url( '/css/simple-recipes.css', __FILE__ ) );
		
		}
		
	}

	/**
	 * Enqueues the necessary scripts and styles in the admin area
	 *
	 * @since 1.0
	 */
	public static function enqueue_admin_styles_and_scripts() {

		global $post_type;
				
		wp_register_style( 'simple-recipes-admin', self::get_url( '/css/simple-recipes-admin.css', __FILE__ ) , false, '1.0' );
		wp_enqueue_style( 'simple-recipes-admin' );
							
		if ( self::$post_type_name == $post_type ) {

			wp_register_script( 'simple-recipes-admin', self::get_url( '/js/simple-recipes-admin.js', __FILE__ ) , false, '1.0', true );
			wp_enqueue_script( 'simple-recipes-admin' );		
		
		}
		
	}
	
	/**
	 * Add recipe meta box/es
	 *
	 * @wp-action add_meta_boxes
	 */
	public static function add_meta_box() {

		add_meta_box( 'recipe-ingredients', __( 'Recipe Ingredients', self::$text_domain  ), array( __CLASS__, 'do_recipe_ingredients_meta_box' ), self::$post_type_name , 'normal', 'core' );	
		add_meta_box( 'recipe-information', __( 'Recipe Information', self::$text_domain  ), array( __CLASS__, 'do_recipe_information_meta_box' ), self::$post_type_name , 'normal', 'core' );
		add_meta_box( 'recipe-instructions', __( 'Recipe Instructions', self::$text_domain  ), array( __CLASS__, 'do_recipe_instructions_meta_box' ), self::$post_type_name , 'normal', 'core' );
		add_meta_box( 'recipe-nutrition', __( 'Recipe Nutrition', self::$text_domain  ), array( __CLASS__, 'do_recipe_nutrition_meta_box' ), self::$post_type_name , 'normal', 'core' );
		
	}

	/**
	 * Output the recipe instructions meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_recipe_instructions_meta_box( $object, $box ) {
	
		wp_nonce_field( basename( __FILE__ ), 'recipe-instructions-nonce' );
		
		$instructions = get_post_meta( $object->ID, '_recipe_instructions' , true );
					
		wp_editor( $instructions , 'recipe_instructions', array( 'textarea_name' => 'recipe_instructions' ) ); 
 
	}

	/**
	 * Save the recipe instructions
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_instruction_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['recipe-instructions-nonce'] ) || !wp_verify_nonce( $_POST['recipe-instructions-nonce'], basename( __FILE__ ) ) )
			return $post_id;

		$new_meta_value = $_POST['recipe_instructions'];
								
		/* Get the meta value of the custom field key. */
		$meta_value = get_post_meta( $post_id, '_recipe_instructions' , true );

		/* If there is no new meta value but an old value exists, delete it. */
		if ( '' == $new_meta_value && $meta_value )
			delete_post_meta( $post_id, '_recipe_instructions' , $meta_value );

		/* If a new meta value was added and there was no previous value, add it. */
		elseif ( $new_meta_value && empty( $meta_value ) )
			add_post_meta( $post_id, '_recipe_instructions' , $new_meta_value, true );

		/* If the new meta value does not match the old value, update it. */
		elseif ( $new_meta_value && $new_meta_value != $meta_value )
			update_post_meta( $post_id, '_recipe_instructions' , $new_meta_value );
	
	}

	/**
	 * Output the recipe nutrition meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_recipe_nutrition_meta_box( $object, $box ) {
	
		wp_nonce_field( basename( __FILE__ ), 'recipe-nutrition-nonce' );
											
		?>
		<div class="post-settings">
			<p>
				<label for="servingSize"><?php _e( 'Serving Size:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="servingSize" id="servingSize" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_servingSize', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="calories"><?php _e( 'Calories:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="calories" id="calories" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_calories', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="carbohydrateContent"><?php _e( 'Carbohydrates:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="carbohydrateContent" id="carbohydrateContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_carbohydrateContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="cholesterolContent"><?php _e( 'Cholesterol:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="cholesterolContent" id="cholesterolContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_cholesterolContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="fibreContent"><?php _e( 'Fibre:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="fibreContent" id="fibreContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_fibreContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="proteinContent"><?php _e( 'Protein:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="proteinContent" id="proteinContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_proteinContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="sodiumContent"><?php _e( 'Sodium:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="sodiumContent" id="sodiumContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_sodiumContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="sugarContent"><?php _e( 'Sugar:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="sugarContent" id="sugarContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_sugarContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>	
			<p>
				<label for="fatContent"><?php _e( 'Fat:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="fatContent" id="fatContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_fatContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="saturatedFatContent"><?php _e( 'Saturated Fat:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="saturatedFatContent" id="saturatedFatContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_saturatedFatContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>	
			<p>
				<label for="transFatContent"><?php _e( 'Trans Fat:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="transFatContent" id="transFatContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_transFatContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>	
			<p>
				<label for="unsaturatedFatContent"><?php _e( 'Unsaturated Fat:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="unsaturatedFatContent" id="unsaturatedFatContent" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_nutrition_unsaturatedFatContent', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>	
		</div> 
		<?php
	}

	/**
	 * Save the recipe nutrition metadata / options
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_nutrition_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['recipe-nutrition-nonce'] ) || !wp_verify_nonce( $_POST['recipe-nutrition-nonce'], basename( __FILE__ ) ) )
			return $post_id;

		$meta = array(
			'calories' => $_POST['calories'],
			'carbohydrateContent' => $_POST['carbohydrateContent'],
			'cholesterolContent' => $_POST['cholesterolContent'],
			'fatContent' => $_POST['fatContent'],
			'fiberContent' => $_POST['fiberContent'],
			'proteinContent' => $_POST['proteinContent'],
			'saturatedFatContent' => $_POST['saturatedFatContent'],
			'servingSize' => $_POST['servingSize'],
			'sodiumContent' => $_POST['sodiumContent'],
			'sugarContent' => $_POST['sugarContent'],
			'transFatContent' => $_POST['transFatContent'],
			'unsaturatedFatContent' => $_POST['unsaturatedFatContent']
		);	
		
		foreach ( $meta as $meta_key => $new_meta_value ) {
				
			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, '_recipe_nutrition_' . $meta_key , true );
	
			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, '_recipe_nutrition_' . $meta_key , $meta_value );
	
			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && empty( $meta_value ) )
				add_post_meta( $post_id, '_recipe_nutrition_' . $meta_key , $new_meta_value, true );
	
			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, '_recipe_nutrition_' . $meta_key , $new_meta_value );

		}

	
	}
	
	/**
	 * Output the recipe information meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_recipe_information_meta_box( $object, $box ) {
	
		wp_nonce_field( basename( __FILE__ ), 'recipe-information-nonce' );
													
		?>
		<div class="post-settings">
			<p>
				<label for="prepTime"><?php _e( 'Prepation Time:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="prepTime" id="prepTime" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_information_prepTime', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="cookTime"><?php _e( 'Cooking Time:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="cookTime" id="cookTime" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_information_cookTime', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
			<p>
				<label for="recipeYield"><?php _e( 'Yield:', Simple_Recipes::$text_domain ); ?></label>
				<br />
				<input type="text" name="recipeYield" id="recipeYield" value="<?php echo esc_attr( get_post_meta( $object->ID, '_recipe_information_recipeYield', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
			</p>
		</div> 
		<?php
	}

	/**
	 * Save the recipe nutrition metadata / options
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_information_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['recipe-information-nonce'] ) || !wp_verify_nonce( $_POST['recipe-information-nonce'], basename( __FILE__ ) ) )
			return $post_id;

		$meta = array(
			'prepTime' => $_POST['prepTime'],
			'cookTime' => $_POST['cookTime'],
			'recipeYield' => $_POST['recipeYield']
		);	

		foreach ( $meta as $meta_key => $new_meta_value ) {
								
			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, '_recipe_information_' . $meta_key , true );
	
			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, '_recipe_information_' . $meta_key , $meta_value );
	
			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && empty( $meta_value ) )
				add_post_meta( $post_id, '_recipe_information_' . $meta_key , $new_meta_value, true );
	
			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, '_recipe_information_' . $meta_key , $new_meta_value );
		
		}
		
	}
	
	/**
	 * Output the ingredients meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_recipe_ingredients_meta_box( $object, $box ) {
	
		global $post;

		wp_nonce_field( basename( __FILE__ ), 'recipe-ingredients-nonce' );
								
		$ingredients_count = get_post_meta( $post->ID, '_recipe_ingredients_count' , true ); 
												 														
?>		
				
		<a href="#" class="add_ingredient">Add Ingredient <span class="ui-icon ui-icon-circle-plus"></span></a>
		
		<div class="wrap">
		
			<ul id="ingredients_wrap">
	
				<?php if ( empty( $ingredients_count ) || $ingredients_count == 0 ) { ?>
				
				<li class="ingredient clearfix" id="ingredient-0" >
					<span class="handle ui-icon ui-icon-carat-2-n-s">handle</span>
					<div>
						<label for="recipe_ingredient_0" style="display:none;" >Ingredient:</label>
						<input type="text" id="recipe_ingredient_0" name="recipe_ingredient[0]" value="" size="30" tabindex="30" />
					</div>
					<a href="#" class="remove_ingredient ui-icon ui-icon-circle-minus" title="Remove" >Remove</a>
				</li>
				
				<?php } else { ?>		
	
				<?php for ( $count = 0; $count <= $ingredients_count-1; $count++ ) : ?>
				
				<?php $ingredient = get_post_meta( $post->ID, '_recipe_ingredient_' . $count , true ); ?>
				
				<li class="ingredient clearfix" id="ingredient-<?php echo $count; ?>" >
					<span class="handle ui-icon ui-icon-carat-2-n-s">handle</span>
					<div>
						<label for="recipe_ingredient_<?php echo $count; ?>" style="display:none;">Ingredient:</label>
						<input type="text" id="recipe_ingredient_<?php echo $count; ?>" name="recipe_ingredient[<?php echo $count; ?>]" value="<?php echo esc_attr( $ingredient ); ?>" size="30" tabindex="30" />
					</div>			
					<a href="#" class="remove_ingredient ui-icon ui-icon-circle-minus" title="Remove" >Remove</a>
				</li>
				
				<?php endfor; ?>			
				
				<?php } ?>
				
			</ul>
		
		</div>
				
<?php
	}	
	
	/**
	 * Save the recipe ingredients metadata
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_ingredients_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['recipe-ingredients-nonce'] ) || !wp_verify_nonce( $_POST['recipe-ingredients-nonce'], basename( __FILE__ ) ) )
			return $post_id;
			
		$count = 0;	
						
		foreach ( $_POST[ 'recipe_ingredient' ] as $ingredient ) {
				
			$new_meta_value = $ingredient;
			
			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, '_recipe_ingredient_' . $count , true );

			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, '_recipe_ingredient_' . $count , $meta_value );

			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && '' == $meta_value )
				add_post_meta( $post_id, '_recipe_ingredient_' . $count , $new_meta_value , true );

			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, '_recipe_ingredient_' . $count , $new_meta_value );
				
			$count++;
							
		}
		
		/* If less ingredients than previous - delete the ones that have been removed */
		
		$existing_count = get_post_meta( $post_id, '_recipe_ingredients_count' , true );
		
		$count_temp = $count; // so that the actual count doesn't get incremented

		if ( !empty( $count_temp )  && $count_temp < $existing_count ) {

			for ( $count_temp; $count_temp <= $existing_count; $count_temp++ ) {
			
				delete_post_meta( $post_id, '_recipe_ingredient_' . $count_temp );

			}

		}

						
		// Save the recipent ingredients count				
		if ( !empty( $count )  && $count > 0 ) {

			/* Get the current count. */
			$meta_value = get_post_meta( $post_id, '_recipe_ingredients_count' , true );

			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $count && $meta_value )
				delete_post_meta( $post_id, '_recipe_ingredients_count', $meta_value );

			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $count && '' == $meta_value )
				add_post_meta( $post_id, '_recipe_ingredients_count' , $count , true );

			/* If the new meta value does not match the old value, update it. */
			elseif ( $count && $count != $meta_value )
				update_post_meta( $post_id, '_recipe_ingredients_count' , $count );
		
		}
		
	}	

	/**
	 * Register admin thumbnail size
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function register_image_sizes( ){
			
		add_image_size( 'simple_recipe_admin' , '60' , '60' , true );

		add_filter( 'image_size_names_choose', array ( __CLASS__ , 'remove_image_size_options' ) );

	}

	/**
	 * Remove admin thumbnail size from the list of available sizes in the media uploader
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function remove_image_size_options( $sizes ){
				 	
		unset( $sizes['simple_recipe_admin'] );
		
		return $sizes;
	 
	}

	/**
	 * Helper function to get the URL of a given file. 
	 * 
	 * As this plugin may be used as both a stand-alone plugin and as a submodule of 
	 * a theme, the standard WP API functions, like plugins_url() can not be used. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_url( $file ) {

		// Get the path of this file after the WP content directory
		$post_content_path = substr( dirname( str_replace('\\','/',__FILE__) ), strpos( __FILE__, basename( WP_CONTENT_DIR ) ) + strlen( basename( WP_CONTENT_DIR ) ) );

		// Return a content URL for this path & the specified file
		return content_url( $post_content_path . $file );
	}	

	/**
	 * Replaces the "Enter title here" text
	 *
	 * @author Brent Shepherd <brent@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function change_default_title( $title ){
		$screen = get_current_screen();

		if  ( self::$post_type_name == $screen->post_type )
			$title = __( 'Enter Recipe Title', self::$text_domain );

		return $title;
	}


	/**
	 * Prepend the new ID column to the columns array
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function manage_columns($cols) {
		
		//Remove date column
		unset( $cols['date'] );

		//Add new column for recipe ids
		$cols['srid'] = 'ID';
		
		return $cols;
		
	}
	
	/**
	 * Echo the ID for the new column
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function manage_columns_values( $column_name , $id ) {
	
		if ( $column_name == 'srid' )
			echo $id;
			
	}

	/**
	 * Build recipe shortcode.
	 *
	 * @since 1.0
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 *
	 */
	 
	public static function shortcode_recipe( $atts, $content = null ) {
	
		global $wpdb;
	
		extract( shortcode_atts( array(	'id' => '' ), $atts) );
				
		$content = '' ;
		
		if ( !empty( $id ) ) {
		
			self::$add_scripts = true;
		
			$content .= self::get_recipe( $id , false ) ;
		
		}
			
		return $content;
	
	}

	/**
	 * Display (or return) a recipe
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function get_recipe( $id , $echo = true ) {

		//if no recipe ID exit
		if ( empty( $id ) )
			return;
								
		$recipe = self::build_recipe( $id );
		
		if ( empty( $recipe ) )
			return;
		
		if ( $echo ) {
		
			self::$add_scripts = true;
				
			echo $recipe;
		
		} else {
			
			return $recipe;
			
		} 
	
	}


	/**
	 * Build the recipe html ready for displaying or returning via shortcode
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function build_recipe ( $id ) {
			
		//if no recipe ID exit
		if ( empty( $id ) )
			return;
			
		//get recipe specific options		
		$recipe_options = get_post_meta( $id, '_recipe_options' , true );
				
		//empty recipe	
		$recipe = '';
							
		$recipe .= 'Recipe';
		
		return $recipe;

	}

	/**
	 * Better Empty
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function better_empty( $question ){
	
		return ( !isset($question) || trim($question)==='' );
		
	}
	

	/**
	 * Recipe Name
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_name( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_name_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'name',
			'content' => get_the_title()
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}
	
	/**
	 * Recipe Description
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_description( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_description_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'descripion',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Image
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_image( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_image_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'image',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe URL
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_url( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_url_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'url',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Cooking Method
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_cooking_method( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_cooking_method_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'cookingMethod',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Cook Time
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_cook_time( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_cook_time_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'cookTime',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Ingredients
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_ingredients( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_ingredients_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'ingredients',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Prep Time
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_prep_time( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_prep_time_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'prepTime',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Instructions
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_instructions( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_instructions_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'recipeInstructions',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Yield
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_yield( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_yield_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'recipeYield',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Total Time
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_total_time( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_total_time_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'totalTime',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Recipe Nutrition
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	public static function recipe_nutrition( $args = array() ) {

		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_nutrition_args', $args );
		extract( $args, EXTR_SKIP );

		$attributes = array();

		$attributes = array(
			'itemprop' => 'nutrition',
			'content' => ''
		);
		
		if ( empty( $attributes ) )
			return;
		
		if ( $echo )
			echo self::recipe_output( $attributes , $args );
		else
			return self::recipe_output( $attributes , $args );

	}	
	
	/**
	 * Format output according to argument
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function recipe_output( $attributes = array() , $args = array() ) {
	
		$args = wp_parse_args( $args, self::$defaults );
		$args = apply_filters( self::$post_type_name . '_output_args' , $args );
		extract( $args, EXTR_SKIP );
	
		if ( $echo && !empty($attributes) ) {
		
			$tag = $args['before'] . '<' . $element . ' ';
            
            foreach ($attributes as $attribute => $value) {
            	
            	if ( !empty( $value ) ) 
                	$tag .= $attribute . '="' . $value . '" ';
            
            }
            
            $tag .= '/>' . $args['after'];
            
            return $tag;

		} 
		
		return $attributes;
	
	}

	//create duration output -> http://stackoverflow.com/questions/13301142/php-how-to-convert-string-duration-to-iso-8601-duration-format-ie-30-minute
	public static function time_to_iso8601_duration($time) {
		$units = array(
			"Y" => 365*24*3600,
			"D" =>     24*3600,
			"H" =>        3600,
			"M" =>          60,
			"S" =>           1,
		);
	
		$str = "P";
		$istime = false;
	
		foreach ($units as $unitName => &$unit) {
			$quot  = intval($time / $unit);
			$time -= $quot * $unit;
			$unit  = $quot;
			if ($unit > 0) {
				if (!$istime && in_array($unitName, array("H", "M", "S"))) { // There may be a better way to do this
					$str .= "T";
					$istime = true;
				}
				$str .= strval($unit) . $unitName;
			}
		}
	
		return $str;
	}
	
	/**
	 * Check for Nutritional Information
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function recipe_nutritional_info_exist( $id = '' ) {
	
		$exists = false;
	
		if ( empty( $id ) ) {
			global $post;
			$id = $post->ID;
		}

		$nutrition_info = array(
			'calories',
			'carbohydrateContent',
			'cholesterolContent',
			'fatContent',
			'fiberContent',
			'proteinContent',
			'saturatedFatContent',
			'servingSize',
			'sodiumContent',
			'sugarContent',
			'transFatContent',
			'unsaturatedFatContent'
		);	
		
		foreach ( $nutrition_info as $nutrition	) {
		
			$existing_nutrition = get_post_meta( $id, '_recipe_nutrition_' . $nutrition , true );
			
			if ( !empty( $existing_nutrition ) ) {
				$exists = true;
				break;	
			}
				
		}	
		
		return $exists;
		
	}
	
	/**
	 * Check for Ingredients
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */	
	public static function recipe_ingredients_exist( $id = '' ) {
	
		$exists = false;
	
		if ( empty( $id ) ) {
			global $post;
			$id = $post->ID;
		}

		$ingredients_count = get_post_meta( $id, '_recipe_ingredients_count', true );
				
		$ingredient_one = get_post_meta( $post->ID, '_recipe_ingredient_0' , true );

		if ( ( $ingredients_count >= 1 ) && !empty( $ingredient_one ) )
			$exists = true;

		return $exists;
		
	}
			
}

endif;