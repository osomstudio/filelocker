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
				<?php
				$wp_max_file_size = wp_max_upload_size();
				$max_file_size = apply_filters( 'filelocker_max_file_size', $wp_max_file_size );
				$max_size_mb = round( $max_file_size / ( 1024 * 1024 ), 1 );
				?>
				<p class="description">Choose a file to upload to the restricted files directory. Files will be protected and only accessible to logged-in users. Maximum upload size: <?php echo esc_html( $max_size_mb ); ?>MB</p>
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
					$file_name = basename( $single_file['dir'] );
					$upload_date = date( 'Y-m-d H:i:s', $single_file['mtime'] );
					$delete_nonce = wp_create_nonce( 'filelocker_delete_action' );
					$current_url = esc_url_raw( $_SERVER['REQUEST_URI'] );
					?>
					<tr>
						<td><?php echo esc_html( $file_name ); ?></td>
						<td><a href="<?php echo esc_url( $single_file['url'] ); ?>" target="_blank"><?php echo esc_html( $single_file['url'] ); ?></a></td>
						<td><?php echo esc_html( $upload_date ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<input type="hidden" name="action" value="filelocker_delete">
								<input type="hidden" name="filelocker_name" value="<?php echo esc_attr( $single_file['dir'] ); ?>">
								<input type="hidden" name="filelocker_delete_nonce" value="<?php echo esc_attr( $delete_nonce ); ?>">
								<input type="hidden" name="redirect_url" value="<?php echo esc_attr( $current_url ); ?>">
								<button type="submit" class="filelocker-delete-btn" onclick="return confirm('Are you sure you want to delete this file?');">Delete</button>
							</form>
						</td>
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



function filelocker_handle_delete() {
	// Verify nonce for security
	if ( ! isset( $_POST['filelocker_delete_nonce'] ) || ! wp_verify_nonce( $_POST['filelocker_delete_nonce'], 'filelocker_delete_action' ) ) {
		wp_die( 'Security check failed. Please try again.', 'Security Error', array( 'response' => 403 ) );
	}

	// Check user capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to delete files.', 'Permission Error', array( 'response' => 403 ) );
	}

	// Sanitize and validate file name input
	if ( ! isset( $_POST['filelocker_name'] ) || empty( $_POST['filelocker_name'] ) ) {
		wp_die( 'No file specified for deletion.', 'Invalid Request', array( 'response' => 400 ) );
	}

	$file_name = sanitize_text_field( $_POST['filelocker_name'] );

	// Get redirect URL
	$redirect_url = isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : admin_url( 'admin.php?page=file-locker' );

	// Instantiate FileLocker and perform deletion
	$filelocker = new FileLocker();
	$delete_result = $filelocker->delete_filelocker_file( $file_name );

	// Handle deletion result and redirect with appropriate notice
	if ( is_array( $delete_result ) && $delete_result['success'] ) {
		$redirect_url = add_query_arg( 'filelocker_deleted', '1', $redirect_url );
	} else {
		$error_message = is_array( $delete_result ) ? $delete_result['error'] : 'Unknown error occurred';
		$redirect_url = add_query_arg( array(
			'filelocker_delete_error' => '1',
			'error_message' => urlencode( $error_message )
		), $redirect_url );
	}

	wp_safe_redirect( $redirect_url );
	exit;
}

// Hook the deletion handler to admin_post action
add_action( 'admin_post_filelocker_delete', 'filelocker_handle_delete' );

// Handle admin notices for deletion results
function filelocker_display_deletion_notices() {
	if ( isset( $_GET['filelocker_deleted'] ) && $_GET['filelocker_deleted'] === '1' ) {
		echo '<div class="notice notice-success is-dismissible"><p>File deleted successfully.</p></div>';
	}
	
	if ( isset( $_GET['filelocker_delete_error'] ) && $_GET['filelocker_delete_error'] === '1' ) {
		$error_message = isset( $_GET['error_message'] ) ? urldecode( sanitize_text_field( $_GET['error_message'] ) ) : 'Unknown error occurred';
		echo '<div class="notice notice-error is-dismissible"><p>Error deleting file: ' . esc_html( $error_message ) . '</p></div>';
	}
}

// Handle upload notices from transients
function filelocker_display_upload_notices() {
	$user_id = get_current_user_id();
	
	// Check for upload success message
	$success_message = get_transient( 'filelocker_upload_success_' . $user_id );
	if ( $success_message ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success_message ) . '</p></div>';
		delete_transient( 'filelocker_upload_success_' . $user_id );
	}
	
	// Check for upload error message
	$error_message = get_transient( 'filelocker_upload_error_' . $user_id );
	if ( $error_message ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
		delete_transient( 'filelocker_upload_error_' . $user_id );
	}
}

// Hook admin notices for the file locker page
add_action( 'admin_notices', 'filelocker_display_deletion_notices' );
add_action( 'admin_notices', 'filelocker_display_upload_notices' );
