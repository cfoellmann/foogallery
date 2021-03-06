<?php

/**
 * Template loader for FooGallery
 *
 * @package FooGallery
 * @author  Brad vincent
 */
class FooGallery_Template_Loader {

	/**
	 * Locates and renders the gallery based on the template
	 * Will look in the following locations
	 *  wp-content/themes/{child-theme}/foogallery/gallery-{template}.php
	 *    wp-content/themes/{theme}/foogallery/gallery-{template}.php
	 *  wp-content/plugins/foogallery/templates/gallery-{template}.php
	 *
	 * @param $args array       Arguments passed in from the shortcode
	 */
	public function render_template($args) {
		//do some work before we locate the template
		global $current_foogallery;
		global $current_foogallery_arguments;
		global $current_foogallery_template;

		//set the arguments
		$current_foogallery_arguments = $args;

		//load our gallery
		$current_foogallery = $this->find_gallery( $args );

		//find the gallery template we will use to render the gallery
		$current_foogallery_template = $this->get_arg( $args, 'template',
			$current_foogallery->gallery_template );

		//set a default if we have no gallery template
		if ( empty($current_foogallery_template) ) {
			$current_foogallery_template = foogallery_get_default( 'gallery_template' );
		}

		//check if we have any attachments
		if ( !$current_foogallery->has_attachments() ) {
			//no attachments!
			do_action( "foogallery_template_no_attachments-($current_foogallery_template)", $current_foogallery );
		} else {

			//create locator instance
			$instance_name = FOOGALLERY_SLUG . '_gallery_templates';
			$loader = new Foo_Plugin_File_Locator_v1( $instance_name, FOOGALLERY_FILE, 'templates', FOOGALLERY_SLUG );

			//allow extensions to very easily add pickup locations for their files
			$this->add_extension_pickup_locations( $loader, apply_filters( $instance_name . '_files', array() ) );

			if ( false !== ($template_location = $loader->locate_file( "gallery-{$current_foogallery_template}.php" )) ) {

				//we have found a template!
				do_action( "foogallery_located_template", $current_foogallery );
				do_action( "foogallery_located_template-{$current_foogallery_template}", $current_foogallery );

				//try to include some JS
				if ( false !== ($js_location = $loader->locate_file( "gallery-{$current_foogallery_template}.js" )) ) {
					wp_enqueue_script( "foogallery-template-{$current_foogallery_template}", $js_location['url'] );
				}

				//try to include some CSS
				if ( false !== ($css_location = $loader->locate_file( "gallery-{$current_foogallery_template}.css" )) ) {
					wp_enqueue_style( "foogallery-template-{$current_foogallery_template}", $css_location['url'] );
				}

				//finally include the actual php template!
				if ( $template_location ) {
					load_template( $template_location['path'], false );
				}

				//we have loaded all files, now let extensions do some stuff
				do_action( "foogallery_loaded_template", $current_foogallery );
				do_action( "foogallery_loaded_template-($current_foogallery_template)", $current_foogallery );

			} else {
				//we could not find a template!
				echo __( 'No gallery template found!', 'foogallery' );
			}
		}
	}

	/**
	 * Add pickup locations to the loader to make it easier for extensions
	 *
	 * @param $loader			Foo_Plugin_File_Locator_v1
	 * @param $extension_files	array
	 */
	function add_extension_pickup_locations( $loader, $extension_files ) {
		if ( count( $extension_files ) > 0 ) {
			$position = 120;
			foreach ( $extension_files as $file ) {

				//add pickup location for php template
				$loader->add_location( $position, array(
					'path' => trailingslashit( plugin_dir_path( $file ) ),
					'url'  => trailingslashit( plugin_dir_url( $file ) )
				) );

				$position++;

				//add pickup location for extensions js folder
				$loader->add_location( $position, array(
					'path' => trailingslashit( plugin_dir_path( $file ) . 'js' ),
					'url'  => trailingslashit( plugin_dir_url( $file ) . 'js' )
				) );

				$position++;

				//add pickup location for extension css folder
				$loader->add_location( $position, array(
					'path' => trailingslashit( plugin_dir_path( $file ) . 'css' ),
					'url'  => trailingslashit( plugin_dir_url( $file ) . 'css' )
				) );

				$position++;

			}
		}
	}

	/**
	 * load the gallery based on either the id or slug, passed in via arguments
	 *
	 * @param $args array       Arguments passed in from the shortcode
	 *
	 * @return bool|FooGallery  The gallery object we want to render
	 */
	function find_gallery($args) {

		$id = intval( $this->get_arg( $args, 'id' ), 0 );

		if ( $id > 0 ) {

			//load gallery by ID
			return FooGallery::get_by_id( $id );

		} else {

			//take into account the cases where id is passed in via the 'gallery' attribute
			$gallery = $this->get_arg( 'gallery', 0 );

			if ( intval( $gallery ) > 0 ) {
				//we have an id, so load
				return FooGallery::get_by_id( intval( $gallery ) );
			}

			//we are dealing with a slug
			return FooGallery::get_by_slug( $gallery );
		}
	}

	/**
	 * Helper to get an argument value from an array arguments
	 *
	 * @param $args    Array    the array of arguments to search
	 * @param $key     string   the key of the argument you are looking for
	 * @param $default string   a default value if the argument is not found
	 *
	 * @return string
	 */
	function get_arg($args, $key, $default = '') {
		if ( empty($args) || !array_key_exists( $key, $args ) ) {
			return $default;
		}

		return $args[$key];
	}
}
