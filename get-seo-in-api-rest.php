<?php
/*
Plugin Name: Get SEO in API REST
Author: JuanMi Carmona
Description: Get SEO information of the requested post through the API and Yoast 
*/

add_action( 'rest_api_init', 'custom_api_get_seo_post' );   

function custom_api_get_seo_post() {
    register_rest_route( 'wp/v2', '/seo-post/(?P<slug>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'custom_api_get_seo_post_callback',
    ));
}

function custom_api_get_seo_post_callback( $request ) {
    $posts_data = array();
    $paged = $request->get_param( 'page' );
	$paged = ( isset( $paged ) || ! ( empty( $paged ) ) ) ? $paged : 1; 
	$slug = $request['slug'];

	
	if ( isset( $slug ) || ! ( empty( $slug ) ) ) {
		$arrs = array(
			'paged' => $paged,
            'post__not_in' => get_option( 'sticky_posts' ),
            'posts_per_page' => 100,            
            'post_type' => array( 'post' ),
			'post_status' => 'publish',
			'name' => $slug
        );
		
		$posts = get_posts($arrs);
		if (count($posts)<=0){
			return new WP_REST_Response(null, 404);
		}
		foreach ($posts as $post) {
			$id = $post->ID; 
			$seo = api_get_yoast($id);
			$posts_data = (object) array(
				'body' => $seo
			);
		}

	} 
    return $posts_data;                   
} 

function api_get_yoast( $post_ID = false ) {
  include_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
  if ( $post_ID && is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
      $wpseo_frontend = WPSEO_Frontend::get_instance();
  $keyword = get_post_meta( $post_ID, '_yoast_wpseo_focuskw', true );
      $yoast_fields = array(
    'metas' => array(
      'keyword'	=> str_replace(';',',',$keyword),
      'title'		=> $wpseo_frontend->get_content_title( get_post( $post_ID ) ),
      'meta_desc'	=> get_post_meta( $post_ID, '_yoast_wpseo_metadesc', true )
    )
  );
      if ( $yoast_fields ) {
        return $yoast_fields;
      }
  } else {
      return false;
  }
}