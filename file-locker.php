<?php
/**
 * Plugin Name: File Locker
 * Plugin URI: https://www.osomstudio.com/
 * Description: File Locker
 * Version: 1.6
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

function filelocker_enqueue_admin_assets( $hook ) {
	// Only load on our plugin's admin page
	if ( $hook !== 'toplevel_page_filelocker' ) {
		return;
	}

	// Get plugin version dynamically
	$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	$version = $plugin_data['Version'];

	// Enqueue CSS
	wp_enqueue_style(
		'filelocker-admin-css',
		plugin_dir_url( __FILE__ ) . 'assets/css/filelocker-admin.css',
		array(),
		$version
	);

	// Enqueue JavaScript
	wp_enqueue_script(
		'filelocker-admin-js',
		plugin_dir_url( __FILE__ ) . 'assets/js/filelocker-admin.js',
		array(),
		$version,
		true
	);
}

add_action( 'admin_enqueue_scripts', 'filelocker_enqueue_admin_assets' );

function filelocker_menu_page() {
	$filelocker           = new FileLocker();
	$all_files            = $filelocker->list_all_restricted_files();
	$filelocker_admin_url = $filelocker->get_filelocker_admin_page();

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">File Locker</h1>
		<hr class="wp-header-end">
		
		<h2 class="filelocker-section-title">Upload Restricted File</h2>
		<div class="filelocker-upload-section">
			<form action="<?php echo $filelocker_admin_url; ?>" method="post" enctype="multipart/form-data" class="filelocker-upload-form">
				<?php wp_nonce_field( 'filelocker_upload_action', 'filelocker_upload_nonce' ); ?>
				<div class="filelocker-drop-zone" id="filelockerDropZone">
					<div class="drop-zone-content">
						<div class="drop-zone-icon">📁</div>
						<p class="drop-zone-text">Drag & drop your file here or <button type="button" class="drop-zone-browse">browse files</button></p>
						<p class="drop-zone-selected" id="selectedFileName" style="display: none;"></p>
					</div>
					<input type="file" name="fileLockerFile" id="fileLockerFile" class="filelocker-file-input" accept="*/*" style="display: none;">
				</div>
				<div class="upload-actions">
					<input type="submit" value="Upload File" name="submitFileLocker" class="button button-primary filelocker-upload-btn" id="uploadButton" disabled>
					<button type="button" class="button" id="clearButton" style="display: none;">Clear</button>
				</div>
				<p class="description">Choose a file to upload to the restricted files directory. Files will be protected and only accessible to logged-in users.</p>
			</form>
		</div>
		
		<h2 class="filelocker-section-title">All Restricted Files</h2>
		
	<?php
	if ( !empty( $all_files ) ) {
		?>
		<div class="filelocker-table-wrapper">
		<table class="filelocker-table">
			<thead>
				<tr>
					<th>File Name</th>
					<th>File URL</th>
					<th>Upload Date</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $all_files as $single_file ) {
					$delete_file_parameter = $filelocker_admin_url . '&delete_filelocker=true&filelocker_name=' . $single_file['dir'];
					$file_name = basename( $single_file['dir'] );
					$upload_date = date( 'Y-m-d H:i:s', $single_file['mtime'] );
					?>
					<tr>
						<td><?php echo esc_html( $file_name ); ?></td>
						<td><a href="<?php echo esc_url( $single_file['url'] ); ?>" target="_blank"><?php echo esc_html( $single_file['url'] ); ?></a></td>
						<td><?php echo esc_html( $upload_date ); ?></td>
						<td><a href="<?php echo esc_url( $delete_file_parameter ); ?>" class="filelocker-delete-btn" onclick="return confirm('Are you sure you want to delete this file?');">Delete</a></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		</div>
		<?php
	} else {
		echo '<p>No files uploaded yet.</p>';
	}
	?>
	</div>
	<?php
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

function filelocker_delete_failure( $error_message = '' ) {
	$message = 'There was a problem with deleting selected file.';
	if ( ! empty( $error_message ) ) {
		$message .= ' Error: ' . esc_html( $error_message );
	}
	echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
}

function delete_filelocker_restricted_file() {
	if ( isset( $_GET['delete_filelocker'] ) && $_GET['delete_filelocker'] === 'true' ) {
		$filelocker = new FileLocker();

		$delete_result = $filelocker->delete_filelocker_file();

		// Handle both new array format and legacy boolean format
		if ( is_array( $delete_result ) ) {
			if ( $delete_result['success'] ) {
				add_action( 'admin_notices', 'filelocker_delete_success' );
			} else {
				add_action( 'admin_notices', function() use ( $delete_result ) {
					filelocker_delete_failure( $delete_result['error'] );
				});
			}
		} else {
			// Legacy boolean handling
			if ( $delete_result ) {
				add_action( 'admin_notices', 'filelocker_delete_success' );
			} else {
				add_action( 'admin_notices', function() {
					filelocker_delete_failure( 'Unknown error occurred' );
				});
			}
		}
	}

}

add_action( 'init', 'delete_filelocker_restricted_file' );
