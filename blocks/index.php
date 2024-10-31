<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AAT_HPCL_Admin starts here. Manager sets mode, adds required wp hooks and loads required object of structure
 *
 * Manager controls and access to all modules and classes of AAT_HPCL_Admin.
 *
 * @package AAT_HPCL_Admin
 * @since   1.0
 */
class AAT_HPCL_Admin {

	/**
	 * Constructor loads API functions, defines paths and adds required wp actions
	 *
	 * @since  1.0
	 */
	public function __construct() 
	{
		add_action( 'init', array( $this, 'init' ), 9 );

		add_action( 'wp_ajax_AAT_HPCL_ajax_request', array( $this, 'ajax_request' ) );
		add_action( 'wp_ajax_nopriv_AAT_HPCL_ajax_request', array( $this, 'ajax_request' ) );
	}

	/**
	 * Callback function for WP init action hook. Sets AAT_HPCL_Admin mode and loads required objects.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() 
	{
		do_action( 'AATHP_before_init' );
		
		global $wpdb;
		$this->db = $wpdb;

		if( is_admin() ){
			$this->editor_assets();
		}
		else{
			$this->frontend_assets();
		}

		do_action( 'AATHP_after_init' );
	}

	public function frontend_assets()
	{
		wp_enqueue_style(
			'hotspot-blocks/view-style',
			plugins_url( 'frontend/frontend.view.css', __FILE__ ),
			array( 'wp-edit-blocks' )
		);

		wp_enqueue_script(
			'hotspot-blocks/view-script',
			plugins_url( 'frontend/frontend.build.js', __FILE__ )
	  	);

	  	wp_localize_script( 'hotspot-blocks/view-script', 'AAT_HPCL', array(
			'ajax_url' => apply_filters( 'AAT_HPCL_ref', admin_url('admin-ajax.php?action=AAT_HPCL_ajax_request') ),
			'assets_url' => AATHP_BLOCK_URL,
			'fonts' => array(),
			'tag' => apply_filters( 'affID', 'Your_affiate_ID' )
		) );
	}
	
	public function editor_assets()
	{
		wp_enqueue_script(
			'hotspot/editor',
			plugins_url( 'backend/backend.build.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-editor' )
	  	);

		wp_enqueue_style(
			'hotspot/editor',
			plugins_url( 'backend/backend.editor.css', __FILE__ ),
			array( 'wp-edit-blocks' )
		);

		wp_localize_script( 'hotspot/editor', 'AAT_HPCL', array(
			'ajax_url' => apply_filters( 'AAT_HPCL_ref', admin_url('admin-ajax.php?action=AAT_HPCL_ajax_request') ),
			'assets_url' => AATHP_BLOCK_URL,
			'fonts' => array(),
			'buy_url' => 'aHR0cHM6Ly9jb2RlY2FueW9uLm5ldC9pdGVtL2d1dGVuc3BvdC1pbWFnZS1nYWxsZXJ5LWhvdHNwb3RzLWZvci1ndXRlbmJlcmcvMjMyNzYxMTc/cmVmPUFBLVRlYW0='
		) );

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-draggable' );
	}

	private function print_response( $status='valid', $msg='', $data=array() )
	{
		die( json_encode( array(
			'status' => $status,
			'msg' => $msg,
			'data' => $data
		) ) );
	}

	private function get_first_paragraph( $post_content='' )
	{
	    $str = wpautop( do_shortcode( $post_content ) );
	    $str = substr( $str, 0, strpos( $str, '</p>' ) + 4 );
	    $str = strip_tags($str, '<a><strong><em>');

	    if( strlen($str) ){
	    	return '<p>' . $str . '</p>';
	    }

	    return '';
	}

	private function search_wp_post( $args=array() )
	{
		$query = new WP_Query( $args );
		$base = array();

		if( $query->have_posts() ){
			foreach( $query->posts as $post ){

				$excerpt = get_the_excerpt( $post );
				if( $excerpt == "" ){
					$excerpt = $this->get_first_paragraph( $post->post_content );
				}
				$base[] = array(
					'ID' => $post->ID,
					'post_title' => $post->post_title,
					'excerpt' => $excerpt
				);
			}
		}

		return $base;
	}

	public function ajax_request()
	{
		$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';

		if( $action == 'search_post_by_keyword' ){
			$posts = $this->search_wp_post( array(
				'post_type' 		=> array('post', 'page'),
				'post_status'		=> 'publish',
				'posts_per_page'   	=> -1,
				's'					=> $_REQUEST['keyword']
			) );

			$products = $posts;
			$posts = array();
			foreach ($products as $key => $post) {

				$posts[$key]['ID'] = $post['ID'];
				$posts[$key]['post_title'] = $post['post_title'];
				$posts[$key]['excerpt'] = $post['excerpt'];

				if( has_post_thumbnail( $post['ID'] ) ){
					$url = wp_get_attachment_image_src( get_post_thumbnail_id( $post['ID'] ), array( 50, 50 ) );
					$posts[$key]['image'] = "<img src='" . ( $url[0] ) . "' />";
					
					$url = wp_get_attachment_image_src( get_post_thumbnail_id( $post['ID'] ), array( 150, 150 ) );
					$posts[$key]['medium_image'] = "<img src='" . ( $url[0] ) . "' />";
				}

				$posts[$key]['permalink'] = get_permalink( $post['ID'] );
			}

			if( $posts && count($posts) ){
				$this->print_response( 'valid', 'Data ok', $posts );
			}else{
				$this->print_response( 'invalid', 'Unable to get any post for your searched keyword!' );
			}
		}

		if( $action == 'search_woocommerce_product_by_keyword' ){
			$posts = $this->search_wp_post( array(
				'post_type' 		=> 'product',
				'post_status'		=> 'publish',
				'posts_per_page'   	=> -1,
				's'					=> $_REQUEST['keyword']

			) );

			if( $posts && count($posts) ){

				$products = $posts;
				$posts = array();
				foreach ($products as $key => $post) {
					$product = wc_get_product( $post['ID'] );

					$posts[$key]['ID'] = $post['ID'];
					$posts[$key]['post_title'] = $post['post_title'];
					$posts[$key]['excerpt'] = $post['excerpt'];
					$posts[$key]['image'] = $product->get_image( array(50, 50) );
					$posts[$key]['medium_image'] = $product->get_image( array(250, 250) );
					$posts[$key]['price'] = $product->get_price_html();
					$posts[$key]['permalink'] = get_permalink( $post['ID'] );
				}

				$this->print_response( 'valid', 'Data ok', $posts );
			}else{
				$this->print_response( 'invalid', 'Unable to get any product for your searched keyword!' );
			}
		}
			
		if( $action == 'get_images_by_ids' ){
			$images = array();

			$imageIDs = $_REQUEST['imagesArray'];
			if( count($imageIDs) ){
				foreach ($imageIDs as $image_id) {

					$image_id = str_replace( "id_", '', $image_id );

					$image_full = wp_get_attachment_image_src( $image_id, 'full');
					$image_thumbnail = wp_get_attachment_image_src( $image_id );

					if( isset($image_full[0]) ){
						$images["id_" . $image_id] = array(
							'thumb' => $image_thumbnail[0],
							'full' => $image_full[0]
						);
					}
				}
			}

			if( $images && count($images) ){
				$this->print_response( 'valid', 'data ok', $images );
			}

			$this->print_response( 'invalid', 'Unable to get any images for your images IDs!' );
		}

		if( $action == 'need_data_for' ){
			$items = (array) $_REQUEST['items'];
			$posts = array();

			$multiple_id_post = array();
			foreach ($items as $item) {
				$item = json_decode( stripslashes($item), true );

				if( $item['type'] == 'woocommerce' ) {

					if( (int)$item['postID'] ){
						$product = wc_get_product( (int)$item['postID'] );

						$posts[$item['postID']]['ID'] = $item['postID'];
						$posts[$item['postID']]['post_title'] = $product->get_title();
						$posts[$item['postID']]['image'] = $product->get_image( array(50, 50) );
						$posts[$item['postID']]['medium_image'] = $product->get_image( array(250, 250) );
						$posts[$item['postID']]['price'] = $product->get_price_html();
						$posts[$item['postID']]['permalink'] = get_permalink( $item['postID'] );
					}

				}
				elseif( $item['type'] == 'post' ) {
					$multiple_id_post[] = $item['postID'];
				}
				elseif( $item['type'] == 'static' ) {

				}
				else{
					die( __FILE__ . ":" . __LINE__  );
				}
			}

			if( count($multiple_id_post) > 0 ){
				$multiple_posts = $this->search_wp_post( array(
					'post_type' 		=> array('post', 'page'),
					'post_status'		=> 'publish',
					'posts_per_page'   	=> -1,
					'post__in'			=> $multiple_id_post
				) );
				foreach ($multiple_posts as $key => $post) {

					$posts[$post['ID']]['ID'] = $post['ID'];
					$posts[$post['ID']]['post_title'] = $post['post_title'];
					$posts[$post['ID']]['permalink'] = get_permalink( $post['ID'] );

					if( has_post_thumbnail( $post['ID'] ) ){
						$url = wp_get_attachment_image_src( get_post_thumbnail_id( $post['ID'] ), array( 50, 50 ) );
						$posts[$post['ID']]['image'] = "<img src='" . ( $url[0] ) . "' />";
						
						$url = wp_get_attachment_image_src( get_post_thumbnail_id( $post['ID'] ), array( 150, 150 ) );
						$posts[$post['ID']]['medium_image'] = "<img src='" . ( $url[0] ) . "' />";
					}
				}
			}
			
			if( $posts && count($posts) ){
				$this->print_response( 'valid', 'data ok', $posts );
			}else{
				$this->print_response( 'invalid', 'Unable to get any post for your list!' );
			}
		}

		if( $action == 'search_post_by_post_id' ){

			$posts = array();
			if( (int)$_REQUEST['post_id'] > 0 ){

				$posts = $this->search_wp_post( array(
					'p' 		=> (int)$_REQUEST['post_id'],
					'post_type' => 'any'
				) );
			}

			if( $posts && count($posts) ){
				$this->print_response( 'valid', 'Data ok', $posts );
			}else{
				$this->print_response( 'invalid', 'Unable to get any post for your post ID!' );
			}
		}

		
		die("Cmon'!");
	}
}

/**
 * Main AAT_HPCL_Admin manager.
 * @var AAT_HPCL_Admin $AAT_HPCL_Admin - instance of composer management.
 * @since 1.0
 */
global $AAT_HPCL_Admin;
$AAT_HPCL_Admin = new AAT_HPCL_Admin();

function AAT_HPCL_Admin(){
	global $AAT_HPCL_Admin;

	return $AAT_HPCL_Admin;
}