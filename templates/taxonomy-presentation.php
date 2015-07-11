<?php global $reveal_presentations_obj; ?><!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8) ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<!--[if lt IE 9]>
	<script src="<?php echo plugins_url( '/scripts/html5.js', dirname( __FILE__ ) ) ?>"></script>
	<![endif]-->
	<?php $reveal_presentations_obj->open_graph_tags() ?>
	<?php $reveal_presentations_obj->twitter_card_tags() ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php
if ( isset( $reveal_presentations_obj->slideshow_thumbnail ) && esc_url( $reveal_presentations_obj->slideshow_thumbnail ) ) {
	printf( '<img src="%s" alt="" style="display: block; width: 1px; height: 1px; overflow: hidden; margin: 0; padding: 0; position: absolute; top: 0; left: 0;"/>', esc_url( $reveal_presentations_obj->slideshow_thumbnail ) );
}
?>
	<?php $reveal_presentations_obj->do_presentation_body(); ?>
	<?php wp_footer(); ?>
</body>
</html>