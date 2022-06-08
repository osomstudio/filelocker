<?php
/**
 * Plugin Name: File Locker
 * Plugin URI: https://www.osomstudio.com/
 * Description: File Locker
 * Version: 1.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Osom Studio
 * Author URI: https://www.osomstudio.com/
 */

require 'inc/class-file-locker.php';

use FileLocker\FileLocker;

function call_view_download_file() {
	$view_file = new FileLocker();
	$view_file->view_download_file();
}

add_action( 'init', 'call_view_download_file' );


function register_filelocker_menu_page() {
	add_menu_page(
		__( 'FileLocker', 'filelocker' ),
		'File Locker',
		'manage_options',
		'filelocker',
		'filelocker_menu_page',
		'dashicons-admin-network'
	);
}

add_action( 'admin_menu', 'register_filelocker_menu_page' );

function filelocker_menu_page() {
	$filelocker           = new FileLocker();
	$all_files            = $filelocker->list_all_restricted_files();
	$filelocker_admin_url = $filelocker->get_filelocker_admin_page();

	echo '<h2>Upload file : </h2>';
	?>
	<form action="<?php echo $filelocker_admin_url; ?>" method="post" enctype="multipart/form-data">
		Select file to upload:
		<input type="file" name="fileLockerFile" id="fileLockerFile">
		<input type="submit" value="Upload File" name="submitFileLocker">
	</form>
	<?php

	echo '<h2>All restricted files : </h2>';

	foreach ( $all_files as $single_file ) {
		$delete_file_parameter = $filelocker_admin_url . '&delete_filelocker=true&filelocker_name=' . $single_file['dir'];
		echo $single_file['url'] . '<a href="' . $delete_file_parameter . '" style="margin-left: 30px;">Delete file</a><br>';
	}
}

function filelocker_uploader() {
	$filelocker_uploads = new FileLocker();
	$filelocker_uploads->file_handler();
}

add_action( 'init', 'filelocker_uploader' );

function filelocker_error_notice() {
	$filelocker        = new FileLocker();
	$filelocker_errors = $filelocker->config_error();

	if ( ! empty( $filelocker_errors ) ) {
		foreach ( $filelocker_errors as $single_error ) {
			echo '<div class="error"><p>' . $single_error . '</p></div>';
		}
	}
}

add_action( 'admin_notices', 'filelocker_error_notice' );


function filelocker_delete_success() {
	echo '<div class="notice notice-success is-dismissible"><p>File deleted succesfully.</p></div>';
}

function filelocker_delete_failure() {
	echo '<div class="notice notice-error"><p>There was a problem with deleting selected file.</p></div>';
}

function delete_filelocker_restricted_file() {
	if ( isset( $_GET['delete_filelocker'] ) && $_GET['delete_filelocker'] === 'true' ) {
		$filelocker = new FileLocker();

		$delete_file = $filelocker->delete_filelocker_file();

		if ( $delete_file ) {
			add_action( 'admin_notices', 'filelocker_delete_success' );
		} else {
			add_action( 'admin_notices', 'filelocker_delete_failure' );
		}
	}

}

add_action( 'init', 'delete_filelocker_restricted_file' );


//function filelocker_permissions(): bool {
//	if ( current_user_can( 'administrator' ) ) {
//		return true;
//	}
//	return false;
//}
//
//function filelocker_redirect(): string {
//	return 'https://google.com';
//}


