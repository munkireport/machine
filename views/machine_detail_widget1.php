<div class="col-lg-4">
    <div class="row machine-info_1">
        <div class="col-xs-6" style="padding-bottom: 8px;">
            <img id="apple_hardware_icon" class="img-responsive" style="filter: drop-shadow(2px 4px 5px rgba(0, 0, 0, 0.3));">
        </div>
        <div class="col-xs-6" style="font-size: 1.4em; overflow: hidden">
            <span class="label label-info">macOS <span class="machine-os_version"></span></span><br>
            <span class="label label-info"><span class="machine-physical_memory"></span> GB</span><br>
            <span class="label label-info"><span class="machine-serial_number"></span></span><br>
            <span class="label label-info"><span class="reportdata-remote_ip"></span></span><br>
        </div>
    </div>
    <span class="machine-machine_desc" style="position: relative;">
      <span class="machine-desc-text"></span>
      <span id="device-color-circle" style="position: absolute; top: 50%; right: 5px; transform: translateY(-50%);"></span>
    </span>
    <a class="machine-refresh-desc" href="" style="margin-left: 4px;"><i class="fa fa-refresh"></i></a>
</div>

<script>
    // Track if we've already loaded an image
    var imageLoaded = false;
    var machineDataCache = null;
    var cachingEnabled = false; // Default to caching disabled
    
    // Function to check if an image exists and is valid
    function checkImageExists(url) {
        return new Promise((resolve, reject) => {
            var img = new Image();
            var timer = setTimeout(() => {
                img.src = ''; // Prevents memory leaks
                reject(new Error('Timeout'));
            }, 5000); // 5 second timeout

            img.onload = function() {
                clearTimeout(timer);
                resolve(true);
            };

            img.onerror = function() {
                clearTimeout(timer);
                reject(new Error('Failed to load image'));
            };

            img.src = url;
        });
    }

    // Function to load fallback image - only called when needed
    function loadFallbackImage() {
        if (imageLoaded) return; // Don't override if we already have an image
        
        $.getJSON(appUrl + '/module/machine/get_model_icon/' + serialNumber, function(data) {
            if (data && data.url) {
                $('#apple_hardware_icon').attr('src', data.url);
                imageLoaded = true;
            }
        });
    }

    // Check if image caching is enabled
    async function checkCachingEnabled() {
        try {
            const response = await $.ajax({
                url: `${appUrl}/module/machine/get_config`,
                type: 'GET',
                cache: false
            });
            
            // Extract the image_cache value from the response
            let cacheValue = null;
            
            if (response.msg && response.msg.image_cache !== undefined) {
                cacheValue = response.msg.image_cache;
            } else if (response.image_cache !== undefined) {
                cacheValue = response.image_cache;
            } else {
                cacheValue = false;
            }
            
            // Convert to boolean based on the value
            if (cacheValue === true || 
                cacheValue === 1 || 
                cacheValue === "1" || 
                cacheValue === "true" || 
                cacheValue === "TRUE" || 
                cacheValue === "yes" || 
                cacheValue === "YES" || 
                cacheValue === "on" || 
                cacheValue === "ON") {
                cachingEnabled = true;
            } else {
                cachingEnabled = false;
            }
            
            return cachingEnabled;
        } catch (e) {
            // Default to false if error (to be safe)
            cachingEnabled = false;
            return false;
        }
    }

    // Function to handle device image loading and caching
    async function handleDeviceImage(machineData, deviceColor) {
        if (!machineData || !machineData.machine_desc || !machineData.machine_model || !deviceColor) {
            loadFallbackImage();
            return;
        }

        const machine_desc = machineData.machine_desc;
        const machine_model = machineData.machine_model;
        let color_url = deviceColor.replaceAll(' ', '').toLowerCase();

        // Apply color circle if createDeviceColorCircle is available
        if (window.createDeviceColorCircle) {
            const colorCircleHtml = window.createDeviceColorCircle(deviceColor);
            $('#device-color-circle').html(colorCircleHtml);
        }

        // Special case handling
        // 13" 2022 Macbook Air Starlight color doesn't work :(
        if (color_url === "starlight" && machine_model === "Mac14,2") {
            color_url = "silver";
        }

        // Pink color should use red image
        if (color_url === "pink") {
            color_url = "red";
        }

        // Exclude some Macs because they only have one color or the iBridge doesn't properly report the color
        if (machine_desc.indexOf("iMac Pro") !== -1 || 
            machine_model === "iMac20,1" || 
            machine_model === "iMac20,2") {
            loadFallbackImage();
            return;
        }

        // Reset the image loaded flag since we're attempting to load a color-specific image
        imageLoaded = false;

        // Construct paths - use encoded model only for loading images
        const modelName = machine_desc.split(' (')[0].replaceAll(' ', '');
        const encodedModel = machine_model.replace(',', '%2C');
        const appleImageUrl = `https://statici.icloud.com/fmipmobile/deviceImages-9.0/${modelName}/${machine_model}-${color_url}/online-infobox__2x.png`;

        // Check if caching is enabled
        const isCachingEnabled = await checkCachingEnabled();
        
        // If caching is enabled, try cached image first
        if (isCachingEnabled === true) {
            const cachePath = `${modelName}/${machine_model}-${color_url}/online-infobox__2x.png`;
            const cachedImageUrl = `${appUrl}/apple_img_cache/${modelName}/${encodedModel}-${color_url}/online-infobox__2x.png`;
            
            try {
                await checkImageExists(cachedImageUrl);
                $('#apple_hardware_icon').attr('src', cachedImageUrl);
                imageLoaded = true;
                return;
            } catch (e) {
                // Cache miss, continue to fetch from Apple
            }
            
            // Try Apple's image
            try {
                await checkImageExists(appleImageUrl);
                $('#apple_hardware_icon').attr('src', appleImageUrl);
                imageLoaded = true;

                // Save to cache in background (only when caching is enabled)
                $.ajax({
                    url: `${appUrl}/module/machine/save_image_to_cache`,
                    type: 'POST',
                    data: {
                        image_url: appleImageUrl,
                        cache_path: cachePath
                    }
                });
                
                return;
            } catch (e) {
                loadFallbackImage();
            }
        } else {
            // Caching is disabled, load directly from Apple (default behavior)
            try {
                await checkImageExists(appleImageUrl);
                $('#apple_hardware_icon').attr('src', appleImageUrl);
                imageLoaded = true;
            } catch (e) {
                loadFallbackImage();
            }
        }
    }

    // Process device color when it becomes available
    function processDeviceColor() {
        if (!machineDataCache) return;
        
        // Get device color from ibridge data
        let deviceColor = null;
        if (window.ibridge_data && window.ibridge_data.length > 0) {
            for (let i = 0; i < window.ibridge_data.length; i++) {
                if (window.ibridge_data[i].device_color) {
                    deviceColor = window.ibridge_data[i].device_color;
                    break;
                }
            }
        }
        
        if (deviceColor) {
            // If we have a device color, try to load the color-specific image
            handleDeviceImage(machineDataCache, deviceColor);
        } else {
            // If no device color is available, load the fallback image
            loadFallbackImage();
        }
    }

    // Initialize image loading when document is ready
    $(document).ready(function() {
        // Load machine data
        $.getJSON(appUrl + '/module/machine/report/' + serialNumber, function(machineData) {
            // Cache machine data for later use
            machineDataCache = machineData;
            
            // If ibridge data is already available, process it immediately
            if (window.ibridge_data !== undefined) {
                processDeviceColor();
            }
            // Otherwise, we'll wait for the ibridge_data_ready event
            // No need to load fallback image preemptively
        }).fail(function() {
            // Only load fallback image if machine data request fails
            loadFallbackImage();
        });
    });
    
    // Listen for ibridge data ready event
    $(document).on('ibridge_data_ready', function() {
        // Process device color when ibridge data becomes available
        processDeviceColor();
    });

    // ------------------------------------ Refresh machine description
    $('.machine-refresh-desc')
        .attr('href', appUrl + '/module/machine/model_lookup/' + serialNumber)
        .click(function(e) {
            e.preventDefault();
            // show that we're doing a lookup
            $('.machine-desc-text').text(i18n.t('loading'));

            $.getJSON(appUrl + '/module/machine/model_lookup/' + serialNumber, function(data) {
                if (data['error'] == '') {
                    $('.machine-desc-text').text(data['model']);

                    // Update the color circle content only if deviceColor is available
                    let deviceColor = null;
                    if (window.ibridge_data && window.ibridge_data.length > 0) {
                        for (let i = 0; i < window.ibridge_data.length; i++) {
                            if (window.ibridge_data[i].device_color) {
                                deviceColor = window.ibridge_data[i].device_color;
                                break;
                            }
                        }
                    }
                    
                    if (deviceColor && window.createDeviceColorCircle) {
                        const colorCircleHtml = window.createDeviceColorCircle(deviceColor);
                        $('#device-color-circle').html(colorCircleHtml);
                    }
                } else {
                    $('.machine-desc-text').text(data['error']);
                }
            });
        });
</script>