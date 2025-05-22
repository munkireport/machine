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
function loadMachineData() {
    $.getJSON(appUrl + '/module/machine/report/' + serialNumber, function (data) {
        let mac_name = data.machine_name ? data.machine_name.replace(/ /g, '') : '';
        const mac_model = data.machine_model || '';

        // Override mac_name if mac_model is iMacPro1,1
        if (mac_model === "iMacPro1,1") {
            mac_name = "iMac";
        }

        console.log("mac_name:", mac_name);
        console.log("mac_model:", mac_model);

        $('.mac_name').text(mac_name);
        $('.mac_model').text(mac_model);

        // Construct base image URL
        let imageUrl = "https://statici.icloud.com/fmipmobile/deviceImages-9.0/" +
            encodeURIComponent(mac_name) + "/" +
            encodeURIComponent(mac_model) +
            "/online-infobox__2x.png";

        $('#apple_mac_icon').attr('src', imageUrl);
    });
}

$(document).ready(function () {
    loadMachineData();

    $('.machine-refresh-desc').on('click', function (e) {
        e.preventDefault();
        loadMachineData();
    });
});
</script>
