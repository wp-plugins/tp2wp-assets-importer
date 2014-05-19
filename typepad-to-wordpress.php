<?php
	/**
	 * Plugin Name: TypePad to Wordpress
	 * Plugin URI: http://www.digitallift.fr
	 * Description: This plugin gets the assets from your TypePad blog, based on the posts you have imported, and uploaded them into your Wordpress blog.
	 * Version: 1.0.0
	 * Author: Romain Biard
	 * Author URI: http://www.digitallift.fr
	 * License: GPL2
	 */


	/*  Copyright 2014 Romain Biard  (email : romain.biard@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/


	// Define the menu element
	add_action( 'admin_menu', 'my_plugin_menu' );
	
	function my_plugin_menu() {
		add_options_page( 'TypePad to Wordpress Options', 'TypePad to Wordpress', 'manage_options', 'typepad-to-wordpress', 'tp2wp_options' );
	}

	function tp2wp_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		include('typepad-to-wordpress-display.php');
	}



	// Adding the list of posts to the file
	function tp2wp_add_posts() {
		global $wpdb; // this is how you get access to the database

		$posts = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts', OBJECT);

		echo "<script type='text/javascript'>";
	    echo "var posts = ".json_encode($posts);
	    echo "</script>";
	}



	// Function to enqueue the js file
	function tp2wp_adding_scripts() {
		wp_enqueue_script('typepad_to_wordpress_js', plugins_url('typepad-to-wordpress.js', __FILE__), array('jquery'), '1.0', TRUE);
		// tp2wp_add_posts();
	}
 
	add_action( 'admin_enqueue_scripts', 'tp2wp_adding_scripts' ); 




	// Function to get the list of posts, called through ajax
	add_action( 'wp_ajax_get_posts', 'get_posts_callback' );

	function get_posts_callback() {
		global $wpdb; // this is how you get access to the database

		$posts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE (`post_type` = 'post' OR `post_type`='page')", OBJECT);

	    echo json_encode($posts);

		die(); // this is required to return a proper result
	}



	// Function to copy images
	add_action( 'wp_ajax_copy_images', 'copy_images_callback' );

	function copy_images_callback() {
		global $wpdb; // this is how you get access to the database

		$upload_dir = wp_upload_dir();

		// Define the path and name of the new image
		$segments = explode("/", $_POST['image_url']);
		$new_file = $upload_dir["path"]."/".$segments[count($segments)-1].".jpg";
		$new_file_url = $upload_dir["url"]."/".$segments[count($segments)-1].".jpg";

		$image_to_copy = str_replace($_POST['original_url'], $_POST['typepad_url'], $_POST['image_url']);

		// Copy the image
		if(copy($image_to_copy, $new_file)) {

			$filetype = wp_check_filetype( basename( $new_file ), NULL );

			// Save the image in the medias
			$attachment = array(
			    'post_content' => '',
			    'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $segments[count($segments)-1] ) ),
			    'post_status' => 'inherit',
			    'guid' => $new_file_url,
			    'post_mime_type' => $filetype['type']
			);

			$attachment_id = wp_insert_attachment( $attachment, $new_file );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate the metadata for the attachment, and update the database record.
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $new_file );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );

			// Replace the URL in the content of the post and in the excerpt
			$content = str_replace($_POST['image_url'], $new_file_url, $_POST['content']);
			$excerpt = str_replace($_POST['image_url'], $new_file_url, $_POST['excerpt']);

			$data = array("post_content" => stripslashes($content), "post_excerpt" => stripslashes($excerpt));

			// Update the post
			$result = $wpdb->update($wpdb->prefix."posts", $data, array("ID" => $_POST['id']));

			echo json_encode(
				array(
					"response" => TRUE, "file" => $new_file, 
					"attachment_id" => $attachment_id, "filetype" => $filetype, 
					"nb_rows_affected" => $result, "old_url" => $_POST['image_url'],
					"new_url" => $new_file_url, "post_id" => $_POST['id']
				)
			);
		}

		die(); // this is required to return a proper result
	}