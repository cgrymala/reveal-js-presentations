<?php
if ( ! class_exists( 'Reveal_Presentations' ) ) {
	class Reveal_Presentations {
		var $version = '0.1.2';
		var $defaults = array(
			'theme'       => 'default', 
			'controls'    => true, 
			'progress'    => true, 
			'slideNumber' => true, 
			'history'     => false, 
			'keyboard'    => true, 
			'overview'    => true, 
			'center'      => true, 
			'touch'       => true, 
			'loop'        => false, 
			'rtl'         => false, 
			'fragments'   => false, 
			'embedded'    => false, 
			'autoSlide'   => 0, 
			'autoSlideStoppable' => true, 
			'mouseWheel'  => false, 
			'hideAddressBar' => true, 
			'previewLinks' => false, 
			'transition'  => 'default', 
			'transitionSpeed' => 'default', 
			'backgroundTransition' => 'default', 
			'viewDistance' => 3, 
			'parallaxBackgroundImage' => '', 
			'parallaxBackgroundSize' => '', 
			'customCSS' => null, 
		);
		var $themes = array( 'default', 'beige', 'sky', 'night', 'serif', 'simple', 'solarized', 'none' );
		var $transitions = array( 'default', 'cube', 'page', 'concave', 'zoom', 'linear', 'fade', 'none' );
		var $customcss = '';
		
		/**
		 * Create our Reveal_Presentations object
		 */
		function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_filter( 'template_include', array( $this, 'template_include' ), 99 );
			add_action( 'template_redirect', array( $this, 'template_redirect' ), 1 );
			add_action( 'wp_head', array( $this, 'header_code' ), 99 );
			add_filter( 'post_type_link', array( $this, 'slide_link' ), 10, 2 );
		}
		
		/**
		 * Pull in the custom template file if this is a presentation
		 */
		function template_include( $template ) {
			if ( ! is_tax( 'presentation' ) )
				return $template;
			
			$opt = $this->get_presentation_meta();
			$templates = array();
			if ( is_object( $opt ) && property_exists( $opt, 'slug' ) ) {
				$templates[] = sprintf( 'taxonomy-presentation-%s.php', $opt->slug );
			}
			$templates[] = 'taxonomy-presentation.php';
			
			$tmp = locate_template( $templates );
			if ( '' != $tmp )
				return $tmp;
			
			return plugin_dir_path( dirname( __FILE__ ) ) . '/templates/taxonomy-presentation.php';
		}
		
		/**
		 * Modify the permalink for an individual slide to lead to that point in the presentation
		 */
		function slide_link( $url, $post ) {
			if ( 'slides' != get_post_type( $post ) )
				return $url;
			
			$terms = get_the_terms( $post->ID, 'presentation' );
			if ( false === $terms )
				return $url;
			
			$pres = array_shift( $terms );
			
			if ( is_post_type_archive( 'slides' ) && is_main_query() ) {
				return sprintf( '%1$s', trailingslashit( get_term_link( $pres ) ) );
			}
			
			return sprintf( '%1$s#/rjs-slide-%2$d', trailingslashit( get_term_link( $pres ) ), $post->ID );
		}
		
		/**
		 * Perform any actions that need to happen during template_redirect
		 */
		function template_redirect() {
			if ( is_singular( 'slides' ) ) {
				$post_ID = get_the_ID();
				$presentations = get_the_terms( $post_ID, 'presentation' );
				if ( ! is_array( $presentations ) )
					return;
				$pres = array_shift( $presentations );
				wp_safe_redirect( trailingslashit( get_term_link( $pres ) ) . '#/rjs-slide-' . $post_ID );
			}
			
			if ( ! is_post_type_archive( 'slides' ) ) 
				return;
			
			/*if ( function_exists( 'genesis' ) ) {
				remove_all_actions( 'genesis_loop' );
				add_action( 'genesis_loop', array( $this, 'do_presentation_list_genesis' ) );
			} else {*/
				add_action( 'loop_start', array( $this, 'alter_preso_list_query' ), 1 );
				/*$this->alter_preso_list_query();*/
				add_action( 'loop_end', 'wp_reset_query', 1 );
				add_action( 'loop_end', 'wp_reset_postdata', 2 );
			/*}*/
		}
		
		/**
		 * Alter the query to short-circuit the list of presentations
		 */
		function alter_preso_list_query() {
			if ( ! is_main_query() )
				return;
			
			global $wp_query, $post;
			$presentations = get_terms( 'presentation', array(
				'orderby' => 'id name', 
				'order'   => 'ASC', 
			) );
			
			if ( empty( $presentations ) ) {
				print( "\n<!-- We could not find any presentations, so we're bouncing out and returning an empty query. -->\n" );
				query_posts( array( 'name' => 'rjs-presentations-fake-slug-to-return-no-posts' ) );
				return;
			}
			
			$posts = array();
			foreach( $presentations as $p ) {
				$first = new WP_Query( array( 
					'post_type' => 'slides', 
					'post_status' => 'publish', 
					'orderby' => 'menu_order date', 
					'order' => 'ASC', 
					'posts_per_page' => 1, 
					'numberposts' => 1, 
					'post_parent' => 0, 
					'presentation' => $p->slug, 
				) );
				if ( property_exists( $first, 'posts' ) && is_array( $first->posts ) ) {
					$tmp = array_shift( $first->posts );
					if ( has_post_thumbnail( $tmp->ID ) ) {
						$tmp->post_content = '<a href="' . get_term_link( $p ) . '">' . get_the_post_thumbnail( $tmp->ID, 'full', array( 'class' => 'alignnone' ) ) . '</a>';
					}
					$tmp->post_title = $p->name;
					$posts[] = $tmp;
				}
			}
			
			if ( empty( $posts ) ) {
				print( "\n<!-- Even though we found presentation terms, we found no posts inside any of them. -->\n" );
				query_posts( array( 'name' => 'rjs-presentations-fake-slug-to-return-no-posts' ) );
				return;
			} else {
				printf( "\n<!-- We found a total of %d posts inside of the various presentation terms, and are setting some vars to reflect that. -->\n", count( $posts ) );
				$wp_query->posts = $posts;
				$wp_query->post_count = count( $posts );
				$wp_query->found_posts = count( $posts );
				$wp_query->max_num_pages = 0;
				$post = $posts[0];
				return;
			}
		}
		
		/**
		 * Output the list of presentations
		 */
		function do_presentation_list_genesis() {
			$presentations = get_terms( 'presentation', array( 
				'orderby' => 'id name', 
				'order' => 'ASC', 
			) );
			
			if ( empty( $presentations ) ) {
?>
<article class="entry not-found">
	<h1><?php _e( 'Not Found' ) ?></h1>
	<div class="entry-content">
		<?php _e( 'Unfortunately, no presentations could be found.' ) ?>
	</div>
</article>
<?php
			}
			
			foreach ( $presentations as $p ) {
				$first = new WP_Query( array( 
					'post_type' => 'slides', 
					'post_status' => 'publish', 
					'orderby' => 'menu_order date', 
					'order' => 'ASC', 
					'posts_per_page' => 1, 
					'numberposts' => 1, 
					'post_parent' => 0, 
					'presentation' => $p->slug, 
				) );
?>
<article class="entry">
	<h1><a href="<?php echo get_term_link( $p ) ?>"><?php echo apply_filters( 'the_title', $p->name ) ?></a></h1>
	<div class="entry-content">
<?php
				if ( $first->have_posts() ) : while ( $first->have_posts() ) : $first->the_post();
?>
		<?php the_post_thumbnail( 'full', array( 'class' => 'aligncenter' ) ) ?>
<?php
				endwhile; endif;
?>
		<?php echo apply_filters( 'the_content', $p->description ) ?>
	</div>
</article>
<?php
			}
		}
		
		/**
		 * Perform any actions that need to happen on the init hook
		 */
		function init() {
			return;
		}
		
		/**
		 * Register the slide post type and the presentation taxonomy
		 */
		function register_post_types() {
			/**
			 * Set up the slide post type
			 */
			$labels = array(
				'name'				=> _x('Presentation Slides', 'post type general name'),
				'singular_name' 	=> _x('Presentation Slide', 'post type singular name'),
				'add_new' 			=> _x('Add New', 'announcement'),
				'add_new_item' 		=> __('Add New Slide'),
				'edit_item' 		=> __('Edit Slide'),
				'new_item' 			=> __('New Slide'),
				'all_items' 		=> __('All Slides'),
				'view_item' 		=> __('View Slide'),
				'search_items' 		=> __('Search Slides'),
				'not_found' 		=>  __('No slides found'),
				'not_found_in_trash'=> __('No slides found in Trash'), 
				'parent_item_colon'	=> '',
				'menu_name' 		=> __( 'Pres. Slides' ),
			);
			$args = array(
				'labels' 			=> $labels,
				'public' 			=> true,
				'publicly_queryable'=> true,
				'show_ui' 			=> true, 
				'show_in_menu' 		=> true, 
				'query_var' 		=> true,
				'rewrite' 			=> true,
				'capability_type' 	=> 'post',
				'has_archive' 		=> true, 
				'hierarchical' 		=> true,
				'menu_position' 	=> null,
				'supports' 			=> array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
			);
			register_post_type( 'slides', $args );
			
			/**
			 * Set up the presentation taxonomy
			 */
			$labels = array(
				'name'				=> _x( 'Presentations', 'taxonomy general name' ),
				'singular_name'		=> _x( 'Presentation', 'taxonomy singular name' ),
				'search_items'		=> __( 'Search Presentations' ),
				'popular_items'		=> __( 'Popular Presentations' ),
				'all_items'			=> __( 'All Presentations' ),
				'parent_item'		=> __( 'Parent Presentation' ),
				'parent_item_colon'	=> __( 'Parent Presentation:' ),
				'edit_item'			=> __( 'Edit Presentation' ),
				'update_item'		=> __( 'Update Presentation' ),
				'add_new_item'		=> __( 'Add New Presentation' ),
				'new_item_name'		=> __( 'New Presentation Name' ),
				'add_or_remove_items'	=> __( 'Add or remove presentations' ),
			);
			$args = array(
				'labels'       => $labels,
				'public'       => true,
				'hierarchical' => true, 
				'rewrite'      => true, 
				'sort'         => true, 
			);
			register_taxonomy( 'presentation', 'slides', $args );
			
			/**
			 * Register the action we need for the presentation settings
			 */
			add_action( 'presentation_edit_form_fields', array( $this, 'edit_presentation_form_fields' ) );
			add_action( 'presentation_add_form_fields', array( $this, 'add_presentation_form_fields' ) );
			add_action( 'get_presentation', array( $this, 'get_presentation_meta' ) );
			add_action( 'created_term', array( $this, 'save_presentation_term' ) );
			add_action( 'edited_term', array( $this, 'save_presentation_term' ) );
			
			return;
		}
		
		function add_presentation_form_fields( $term ) {
			$this->_presentation_form_fields( $term, 'add' );
		}
		
		function edit_presentation_form_fields( $term ) {
			$this->_presentation_form_fields( $term, 'edit' );
		}
		
		/**
		 * Render the form fields we need for presentation settings
		 */
		function _presentation_form_fields( $term, $addedit='add' ) {
			$vals = $this->get_presentation_settings( $term );
			/**
			 * Output our normal settings fields
			 */
			
			if ( 'add' == $addedit ) {
				$hformat = '%s';
				$format = '<div class="form-field">%s %s</div>';
			} else {
				$hformat = '<tr><th scope="col" colspan="2">%s</th></tr>';
				$format = '<tr><th scope="row" valign="top">%s</th><td>%s</td></tr>';
			}
			
			printf( $hformat, '<h3>' . __( 'Presentation Settings' ) . '</h3>' . wp_nonce_field( 'presentation-setting-fields', '_rp_dim_nonce', true, false ) . '<input type="hidden" name="' . $this->presentation_meta_name( 'action', false ) . '" value="' . $addedit . '"/>' );
			/* The theme selector */
			$l = sprintf( '<label for="%s">%s</label>', $this->presentation_meta_id( 'theme', false ), __( 'Theme:' ) );
			$f = sprintf( '<select name="%s" id="%s">', $this->presentation_meta_name( 'theme', false ), $this->presentation_meta_id( 'theme', false ) );
			foreach ( $this->themes as $opt ) {
				$f .= sprintf( '<option value="%s"%s>%s</option>', $opt, selected( $vals['theme'], $opt, false ), ucfirst( $opt ) );
			}
			$f .= '</select>';
			printf( $format, $l, $f );
			
			/* The transition selector */
			$l = sprintf( '<label for="%s">%s</label>', $this->presentation_meta_id( 'transition', false ), __( 'Transition:' ) );
			$f = sprintf( '<select name="%s" id="%s">', $this->presentation_meta_name( 'transition', false ), $this->presentation_meta_id( 'transition', false ) );
			foreach ( $this->transitions as $opt ) {
				$f .= sprintf( '<option value="%s"%s>%s</option>', $opt, selected( $vals['transition'], $opt, false ), ucfirst( $opt ) );
			}
			$f .= '</select>';
			printf( $format, $l, $f );
			
			/* The transition speed field */
			$l = sprintf( '<label for="%s">%s</label>', $this->presentation_meta_id( 'transitionSpeed', false ), __( 'Transition speed:' ) );
			$f = sprintf( '<select name="%s" id="%s">', $this->presentation_meta_name( 'transitionSpeed', false ), $this->presentation_meta_id( 'transitionSpeed', false ) );
			foreach ( array( 'default', 'fast', 'slow' ) as $opt ) {
				$f .= sprintf( '<option value="%s"%s>%s</option>', $opt, selected( $vals['transitionSpeed'], $opt, false ), ucfirst( $opt ) );
			}
			$f .= '</select>';
			printf( $format, $l, $f );
			
			/**
			 * Bail out at this point if we're creating a new presentation. 
			 * 		No need to bombard with advanced settings on the screen where
			 *		new terms are created.
			 */
			if ( 'add' == $addedit ) {
				return;
			}
			/**
			 * Output presentation background settings
			 */
?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<?php _e( 'Parallax background image' ) ?>
			</th>
			<td>
				<input class="attachment-url" id="<?php $this->presentation_meta_id( 'parallaxBackgroundImage' ) ?>" name="<?php $this->presentation_meta_name( 'parallaxBackgroundImage' ) ?>" type="url" value="<?php echo esc_url( $vals['parallaxBackgroundImage'] ) ?>"/>
				<input type="button" class="upload_image_button button" value="<?php _e( 'Upload Image' ) ?>"/>
			</td>
		</tr>
<?php
			/**
			 * Output some advanced settings fields
			 */
?>
		<tr>
        	<th scope="col" colspan="2">
            	<h3><?php _e( 'Advanced Presentation Settings' ) ?></h3>
            </th>
        </tr>
		<tr>
			<th scope="row" valign="top">
				<label for="<?php $this->presentation_meta_id( 'customCSS' ) ?>"><?php _e( 'Custom CSS for this presentation:' ) ?></label>
			</th>
			<td>
				<textarea class="widefat largetext" cols="25" rows="10" name="<?php $this->presentation_meta_name( 'customCSS' ) ?>" id="<?php $this->presentation_meta_id( 'customCSS' ) ?>"><?php echo stripslashes( $vals['customCSS'] ) ?></textarea>
			</td>
		</tr>
<?php
			$boolfields = array(
				'controls' => __( 'Display slide controls?' ), 
				'progress' => __( 'Display presentation progress bar?' ), 
				'slideNumber' => __( 'Display the page number of the current slide?' ), 
				'history'  => __( 'Keep track of slide changes in the address bar?' ), 
				'keyboard' => __( 'Enable keyboard navigation?' ), 
				'overview' => __( 'Enable slide overview mode?' ), 
				'center'   => __( 'Vertically center slides in the window?' ), 
				'touch'    => __( 'Enable touch navigation?' ), 
				'loop'     => __( 'Loop the presentation?' ), 
				'rtl'      => __( 'Set up presentation in RTL mode?' ), 
				'fragments' => __( 'Turn fragments on?' ), 
				'mouseWheel' => __( 'Enable mousewheel navigation?' ), 
				'hideAddressBar' => __( 'Hide the address bar on mobile devices?' ), 
				'previewLinks' => __( 'Open links in a popup preview iFrame?' ), 
			);
			foreach ( $boolfields as $field=>$label ) {
?>
		<tr>
			<th scope="row" valign="top">
				<label for="<?php $this->presentation_meta_id( $field ) ?>"><?php echo $label ?></label>
			</th>
			<td>
				<input type="checkbox" name="<?php $this->presentation_meta_name( $field ) ?>" id="<?php $this->presentation_meta_id( $field ) ?>" value="1"<?php checked( $vals[$field] ) ?>/>
			</td>
		</tr>
<?php
			}
?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="<?php $this->presentation_meta_id( 'autoSlide' ) ?>"><?php _e( 'How long, in milliseconds, should each slide appear on the screen?' ) ?></label>
			</th>
			<td>
				<input type="number" name="<?php $this->presentation_meta_name( 'autoSlide' ) ?>" id="<?php $this->presentation_meta_id( 'autoSlide' ) ?>" value="<?php echo absint( $vals['autoSlide'] ) ?>"/>
				<p style="font-style: italic"><?php _e( 'Leave this setting at 0 if you do not want the slides to advance automatically.' ) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">
				<label for="<?php $this->presentation_meta_id( 'autoSlideStoppable' ) ?>"><?php _e( 'If the presentation auto-advances, do you want it to stop after user interaction?' ) ?></label>
			</th>
			<td>
				<input type="checkbox" name="<?php $this->presentation_meta_name( 'autoSlideStoppable' ) ?>" id="<?php $this->presentation_meta_id( 'autoSlideStoppable' ) ?>" value="1"<?php checked( $vals['autoSlideStoppable'] ) ?>/>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="<?php $this->presentation_meta_id( 'viewDistance' ) ?>"><?php _e( 'Show how many slides on either side of current slide in overview mode?' ) ?></label>
			</th>
			<td>
				<input type="number" name="<?php $this->presentation_meta_name( 'viewDistance' ) ?>" id="<?php $this->presentation_meta_id( 'viewDistance' ) ?>" value="<?php echo absint( $vals['viewDistance'] ) ?>"/>
			</td>
		</tr>
<?php
		}
		
		/**
		 * Save the presentation settings
		 */
		function save_presentation_term( $term_id ) {
			if ( ! wp_verify_nonce( $_POST['_rp_dim_nonce'], 'presentation-setting-fields' ) )
				return $term_id;
			
			$opts = array();
			$instance = array();
			if ( isset( $_POST['presentation_meta'] ) )
				$instance = $_POST['presentation_meta'];
			elseif ( isset( $_GET['presentation_meta'] ) )
				$instance = $_GET['presentation_meta'];
			if ( empty( $instance ) ) {
				return false;
			}
			
			$boolfields = array(
				'controls'    => true, 
				'progress'    => true, 
				'slideNumber' => true, 
				'history'     => false, 
				'keyboard'    => true, 
				'overview'    => true, 
				'center'      => true, 
				'touch'       => true, 
				'loop'        => false, 
				'rtl'         => false, 
				'fragments'   => false, 
				'embedded'    => false, 
				'autoSlideStoppable' => true, 
				'mouseWheel'  => false, 
				'hideAddressBar' => true, 
				'previewLinks' => false, 
			);
			
			$numfields = array(
				'autoSlide'   => 0, 
				'viewDistance' => 3, 
			);
			
			$urlfields = array(
				'parallaxBackgroundImage' => '', 
			);
			
			$otherfields = array( 
				'theme'       => 'default', 
				'transition'  => 'default', 
				'transitionSpeed' => 'default', 
				'backgroundTransition' => 'default', 
				'parallaxBackgroundSize' => '', 
				'customCSS' => null, 
			);
			
			/**
			 * If we're adding a new term, we need to be sure to set all of the advanced default settings
			 */
			if ( isset( $instance['action'] ) && 'add' == $instance['action'] ) {
				$opts['theme'] = isset( $instance['theme'] ) && in_array( $instance['theme'], $this->themes ) ? $instance['theme'] : 'default';
				$opts['transition'] = isset( $instance['transition'] ) && in_array( $instance['transition'], $this->transitions ) ? $instance['transition'] : 'default';
				$opts['transitionSpeed'] = isset( $instance['transitionSpeed'] ) && in_array( $instance['transitionSpeed'], array( 'default', 'fast', 'slow' ) ) ? $instance['transitionSpeed'] : 'default';
				
				$opts = array_merge( $this->defaults, $opts );
				update_option( sprintf( 'reveal-presentation-meta-%d', $term_id ), $opts );
				return;
			}

			
			foreach ( $this->defaults as $k=>$v ) {
				if ( array_key_exists( $k, $boolfields ) ) {
					if ( array_key_exists( $k, $instance ) ) {
						$opts[$k] = true;
					} else {
						$opts[$k] = false;
					}
				} elseif ( array_key_exists( $k, $numfields ) ) {
					if ( array_key_exists( $k, $instance ) ) {
						$opts[$k] = absint( $instance[$k] );
					} else {
						$opts[$k] = 0;
					}
				} elseif ( array_key_exists( $k, $urlfields ) ) {
					if ( array_key_exists( $k, $instance ) ) {
						$opts[$k] = esc_url( $instance[$k] );
					} else {
						$opts[$k] = null;
					}
				} else {
					if ( ! isset( $instance[$k] ) ) {
						$opts[$k] = $v;
					}
					switch( $k ) {
						case 'theme' :
							if ( ! in_array( $instance[$k], $this->themes ) ) {
								$opts[$k] = $v;
							} else {
								$opts[$k] = $instance[$k];
							}
							break;
						case 'transition' : 
							if ( ! in_array( $instance[$k], $this->transitions ) ) {
								$opts[$k] = $v;
							} else {
								$opts[$k] = $instance[$k];
							}
							break;
						case 'transitionSpeed' : 
							if ( ! in_array( $instance[$k], array( 'default', 'fast', 'slow' ) ) ) {
								$opts[$k] = $v;
							} else {
								$opts[$k] = $instance[$k];
							}
							break;
						case 'customCSS' : 
							if ( empty( $instance[$k] ) ) {
								$opts[$k] = null;
							} else {
								$opts[$k] = esc_textarea( $instance[$k] );
							}
							break;
						default : 
							$opts[$k] = $instance[$k];
							break;
					}
				}
			}
			
			/*print( '<pre><code>' );
			var_dump( $instance );
			print( '</code></pre>' );
			print( '<pre><code>' );
			var_dump( $opts );
			print( '</code></pre>' );
			wp_die( 'Done' );*/
			
			update_option( sprintf( 'reveal-presentation-meta-%d', $term_id ), $opts );
		}
		
		/**
		 * Output the HTML name of the presentation meta form field
		 * @param string $name the name of the field
		 * @param bool $echo whether or not to echo the name
		 */
		function presentation_meta_name( $name, $echo=true ) {
			if ( $echo )
				printf( 'presentation_meta[%s]', $name );
			else
				return sprintf( 'presentation_meta[%s]', $name );
		}
		
		/**
		 * Output the HTML ID of the presentation meta form field
		 * @param string $name the name of the field
		 * @param bool $echo whether or not to echo the ID
		 */
		function presentation_meta_id( $name, $echo=true ) {
			if ( $echo )
				printf( 'presentation-%s', $name );
			else
				return sprintf( 'presentation-%s', $name );
		}
		
		/**
		 * Perform any actions that need to happen on the admin_init hook
		 */
		function admin_init() {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
			return;
		}
		
		/**
		 * Set up the meta boxes we need for slide and presentation settings
		 */
		function add_meta_boxes() {
			/**
			 * Register the slide settings meta box
			 */
			add_meta_box( 'slide-settings', __( 'Slide Settings' ), array( $this, 'slide_settings_metabox' ), 'slides', 'normal', 'high' );
		}
		
		/**
		 * Output the slide settings metabox
		 */
		function slide_settings_metabox() {
			if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
				$post_id = $_GET['post'];
			} else {
				global $post;
				if ( is_object( $post ) )
					$post_id = $post->ID;
			}
			if ( ! isset( $post_id ) ) {
				return;
			}
			$slide_settings = get_post_meta( $post_id, '_rjs_slide_settings', true );
			if ( ! is_array( $slide_settings ) )
				$slide_settings = maybe_unserialize( $slide_settings );
			if ( empty( $slide_settings ) )
				$slide_settings = $this->_slide_defaults();
			
			wp_nonce_field( 'rjs-slide-settings', '_rjs_slide_settings_nonce' );
?>
<div>
<p><?php _e( 'Speaker Notes' ) ?></p>
<?php wp_editor( $slide_settings['notes'], $this->_slide_settings_id( 'notes' ), array( 'media_buttons' => false, 'textarea_name' => $this->_slide_settings_name( 'notes' ), 'teeny' => true ) ) ?>
</div>
<p><input type="checkbox" name="<?php echo $this->_slide_settings_name( 'use-title' ) ?>" id="<?php echo $this->_slide_settings_id( 'use-title' ) ?>" value="1" class="checkbox"<?php checked( $slide_settings['use-title'] ) ?>/> 
	<label for="<?php echo $this->_slide_settings_id( 'use-title' ) ?>"><?php _e( 'Use the Slide Title on the slide?' ) ?></label></p>
<p><input type="checkbox" name="<?php echo $this->_slide_settings_name( 'use-image' ) ?>" id="<?php echo $this->_slide_settings_id( 'use-image' ) ?>" value="1" class="checkbox"<?php checked( $slide_settings['use-image'] ) ?>/> 
	<label for="<?php echo $this->_slide_settings_id( 'use-image' ) ?>"><?php _e( 'Use the featured image as the background for this slide?' ) ?></label></p>
<div>
	<p><input type="checkbox" name="<?php echo $this->_slide_settings_name( 'use-background' ) ?>" id="<?php echo $this->_slide_settings_id( 'use-background' ) ?>" value="1" class="checkbox"<?php checked( $slide_settings['use-background'] ) ?>/> 
		<label for="<?php echo $this->_slide_settings_id( 'use-background' ) ?>"><?php _e( 'Use a custom background color for this slide?' ) ?></label></p>
	<p><label for="<?php echo $this->_slide_settings_id( 'background' ) ?>"><?php _e( 'If so, what color should the slide background be?' ) ?></label> 
		<input type="color" name="<?php echo $this->_slide_settings_name( 'background' ) ?>" id="<?php echo $this->_slide_settings_id( 'background' ) ?>" value="<?php echo $slide_settings['background'] ?>"/></p>
</div>
<div>
	<p><input type="checkbox" name="<?php echo $this->_slide_settings_name( 'use-transition' ) ?>" id="<?php echo $this->_slide_settings_id( 'use-transition' ) ?>" value="1" class="checkbox"<?php checked( $slide_settings['use-transition'] ) ?>/> 
	<label for="<?php echo $this->_slide_settings_id( 'use-transition' ) ?>"><?php _e( 'Use custom transition settings for this slide?' ) ?></label></p>
	<p><label for="<?php echo $this->_slide_settings_id( 'transition' ) ?>"><?php _e( 'If so, what transition should be used for this slide:' ) ?></label> 
		<select name="<?php echo $this->_slide_settings_name( 'transition' ) ?>" id="<?php echo $this->_slide_settings_id( 'transition' ) ?>">
			<option value=""<?php selected( $slide_settings['transition'], null ) ?>><?php _e( 'Use the global presentation transition' ) ?></option>
<?php
			foreach ( $this->transitions as $t ) {
?>
			<option value="<?php echo $t ?>"<?php selected( $slide_settings['transition'], $t ) ?>><?php echo ucfirst( $t ) ?></option>
<?php
			}
?>
		</select></p>
	<p><label for="<?php echo $this->_slide_settings_id( 'transition-speed' ) ?>"><?php _e( 'What speed transition should be used?' ) ?></label> 
		<select name="<?php echo $this->_slide_settings_name( 'transition-speed' ) ?>" id="<?php echo $this->_slide_settings_id( 'transition-speed' ) ?>">
			<option value=""<?php checked( $slide_settings['transition-speed'], null ) ?>><?php _e( 'Use the global presentation setting' ) ?></option>
<?php
			foreach ( array( 'default', 'fast', 'slow' ) as $opt ) {
?>
			<option value="<?php echo $opt ?>"<?php checked( $slide_settings['transition-speed'], $opt ) ?>><?php echo ucfirst( $opt ) ?></option>
<?php
			}
?>
		</select></p>
</div>
<p><label for="<?php echo $this->_slide_settings_id( 'custom-css' ) ?>"><?php _e( 'Custom CSS for this slide:' ) ?></label><br/> 
	<textarea cols="25" rows="8" class="widefat" name="<?php echo $this->_slide_settings_name( 'custom-css' ) ?>" id="<?php echo $this->_slide_settings_id( 'custom-css' ) ?>"><?php echo stripslashes( $slide_settings['custom_css'] ) ?></textarea> <br/>
	<em><?php printf( __( 'Hint: The HTML ID of this slide is <strong>%s</strong>' ), 'rjs-slide-' . $post_id ) ?></em></p>
<?php
			return;
		}
		
		/**
		 * Save the slide settings
		 */
		function save_meta_boxes( $post_id=null ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return/* wp_die( 'The user cannot edit this post' )*/;
			if ( ! isset( $_REQUEST['post_type'] ) || 'slides' != $_REQUEST['post_type'] )
				return;
			if ( ! wp_verify_nonce( $_REQUEST['_rjs_slide_settings_nonce'], 'rjs-slide-settings' ) )
				return;
			if ( isset( $_REQUEST['post_ID'] ) && is_numeric( $_REQUEST['post_ID'] ) )
				$post_id = $_REQUEST['post_ID'];
			if ( empty( $post_id ) )
				return;
			
			$vals = $this->_slide_defaults();
			$opts = $_REQUEST['_rjs_slide'];
			
			$vals['notes'] = isset( $opts['notes'] ) && ! empty( $opts['notes'] ) ? $opts['notes'] : null;
			$vals['use-title'] = isset( $opts['use-title'] ) && ! empty( $opts['use-title'] );
			$vals['use-image'] = isset( $opts['use-image'] ) && ! empty( $opts['use-image'] );
			$vals['use-transition'] = isset( $opts['use-transition'] ) && ! empty( $opts['use-transition'] );
			$vals['use-background'] = isset( $opts['use-background'] ) && ! empty( $opts['use-background'] );
			$vals['background'] = isset( $opts['background'] ) ? $opts['background'] : null;
			$vals['transition'] = isset( $opts['transition'] ) && in_array( $opts['transition'], $this->transitions ) ? $opts['transition'] : null;
			$vals['transition-speed'] = isset( $opts['transition-speed'] ) && in_array( $opts['transition-speed'], array( 'default', 'fast', 'slow' ) ) ? $opts['transition-speed'] : null;
			$vals['custom-css'] = isset( $opts['custom-css'] ) && ! empty( $opts['custom-css'] ) ? esc_textarea( $opts['custom-css'] ) : null;
			
			update_post_meta( $post_id, '_rjs_slide_settings', $vals );
		}
		
		/**
		 * Output an HTML ID for a slide settings field
		 */
		private function _slide_settings_id( $name ) {
			return sprintf( '_rjs_slide_%s', $name );
		}
		
		/**
		 * Output an HTML name for a slide settings field
		 */
		private function _slide_settings_name( $name ) {
			return sprintf( '_rjs_slide[%s]', $name );
		}
		
		/**
		 * Set up default options for slide settings
		 */
		function _slide_defaults() {
			return apply_filters( 'reveal-js-slide-defaults', array( 
				'use-title' => false, 
				'use-image' => false, 
				'background' => null, 
				'transition' => null, 
				'transition-speed' => null, 
			) );
		}
		
		/**
		 * Set up any javascript we need
		 */
		function enqueue_scripts() {
			wp_register_script( 'reveal-head', plugins_url( 'reveal-js/lib/js/head.min.js', dirname( __FILE__ ) ), array(), $this->version, true );
			wp_register_script( 'reveal-js', plugins_url( 'reveal-js/js/reveal.min.js', dirname( __FILE__ ) ), array( 'reveal-head' ), $this->version, true );
			if ( is_tax( 'presentation' ) ) {
				wp_enqueue_script( 'reveal-js' );
				add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ), 99 );
			}
			return;
		}
		
		/**
		 * Output the Reveal initialization script
		 */
		function print_footer_scripts() {
			$term = $this->get_presentation_settings();
			$jsurl = plugins_url( 'reveal-js/', dirname( __FILE__ ) );
			
			do_action( 'rjs-before-footer-scripts' );
			
			$dependencies = apply_filters( 'rjs-dependencies', array(
				array( 
					'src' => plugins_url( 'reveal-js/lib/js/classList.js', dirname( __FILE__ ) ), 
					'condition' => 'function() { return !document.body.classList; }'
				), 
				array( 
					'src' => plugins_url( 'reveal-js/plugin/markdown/marked.js', dirname( __FILE__ ) ), 
					'condition' => 'function() { return !!document.querySelector( \'[data-markdown]\' ); }'
				), 
				array( 
					'src' => plugins_url( 'reveal-js/plugin/markdown/markdown.js', dirname( __FILE__ ) ), 
					'condition' => 'function() { return !!document.querySelector( \'[data-markdown]\' ); }'
				), 
				array( 
					'src' => plugins_url( 'reveal-js/plugin/highlight/highlight.js', dirname( __FILE__ ) ), 
					'async' => true, 
					'callback' => 'function() { hljs.initHighlightingOnLoad(); }'
				), 
				array(
					'src' => plugins_url( 'reveal-js/plugin/zoom-js/zoom.js', dirname( __FILE__ ) ), 
					'async' => true, 
					'condition' => 'function() { return !!document.body.classList; }'
				), 
				array( 
					'src' => plugins_url( 'reveal-js/plugin/notes/notes.js', dirname( __FILE__ ) ), 
					'async' => true, 
					'condition' => 'function() { return !!document.body.classList; }'
				)
			) );
			
			/*$term['dependencies'] = $dependencies;*/
?>
<script>
var RJSInitConfig = <?php echo json_encode( $term ) ?>;
/*if( 'dependencies' in RJSInitConfig ) {
	for( var i in RJSInitConfig.dependencies ) {
		if( 'src' in RJSInitConfig.dependencies[i] ) {
			RJSInitConfig.dependencies[i].src = RJSInitConfig.dependencies[i].src.replace( '\/', '/' );
		}
		if( 'condition' in RJSInitConfig.dependencies[i] ) {
			RJSInitConfig.dependencies[i].condition = eval( RJSInitConfig.dependencies[i].condition );
		}
		if( 'callback' in RJSInitConfig.dependencies[i] ) {
			RJSInitConfig.dependencies[i].callback = eval( RJSInitConfig.dependencies[i].callback );
		}
	}
}*/
RJSInitConfig.dependencies = [
	{ src: '<?php echo $jsurl ?>lib/js/classList.js' , condition: function() { return !document.body.classList; } },
	{ src: '<?php echo $jsurl ?>plugin/markdown/marked.js', condition: function() { return !!document.querySelector( '[data-markdown]' ); } },
	{ src: '<?php echo $jsurl ?>plugin/markdown/markdown.js', condition: function() { return !!document.querySelector( '[data-markdown]' ); } },
	{ src: '<?php echo $jsurl ?>plugin/highlight/highlight.js', async: true, callback: function() { hljs.initHighlightingOnLoad(); } },
	{ src: '<?php echo $jsurl ?>plugin/zoom-js/zoom.js', async: true, condition: function() { return !!document.body.classList; } },
	{ src: '<?php echo $jsurl ?>plugin/notes/notes.js', async: true, condition: function() { return !!document.body.classList; } }
]
Reveal.initialize( RJSInitConfig );
</script>
<?php
			do_action( 'rjs-after-footer-scripts' );
		}
		
		/**
		 * Set up any admin javascript we need
		 */
		function admin_enqueue_scripts() {
			if ( isset( $_GET['taxonomy'] ) && 'presentation' == $_GET['taxonomy'] ) {
				wp_enqueue_media();
				wp_enqueue_script( 'presentation-admin-scripts', plugins_url( 'scripts/admin-scripts.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, true );
			}
		}
		
		/**
		 * Set up any style sheets we need
		 */
		function enqueue_styles() {
			if ( ! is_tax( 'presentation' ) )
				return;
			
			$options = $this->get_presentation_settings();
			wp_register_style( 'theme-base', get_stylesheet_uri(), array(), $this->version, 'all' );
			wp_register_style( 'reveal-js-presentations', plugins_url( 'css/reveal-js-presentations.css', dirname( __FILE__ ) ), array(), $this->version, 'all' );
			wp_register_style( 'reveal-js', plugins_url( 'reveal-js/css/reveal.min.css', dirname( __FILE__ ) ), array( 'reveal-js-presentations' ), $this->version, 'all' );
			wp_register_style( 'reveal-theme', plugins_url( sprintf( 'reveal-js/css/theme/%s.css', $options['theme'] ), dirname( __FILE__ ) ), array( 'reveal-js' ), $this->version, 'all' );
			wp_register_style( 'reveal-syntax', plugins_url( 'reveal-js/lib/css/zenburn.css', dirname( __FILE__ ) ), array(), $this->version, 'all' );
			wp_enqueue_style( 'reveal-theme' );
			wp_enqueue_style( 'reveal-syntax' );
		}
		
		/**
		 * Output any additional code that needs to go in the <head>
		 */
		function header_code() {
?>
		<!-- If the query includes 'print-pdf', include the PDF print sheet -->
		<script>
			if( window.location.search.match( /print-pdf/gi ) ) {
				var link = document.createElement( 'link' );
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = '<?php echo plugins_url( '/reveal-js/', dirname( __FILE__ ) ) ?>css/print/pdf.css';
				document.getElementsByTagName( 'head' )[0].appendChild( link );
			}
		</script>
<?php
			do_action( 'rjs-html-head' );
		}
		
		/**
		 * Retrieve the presentation settings
		 * @return array the array of settings
		 */
		function get_presentation_settings( $term=null ) {
			if ( ! is_object( $term ) ) {
				if ( function_exists( 'get_queried_object' ) ) {
					$term = get_queried_object();
				}
				if ( ! is_object( $term ) ) {
					return $this->defaults;
				}
				if ( ! property_exists( $term, 'term_id' ) ) {
					return $this->defaults;
				}
			}
			
			$tmp = get_option( sprintf( 'reveal-presentation-meta-%d', $term->term_id ), array() );
			return array_merge( $this->defaults, $tmp );
		}
		
		/**
		 * Retrieve the presentation settings & store them in the term object
		 * @return stdClass the term object with the settings added
		 */
		function get_presentation_meta( $term=null ) {
			if ( ! is_object( $term ) ) {
				if ( function_exists( 'get_queried_object' ) ) {
					$term = get_queried_object();
				}
				if ( ! property_exists( $term, 'term_id' ) )
					return false;
			}
			
			$tmp = get_option( sprintf( 'reveal-presentation-meta-%d', $term->term_id ), array() );
			$tmp = array_merge( $this->defaults, $tmp );
			
			foreach ( $tmp as $k=>$v ) {
				$term->$k = $v;
			}
			
			return $term;
		}
		
		/**
		 * Actually output the presentation
		 */
		function do_presentation_body() {
			add_action( 'rjs-after-presentation', 'wp_reset_postdata' );
			add_action( 'rjs-after-presentation', 'wp_reset_query' );
			
			$term = $this->get_presentation_meta();
			$q = new WP_Query( array( 
				'post_type' => 'slides', 
				'post_status' => 'publish', 
				'orderby' => 'menu_order date', 
				'order' => 'ASC', 
				'posts_per_page' => -1, 
				'numberposts' => -1, 
				'post_parent' => 0, 
			) );
			
			do_action( 'rjs-before-presentation' );
			
			if ( $q->have_posts() ) : 
?>
<div class="reveal">
	<div class="slides">
<?php
				do_action( 'rjs-before-loop' );
				
				global $post;
				while ( $q->have_posts() ) : $q->the_post();
					$this->do_slide_body();
				endwhile; 
				
				do_action( 'rjs-after-loop' );
				
				$this->customcss = '
/**
 * Custom CSS for presentation
 */
' . stripslashes( html_entity_decode( $term->customCSS ) ) . $this->customcss;
				$this->customcss = apply_filters( 'rjs-custom-css', $this->customcss );
?>
	</div>
</div>
<style type="text/css">
<?php echo $this->customcss ?>
</style>
<?php
			endif;
			
			do_action( 'rjs-after-presentation' );
		}
		
		/**
		 * Output the body of a specific slide
		 */
		function do_slide_body( $obj=null ) {
			global $post;
			if ( ! empty( $obj ) ) {
				$post = $obj;
			}
			setup_postdata( $post );
			
			$l = new WP_Query( array(
				'post_parent' => $post->ID, 
				'post_type' => 'slides', 
				'numberposts' => -1, 
				'posts_per_page' => -1, 
				'post_status' => 'publish', 
				'orderby' => 'menu_order date', 
				'order' => 'ASC'
			) );
			
			if ( $l->have_posts() ) :
?>
<section>
<?php
				$this->do_slide_content();
				while ( $l->have_posts() ) : $l->the_post();
					$this->do_slide_content();
				endwhile;
?>
</section>
<?php
			else :
				$this->do_slide_content();
			endif;
		}
		
		function do_slide_content() {
			$opts = get_post_meta( get_the_ID(), '_rjs_slide_settings', true );
			if ( ! empty( $opts['custom-css'] ) ) {
				$this->customcss .= '
/**
 * Custom styles for slide ' . get_the_title() . '
 */
' . stripslashes( html_entity_decode( $opts['custom-css'] ) );
			}
			
			$slideatts = '';
			
			if ( has_post_thumbnail() ) {
				$thumb = get_post_thumbnail_id();
				list( $src, $w, $h ) = wp_get_attachment_image_src( $thumb, 'full', false );
				$src = esc_url( $src );
			}
			if ( $opts['use-image'] && ! empty( $src ) ) : 
				$slideatts .= sprintf( ' data-background="%s"', $src ); 
			elseif ( $opts['use-background'] && ! empty( $opts['background'] ) ) : 
				$slideatts .= sprintf( ' data-background="%s"', $opts['background'] ); 
			endif;
			
			if ( $opts['use-transition'] && ! empty( $opts['transition'] ) ) :
				$slideatts .= sprintf( ' data-transition="%s"', $opts['transition'] );
			endif;
			
			if ( $opts['use-transition'] && ! empty( $opts['transition-speed'] ) ) :
				$slideatts .= sprintf( ' data-transition-speed="%s"', $opts['transition-speed'] );
			endif;
			
			if ( ! empty( $opts['notes'] ) ) {
				$notes = sprintf( '<aside class="notes">%s</aside>', apply_filters( 'the_excerpt', $opts['notes'] ) );
			} else {
				$notes = '';
			}
			
			$notes = apply_filters( 'rjs-slide-notes', $notes, $opts['notes'] );
			
			do_action( 'rjs-before-slide' );
?>
		<section<?php echo $slideatts ?> id="<?php printf( 'rjs-slide-%d', get_the_ID() ) ?>">
			<?php do_action( 'rjs-before-title' ) ?>
			<?php if ( $opts['use-title'] ) : printf( '<h1 class="slide-title">%s</h1>', get_the_title() ); endif; ?>
			<?php do_action( 'rjs-after-title' ) ?>
			<div class="slide-content">
			<?php do_action( 'rjs-before-slide-content' ) ?>
			<?php the_content() ?>
			<?php do_action( 'rjs-after-slide-content' ) ?>
			</div>
			<?php do_action( 'rjs-before-slide-notes' ) ?>
			<?php echo $notes ?>
			<?php do_action( 'rjs-after-slide-notes' ) ?>
		</section>
<?php

			do_action( 'rjs-after-slide' );
		}
	}
	
	function inst_reveal_presentations_obj() {
		global $reveal_presentations_obj;
		$reveal_presentations_obj = new Reveal_Presentations;
	}
	inst_reveal_presentations_obj();
}