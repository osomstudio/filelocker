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
		} else {
			if ( is_user_logged_in() ) {
				return true;
			}
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

		if ( 'nginx' === $this->server_status ) {
			$error_array[] = 'Your server is running with <strong>nginx</strong>. This means you need to perform additional steps for it to work.
			Find your nginx .conf file and paste below within <code>location / {...}</code> rule: <br><br><code>if ($request_filename ~ uploads/filelocker/.+){<br>
            &nbsp;&nbsp;rewrite ^(.*)$ $scheme://$host/?filelocker=$request_filename redirect;<br>}</code>';
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

	public function view_download_file()
    {
        if ( isset( $_GET['filelocker'] ) ) {
            $filename = $_GET['filelocker'];

            if ($filename) {
                if (file_exists($filename) && $this->access_conditions() && $this->check_if_file_in_directory($filename)) {
                    $content_type = $this->correct_type_header($filename);

                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . $content_type);
                    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($filename));
                    readfile($filename);
                    exit;
                } else {
                    if (function_exists('filelocker_redirect')) {
                        $redirect_url = filelocker_redirect();
                    } else {
                        $redirect_url = $this->home_url;
                    }

                    header('Location: ' . $redirect_url);
                    die;
                }
            }
        }
	}

	private function correct_type_header( string $filename ): string {
		$path_parts     = pathinfo( $filename );
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
		} else {
			return 'Content-Type: application/octet-stream';
		}
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
			mkdir( $this->filelocker_dir );
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
			$htaccess_file = fopen( $this->filelocker_dir . '/.htaccess', 'w' ) or die( 'Unable to open file!' );
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
		$all_files     = scandir( $this->filelocker_dir );
		$files_array   = array();
		$array_to_omit = array(
			'.htaccess',
			'.',
			'..',
		);

		foreach ( $all_files as $single_file ) {
			if ( in_array( $single_file, $array_to_omit, true ) === false ) {
				$file_array['url'] = $this->filelocker_url . $single_file;
				$file_array['dir'] = $this->filelocker_dir . '/' . $single_file;

				$files_array[] = $file_array;
			}
		}

		return $files_array;
	}

	public function file_handler() {
		$target_dir  = $this->filelocker_dir;

        if ( isset ( $_FILES['fileLockerFile'] ) ) {
            $target_file = $target_dir . '/' . basename( $_FILES['fileLockerFile']['name'] );

            if ( isset( $_POST['submitFileLocker'] ) && current_user_can( 'manage_options' ) ) {
                $file_tmp = $_FILES['fileLockerFile']['tmp_name'];

                move_uploaded_file( $file_tmp, $target_file );
            }
        }
	}

	public function delete_filelocker_file() {
		if ( false === current_user_can( 'administrator' ) ) {
			return false;
		}

		if ( isset( $_GET['filelocker_name'] ) ) {
			$filelocker_name = $_GET['filelocker_name'];
			return unlink( $filelocker_name );
		}

		return false;
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
