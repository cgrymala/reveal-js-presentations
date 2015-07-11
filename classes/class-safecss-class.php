<?php
/**
 * Create a stripped-down version of the JetPack SafeCSS module, 
 * 		in case JetPack isn't active
 */
if ( ! function_exists( 'safecss_class' ) ) {
	function safecss_class() {
		// Wrapped so we don't need the parent class just to load the plugin
		if ( class_exists('safecss') )
			return;
	
		require_once( dirname( __FILE__ ) . '/inc/csstidy/class.csstidy.php' );
	
		class safecss extends csstidy_optimise {
			function safecss( &$css ) {
				return $this->csstidy_optimise( $css );
			}
	
			function postparse() {
				
				/**
				 * Do actions after parsing the css
				 *
				 * @since ?
				 * @module Custom_CSS
				 * @param safecss $obj
				 **/
				do_action( 'csstidy_optimize_postparse', $this );
	
				return parent::postparse();
			}
	
			function subvalue() {
	
				/**
				 * Do action before optimizing the subvalue
				 *
				 * @since ?
				 * @module Custom_CSS
				 * @param safecss $obj
				 **/
				do_action( 'csstidy_optimize_subvalue', $this );
	
				return parent::subvalue();
			}
		}
	}
}