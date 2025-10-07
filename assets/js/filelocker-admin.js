document.addEventListener('DOMContentLoaded', function() {
	const dropZone = document.getElementById('filelockerDropZone');
	const fileInput = document.getElementById('fileLockerFile');
	const uploadButton = document.getElementById('uploadButton');
	const clearButton = document.getElementById('clearButton');
	const selectedFileName = document.getElementById('selectedFileName');
	const browseButton = document.querySelector('.drop-zone-browse');

	if (browseButton && dropZone && fileInput) {
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
		if (clearButton) {
			clearButton.addEventListener('click', function() {
				fileInput.value = '';
				handleFileSelection(null);
			});
		}
	}

	/**
	 * Update the UI to reflect a selected file or clear the selection.
	 *
	 * When provided a File, displays the file name, enables the upload and clear controls,
	 * adds the 'has-file' class to the drop zone, and enforces a 100 MB size limit
	 * (displays an error and disables upload if exceeded). When passed `null`, hides file
	 * UI, disables controls, removes the 'has-file' class, and resets the filename color.
	 *
	 * @param {File|null} file - The selected File object, or `null` to clear the current selection.
	 */
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