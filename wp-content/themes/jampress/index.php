<?php
/**
 *
 * @package Headless_WP_Theme
 */
?>

<!DOCTYPE html>
<html <?php language_attributes();?>>
<head>
    <title><?php bloginfo('name');?></title>
    <link rel="stylesheet" href="<?php bloginfo('stylesheet_url');?>">
    <?php wp_head();?>
</head>
<body>

      <header>
         <h1><?php bloginfo('name');?></h1>
         <h3><?php bloginfo('description');?></h3>
      </header>

        <?php
if (have_posts()):
    /* Start the Loop */
    while (have_posts()):
        the_post();
    endwhile;
endif;
?>

</body>
