/**
 * @file
 * Enhanced CKEditor clipboard image processing plugin with improved reliability
 */

// Register the plugin
CKEDITOR.plugins.add('clipboard_image', {
  init: function(editor) {
    console.log('CKEditor Clipboard Image plugin initialized');

    // Enhanced paste event handler with multiple fallback strategies
    editor.on('paste', function(event) {
      console.log('CKEditor Clipboard Image: paste event triggered');
      console.log('CKEditor Clipboard Image: event object structure:', {
        hasData: !!event.data,
        hasDataTransfer: !!(event.data && event.data.dataTransfer),
        hasNativeEvent: !!(event.data && event.data.$)
      });

      // Strategy 1: Try to get native clipboard data from multiple sources
      var clipboardData = null;
      var nativeEvent = null;

      try {
        // Enhanced native event detection
        if (event.data && event.data.$) {
          nativeEvent = event.data.$.originalEvent || event.data.$.clipboardData || event.data.$;
          console.log('CKEditor Clipboard Image: Found native event via event.data.$');
        }

        // Try multiple paths to get clipboardData
        if (nativeEvent) {
          clipboardData = nativeEvent.clipboardData || nativeEvent.originalEvent?.clipboardData;
        }

        // Fallback to global clipboardData (IE)
        if (!clipboardData && window.clipboardData) {
          clipboardData = window.clipboardData;
          console.log('CKEditor Clipboard Image: Using window.clipboardData (IE fallback)');
        }

        if (clipboardData) {
          console.log('CKEditor Clipboard Image: Strategy 1 - Found clipboardData');
          if (processClipboardData(clipboardData, editor)) {
            event.stop();
            return;
          }
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Strategy 1 failed:', e);
      }

      // Strategy 2: CKEditor dataTransfer with enhanced error handling
      if (event.data && event.data.dataTransfer) {
        console.log('CKEditor Clipboard Image: Strategy 2 - Using CKEditor dataTransfer');

        var dataTransfer = event.data.dataTransfer;
        console.log('CKEditor Clipboard Image: dataTransfer methods available:', {
          hasGetFilesCount: typeof dataTransfer.getFilesCount === 'function',
          hasGetFile: typeof dataTransfer.getFile === 'function'
        });

        try {
          var fileCount = 0;

          // Enhanced file count detection
          if (typeof dataTransfer.getFilesCount === 'function') {
            fileCount = dataTransfer.getFilesCount();
          } else if (dataTransfer._.filesList && dataTransfer._.filesList.length) {
            fileCount = dataTransfer._.filesList.length;
          }

          console.log('CKEditor Clipboard Image: File count detected:', fileCount);

          if (fileCount > 0) {
            for (var i = 0; i < fileCount; i++) {
              try {
                var file = dataTransfer.getFile(i);
                console.log('CKEditor Clipboard Image: File', i, ':', {
                  exists: !!file,
                  type: file ? file.type : 'N/A',
                  size: file ? file.size : 'N/A'
                });

                if (file && file.type && file.type.match(/^image\//)) {
                  console.log('CKEditor Clipboard Image: Found image file via CKEditor dataTransfer', file.type);
                  processImageFile(file, editor, 'drop');
                  event.stop();
                  return;
                }
              } catch (fileError) {
                console.log('CKEditor Clipboard Image: Error getting file', i, ':', fileError);
              }
            }
          }
        } catch (e) {
          console.log('CKEditor Clipboard Image: Strategy 2 error:', e);
        }
      }

      // Strategy 3: Check for HTML content with images
      try {
        if (event.data && event.data.dataValue) {
          var htmlContent = event.data.dataValue;
          console.log('CKEditor Clipboard Image: Strategy 3 - Checking HTML content');

          if (htmlContent && htmlContent.indexOf('<img') >= 0) {
            console.log('CKEditor Clipboard Image: Found image tag in HTML content');

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlContent;
            var imgElements = tempDiv.getElementsByTagName('img');

            if (imgElements.length > 0) {
              var imgSrc = imgElements[0].src;
              console.log('CKEditor Clipboard Image: Extracted image URL prefix:', imgSrc.substring(0, 50) + '...');

              if (imgSrc.indexOf('data:image/') === 0) {
                console.log('CKEditor Clipboard Image: Found base64 encoded image');
                loadImageFromDataUrl(imgSrc, editor, null, 'paste');
                event.stop();
                return;
              }
            }
          }
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Strategy 3 error:', e);
      }

      console.log('CKEditor Clipboard Image: All strategies failed - No processable image found');
    });

    // Enhanced contentDom event for additional paste handling
    editor.on('contentDom', function() {
      var editable = editor.editable();

      if (editable && editable.attachListener) {
        // Native paste event as ultimate fallback
        editable.attachListener(editable, 'paste', function(evt) {
          console.log('CKEditor Clipboard Image: Native paste event triggered as fallback');

          setTimeout(function() {
            try {
              var nativeEvent = evt.data.$;
              if (nativeEvent && nativeEvent.clipboardData) {
                console.log('CKEditor Clipboard Image: Processing via native paste event');
                processClipboardData(nativeEvent.clipboardData, editor);
              }
            } catch (e) {
              console.log('CKEditor Clipboard Image: Native paste fallback error:', e);
            }
          }, 10); // Small delay to ensure CKEditor processing is complete
        });

        // Enhanced drop handling
        editable.attachListener(editable, 'drop', function(evt) {
          console.log('CKEditor Clipboard Image: Drop event triggered');

          try {
            var nativeEvent = evt.data.$;
            if (nativeEvent && nativeEvent.dataTransfer && nativeEvent.dataTransfer.files) {
              var files = nativeEvent.dataTransfer.files;
              console.log('CKEditor Clipboard Image: Drop files count:', files.length);

              for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (file.type && file.type.indexOf('image/') === 0) {
                  console.log('CKEditor Clipboard Image: Processing dropped image:', file.name, file.type);
                  processImageFile(file, editor, 'drop');
                  evt.data.preventDefault();
                  break;
                }
              }
            }
          } catch (e) {
            console.log('CKEditor Clipboard Image: Drop handler error:', e);
          }
        });
      }
    });

    /**
     * Enhanced clipboard data processing function
     * @param {DataTransfer} clipboardData - The clipboard data object
     * @param {Object} editor - CKEditor instance
     * @returns {boolean} - True if image was processed successfully
     */
    function processClipboardData(clipboardData, editor) {
      console.log('CKEditor Clipboard Image: Processing clipboard data');
      console.log('CKEditor Clipboard Image: Available data types:', clipboardData.types ? Array.from(clipboardData.types) : 'N/A');

      // Method A: Try items API (modern browsers)
      if (clipboardData.items && clipboardData.items.length) {
        console.log('CKEditor Clipboard Image: Using items API, item count:', clipboardData.items.length);

        for (var i = 0; i < clipboardData.items.length; i++) {
          var item = clipboardData.items[i];
          console.log('CKEditor Clipboard Image: Item', i, ':', {
            kind: item.kind,
            type: item.type
          });

          if (item.kind === 'file' && item.type.match(/^image\//)) {
            try {
              var file = item.getAsFile();
              if (file) {
                console.log('CKEditor Clipboard Image: Got image file from items API:', file.type, file.size + ' bytes');
                processImageFile(file, editor, 'paste');
                return true;
              }
            } catch (e) {
              console.log('CKEditor Clipboard Image: Error getting file from item:', e);
            }
          }
        }
      }

      // Method B: Try files API
      if (clipboardData.files && clipboardData.files.length) {
        console.log('CKEditor Clipboard Image: Using files API, file count:', clipboardData.files.length);

        for (var i = 0; i < clipboardData.files.length; i++) {
          var file = clipboardData.files[i];
          if (file && file.type && file.type.match(/^image\//)) {
            console.log('CKEditor Clipboard Image: Got image file from files API:', file.type, file.size + ' bytes');
            processImageFile(file, editor, 'paste');
            return true;
          }
        }
      }

      // Method C: Try HTML content
      try {
        var html = clipboardData.getData('text/html');
        if (html && html.indexOf('<img') >= 0) {
          console.log('CKEditor Clipboard Image: Found image in HTML data');

          var tempDiv = document.createElement('div');
          tempDiv.innerHTML = html;
          var imgElements = tempDiv.getElementsByTagName('img');

          if (imgElements.length > 0) {
            var imgSrc = imgElements[0].src;
            console.log('CKEditor Clipboard Image: Extracted image URL from HTML');

            if (imgSrc.indexOf('data:image/') === 0) {
              console.log('CKEditor Clipboard Image: Processing base64 image from HTML');
              loadImageFromDataUrl(imgSrc, editor, null, 'paste');
              return true;
            }
          }
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Error processing HTML data:', e);
      }

      return false;
    }

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
        console.log('CKEditor Clipboard Image: No original filename available');
      }

      // Generate appropriate suggested name based on source
      var fileExtension = 'jpg'; // Default
      if (file && file.type) {
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
        suggestedName = originalName;
      } else {
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
      var mimeType = originalMimeType || extractMimeTypeFromDataUrl(dataUrl);

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
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');

        // Default settings
        var maxWidth = 800;
        var maxHeight = 600;
        var quality = 0.7;
        var uploadToServer = true;
        var outputFormat = originalMimeType || 'image/jpeg';

        // Try to get settings from Drupal
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

        var newSize = calculateNewSize(img.width, img.height, maxWidth, maxHeight);
        canvas.width = newSize.width;
        canvas.height = newSize.height;

        if (outputFormat === 'image/png') {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        ctx.drawImage(img, 0, 0, newSize.width, newSize.height);

        console.log('CKEditor Clipboard Image: Canvas processing complete, converting to blob with format:', outputFormat);

        canvas.toBlob(function(blob) {
          if (!blob) {
            console.error('CKEditor Clipboard Image: Failed to create blob from canvas');
            return;
          }

          console.log('CKEditor Clipboard Image: Blob created successfully', blob.size + ' bytes, type:', blob.type);

          var fileNames = generateFileNames(originalFile, source);
          console.log('CKEditor Clipboard Image: Generated filenames:', fileNames);

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
     * Insert blob as inline image using object URL
     * @param {Blob} blob - The processed image blob
     * @param {Object} editor - CKEditor instance
     * @param {Object} fileNames - Object containing filename information
     */
    function insertBlobAsInlineImage(blob, editor, fileNames) {
      try {
        var objectUrl = URL.createObjectURL(blob);
        var titleAttr = '';

        if (fileNames.originalName) {
          titleAttr = fileNames.originalName + '|' + fileNames.tempName;
        } else {
          titleAttr = fileNames.suggestedName + '|' + fileNames.tempName;
        }

        console.log('CKEditor Clipboard Image: Inserting image with title:', titleAttr);
        editor.insertHtml('<img src="' + objectUrl + '" alt="Pasted image" title="' + titleAttr + '" />');
        console.log('CKEditor Clipboard Image: Image successfully inserted into editor');

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

      var formData = new FormData();
      formData.append('image', blob, fileNames.tempName);
      formData.append('original_filename', fileNames.originalName || '');
      formData.append('suggested_filename', fileNames.suggestedName);
      formData.append('timestamp', new Date().getTime());

      var uploadUrl = '/civicrm/ajax/editor/image-upload';

      try {
        if (typeof Drupal !== 'undefined' && Drupal.settings && Drupal.settings.basePath) {
          uploadUrl = Drupal.settings.basePath + 'civicrm/ajax/editor/image-upload';
        }
      } catch (e) {
        console.log('CKEditor Clipboard Image: Using default upload URL');
      }

      console.log('CKEditor Clipboard Image: Uploading blob to', uploadUrl);

      // Use fetch API with enhanced error handling
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

            var titleAttr = data.title_attribute || '';
            var imgHtml = '';

            if (titleAttr) {
              imgHtml = '<img src="' + (data.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" title="' + titleAttr + '" />';
            } else {
              imgHtml = '<img src="' + (data.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" />';
            }

            editor.insertHtml(imgHtml);
            console.log('CKEditor Clipboard Image: Image inserted successfully');
          } else {
            console.error('CKEditor Clipboard Image: Server upload failed', data);
            insertBlobAsInlineImage(blob, editor, fileNames);
          }
        })
        .catch(function(error) {
          console.error('CKEditor Clipboard Image: Fetch error:', error);
          insertBlobAsInlineImage(blob, editor, fileNames);
        });

      } else {
        // XMLHttpRequest fallback
        console.log('CKEditor Clipboard Image: Using XMLHttpRequest fallback');

        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            console.log('CKEditor Clipboard Image: XHR complete - Status:', xhr.status);

            try {
              var response = JSON.parse(xhr.responseText);
              console.log('CKEditor Clipboard Image: Parsed response:', response);

              if (xhr.status === 200 && response.status === 1) {
                console.log('CKEditor Clipboard Image: Server upload successful', response);

                var titleAttr = response.title_attribute || '';
                var imgHtml = '';

                if (titleAttr) {
                  imgHtml = '<img src="' + (response.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" title="' + titleAttr + '" />';
                } else {
                  imgHtml = '<img src="' + (response.url || URL.createObjectURL(blob)) + '" alt="CKEditor clipboard image" />';
                }

                editor.insertHtml(imgHtml);
                console.log('CKEditor Clipboard Image: Image inserted successfully');
              } else {
                console.error('CKEditor Clipboard Image: Server upload failed', response);
                insertBlobAsInlineImage(blob, editor, fileNames);
              }
            } catch (e) {
              console.error('CKEditor Clipboard Image: Error parsing server response', e);
              insertBlobAsInlineImage(blob, editor, fileNames);
            }
          }
        };

        xhr.onerror = function() {
          console.error('CKEditor Clipboard Image: XHR network error');
          insertBlobAsInlineImage(blob, editor, fileNames);
        };

        xhr.ontimeout = function() {
          console.error('CKEditor Clipboard Image: XHR timeout');
          insertBlobAsInlineImage(blob, editor, fileNames);
        };

        try {
          xhr.open('POST', uploadUrl, true);
          xhr.timeout = 30000;
          xhr.send(formData);
        } catch (e) {
          console.error('CKEditor Clipboard Image: Exception sending XHR request', e);
          insertBlobAsInlineImage(blob, editor, fileNames);
        }
      }
    }
  }
});