<div class="col-lg-4">
    <div class="row">
        <div class="col-xs-6">
            <img id="apple_mac_icon" class="img-responsive" alt="Mac Image">
        </div>
        <div class="col-xs-6" style="font-size: 1.4em; overflow: hidden">
            <span class="label label-info">macOS <span class="machine-os_version"></span></span><br>
            <span class="label label-info"><span class="machine-physical_memory"></span> GB</span><br>
            <span class="label label-info"><span class="machine-serial_number"></span></span><br>
            <span class="label label-info"><span class="reportdata-remote_ip"></span></span><br>
        </div>
    </div>
    <span class="machine-machine_desc"></span>
    <a class="machine-refresh-desc" href="#"><i class="fa fa-refresh"></i></a>
</div>

<script>
let mac_color = ''; // Shared variable across both functions

$(document).ready(function () {

    $.getJSON(appUrl + '/module/ibridge/get_data/' + serialNumber)
    .done(function (data) {
		if (Array.isArray(data) && data.length > 0 && data[0] && data[0].device_color) {
    		mac_color = data[0].device_color.toLowerCase().replace(/\s+/g, '');
    		console.log("mac_color:", mac_color);
    		$('.mac_color').text(mac_color);
		} else {
    		console.warn("iBridge data is empty or invalid:", data);
    		mac_color = "";
    	$('.mac_color').text('');
		}        
	loadMachineData();
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to fetch iBridge data:", textStatus, errorThrown);
        mac_color = "";
        $('.mac_color').text('');
        loadMachineData();
    });
    
    
function loadMachineData() {
    $.getJSON(appUrl + '/module/machine/report/' + serialNumber, function (data) {
        let mac_name = data.machine_name ? data.machine_name.replace(/ /g, '') : '';
        const mac_model = data.machine_model || '';

        // Override mac_name if mac_model is iMacPro1,1
        if (mac_model === "iMacPro1,1") {
            mac_name = "iMac";
        }

        // Override for 13" 2022 Macbook Air Starlight color doesn't work
        if (mac_color == "starlight" && mac_model == "Mac14,2"){
           mac_color = "silver"
        }

        console.log("mac_name:", mac_name);
        console.log("mac_model:", mac_model);
        console.log("mac_color (used in URL):", mac_color);

        $('.mac_name').text(mac_name);
        $('.mac_model').text(mac_model);

        // Construct base image URL
        let imageUrl = "https://statici.icloud.com/fmipmobile/deviceImages-9.0/" +
            encodeURIComponent(mac_name) + "/" +
            encodeURIComponent(mac_model);

        // Append mac_color if it's valid and model is not iMacPro1,1
        if (mac_model !== "iMacPro1,1" && mac_color && mac_color.trim()) {
            imageUrl += "-" + encodeURIComponent(mac_color);
        }
        imageUrl += "/online-infobox__2x.png";

        $('#apple_mac_icon').attr('src', imageUrl);

        // Update alt attribute dynamically
        const altText = `Image of ${mac_name} model ${mac_model} color ${mac_color}`;
        $('#apple_mac_icon').attr('alt', altText);
    });
}

    // Only call loadIbridgeData initially; it will call loadMachineData after mac_color is set
    loadIbridgeData();

    $('.machine-refresh-desc').on('click', function (e) {
        e.preventDefault();
        loadIbridgeData(); // Same logic for refresh
    });
});
</script>
