<?php

namespace FileLocker;

class FileLocker {

	public $home_url;
	public array $uploads_url;
	private string $uploads_dir;
	private string $filelocker_dir;
	private string $filelocker_url;
	private string $server_status;

	public function __construct() {
		$this->uploads_dir    = $this->get_uploads_path();
		$this->home_url       = home_url();
		$this->server_status  = $this->check_server_status();
		$this->uploads_url    = wp_upload_dir();
		$this->filelocker_url = $this->uploads_url['baseurl'] . '/filelocker/';

		$this->create_filelocker_directory();

		if ( 'apache' === $this->check_server_status() ) {
			$this->create_htaccess();
		}
	}


	public function access_conditions(): bool {
		if ( function_exists( 'filelocker_permissions' ) ) {
			return filelocker_permissions();
		} elseif ( is_user_logged_in() ) {
				return true;
		}

		return false;
	}

	private function check_server_status(): string {
		$server_ver = strtolower( $_SERVER['SERVER_SOFTWARE'] );

		if ( strpos( $server_ver, 'apache' ) !== false ) {
			return 'apache';
		} elseif ( strpos( $server_ver, 'nginx' ) !== false ) {
			return 'nginx';
		} else {
			return 'undefined';
		}
	}

	public function get_server_status(): string {
		return $this->check_server_status();
	}

	public function config_error(): array {
		$error_array = array();

		if ( 'undefined' === $this->server_status ) {
			$error_array[] = 'Current version of "FileLocker Plugin" works only for Apache or Nginx. Your server is using "' . $_SERVER['SERVER_SOFTWARE'] . '".';
		}

		if ( 'nginx' === $this->server_status && ! get_option( 'filelocker_nginx_warning_shown' ) ) {
			$error_array[] = 'Your server is running with <strong>nginx</strong>. This means you need to perform additional steps for it to work.
			Find your nginx .conf file and paste below within <code>location / {...}</code> rule: <br><br><code>if ($request_filename ~ uploads/filelocker/.+){<br>
            &nbsp;&nbsp;rewrite ^(.*)$ $scheme://$host/?filelocker=$request_filename redirect;<br>}</code>';

			update_option( 'filelocker_nginx_warning_shown', true );
		}

		if ( false === $this->check_if_filelocker_directory_exists() ) {
			$error_array[] = 'There was a problem with creating FileLocker files directory.';
		}

		if ( false === $this->check_if_htaccess_exists() ) {
			$error_array[] = 'There was a problem with creating .htaccess file inside FileLocker files directory.';
		}

		return $error_array;
	}

	private function check_if_file_in_directory( $file_path ) {
		if ( strpos( \wp_normalize_path( realpath( $file_path ) ), $this->filelocker_dir ) === false ) {
			return false;
		}

		return true;
	}

	public function view_download_file() {
		if ( isset( $_GET['filelocker'] ) ) {
			$filename = $_GET['filelocker'];

			if ( $filename ) {
				if ( file_exists( $filename ) && $this->access_conditions() && $this->check_if_file_in_directory( $filename ) ) {
					$content_type = $this->correct_type_header( $filename );

					header( 'Content-Description: File Transfer' );
					header( 'Content-Type: ' . $content_type );
					header( 'Content-Disposition: inline; filename="' . basename( $filename ) . '"' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate' );
					header( 'Pragma: public' );
					header( 'Content-Length: ' . filesize( $filename ) );
					readfile( $filename );
					exit;
				} else {
					if ( function_exists( 'filelocker_redirect' ) ) {
						$redirect_url = filelocker_redirect( $filename );
					} else {
						$redirect_url = $this->home_url;
					}

					header( 'Location: ' . $redirect_url );
					die;
				}
			}
		}
	}

	private function correct_type_header( string $filename ): string {
		$path_parts = pathinfo( $filename );

		if ( isset( $path_parts['extension'] ) ) {
			$path_extension = $path_parts['extension'];

			$image_array = array(
				'jpg',
				'jpeg',
				'png',
				'svg',
				'gif',
				'tiff',
			);

			if ( 'pdf' === $path_extension ) {
				return 'Content-Type: application/pdf';
			} elseif ( in_array( $path_extension, $image_array, true ) ) {
				return 'Content-Type: image/' . $path_extension;
			}
		}

		return 'Content-Type: application/octet-stream';
	}

	public function get_uploads_path(): string {
		$plugin_dir = wp_upload_dir();

		$this->uploads_dir = $plugin_dir['basedir'];
		return $plugin_dir['basedir'];
	}


	public function filelocker_directory_exists(): bool {
		$this->filelocker_dir = \wp_normalize_path( $this->uploads_dir . '/filelocker' );

		return file_exists( $this->filelocker_dir );
	}

	public function create_filelocker_directory(): bool {
		if ( $this->filelocker_directory_exists() === false ) {
			$create_filelocker_dir = mkdir( $this->filelocker_dir );

			if ( false === $create_filelocker_dir ) {
				echo 'Change uploads directory permissions or disable File Locker plugin.';
				die();
			}

			return true;
		}
		return false;
	}

	public function check_if_htaccess_exists(): bool {
		$htaccess_path = $this->filelocker_dir . '/.htaccess';
		return file_exists( $htaccess_path );
	}

	public function get_filelocker_admin_page() {
		return get_admin_url( null, 'admin.php?page=filelocker' );
	}

	public function check_if_filelocker_directory_exists(): bool {
		$filelocker_path = $this->filelocker_dir;
		return file_exists( $filelocker_path );
	}

	public function create_htaccess(): bool {

		if ( $this->check_if_htaccess_exists() === false ) {
			$htaccess_file = fopen( $this->filelocker_dir . '/.htaccess', 'w' );

			if ( ! $htaccess_file ) {
				wp_die( 'Unable to open file!' );
			}

			fwrite( $htaccess_file, $this->htaccess_content() );
			fclose( $htaccess_file );
			return true;
		}
		return false;
	}

	public function htaccess_content(): string {
		$htaccess  = 'RewriteEngine on' . PHP_EOL;
		$htaccess .= 'RewriteCond %{REQUEST_FILENAME} ^.*$' . PHP_EOL;
		$htaccess .= 'RewriteRule . ' . $this->home_url . '/?filelocker=%{REQUEST_FILENAME}' . PHP_EOL;

		return $htaccess;
	}

	public function list_all_restricted_files(): array {
		$files_array = $this->scan_directory_recursive( $this->filelocker_dir );

		// Sort by modification time descending (most recent first)
		usort(
			$files_array,
			function ( $a, $b ) {
				return $b['mtime'] - $a['mtime'];
			}
		);

		return $files_array;
	}

	private function scan_directory_recursive( string $directory ): array {
		$files_array        = array();
		$files_to_omit      = array(
			'.',
			'..',
		);
		$extensions_to_omit = array(
			'php',
			'htaccess',
		);

		$all_files = scandir( $directory );

		foreach ( $all_files as $single_file ) {
			if ( in_array( $single_file, $files_to_omit, true ) === false ) {
				$file_path = $directory . '/' . $single_file;

				if ( is_file( $file_path ) ) {
					// Skip files by extension or specific filenames
					$file_extension = pathinfo( $single_file, PATHINFO_EXTENSION );
					if ( in_array( strtolower( $file_extension ), $extensions_to_omit, true ) ||
						in_array( $single_file, array( '.htaccess' ), true ) ) {
						continue;
					}

					// Calculate relative path from filelocker_dir for URL and safer server-side handling
					$relative_path = str_replace( $this->filelocker_dir . '/', '', $file_path );
					// Normalize path separators and remove leading slashes
					$normalized_relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );

					$file_array['url']   = $this->filelocker_url . $relative_path;
					$file_array['dir']   = $file_path; // Keep absolute path for backward compatibility
					$file_array['rel']   = $normalized_relative_path; // Add relative path for safer forms
					$file_array['mtime'] = filemtime( $file_path );

					$files_array[] = $file_array;
				} elseif ( is_dir( $file_path ) ) {
					// Recursively scan subdirectories
					$subdirectory_files = $this->scan_directory_recursive( $file_path );
					$files_array        = array_merge( $files_array, $subdirectory_files );
				}
			}
		}

		return $files_array;
	}

	public function file_handler() {
		$target_dir = $this->filelocker_dir;

		if ( isset( $_POST['submitFileLocker'] ) && current_user_can( 'manage_options' ) ) {
			// Verify nonce for security
			if ( ! isset( $_POST['filelocker_upload_nonce'] ) || ! wp_verify_nonce( $_POST['filelocker_upload_nonce'], 'filelocker_upload_action' ) ) {
				$this->add_upload_error( 'Security verification failed. Please try again.' );
				return;
			}

			// Check if file was uploaded
			if ( ! isset( $_FILES['fileLockerFile'] ) || empty( $_FILES['fileLockerFile']['name'] ) ) {
				$this->add_upload_error( 'No file was selected for upload.' );
				return;
			}

			// Validate upload error status
			if ( UPLOAD_ERR_OK !== $_FILES['fileLockerFile']['error'] ) {
				$error_message = $this->get_upload_error_message( $_FILES['fileLockerFile']['error'] );
				$this->add_upload_error( $error_message );
				return;
			}

			// Enforce max file size (use WordPress upload limit)
			$wp_max_file_size = wp_max_upload_size();
			$max_file_size    = apply_filters( 'filelocker_max_file_size', $wp_max_file_size );
			if ( $_FILES['fileLockerFile']['size'] > $max_file_size ) {
				$max_size_mb = round( $max_file_size / ( 1024 * 1024 ), 1 );
				$this->add_upload_error( "File too large. Maximum allowed size is {$max_size_mb}MB." );
				return;
			}

			$original_filename = basename( $_FILES['fileLockerFile']['name'] );

			// Sanitize filename using WordPress function
			$sanitized_filename = sanitize_file_name( $original_filename );

			if ( empty( $sanitized_filename ) ) {
				$this->add_upload_error( 'Invalid filename. Please rename your file and try again.' );
				return;
			}

			// Validate file type and mime type
			$file_type_check = wp_check_filetype_and_ext( $_FILES['fileLockerFile']['tmp_name'], $sanitized_filename );

			if ( ! $file_type_check['type'] || ! $file_type_check['ext'] ) {
				$this->add_upload_error( 'File type not allowed. Please upload a valid file.' );
				return;
			}

			// Additional security: check against WordPress allowed mime types
			$allowed_mimes = get_allowed_mime_types();
			if ( ! in_array( $file_type_check['type'], $allowed_mimes ) ) {
				$this->add_upload_error( 'File type not permitted by WordPress security settings.' );
				return;
			}

			$target_file = $target_dir . '/' . $sanitized_filename;

			// Handle duplicate filenames by adding a suffix
			if ( file_exists( $target_file ) ) {
				$pathinfo  = pathinfo( $sanitized_filename );
				$filename  = $pathinfo['filename'];
				$extension = isset( $pathinfo['extension'] ) ? '.' . $pathinfo['extension'] : '';
				$counter   = 1;

				do {
					$new_filename = $filename . '_' . $counter . $extension;
					$target_file  = $target_dir . '/' . $new_filename;
					++$counter;
				} while ( file_exists( $target_file ) );
			}

			// Perform the file upload with proper error checking
			$file_tmp = $_FILES['fileLockerFile']['tmp_name'];

			// Verify the temporary file exists and is readable
			if ( ! is_uploaded_file( $file_tmp ) ) {
				$this->add_upload_error( 'Invalid upload. Please try again.' );
				error_log( 'FileLocker: Invalid uploaded file detected for ' . $original_filename );
				return;
			}

			// Attempt to move the uploaded file
			if ( ! move_uploaded_file( $file_tmp, $target_file ) ) {
				$this->add_upload_error( 'Failed to save file. Please check directory permissions.' );
				error_log( 'FileLocker: Failed to move uploaded file from ' . $file_tmp . ' to ' . $target_file );
				return;
			}

			// Set proper file permissions
			chmod( $target_file, 0644 );

			// Log successful upload
			error_log( 'FileLocker: Successfully uploaded file ' . basename( $target_file ) );

			// Add success message
			$this->add_upload_success( 'File uploaded successfully: ' . basename( $target_file ) );
		}
	}

	/**
	 * Get user-friendly upload error message based on PHP upload error code
	 */
	private function get_upload_error_message( $error_code ) {
		switch ( $error_code ) {
			case UPLOAD_ERR_INI_SIZE:
				return 'File too large (exceeds server upload_max_filesize setting).';
			case UPLOAD_ERR_FORM_SIZE:
				return 'File too large (exceeds form MAX_FILE_SIZE setting).';
			case UPLOAD_ERR_PARTIAL:
				return 'File upload was interrupted. Please try again.';
			case UPLOAD_ERR_NO_FILE:
				return 'No file was selected for upload.';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Server configuration error: missing temporary folder.';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Server error: failed to write file to disk.';
			case UPLOAD_ERR_EXTENSION:
				return 'Upload stopped by PHP extension.';
			default:
				return 'Unknown upload error occurred.';
		}
	}

	/**
	 * Add upload error message for display to user
	 */
	private function add_upload_error( $message ) {
		// Store error in WordPress transient for display on next page load
		set_transient( 'filelocker_upload_error_' . get_current_user_id(), $message, 60 );
		error_log( 'FileLocker Upload Error: ' . $message );
	}

	/**
	 * Add upload success message for display to user
	 */
	private function add_upload_success( $message ) {
		// Store success message in WordPress transient for display on next page load
		set_transient( 'filelocker_upload_success_' . get_current_user_id(), $message, 60 );
	}

	/**
	 * Check if a path is absolute (cross-platform)
	 */
	private function is_absolute_path( $path ) {
		// Windows: Check for drive letter (C:) or UNC path (\\)
		if ( DIRECTORY_SEPARATOR === '\\' ) {
			return preg_match( '/^[a-zA-Z]:\\\\/', $path ) || strpos( $path, '\\\\' ) === 0;
		}
		// Unix/Linux: Check for leading slash
		return strpos( $path, '/' ) === 0;
	}

	public function delete_filelocker_file( $filelocker_name = null ) {
		if ( false === current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'error'   => 'Insufficient permissions. Manage options capability required.',
			);
		}

		// If no parameter provided, try to get from POST (new secure method) or fallback to GET (legacy)
		if ( null === $filelocker_name ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled in calling function
			if ( isset( $_POST['filelocker_name'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled in calling function
				$filelocker_name = $_POST['filelocker_name'];
			} elseif ( isset( $_GET['filelocker_name'] ) ) {
				$filelocker_name = $_GET['filelocker_name'];
			} else {
				return array(
					'success' => false,
					'error'   => 'No file specified for deletion.',
				);
			}
		}

		// If the provided path appears to be relative, resolve it safely within the filelocker directory
		if ( ! $this->is_absolute_path( $filelocker_name ) ) {
			// Sanitize the relative path and prevent directory traversal
			$relative_path   = ltrim( str_replace( '\\', '/', $filelocker_name ), '/' );
			$relative_path   = preg_replace( '/\.\.\//', '', $relative_path ); // Remove any ../ patterns
			$filelocker_name = $this->filelocker_dir . '/' . $relative_path;
		}

		if ( ! file_exists( $filelocker_name ) ) {
			return array(
				'success' => false,
				'error'   => 'File not found: ' . basename( $filelocker_name ),
			);
		}

		if ( ! $this->check_if_file_in_directory( $filelocker_name ) ) {
			return array(
				'success' => false,
				'error'   => 'File is not in the secure directory.',
			);
		}

		if ( ! is_writable( dirname( $filelocker_name ) ) ) {
			return array(
				'success' => false,
				'error'   => 'Directory is not writable. Check file permissions.',
			);
		}

		if ( ! is_writable( $filelocker_name ) ) {
			return array(
				'success' => false,
				'error'   => 'File is not writable. Check file permissions.',
			);
		}

		$result = unlink( $filelocker_name );

		if ( $result ) {
			return array( 'success' => true );
		} else {
			$error         = error_get_last();
			$error_message = $error ? $error['message'] : 'Unknown error occurred during file deletion.';
			return array(
				'success' => false,
				'error'   => $error_message,
			);
		}
	}

	public function is_filelocker_file_restricted( string $file_url ): bool {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL           => $file_url,
				CURLOPT_NOBODY        => true,
				CURLOPT_CUSTOMREQUEST => 'GET',
			)
		);

		curl_exec( $curl );
		$httpcode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		if ( 302 === $httpcode ) {
			return true;
		}

		return false;
	}
}
