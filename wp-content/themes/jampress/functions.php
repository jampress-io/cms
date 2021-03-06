<?php

/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package JamPress_Theme
 * @since JamPress 1.0
 */


/* ==========================================================================
Theme support
========================================================================== */
/**
 * Add thumbnail sizes
 *
 */
if (function_exists('add_theme_support')) {
    // Add Menu Support
    add_theme_support('menus');

    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');
    add_image_size('large', 700, '', true); // Large Thumbnail
    add_image_size('medium', 250, '', true); // Medium Thumbnail
    add_image_size('small', 120, '', true); // Small Thumbnail
    add_image_size('header', 1600, 900, array('center', 'center')); // Header Image
}


/**
 * Register Menus in REST API
 *
 */
function register_rest_menus()
{
    register_nav_menu('main-menu', __('Main Menu'));
}
add_action('init', 'register_rest_menus');


/**
 * Register SEO meta fields
 *
 */
function slug_register_yoast_seo_meta()
{
    // Posts
    register_rest_field(
        'post',
        '_yoast_wpseo_title',
        array(
            'get_callback' => 'get_seo_meta_field',
            'update_callback' => null,
            'schema' => null,
        )
    );
    register_rest_field(
        'post',
        '_yoast_wpseo_metadesc',
        array(
            'get_callback' => 'get_seo_meta_field',
            'update_callback' => null,
            'schema' => null,
        )
    );

    // Pages
    register_rest_field(
        'page',
        '_yoast_wpseo_title',
        array(
            'get_callback' => 'get_seo_meta_field',
            'update_callback' => null,
            'schema' => null,
        )
    );
    register_rest_field(
        'page',
        '_yoast_wpseo_metadesc',
        array(
            'get_callback' => 'get_seo_meta_field',
            'update_callback' => null,
            'schema' => null,
        )
    );
}
add_action('rest_api_init', 'slug_register_yoast_seo_meta');


function get_seo_meta_field($object, $field_name, $request)
{
    return get_post_meta($object['id'], $field_name, true);
}


/**
 * Reset CORS for external access
 *
 */
// function customize_rest_cors()
// {
//     remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
//     add_filter('rest_pre_serve_request', function ($value) {
//         header('Access-Control-Allow-Origin: *');
//         header('Access-Control-Allow-Headers: X-Requested-With');
//         header('Access-Control-Allow-Methods: GET');
//         header('Access-Control-Allow-Credentials: true');
//         header('Access-Control-Expose-Headers: Link', false);

//         return $value;
//     });
// }
// add_action('rest_api_init', 'customize_rest_cors', 15);


/**
 * Hide editor on specific pages
 *
 */
function hide_editor()
{
    // Hide the editor on pages
    remove_post_type_support('page', 'editor');
    remove_post_type_support('post', 'editor');
}
add_action('admin_init', 'hide_editor');
