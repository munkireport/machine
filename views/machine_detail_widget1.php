<div class="col-lg-4">
    <div class="row">
        <div class="col-xs-6">
            <img class="img-responsive" src="<?php printf(conf('apple_hardware_icon_url'), substr($serial_number, 8)); ?>" />
        </div>
        <div class="col-xs-6" style="font-size: 1.4em; overflow: hidden">
            <span class="label label-info">macOS <span class="mr-os_version"></span></span><br>
            <span class="label label-info"><span class="mr-physical_memory"></span> GB</span><br>
            <span class="label label-info"><span class="mr-serial_number"></span></span><br>
            <span class="label label-info"><span class="mr-remote_ip"></span></span><br>
        </div>
    </div>
    <span class="mr-machine_desc"></span> <a class="mr-refresh-desc" href=""><i class="fa fa-refresh"></i></a>
</div>