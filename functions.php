<?php
	
/**
 * alm_acf_loop_repeater_rows
 * Access ACF fields for Repeater and Flexible Content field types.
 *
 * @param {*} $type
 * @param {*} $parent_field_name
 * @param {*} $field_name
 * @param {*} $id
 * @param {*} $options
 * @since 1.3.0
 */
function alm_acf_loop_repeater_rows($type = 'query', $parent_field_name = '', $field_name, $id, $options = ''){      	
	
	if(!$field_name || !$id){
   	return ''; // Exit if empty
	}
	
	$content = '';
	
	if(empty($parent_field_name)){	     
   	// Standard Field 	
		$total = count(get_field($field_name, $id));
		if($type === 'query'){
   		$content = alm_acf_get_repeater_fields($field_name, $id, $options, $total);	
   	}
   	
   } else {
		
		// Sub Fields
		$parent = explode(':', $parent_field_name); // Split into array
		$parent_count = count($parent);
	
		// Loop sub fields to get at the field
		if($parent_count == 1){
			while (have_rows($parent[0], $id)) : the_row();
				$total = count(get_sub_field($field_name, $id));
				if($type === 'query'){
            	$content = alm_acf_get_repeater_fields($field_name, $id, $options, $total);
				}
         endwhile;
		}
		if($parent_count == 2){
         while ( have_rows( $parent[0], $id ) ) : the_row();
            while ( have_rows( $parent[1], $id ) ) : the_row();
               $total = count(get_sub_field($field_name, $id));
               if($type === 'query'){
						$content = alm_acf_get_repeater_fields($field_name, $id, $options, $total);
					}
            endwhile;
         endwhile;
      }
      if($parent_count == 3){
         while ( have_rows( $parent[0], $id ) ) : the_row();
            while ( have_rows( $parent[1], $id ) ) : the_row();
               while ( have_rows( $parent[2], $id ) ) : the_row();
                  $total = count(get_sub_field($field_name, $id));
                  if($type === 'query'){
							$content = alm_acf_get_repeater_fields($field_name, $id, $options, $total);
						}
               endwhile;
            endwhile;
         endwhile;
      }
   }      	
	
	// If count, return the count
   $content = ($type === 'count') ? $total : $content;
   
	return $content;	        
}


	
/**
 * alm_acf_get_repeater_fields
 * Get the fields for Repeater and Flexible Content fields
 *
 * @param {*} $field_name
 * @param {*} $id
 * @param {*} $options
 * @param {*} total
 * @since 1.3.0
 */	

function alm_acf_get_repeater_fields($field_name, $id, $options, $total = 0){
   	
	if(!$field_name || !$id){
   	return ''; // Exit if empty
	}
	
	$content = '';
	$data = '';
	$total = $total;
	$postcount = 0;
	
	
	// Preloaded 
	if($options['preloaded']){
	
   	if(have_rows($field_name, $id)){

			$row_count = 0;

			ob_start();

			while (have_rows($field_name, $id)) : the_row();
			
				// Start displaying rows after the offset
				if ($row_count >= $options['offset']){

					// Exit when rows exceeds max pages
					if ($row_count >= $options['max_pages']) {
						break; // exit early
					}					
					
					// Set ALM Variables		
					$alm_found_posts = $total;
					$alm_page = 1;
					$alm_item = $row_count;
					$alm_current = $alm_item;

					// Repeater Template
					if($options['theme_repeater'] != 'null' && has_action('alm_get_theme_repeater')){
						// Theme Repeater
						do_action('alm_get_theme_repeater', $options['theme_repeater'], $alm_found_posts, $alm_page, $alm_item, $alm_current);
					}else{
						// Repeater
						$type = alm_get_repeater_type($options['repeater']);
						include(alm_get_current_repeater($options['repeater'], $type));
					}				
				}
				
				$row_count++;

			endwhile;

			$content = ob_get_clean();	
							
		}				
	} 			
	
	// Standard			
	else {
		
		if(have_rows($field_name, $id)){
		
			$per_page = ($options['posts_per_page'] * $options['page']) + 1;
			$start = ($options['posts_per_page'] * $options['page']) + $options['offset'];
			$end = $start + $options['posts_per_page'];

			$count = 0;
			$row_counter = 0;

			ob_start();

			while (have_rows($field_name, $id)) : the_row();

				// Only display rows between the values
				if ($row_counter < $options['posts_per_page'] && $count >= $start) {
					$row_counter++;

					// Set ALM Variables
					$alm_found_posts = $total;
					$alm_page = $page + 1;
					$alm_item = $count + 1;
					$alm_current = $row_counter + 1;

					// Repeater Template
					if($options['theme_repeater'] != 'null' && has_action('alm_get_theme_repeater')){
						// Theme Repeater
						do_action('alm_get_theme_repeater', $options['theme_repeater'], $alm_found_posts, $alm_page, $alm_item, $alm_current);
					}else{
						// Repeater
						$type = alm_get_repeater_type($options['repeater']);
						include(alm_get_current_repeater($options['repeater'], $type));
					}

				}
				$count++;

				if($count >= $end){
					break; // exit
				}

			endwhile;

			$acf_data = ob_get_clean();
		
		}
		
		// Return $data
		$content = array(
			'content' => $acf_data,
			'postcount' => $row_counter,
			'totalposts' => $total
		);
		
	}
	
	return $content;
	   	
}



/**
 * alm_acf_loop_gallery_rows
 * Access ACF fields for the Gallery field type.
 *
 * @param {*} $type
 * @param {*} $parent_field_name
 * @param {*} $field_name
 * @param {*} $id
 * @since 1.3.0
 * @return $content
 */
function alm_acf_loop_gallery_rows($type = 'query', $parent_field_name = '', $field_name, $id){      	
	
	if(!$field_name || !$id){
   	return ''; // Exit if empty
	}
	
	$content = '';      
	
	if(empty($parent_field_name)){
		// Standard Field
		while ( have_rows( $field_name, $id ) ) : the_row();
         $content = get_field($field_name, $id);
      endwhile;
      
	} else {
		
		// Sub Fields
		$parent = explode(':', $parent_field_name); // Split into array
		$parent_count = count($parent);
	
		// Loop sub fields to get at the field
		if($parent_count == 1){
         while ( have_rows( $parent[0], $id ) ) : the_row();
            $content = get_sub_field($field_name, $id);
         endwhile;
      }
      if($parent_count == 2){
         while ( have_rows( $parent[0], $id ) ) : the_row();
            while ( have_rows( $parent[1], $id ) ) : the_row();
               $content = get_sub_field($field_name, $id);
            endwhile;
         endwhile;
      }
      if($parent_count == 3){
         while ( have_rows( $parent[0], $id ) ) : the_row();
            while ( have_rows( $parent[1], $id ) ) : the_row();
               while ( have_rows( $parent[2], $id ) ) : the_row();
                  $content = get_sub_field($field_name, $id);
               endwhile;
            endwhile;
         endwhile;
      }
   }
   
   // If count, return the count
   $content = ($type === 'count') ? count($content) : $content;
   
   return $content;     
   
}



/**
 * alm_acf_loop_relationship_rows
 * Access nested ACF fields for the Relationship field type.
 *
 * @param {*} $parent_field_name
 * @param {*} $field_name
 * @param {*} $id
 * @since 1.3.0
 * @return $content
 */
function alm_acf_loop_relationship_rows($parent_field_name = '', $field_name, $id){      	
	
	if(!$field_name || !$id){
   	return ''; // Exit if empty
	}
	
	$content = '';  
		
	// Sub Fields
	$parent = explode(':', $parent_field_name); // Split into array
	$parent_count = count($parent);

	// Loop sub fields to get at the field
	if($parent_count == 1){
      while ( have_rows( $parent[0], $id ) ) : the_row();
         $content = get_sub_field($field_name, $id);
      endwhile;
   }
   if($parent_count == 2){
      while ( have_rows( $parent[0], $id ) ) : the_row();
         while ( have_rows( $parent[1], $id ) ) : the_row();
            $content = get_sub_field($field_name, $id);
         endwhile;
      endwhile;
   }
   if($parent_count == 3){
      while ( have_rows( $parent[0], $id ) ) : the_row();
         while ( have_rows( $parent[1], $id ) ) : the_row();
            while ( have_rows( $parent[2], $id ) ) : the_row();
               $content = get_sub_field($field_name, $id);
            endwhile;
         endwhile;
      endwhile;
   }
   
   return $content;     
   
}