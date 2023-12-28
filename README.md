# File Locker WordPress Plugin

File Locker is a WordPress plugin that enables you to manage and restrict access to files.

## Description

This plugin allows you to upload files securely and manage access to them via the WordPress admin panel. It creates a simple interface to upload, restrict, and delete files based on user permissions.

## Installation

1. Upload the `file-locker` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

Once activated, the plugin creates an admin menu named 'File Locker':
- **Upload File**: Upload files securely via the provided form.
- **Restricted Files**: View and manage all restricted files.

## Requirements

- WordPress version: 5.2 or higher
- PHP version: 7.2 or higher

## Contributors

- **Author**: [Osom Studio](https://www.osomstudio.com/)

## Support

For any issues, feature requests, or questions, please contact us [here](https://www.osomstudio.com/contact/).

## License

This project is licensed under the GNU GPLv3 License.

---

## Developer Notes

### FileLocker Class

The `FileLocker` class within this plugin contains several key methods and functionalities:

- `__construct()`: Initializes necessary directories and configurations.
- `access_conditions()`: Manages access conditions for file downloads.
- `view_download_file()`: Handles viewing and downloading of restricted files.
- `file_handler()`: Manages file upload functionality.
- `delete_filelocker_file()`: Handles the deletion of restricted files.

### Server Compatibility

File Locker is compatible with Apache and Nginx servers. It performs checks and configurations based on the server type to ensure proper functionality.

### Error Handling

The `config_error()` method within the `FileLocker` class identifies and displays errors related to server compatibility, directory creation, and `.htaccess` file creation.

For more technical details, please refer to the code within the `FileLocker` class.

---
