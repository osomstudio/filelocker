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


/**
 * Register the File Locker top-level admin menu and its Settings submenu.
 *
 * Adds a "File Locker" admin menu entry (visible to users with the `manage_options`
 * capability) and a "Settings" submenu that links to the plugin settings page.
 */
function register_filelocker_menu_page() {
	add_menu_page(
		__( 'FileLocker', 'filelocker' ),
		'File Locker',
		'manage_options',
		'filelocker',
		'filelocker_menu_page',
		'dashicons-admin-network'
	);

	add_submenu_page(
		'filelocker',
		__( 'Settings', 'filelocker' ),
		'Settings',
		'manage_options',
		'filelocker-settings',
		'filelocker_settings_page'
	);
}

add_action( 'admin_menu', 'register_filelocker_menu_page' );

/**
 * Registers the 'filelocker_custom_url' option under the 'filelocker_settings' settings group.
 *
 * The registered setting is saved with a sanitize callback of `filelocker_sanitize_url`.
 */
function filelocker_register_settings() {
	register_setting(
		'filelocker_settings',
		'filelocker_custom_url',
		array(
			'sanitize_callback' => 'filelocker_sanitize_url',
		)
	);
}
add_action( 'admin_init', 'filelocker_register_settings' );

/**
 * Sanitizes and validates a custom redirect URL for the File Locker settings.
 *
 * If a non-empty URL is provided but fails validation, registers a settings error
 * and returns the currently saved `filelocker_custom_url` option instead.
 *
 * @param string $url The input URL to sanitize.
 * @return string The sanitized URL, or the existing saved option when the input is invalid.
 */
function filelocker_sanitize_url( $url ) {
	$url = esc_url_raw( $url );

	if ( ! empty( $url ) && ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		add_settings_error( 'filelocker_custom_url', 'invalid_url', 'Please enter a valid URL.' );

		return get_option( 'filelocker_custom_url', '' );
	}

	return $url;
}

/**
 * Enqueues the plugin's admin CSS and JavaScript on the File Locker admin pages.
 *
 * @param string $hook The current admin page hook suffix; assets are enqueued only when this equals
 *                     'toplevel_page_filelocker' (main File Locker page) or
 *                     'file-locker_page_filelocker-settings' (File Locker Settings page).
 */
function filelocker_enqueue_admin_assets( $hook ) {
	if ( 'toplevel_page_filelocker' !== $hook && 'file-locker_page_filelocker-settings' !== $hook ) {
		return;
	}

	// Get plugin version dynamically
	$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	$version     = $plugin_data['Version'];

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

/**
 * Render the File Locker admin page with upload controls and a listing of restricted files.
 *
 * Outputs the HTML for the admin UI: an upload area (with nonce-protected form and file size info)
 * and a table of all restricted files showing file name, URL, upload date, and a delete action.
 */
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
			<form action="<?php echo esc_url( $filelocker_admin_url ); ?>" method="post" enctype="multipart/form-data" class="filelocker-upload-form">
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
				$max_file_size    = apply_filters( 'filelocker_max_file_size', $wp_max_file_size );
				$max_size_mb      = round( $max_file_size / ( 1024 * 1024 ), 1 );
				?>
				<p class="description">Choose a file to upload to the restricted files directory. Files will be protected and only accessible to logged-in users. Maximum upload size: <?php echo esc_html( $max_size_mb ); ?>MB</p>
			</form>
		</div>
		
		<h2 class="filelocker-section-title">All Restricted Files</h2>
		
	<?php
	if ( ! empty( $all_files ) ) {
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
					$file_name    = basename( $single_file['dir'] );
					$upload_date  = gmdate( 'Y-m-d H:i:s', $single_file['mtime'] );
					$delete_nonce = wp_create_nonce( 'filelocker_delete_action' );
					$current_url  = esc_url_raw( $_SERVER['REQUEST_URI'] );
					?>
					<tr>
						<td><?php echo esc_html( $file_name ); ?></td>
						<td><a href="<?php echo esc_url( $single_file['url'] ); ?>" target="_blank"><?php echo esc_html( $single_file['url'] ); ?></a></td>
						<td><?php echo esc_html( $upload_date ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<input type="hidden" name="action" value="filelocker_delete">
								<input type="hidden" name="filelocker_name" value="<?php echo esc_attr( $single_file['rel'] ); ?>">
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

/**
 * Displays the File Locker settings admin page with a Redirect Page field.
 *
 * Renders a settings form that lets administrators enter or select a custom redirect URL (via a datalist of site pages) used when a file is restricted; if left blank, the site homepage will be used as the redirect destination.
 */
function filelocker_settings_page() {
	$saved_url = get_option( 'filelocker_custom_url', '' );

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'File Locker Settings', 'filelocker' ); ?></h1>
		<hr class="wp-header-end">
		
		<h2 class="filelocker-section-title"><?php esc_html_e( 'Custom Redirect URL', 'filelocker' ); ?></h2>
		<div class="filelocker-settings-section">
			<form method="post" action="options.php" class="filelocker-settings-form">
				<?php
				settings_fields( 'filelocker_settings' );
				do_settings_sections( 'filelocker_settings' );
				?>
				
				<div class="filelocker-form-group">
					<label for="filelocker_custom_url" class="filelocker-label"><?php esc_html_e( 'Redirect Page', 'filelocker' ); ?></label>
					<input type="text" 
							id="filelocker_custom_url" 
							name="filelocker_custom_url" 
							class="filelocker-input" 
							list="filelocker_pages_list" 
							value="<?php echo esc_attr( $saved_url ); ?>" 
							placeholder="<?php esc_attr_e( 'Search and select a page...', 'filelocker' ); ?>" />
					<datalist id="filelocker_pages_list">
						<?php
						$pages = get_pages(
							array(
								'sort_column' => 'post_title',
								'sort_order'  => 'ASC',
							)
						);
						foreach ( $pages as $page ) {
							echo '<option value="' . esc_attr( get_permalink( $page->ID ) ) . '">' . esc_html( $page->post_title ) . '</option>';
						}
						?>
					</datalist>
					<p class="filelocker-description"><?php esc_html_e( 'Start typing to search and select a page to redirect users when a file is restricted. If left blank, users will be redirected to the homepage by default.', 'filelocker' ); ?></p>
				</div>
				
				<div class="filelocker-actions">
					<?php submit_button( 'Save Settings', 'primary', 'submit', false, array( 'class' => 'filelocker-save-btn' ) ); ?>
				</div>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Initialize the FileLocker upload handler and process any pending upload request.
 *
 * Instantiates the FileLocker class and invokes its file_handler method to handle file upload processing.
 *
 * @return void
 */
function filelocker_uploader() {
	$filelocker_uploads = new FileLocker();
	$filelocker_uploads->file_handler();
}

add_action( 'init', 'filelocker_uploader' );

/**
 * Renders WordPress admin error notices for any configuration errors reported by FileLocker.
 *
 * For each configuration error reported by the FileLocker instance, an admin error notice is output so administrators can see and address configuration issues.
 */
function filelocker_error_notice() {
	$filelocker        = new FileLocker();
	$filelocker_errors = $filelocker->config_error();

	if ( ! empty( $filelocker_errors ) ) {
		foreach ( $filelocker_errors as $single_error ) {
			echo '<div class="error"><p>' . wp_kses_post( $single_error ) . '</p></div>';
		}
	}
}

add_action( 'admin_notices', 'filelocker_error_notice' );



/**
 * Handle an admin request to delete a restricted file and redirect back to the admin UI.
 *
 * Verifies a deletion nonce and current user's capability, validates the posted file name, invokes
 * FileLocker->delete_filelocker_file() to remove the file, and redirects to the provided or default
 * admin page. On success the redirect URL will include `filelocker_deleted=1`. On failure the
 * redirect URL will include `filelocker_delete_error=1` and an `error_message` query parameter.
 *
 * This function will abort with wp_die() on failed security checks, insufficient permissions, or
 * missing file name input; on normal completion it issues a safe redirect and exits.
 */
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
	$filelocker    = new FileLocker();
	$delete_result = $filelocker->delete_filelocker_file( $file_name );

	// Handle deletion result and redirect with appropriate notice
	if ( is_array( $delete_result ) && $delete_result['success'] ) {
		$redirect_url = add_query_arg( 'filelocker_deleted', '1', $redirect_url );
	} else {
		$error_message = is_array( $delete_result ) ? $delete_result['error'] : 'Unknown error occurred';
		$redirect_url  = add_query_arg(
			array(
				'filelocker_delete_error' => '1',
				'error_message'           => rawurlencode( $error_message ),
			),
			$redirect_url
		);
	}

	wp_safe_redirect( $redirect_url );
	exit;
}

// Hook the deletion handler to admin_post action
add_action( 'admin_post_filelocker_delete', 'filelocker_handle_delete' );

/**
 * Displays admin notices indicating the result of a file deletion operation.
 *
 * Reads the `filelocker_deleted` and `filelocker_delete_error` query parameters to render
 * a success notice when deletion succeeded, or an error notice when deletion failed.
 * When an error occurs, uses the `error_message` query parameter (sanitized and URL-decoded)
 * as the message text; falls back to "Unknown error occurred" if not provided.
 */
function filelocker_display_deletion_notices() {
	if ( isset( $_GET['filelocker_deleted'] ) && '1' === $_GET['filelocker_deleted'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>File deleted successfully.</p></div>';
	}

	if ( isset( $_GET['filelocker_delete_error'] ) && '1' === $_GET['filelocker_delete_error'] ) {
		$error_message = isset( $_GET['error_message'] ) ? urldecode( sanitize_text_field( $_GET['error_message'] ) ) : 'Unknown error occurred';
		echo '<div class="notice notice-error is-dismissible"><p>Error deleting file: ' . esc_html( $error_message ) . '</p></div>';
	}
}

/**
 * Displays any per-user upload success or error notices stored in transients and clears them.
 *
 * Retrieves transient messages keyed to the current user for upload success and upload error,
 * prints corresponding admin notices if present, and deletes those transients to avoid repeat display.
 */
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

/**
 * Display settings-related admin notices for the File Locker plugin.
 *
 * Outputs any validation errors registered for the 'filelocker_custom_url' setting
 * and shows a dismissible success notice when settings have been saved.
 */
function filelocker_display_settings_notices() {
	settings_errors( 'filelocker_custom_url' );

	if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'filelocker' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'filelocker_display_settings_notices' );

/**
 * Build a redirect URL for a restricted file access flow.
 *
 * If a custom redirect URL option is configured, returns that URL with a `referrer` query
 * parameter pointing to either the uploads-based path derived from `$filename` (when the
 * filename contains `/uploads/...`) or the current request URI (with any `filelocker` query
 * segment removed). If no custom redirect URL is configured, returns the site home URL.
 *
 * @param string $filename The requested file path or name used to derive a referrer path.
 * @return string The URL to redirect the user to.
 */
function filelocker_redirect( $filename ) {
	$custom_url = get_option( 'filelocker_custom_url', '' );

	if ( ! empty( $custom_url ) ) {
		if ( preg_match( '#/uploads/(.+)$#', $filename, $matches ) ) {
			$url_path = '/app/uploads/' . $matches[1];
		} else {
			$url_path = esc_url_raw( $_SERVER['REQUEST_URI'] );
			$url_path = preg_replace( '/[?&]filelocker=.*/', '', $url_path );
		}

		$separator = ( false !== strpos( $custom_url, '?' ) ) ? '&' : '?';

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$locale_param = ICL_LANGUAGE_CODE;
		} else {
			$current_locale = get_locale();
			$locale_param   = ( 'en_US' === $current_locale ) ? 'en' : substr( $current_locale, 0, 2 );
		}

		return $custom_url . $separator . 'referrer=' . rawurlencode( $url_path ) . '&locale=' . $locale_param;
	}

	return home_url();
}