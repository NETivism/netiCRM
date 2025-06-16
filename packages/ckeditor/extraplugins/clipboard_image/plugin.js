/**
 * @file
 * Enhanced CKEditor clipboard image processing plugin with proper filename handling
 */

// Register the plugin
CKEDITOR.plugins.add('clipboard_image', {
  init: function(editor) {
    // Log initialization
    console.log('CKEditor Clipboard Image plugin initialized');

    // Use paste event as primary handler with improved error handling
    editor.on('paste', function(event) {
      console.log('CKEditor Clipboard Image: paste event triggered');

      // Method 1: Try to get clipboardData safely from multiple sources
      var clipboardData = null;
      var nativeEvent = null;

      try {
        // Try to get native event from different possible sources
        if (event.data && event.data.$) {
          nativeEvent = event.data.$.originalEvent || event.data.$;
        }

        if (nativeEvent && nativeEvent.clipboardData) {
          clipboardData = nativeEvent.clipboardData;
        } else if (window.clipboardData) {
          // Fallback for IE
          clipboardData = window.clipboardData;
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Failed to get clipboardData from event.data.$', e);
      }

      // Method 2: Try to use CKEditor's dataTransfer if available
      if (!clipboardData && event.data && event.data.dataTransfer) {
        console.log('CKEditor Clipboard Image: Using CKEditor dataTransfer');
        // CKEditor provides its own dataTransfer wrapper
        var dataTransfer = event.data.dataTransfer;

        // Check if dataTransfer has file information
        try {
          var fileCount = dataTransfer.getFilesCount ? dataTransfer.getFilesCount() : 0;
          if (fileCount > 0) {
            var file = dataTransfer.getFile(0);
            if (file && file.type && file.type.match(/^image\//)) {
              console.log('CKEditor Clipboard Image: Found image file via CKEditor dataTransfer', file.type);
              processImageFile(file, editor, 'drop'); // Mark as drop event
              event.stop();
              return;
            }
          }
        } catch (e) {
          console.log('CKEditor Clipboard Image: Error accessing CKEditor dataTransfer files', e);
        }
      }

      // Method 3: Process clipboard data if found
      if (clipboardData) {
        console.log('CKEditor Clipboard Image: Found clipboardData, examining contents');

        // Check available data types
        if (clipboardData.types) {
          console.log('CKEditor Clipboard Image: Available data types', clipboardData.types);
        }

        // Try items API (Chrome, Safari, Firefox support)
        if (clipboardData.items && clipboardData.items.length) {
          console.log('CKEditor Clipboard Image: Using items API');

          var imageFound = false;
          for (var i = 0; i < clipboardData.items.length; i++) {
            var item = clipboardData.items[i];
            console.log('CKEditor Clipboard Image: Checking item', item.kind, item.type);

            if (item.kind === 'file' && item.type.match(/^image\//)) {
              try {
                var file = item.getAsFile();
                if (file) {
                  console.log('CKEditor Clipboard Image: Got image file from items', file.name || 'unnamed', file.type, file.size + ' bytes');
                  processImageFile(file, editor, 'paste'); // Mark as paste event
                  imageFound = true;
                  event.stop();
                  break;
                }
              } catch (e) {
                console.log('CKEditor Clipboard Image: Error getting file from item', e);
              }
            }
          }

          if (imageFound) {
            return;
          }
        }

        // Try files API
        if (clipboardData.files && clipboardData.files.length) {
          console.log('CKEditor Clipboard Image: Using files API, file count:', clipboardData.files.length);

          var file = clipboardData.files[0];
          if (file && file.type && file.type.match(/^image\//)) {
            console.log('CKEditor Clipboard Image: Got image file from files', file.name || 'unnamed', file.type, file.size + ' bytes');
            processImageFile(file, editor, 'paste'); // Mark as paste event
            event.stop();
            return;
          }
        }

        // Try HTML content with image tags
        try {
          var html = clipboardData.getData('text/html');
          if (html && html.indexOf('<img') >= 0) {
            console.log('CKEditor Clipboard Image: Found image tag in HTML');

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            var imgElements = tempDiv.getElementsByTagName('img');

            if (imgElements.length > 0) {
              var imgSrc = imgElements[0].src;
              console.log('CKEditor Clipboard Image: Extracted image URL', imgSrc.substring(0, 100) + '...');

              if (imgSrc.indexOf('data:image/') === 0) {
                console.log('CKEditor Clipboard Image: Found base64 encoded image');
                loadImageFromDataUrl(imgSrc, editor, null, 'paste'); // Mark as paste event
                event.stop();
                return;
              } else if (imgSrc.indexOf('http') === 0) {
                console.log('CKEditor Clipboard Image: Found web image URL');
                // Optional: handle web images
              }
            }
          }
        } catch (e) {
          console.log('CKEditor Clipboard Image: Unable to extract image from HTML', e);
        }
      }

      console.log('CKEditor Clipboard Image: No processable image found');
    });

    // Enhanced drag and drop handling
    editor.on('contentDom', function() {
      var editable = editor.editable();

      if (editable && editable.attachListener) {
        // Handle paste events
        editable.attachListener(editable, 'paste', function(evt) {
          console.log('CKEditor Clipboard Image: editable paste event triggered');

          try {
            var nativeEvent = evt.data.$;
            if (nativeEvent && nativeEvent.clipboardData && nativeEvent.clipboardData.items) {
              var items = nativeEvent.clipboardData.items;
              for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image/') === 0) {
                  var file = items[i].getAsFile();
                  if (file) {
                    console.log('CKEditor Clipboard Image: contentDom method found image', file.type);
                    processImageFile(file, editor, 'paste');
                    evt.data.preventDefault();
                    break;
                  }
                }
              }
            }
          } catch (e) {
            console.log('CKEditor Clipboard Image: Error in contentDom paste handler', e);
          }
        });

        // Handle drop events to capture real filenames
        editable.attachListener(editable, 'drop', function(evt) {
          console.log('CKEditor Clipboard Image: drop event triggered');

          try {
            var nativeEvent = evt.data.$;
            if (nativeEvent && nativeEvent.dataTransfer && nativeEvent.dataTransfer.files) {
              var files = nativeEvent.dataTransfer.files;
              for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (file.type && file.type.indexOf('image/') === 0) {
                  console.log('CKEditor Clipboard Image: drop method found image file', file.name, file.type);
                  processImageFile(file, editor, 'drop');
                  evt.data.preventDefault();
                  break;
                }
              }
            }
          } catch (e) {
            console.log('CKEditor Clipboard Image: Error in drop handler', e);
          }
        });
      }
    });

    /**
     * Validate image format against whitelist
     * @param {string} mimeType - The MIME type to validate
     * @returns {boolean} - True if format is allowed
     */
    function isValidImageFormat(mimeType) {
      var allowedFormats = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif'
      ];

      return allowedFormats.indexOf(mimeType.toLowerCase()) !== -1;
    }

    /**
     * Generate appropriate filename based on source and original name
     * @param {File} file - The image file
     * @param {string} source - Source of the image ('paste' or 'drop')
     * @returns {Object} - Object with originalName and suggestedName
     */
    function generateFileNames(file, source) {
      var timestamp = new Date().getTime();
      var originalName = '';
      var suggestedName = '';

      // Try to get original filename
      if (file && file.name && file.name.trim() !== '') {
        originalName = file.name.trim();
        console.log('CKEditor Clipboard Image: Found original filename:', originalName);
      } else {
        console.log('CKEditor Clipboard Image: No original filename available (typical for clipboard paste)');
      }

      // Generate appropriate suggested name based on source
      var fileExtension = 'jpg'; // Default
      if (file.type) {
        switch (file.type.toLowerCase()) {
          case 'image/png':
            fileExtension = 'png';
            break;
          case 'image/gif':
            fileExtension = 'gif';
            break;
          case 'image/jpeg':
          case 'image/jpg':
          default:
            fileExtension = 'jpg';
            break;
        }
      }

      if (source === 'drop' && originalName) {
        // For dropped files, use original name if available
        suggestedName = originalName;
      } else {
        // For pasted images or dropped files without names, generate descriptive name
        var now = new Date();
        var dateStr = now.getFullYear() + '-' +
          String(now.getMonth() + 1).padStart(2, '0') + '-' +
          String(now.getDate()).padStart(2, '0') + '_' +
          String(now.getHours()).padStart(2, '0') + '-' +
          String(now.getMinutes()).padStart(2, '0') + '-' +
          String(now.getSeconds()).padStart(2, '0');

        if (source === 'paste') {
          suggestedName = 'pasted_image_' + dateStr + '.' + fileExtension;
        } else {
          suggestedName = 'dropped_image_' + dateStr + '.' + fileExtension;
        }
      }

      return {
        originalName: originalName,
        suggestedName: suggestedName,
        tempName: 'temp_' + timestamp + '.' + fileExtension
      };
    }

    /**
     * Process image file and convert to inline data
     * @param {File} file - The image file to process
     * @param {Object} editor - CKEditor instance
     * @param {string} source - Source of the image ('paste' or 'drop')
     */
    function processImageFile(file, editor, source) {
      if (!file) {
        console.error('CKEditor Clipboard Image: No file provided for processing');
        return;
      }

      // Validate image format
      if (!isValidImageFormat(file.type)) {
        console.warn('CKEditor Clipboard Image: Unsupported image format', file.type);
        alert('不支援的圖片格式：' + file.type + '\n支援的格式：JPEG, PNG, GIF');
        return;
      }

      console.log('CKEditor Clipboard Image: Starting to process image file', file.type, 'from', source);

      var reader = new FileReader();

      reader.onerror = function(e) {
        console.error('CKEditor Clipboard Image: Error reading file', e);
      };

      reader.onload = function(e) {
        if (!e.target || !e.target.result) {
          console.error('CKEditor Clipboard Image: FileReader returned no result');
          return;
        }

        console.log('CKEditor Clipboard Image: File read successfully, data length', e.target.result.length);
        loadImageFromDataUrl(e.target.result, editor, file.type, source, file);
      };

      try {
        reader.readAsDataURL(file);
      } catch (e) {
        console.error('CKEditor Clipboard Image: Exception while reading file', e);
      }
    }

    /**
     * Extract MIME type from data URL
     * @param {string} dataUrl - The data URL
     * @returns {string} - The MIME type or 'image/jpeg' as fallback
     */
    function extractMimeTypeFromDataUrl(dataUrl) {
      var match = dataUrl.match(/^data:([^;]+);/);
      return match ? match[1] : 'image/jpeg';
    }

    /**
     * Load image from DataURL and process
     * @param {string} dataUrl - The data URL of the image
     * @param {Object} editor - CKEditor instance
     * @param {string} originalMimeType - Original MIME type (optional)
     * @param {string} source - Source of the image ('paste' or 'drop')
     * @param {File} originalFile - Original file object (optional)
     */
    function loadImageFromDataUrl(dataUrl, editor, originalMimeType, source, originalFile) {
      // Extract MIME type from data URL if not provided
      var mimeType = originalMimeType || extractMimeTypeFromDataUrl(dataUrl);

      // Validate image format
      if (!isValidImageFormat(mimeType)) {
        console.warn('CKEditor Clipboard Image: Unsupported image format', mimeType);
        alert('不支援的圖片格式：' + mimeType + '\n支援的格式：JPEG, PNG, GIF');
        return;
      }

      var img = new Image();

      img.onerror = function() {
        console.error('CKEditor Clipboard Image: Unable to load image from data');
      };

      img.onload = function() {
        console.log('CKEditor Clipboard Image: Image loaded', img.width + 'x' + img.height);
        resizeAndProcessImage(img, editor, mimeType, source, originalFile);
      };

      img.src = dataUrl;
    }

    /**
     * Calculate optimal image size maintaining aspect ratio
     * @param {number} originalWidth - Original image width
     * @param {number} originalHeight - Original image height
     * @param {number} maxWidth - Maximum allowed width
     * @param {number} maxHeight - Maximum allowed height
     * @returns {Object} New dimensions {width, height}
     */
    function calculateNewSize(originalWidth, originalHeight, maxWidth, maxHeight) {
      var width = originalWidth;
      var height = originalHeight;

      // Scale down proportionally if needed
      if (width > maxWidth) {
        height = Math.round(height * (maxWidth / width));
        width = maxWidth;
      }

      if (height > maxHeight) {
        width = Math.round(width * (maxHeight / height));
        height = maxHeight;
      }

      return { width: width, height: height };
    }

    /**
     * Resize image using Canvas API and process as Blob
     * @param {Image} img - The loaded image
     * @param {Object} editor - CKEditor instance
     * @param {string} originalMimeType - Original image MIME type
     * @param {string} source - Source of the image ('paste' or 'drop')
     * @param {File} originalFile - Original file object (optional)
     */
    function resizeAndProcessImage(img, editor, originalMimeType, source, originalFile) {
      try {
        // Create canvas element
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');

        // Get settings with defaults
        var maxWidth = 800;
        var maxHeight = 600;
        var quality = 0.7;
        var uploadToServer = true; // Default to upload mode

        // Use original format to maintain image characteristics
        var outputFormat = originalMimeType || 'image/jpeg';

        // Try to get settings from Drupal if available
        try {
          if (typeof Drupal !== 'undefined' && Drupal.settings && Drupal.settings.clipboard_image) {
            if (Drupal.settings.clipboard_image.maxWidth) {
              maxWidth = parseInt(Drupal.settings.clipboard_image.maxWidth, 10) || maxWidth;
            }
            if (Drupal.settings.clipboard_image.maxHeight) {
              maxHeight = parseInt(Drupal.settings.clipboard_image.maxHeight, 10) || maxHeight;
            }
            if (Drupal.settings.clipboard_image.quality) {
              quality = parseFloat(Drupal.settings.clipboard_image.quality) || quality;
            }
            if (Drupal.settings.clipboard_image.uploadToServer !== undefined) {
              uploadToServer = !!Drupal.settings.clipboard_image.uploadToServer;
            }
          }
        } catch (e) {
          console.error('CKEditor Clipboard Image: Error getting settings', e);
        }

        console.log('CKEditor Clipboard Image: Using settings', {
          maxWidth: maxWidth,
          maxHeight: maxHeight,
          quality: quality,
          uploadToServer: uploadToServer,
          outputFormat: outputFormat,
          source: source
        });

        // Calculate resized dimensions
        var newSize = calculateNewSize(img.width, img.height, maxWidth, maxHeight);

        // Set canvas dimensions
        canvas.width = newSize.width;
        canvas.height = newSize.height;

        // For PNG, ensure transparency is preserved
        if (outputFormat === 'image/png') {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Draw resized image on canvas
        ctx.drawImage(img, 0, 0, newSize.width, newSize.height);

        console.log('CKEditor Clipboard Image: Canvas processing complete, converting to blob with format:', outputFormat);

        // Convert canvas to Blob with original format
        canvas.toBlob(function(blob) {
          if (!blob) {
            console.error('CKEditor Clipboard Image: Failed to create blob from canvas');
            return;
          }

          console.log('CKEditor Clipboard Image: Blob created successfully', blob.size + ' bytes, type:', blob.type);

          // Generate filenames
          var fileNames = generateFileNames(originalFile, source);
          console.log('CKEditor Clipboard Image: Generated filenames:', fileNames);

          // Check upload mode
          if (uploadToServer) {
            console.log('CKEditor Clipboard Image: Upload mode enabled, sending blob to server');
            uploadBlobToServer(blob, editor, fileNames);
          } else {
            console.log('CKEditor Clipboard Image: Inline mode, using blob directly');
            insertBlobAsInlineImage(blob, editor, fileNames);
          }
        }, outputFormat, quality);

      } catch (e) {
        console.error('CKEditor Clipboard Image: Error processing image with canvas', e);
      }
    }

    /**
     * Insert blob as inline image using object URL with proper title attribute
     * @param {Blob} blob - The processed image blob
     * @param {Object} editor - CKEditor instance
     * @param {Object} fileNames - Object containing filename information
     */
    function insertBlobAsInlineImage(blob, editor, fileNames) {
      try {
        // Create object URL directly from blob
        var objectUrl = URL.createObjectURL(blob);

        // Create title attribute with original and temp filename
        var titleAttr = '';
        if (fileNames.originalName) {
          titleAttr = fileNames.originalName + '|' + fileNames.tempName;
        } else {
          titleAttr = fileNames.suggestedName + '|' + fileNames.tempName;
        }

        console.log('CKEditor Clipboard Image: Inserting image with title:', titleAttr);
        editor.insertHtml('<img src="' + objectUrl + '" alt="Pasted image" title="' + titleAttr + '" />');
        console.log('CKEditor Clipboard Image: Image successfully inserted into editor with blob URL and title');

      } catch (e) {
        console.error('CKEditor Clipboard Image: Exception in insertBlobAsInlineImage', e);
      }
    }

    /**
     * Upload blob to server endpoint using FormData
     * @param {Blob} blob - The processed image blob
     * @param {Object} editor - CKEditor instance
     * @param {Object} fileNames - Object containing filename information
     */
    function uploadBlobToServer(blob, editor, fileNames) {
      console.log('CKEditor Clipboard Image: Starting server upload with blob');

      // Create FormData for efficient blob upload
      var formData = new FormData();

      // Use temp name for server-side processing, but send original name for reference
      formData.append('image', blob, fileNames.tempName);
      formData.append('original_filename', fileNames.originalName || '');
      formData.append('suggested_filename', fileNames.suggestedName);
      formData.append('timestamp', new Date().getTime());

      // Determine the correct upload URL
      var uploadUrl = '/civicrm/ajax/editor/image-upload';

      // Try to get base URL from Drupal if available
      try {
        if (typeof Drupal !== 'undefined' && Drupal.settings && Drupal.settings.basePath) {
          uploadUrl = Drupal.settings.basePath + 'civicrm/ajax/editor/image-upload';
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Using default upload URL');
      }

      console.log('CKEditor Clipboard Image: Uploading blob to', uploadUrl);
      console.log('CKEditor Clipboard Image: File information:', fileNames);

      // Try fetch API first (more modern and reliable)
      if (typeof fetch !== 'undefined') {
        console.log('CKEditor Clipboard Image: Using fetch API for blob upload');

        fetch(uploadUrl, {
          method: 'POST',
          body: formData
        })
        .then(function(response) {
          console.log('CKEditor Clipboard Image: Fetch response status:', response.status);
          return response.json();
        })
        .then(function(data) {
          console.log('CKEditor Clipboard Image: Fetch response data:', data);

          if (data.status === 1) {
            console.log('CKEditor Clipboard Image: Server upload successful');

            // Use server-provided title_attribute only if provided
            var titleAttr = data.title_attribute || '';
            var imgHtml = '';

            // Create img tag with or without title attribute
            if (titleAttr) {
              imgHtml = '<img src="' + (data.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" title="' + titleAttr + '" />';
              console.log('CKEditor Clipboard Image: Using server-provided title attribute:', titleAttr);
            } else {
              imgHtml = '<img src="' + (data.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" />';
              console.log('CKEditor Clipboard Image: No title attribute provided by server');
            }

            // Insert the image
            editor.insertHtml(imgHtml);
            console.log('CKEditor Clipboard Image: Image inserted successfully');
          } else {
            console.error('CKEditor Clipboard Image: Server upload failed', data);
            // Fallback to inline insertion
            insertBlobAsInlineImage(blob, editor, fileNames);
          }
        })
        .catch(function(error) {
          console.error('CKEditor Clipboard Image: Fetch error:', error);
          // Fallback to inline insertion
          insertBlobAsInlineImage(blob, editor, fileNames);
        });

      } else {
        // Fallback to XMLHttpRequest for blob upload
        console.log('CKEditor Clipboard Image: Using XMLHttpRequest for blob upload (fetch not available)');

        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            console.log('CKEditor Clipboard Image: XHR complete - Status:', xhr.status);

            try {
              var response = JSON.parse(xhr.responseText);
              console.log('CKEditor Clipboard Image: Parsed response:', response);

              if (xhr.status === 200 && response.status === 1) {
                console.log('CKEditor Clipboard Image: Server upload successful', response);

                // Use server-provided title_attribute only if provided
                var titleAttr = response.title_attribute || '';
                var imgHtml = '';

                // Create img tag with or without title attribute
                if (titleAttr) {
                  imgHtml = '<img src="' + (response.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" title="' + titleAttr + '" />';
                  console.log('CKEditor Clipboard Image: Using server-provided title attribute:', titleAttr);
                } else {
                  imgHtml = '<img src="' + (response.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" />';
                  console.log('CKEditor Clipboard Image: No title attribute provided by server');
                }

                // Insert the image
                editor.insertHtml(imgHtml);
                console.log('CKEditor Clipboard Image: Image inserted successfully');
              } else {
                console.error('CKEditor Clipboard Image: Server upload failed', response);
                // Fallback to inline insertion
                insertBlobAsInlineImage(blob, editor, fileNames);
              }
            } catch (e) {
              console.error('CKEditor Clipboard Image: Error parsing server response', e);
              // Fallback to inline insertion
              insertBlobAsInlineImage(blob, editor, fileNames);
            }
          }
        };

        xhr.onerror = function() {
          console.error('CKEditor Clipboard Image: XHR network error');
          // Fallback to inline insertion
          insertBlobAsInlineImage(blob, editor, fileNames);
        };

        xhr.ontimeout = function() {
          console.error('CKEditor Clipboard Image: XHR timeout');
          // Fallback to inline insertion
          insertBlobAsInlineImage(blob, editor, fileNames);
        };

        try {
          xhr.open('POST', uploadUrl, true);
          xhr.timeout = 30000; // 30 second timeout

          console.log('CKEditor Clipboard Image: About to send XHR request with blob');
          xhr.send(formData);
          console.log('CKEditor Clipboard Image: XHR request sent');

        } catch (e) {
          console.error('CKEditor Clipboard Image: Exception sending XHR request', e);
          // Fallback to inline insertion
          insertBlobAsInlineImage(blob, editor, fileNames);
        }
      }
    }
  }
});