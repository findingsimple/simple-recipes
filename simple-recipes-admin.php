<?php

if ( ! class_exists( 'Simple_Recipes_Admin' ) ) {

	/**
	 * So that themes and other plugins can customise the text domain, the Simple_Recipes_Admin should
	 * not be initialized until after the plugins_loaded and after_setup_theme hooks.
	 * However, it also needs to run early on the init hook.
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Recipes
	 * @since 1.0
	 */
	function simple_initialize_recipes_admin() {
		Simple_Recipes_Admin::init();
	}
	add_action( 'init', 'simple_initialize_recipes_admin', -1 );
	
	
	class Simple_Recipes_Admin {
	
		public static function init() {  
	
			/* create custom plugin settings menu */
			add_action( 'admin_menu',  __CLASS__ . '::simple_recipes_create_menu' );
		
		}
	
		public static function simple_recipes_create_menu() {
	
			//create new top-level menu
			add_options_page( 'Recipe Settings', 'Recipes', 'administrator', 'simple_recipes' , __CLASS__ . '::simple_recipes_settings_page' );
	
			//call register settings function
			add_action( 'admin_init',  __CLASS__ . '::register_mysettings' );
	
		}
	
	
		public static function register_mysettings() {
		
			$page = 'simple_recipes-settings'; 
	
			//general settings
			//add_settings_section( 
			//	'simple_agls-namespace', 
			//	'Namespace Settings',
			//	__CLASS__ . '::simple_agls_namespace_callback',
			//	$page
			//);
	
			//add_settings_field(
			//	'simple_agls-toggle-dublin-core-namespace',
			//	'Toggle Dublin Core Namespace',
			//	__CLASS__ . '::simple_agls_toggle_dublin_core_namespace_callback',
			//	$page,
			//	'simple_agls-namespace'
			//);
			
			//register our settings
			
			//register_setting( $page, 'simple_agls-toggle-dublin-core-namespace' );
	
	
		}
	
		public static function simple_recipes_settings_page() {
		
			$page = 'simple_recipes-settings'; 
		
		?>
		<div class="wrap">
		
			<div id="icon-options-general" class="icon32"><br /></div><h2>Recipe Settings</h2>
			
			<?php settings_errors(); ?>
		
			<form method="post" action="options.php">
				
				<?php settings_fields( $page ); ?>
				
				<?php do_settings_sections( $page ); ?>
			
				<p class="submit">
					<input type="submit" class="button-primary" value="Save Changes" />
				</p>
			
			</form>
			
		</div>
		
		<?php 
		} 
	
	//	public static function simple_recipes_xyz_callback() {
			
			//do nothing
			
	//	}
		
	//	public static function simple_recipes_toggle_xyz_callback() {
		
	//		echo '<input name="simple_agls-toggle-dublin-core-namespace" id="simple_agls-toggle-dublin-core-namespace" type="checkbox" value="1" class="code" ' . checked( 1, get_option('simple_agls-toggle-dublin-core-namespace'), false ) . ' /> Show Dublin Core namespace';
			
	//	}
		
	
	} //end class

} //end if class doesn't exist