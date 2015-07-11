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
	<?php $reveal_presentations_obj->do_presentation_body(); ?>
	<?php wp_footer(); ?>
</body>
</html>