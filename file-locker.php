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
                <div class="filelocker-drop-zone" id="filelockerDropZone">
                    <div class="drop-zone-content">
                        <div class="drop-zone-icon">📁</div>
                        <p class="drop-zone-text">Drag & drop your file here or <button type="button" class="drop-zone-browse">browse files</button></p>
                        <p class="drop-zone-selected" id="selectedFileName" style="display: none;"></p>
                    </div>
                    <input type="file" name="fileLockerFile" id="fileLockerFile" class="filelocker-file-input" style="display: none;">
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
            <style>
                .filelocker-upload-section {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                .filelocker-drop-zone {
                    border: 2px dashed #c3c4c7;
                    border-radius: 8px;
                    padding: 40px 20px;
                    text-align: center;
                    transition: all 0.3s ease;
                    background: #fafafa;
                    cursor: pointer;
                    margin-bottom: 15px;
                }
                .filelocker-drop-zone:hover {
                    border-color: #8c8f94;
                    background: #f0f0f1;
                }
                .filelocker-drop-zone.drag-over {
                    border-color: #2271b1;
                    background: #f0f6fc;
                    border-style: solid;
                }
                .filelocker-drop-zone.has-file {
                    border-color: #00a32a;
                    background: #f6fff8;
                }
                .drop-zone-content {
                    pointer-events: none;
                }
                .drop-zone-icon {
                    font-size: 48px;
                    margin-bottom: 20px;
                }
                .drop-zone-text {
                    margin: 0;
                    color: #50575e;
                    font-size: 14px;
                }
                .drop-zone-browse {
                    background: none;
                    border: none;
                    color: #2271b1;
                    cursor: pointer;
                    text-decoration: underline;
                    font-size: inherit;
                    padding: 0;
                    pointer-events: all;
                }
                .drop-zone-browse:hover {
                    color: #135e96;
                }
                .drop-zone-selected {
                    margin: 10px 0 0 0;
                    color: #00a32a;
                    font-weight: 600;
                }
                .upload-actions {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }
                .filelocker-upload-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                .filelocker-section-title {
                    color: #1d2327;
                    font-size: 1.3em;
                    margin: 30px 0 15px 0;
                    font-weight: 600;
                }
                .wp-heading-inline {
                    color: #1d2327;
                }
                .filelocker-table-wrapper {
                    overflow-x: auto;
                    margin-top: 20px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .filelocker-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 600px;
                }
                .filelocker-table th,
                .filelocker-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                .filelocker-table th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                    white-space: nowrap;
                }
                .filelocker-table td {
                    word-break: break-word;
                }
                .filelocker-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .filelocker-table tr:hover {
                    background-color: #f5f5f5;
                }
                .filelocker-table a {
                    color: #0073aa;
                    text-decoration: none;
                }
                .filelocker-table a:hover {
                    text-decoration: underline;
                }
                .filelocker-table td:nth-child(2) {
                    max-width: 300px;
                    word-break: break-all;
                }
                .filelocker-table td:nth-child(4) {
                    white-space: nowrap;
                }
                @media (max-width: 768px) {
                    .filelocker-table th,
                    .filelocker-table td {
                        padding: 8px;
                        font-size: 14px;
                    }
                    .filelocker-table td:nth-child(2) {
                        max-width: 150px;
                    }
                }
                .filelocker-delete-btn {
                    background-color: #d63638;
                    color: white !important;
                    padding: 6px 12px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-size: 12px;
                    font-weight: bold;
                    display: inline-block;
                    transition: background-color 0.2s ease;
                }
                .filelocker-delete-btn:hover {
                    background-color: #b32d2e;
                    color: white !important;
                    text-decoration: none;
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const dropZone = document.getElementById('filelockerDropZone');
                    const fileInput = document.getElementById('fileLockerFile');
                    const uploadButton = document.getElementById('uploadButton');
                    const clearButton = document.getElementById('clearButton');
                    const selectedFileName = document.getElementById('selectedFileName');
                    const browseButton = document.querySelector('.drop-zone-browse');

                    // Browse button click
                    browseButton.addEventListener('click', function() {
                        fileInput.click();
                    });

                    // File input change
                    fileInput.addEventListener('change', function() {
                        handleFileSelection(this.files[0]);
                    });

                    // Drag and drop events
                    dropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        dropZone.classList.add('drag-over');
                    });

                    dropZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        dropZone.classList.remove('drag-over');
                    });

                    dropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        dropZone.classList.remove('drag-over');

                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            // Set the file to the input element
                            const dt = new DataTransfer();
                            dt.items.add(files[0]);
                            fileInput.files = dt.files;

                            handleFileSelection(files[0]);
                        }
                    });

                    // Clear button
                    clearButton.addEventListener('click', function() {
                        fileInput.value = '';
                        handleFileSelection(null);
                    });

                    function handleFileSelection(file) {
                        if (file) {
                            selectedFileName.textContent = '✓ Selected: ' + file.name;
                            selectedFileName.style.display = 'block';
                            uploadButton.disabled = false;
                            clearButton.style.display = 'inline-block';
                            dropZone.classList.add('has-file');

                            // Basic file validation
                            const maxSize = 100 * 1024 * 1024; // 100MB
                            if (file.size > maxSize) {
                                selectedFileName.textContent = '⚠ File too large (max 100MB)';
                                selectedFileName.style.color = '#d63638';
                                uploadButton.disabled = true;
                            }
                        } else {
                            selectedFileName.style.display = 'none';
                            uploadButton.disabled = true;
                            clearButton.style.display = 'none';
                            dropZone.classList.remove('has-file');
                            selectedFileName.style.color = '#00a32a';
                        }
                    }
                });
            </script>

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


