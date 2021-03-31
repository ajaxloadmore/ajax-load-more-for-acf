<?php
/*
Plugin Name: Ajax Load More for ACF
Plugin URI: http://connekthq.com/plugins/ajax-load-more/extensions/advanced-custom-fields/
Description: An Ajax Load More extension that adds compatibility for ACF field types.
Text Domain: ajax-load-more-for-acf
Author: Darren Cooney
Twitter: @KaptonKaos
Author URI: https://connekthq.com
Version: 1.3.0.1
License: GPL
Copyright: Darren Cooney & Connekt Media
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


define('ALM_ACF_PATH', plugin_dir_path(__FILE__));
define('ALM_ACF_URL', plugins_url('', __FILE__));



/*
*  alm_acf_install
*  Install the add-on
*
*  @since 1.0
*/

register_activation_hook( __FILE__, 'alm_acf_install' );
function alm_acf_install() {
   if(!is_plugin_active('ajax-load-more/ajax-load-more.php')){	//if Ajax Load More is activated
   	die('You must install and activate <a href="https://wordpress.org/plugins/ajax-load-more/">Ajax Load More</a> before installing the Advanced Custom Fields extension.');
	}
}



if(!class_exists('ALM_ACF')) :

   class ALM_ACF{

   	function __construct(){
   		add_action( 'alm_acf_installed', array(&$this, 'alm_acf_installed') );
   	   add_filter( 'alm_acf_shortcode', array(&$this, 'alm_acf_shortcode'), 10, 6 );
   		add_filter( 'alm_acf_preloaded', array(&$this, 'alm_acf_preloaded_query'), 10, 3 );
   	   add_action( 'wp_ajax_alm_acf', array(&$this, 'alm_acf_query') );
   	   add_action( 'wp_ajax_nopriv_alm_acf', array(&$this, 'alm_acf_query') );
   		add_filter( 'alm_acf_total_rows', array(&$this, 'alm_acf_total_rows'), 10, 1 );

   		$this->alm_acf_includes();
      }


      /**
   	 * alm_acf_includes
   	 * Load these files before the theme loads
   	 *
   	 * @since 1.3.0
   	 */
      public function alm_acf_includes(){
      	include_once( ALM_ACF_PATH . 'functions.php'); // Load Functions
      }



      /**
   	 *  alm_acf_total_rows
   	 *  Count total rows for Repeater field
   	 *
   	 *  /ajax-load-more/classes/class.alm-shortcode
   	 *
   	 *  @since 1.0
   	 */
   	public function alm_acf_total_rows($args){

	   	$acf = (isset($args['acf'])) ? $args['acf'] : false; // true / false
			$post_id = (isset($args['acf_post_id'])) ? $args['acf_post_id'] : ''; // Post ID
			$field_type = (isset($args['acf_field_type'])) ? $args['acf_field_type'] : 'repeater'; // ACF Field Type
			$field_name = (isset($args['acf_field_name'])) ? $args['acf_field_name'] : ''; // ACF Field Name
			$parent_field_name = (isset($args['acf_parent_field_name'])) ? $args['acf_parent_field_name'] : ''; // ACF Parent Field Name
			$total = 0;

			if(empty($post_id)){
				$post_id = (isset($args['post_id'])) ? $args['post_id'] : ''; // Backup Post ID
			}

			if($acf && $post_id && ($field_type !== 'relationship') ){

				if($parent_field_name) {

					// Gallery
					if($field_type === 'gallery'){
						$total = alm_acf_loop_gallery_rows('count', $parent_field_name, $field_name, $post_id);
					}

					// Repeater OR Flexible Content
					if($field_type === 'repeater' || $field_type === 'flexible'){
						$total = alm_acf_loop_repeater_rows('count', $parent_field_name, $field_name, $post_id);
					}


				} else {

					$total = count( get_field( $field_name, $post_id ) );

				}
			}

			return $total;
	   }



      /**
   	 *  alm_acf_preloaded
   	 *  Preloaded Query for Advanced Custom Fields
   	 *
   	 *  @since 1.0
   	 */
   	public function alm_acf_preloaded_query($args, $repeater, $theme_repeater){

         $acf_data = '';

      	$acf = (isset($args['acf'])) ? $args['acf'] : false; // true / false
			$acf_post_id = (isset($args['acf_post_id'])) ? $args['acf_post_id'] : ''; // Post ID
			$field_type = (isset($args['acf_field_type'])) ? $args['acf_field_type'] : 'repeater'; // ACF Field Type
			$field_name = (isset($args['acf_field_name'])) ? $args['acf_field_name'] : ''; // ACF Field Name
			$parent_field_name = (isset($args['acf_parent_field_name'])) ? $args['acf_parent_field_name'] : ''; // ACF Parent Field Name

         $posts_per_page = (isset($args['posts_per_page'])) ? $args['posts_per_page'] : 5;
         $offset = (isset($args['offset'])) ? (int)$args['offset'] : 0;
         $max_pages = $posts_per_page + $offset;         
         
         // Check for empty ACF post ID
         $post_id = (empty($acf_post_id)) ? $args['post_id'] : $acf_post_id;
                  
   		// Repeater OR Flexible Content
			if($field_type === 'repeater' || $field_type === 'flexible'){

				$options = array(
					'posts_per_page' => $posts_per_page,
					'offset' => $offset,
					'max_pages' => $max_pages,
					'repeater' => $repeater,
					'theme_repeater' => $theme_repeater,
					'preloaded'	=> true
				);

				$acf_data = alm_acf_loop_repeater_rows('query', $parent_field_name, $field_name, $post_id, $options);

			}


			// Gallery
			if($field_type === 'gallery'){

				// Get Images
				$images = alm_acf_loop_gallery_rows('query', $parent_field_name, $field_name, $post_id);

				if($images) {

   				$total = count($images);
					$max_pages = $posts_per_page + $offset;

					$row_count = 0;

   				ob_start();

					foreach( $images as $key=>$image ) :

						// Start displaying rows after the offset
						if ($key >= $offset){

							// Exit when rows exceeds max pages
							if ($key >= $max_pages) {
								break; // exit early
							}
							$row_count++;

							// Set ALM Variables
							$alm_found_posts = $total;
							$alm_page = 1;
							$alm_item = $row_count;
							$alm_current = $alm_item;

							if($theme_repeater != 'null' && has_action('alm_get_acf_gallery_theme_repeater')){
								// Theme Repeater
	                     do_action('alm_get_acf_gallery_theme_repeater', $theme_repeater, $alm_found_posts, $alm_page, $alm_item, $alm_current, $image);
							}else{
								// Repeater
	                     $type = alm_get_repeater_type($repeater);
	                     include(alm_get_current_repeater( $repeater, $type ));
							}

						}

					endforeach;

					$acf_data = ob_get_clean();
				}
			}

			return $acf_data;

   	}



   	/**
   	 *  alm_acf_query
   	 *  Ajax Query ACF fields and return data to ALM
   	 *
   	 *  @since 1.0
   	 */
   	function alm_acf_query(){

		   $data = (isset($_GET['acf'])) ? $_GET['acf'] : ''; // $acf object array

		   $repeater = (isset($_GET['repeater'])) ? $_GET['repeater'] : 'default';
			$type = alm_get_repeater_type($repeater);
			$theme_repeater = (isset($_GET['theme_repeater'])) ? $_GET['theme_repeater'] : 'null';
		   $posts_per_page = (isset($_GET['posts_per_page'])) ? $_GET['posts_per_page'] : 5;
		   $page = (isset($_GET['page'])) ? $_GET['page'] : 1;
		   $offset = (isset($_GET['offset'])) ? $_GET['offset'] : 0;
		   $canonical_url = (isset($_GET['canonical_url'])) ? $_GET['canonical_url'] : $_SERVER['HTTP_REFERER'];

		   // Cache Add-on
		   $cache_id = (isset($_GET['cache_id'])) ? $_GET['cache_id'] : '';

		   // Preload Add-on
			$preloaded = (isset($_GET['preloaded'])) ? $_GET['preloaded'] : false;
			$preloaded_amount = (isset($_GET['preloaded_amount'])) ? $_GET['preloaded_amount'] : '5';
			if(has_action('alm_preload_installed') && $preloaded === 'true'){
			   // If preload - offset the posts_per_page + preload_amount
			   $old_offset = $preloaded_amount;
			   $offset = $offset + $preloaded_amount;
		   }

		   // SEO Add-on
			$seo_start_page = (isset($_GET['seo_start_page'])) ? $_GET['seo_start_page'] : 1;

			$postcount = 0;
			$totalposts = 0;


		   /*
			 *	alm_cache_create_dir
			 *
			 * Cache Add-on hook
			 * Create cache directory + meta .txt file
			 *
			 * @return null
			 */
		   if(!empty($cache_id) && has_action('alm_cache_create_dir')){
		      apply_filters('alm_cache_create_dir', $cache_id, $canonical_url);
		      $page_cache = ''; // set our page cache variable
		   }


		   if($data){

		      $acf_data = '';
		      $acf = (isset($data['acf'])) ? $data['acf'] : false; // true / false
				$post_id = (isset($data['post_id'])) ? $data['post_id'] : ''; // Post ID
				$field_type = (isset($data['field_type'])) ? $data['field_type'] : 'repeater'; // ACF Field Type
				$field_name = (isset($data['field_name'])) ? $data['field_name'] : ''; // ACF Field Name
				$parent_field_name = (isset($data['parent_field_name'])) ? $data['parent_field_name'] : ''; // ACF Parent Field Name
				$max_pages = 99999;

				if(empty($field_name) || empty($post_id)){
					$acf = false; // If field_name and post_id are not set, exit
				}

				if($acf && $post_id){

					// Repeater OR Flexible Content
					if($field_type === 'repeater' || $field_type === 'flexible'){

						$options = array(
							'page' => $page,
							'posts_per_page' => $posts_per_page,
							'offset' => $offset,
							'max_pages' => $max_pages,
							'repeater' => $repeater,
							'theme_repeater' => $theme_repeater,
							'preloaded'	=> false
						);

						$data = alm_acf_loop_repeater_rows('query', $parent_field_name, $field_name, $post_id, $options);

						// Parse return data
						$acf_data = ($data && $data['content']) ? $data['content'] : '';
						$postcount = ($data && $data['postcount']) ? $data['postcount'] : '';
						$total = ($data && $data['totalposts']) ? $data['totalposts'] : '';


	               /*
	                *	alm_cache_file
	                *
	                * Cache Add-on hook
	                * If Cache is enabled, check the cache file
	                *
	                * @return null
	                */
	               if(!empty($cache_id) && has_action('alm_cache_installed') && !empty($acf_data)){
	               	apply_filters('alm_cache_file', $cache_id, $page, $seo_start_page, $acf_data, $preloaded);
	            	}

					}

					// Gallery
					if($field_type === 'gallery'){

						// Get Images
						$images = alm_acf_loop_gallery_rows('query', $parent_field_name, $field_name, $post_id);

						if($images) {

							$total = count($images);
							$per_page = ($posts_per_page * $page) + 1;
							$start = ($posts_per_page * $page) + $offset;
							$end = $start + $posts_per_page;
							$count = 0;

							ob_start();
							foreach( $images as $image ) :

								// Only display rows between the values
								if ($postcount < $posts_per_page && $count >= $start) {
									$postcount++;

									// Set ALM Variables
									$alm_found_posts = $total;
									$alm_page = $page + 1;
									$alm_item = $count + 1;
									$alm_current = $postcount + 1;

									if($theme_repeater != 'null' && has_action('alm_get_acf_gallery_theme_repeater')){
										// Theme Repeater
										do_action('alm_get_acf_gallery_theme_repeater', $theme_repeater, $alm_found_posts, $alm_page, $alm_item, $alm_current, $image);
									}else{
										// Repeater
										include(alm_get_current_repeater( $repeater, $type ));
									}

								}

								$count++;

								if($count >= $end){
									break; // exit
								}

							endforeach;
							$acf_data = ob_get_clean();


		               /*
		                *	alm_cache_file
		                *
		                * Cache Add-on hook
		                * If Cache is enabled, check the cache file
		                *
		                * @return null
		                */
		               if(!empty($cache_id) && has_action('alm_cache_installed') && !empty($acf_data)){
		                  apply_filters('alm_cache_file', $cache_id, $page, $seo_start_page, $acf_data, $preloaded);
		               }

						}
					}


					if($acf_data){
		            $return = array(
		               'html' => $acf_data,
		               'meta'  => array(
		                  'postcount' => $postcount,
		                  'totalposts' => $total
		               )
		            );
		         } else{
		            $return = array(
		               'html' => '',
		               'meta'  => array(
		                  'postcount' => 0,
		                  'totalposts' => 0
		               )
		            );

		         }

		         wp_send_json($return);

				}
			}

			wp_die();
   	}



   	/**
   	 *  alm_acf_shortcode
   	 *  Build ACF shortcode params and send back to core ALM
   	 *
   	 *  @since 1.0
   	 */
   	function alm_acf_shortcode($acf, $acf_field_type, $acf_field_name, $acf_post_id, $post_id, $acf_parent_field_name){
   		$return  = ' data-acf="'.$acf.'"';
   		$return .= ' data-acf-field-type="'.$acf_field_type.'"';
   		$return .= ' data-acf-field-name="'.$acf_field_name.'"';
   		if($acf_parent_field_name){
	   		$return .= ' data-acf-parent-field-name="'.$acf_parent_field_name.'"';
   		}
   		if(empty($acf_post_id)){
      		$acf_post_id = $post_id;
   		}
   		$return .= ' data-acf-post-id="'.$acf_post_id.'"';

		   return $return;
   	}
   }



   /**
   *  ALM_ACF
   *  The main function responsible for returning the one true ALM_ACF Instance.
   *
   *  @since 1.0
   */
   function ALM_ACF(){
      global $ALM_ACF;

      if( !isset($ALM_ACF) ){
         $ALM_ACF = new ALM_ACF();
      }

      return $ALM_ACF;
   }
   ALM_ACF(); // initialize

endif;
