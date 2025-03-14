<?php $this->view('partials/head'); ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h3>&nbsp;&nbsp;<span data-i18n="machine.admin.title"></span>&nbsp;&nbsp;</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><span data-i18n="machine.admin.image_cache_info"></span></h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-condensed">
                        <tbody>
                            <tr>
                                <th data-i18n="machine.admin.cache_status"></th>
                                <td id="cache_status_value">-</td>
                            </tr>
                            <tr>
                                <th data-i18n="machine.admin.cache_file_count"></th>
                                <td id="cache_file_count">-</td>
                            </tr>
                            <tr>
                                <th data-i18n="machine.admin.cache_size"></th>
                                <td id="cache_size">-</td>
                            </tr>
                        </tbody>
                    </table>
                    <button id="purge_cache" class="btn btn-danger" data-i18n="machine.admin.purge_cache"></button>
                    <span id="purge_status" class="text-success" style="margin-left: 10px; display: none;"></span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><span data-i18n="machine.admin.mactracker_cache_info">Mactracker Cache Info</span></h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-condensed">
                        <tbody>
                            <tr>
                                <th data-i18n="machine.admin.mactracker_last_update">Last Update</th>
                                <td id="mactracker_last_update">-</td>
                            </tr>
                            <tr>
                                <th data-i18n="machine.admin.mactracker_source">Data Source</th>
                                <td id="mactracker_source">-</td>
                            </tr>
                            <tr>
                                <th data-i18n="machine.admin.mactracker_model_count">Models in Cache</th>
                                <td id="mactracker_model_count">-</td>
                            </tr>
                        </tbody>
                    </table>
                    <button id="refresh_mactracker" class="btn btn-primary" data-i18n="machine.admin.refresh_mactracker">Refresh Mactracker Data</button>
                    <span id="mactracker_status" class="text-success" style="margin-left: 10px; display: none;"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
    // Get cache information
    $.getJSON(appUrl + '/module/machine/get_cache_info', function(data) {
        if (data) {
            // Update cache status
            $('#cache_status_value').text(data.enabled ? i18n.t('machine.admin.enabled') : i18n.t('machine.admin.disabled'));
            
            // Update file count
            $('#cache_file_count').text(data.file_count);
            
            // Update cache size
            $('#cache_size').text(data.cache_size);
        }
    });
    
    // Get Mactracker cache information
    function updateMactrackerInfo() {
        $.getJSON(appUrl + '/module/machine/get_mactracker_info', function(data) {
            if (data) {
                // Update last update time
                var lastUpdate = data.last_update ? moment(data.last_update * 1000).fromNow() : 'Never';
                $('#mactracker_last_update').text(lastUpdate);
                
                // Update source
                if (data.source == 1) {
                    // GitHub source - hardcoded URL to the GitHub repository page for the file
                    $('#mactracker_source').html('<a href="https://github.com/ofirgalcon/munkireport-mactracker-data/blob/main/mactracker.yml" target="_blank">GitHub</a>');
                } else {
                    // Local file - just text
                    $('#mactracker_source').text('Local File');
                }
                
                // Update model count
                $('#mactracker_model_count').text(data.model_count);
            }
        });
    }
    
    // Initial load
    updateMactrackerInfo();
    
    // Handle refresh Mactracker button
    $('#refresh_mactracker').click(function() {
        // Disable button during refresh
        $(this).prop('disabled', true);
        
        // Step 1: Show refreshing message
        $('#mactracker_status').removeClass('text-danger').addClass('text-success')
            .text(i18n.t('machine.admin.refreshing')).show();
        
        // Step 2: Call refresh endpoint
        $.getJSON(appUrl + '/module/machine/refresh_mactracker')
            .done(function(data) {
                if (data && data.success) {
                    // Step 3: Wait 1.5 seconds to ensure user sees the refreshing message
                    setTimeout(function() {
                        // Step 4: Update cache information
                        updateMactrackerInfo();
                        
                        // Step 5: Show success message and re-enable button
                        $('#mactracker_status').text(i18n.t('machine.admin.refresh_success'));
                        $('#refresh_mactracker').prop('disabled', false);
                        
                    }, 1500); // 1.5 second delay to ensure "refreshing" message is visible
                } else {
                    // Handle error
                    $('#mactracker_status').removeClass('text-success').addClass('text-danger')
                        .text(data.error || i18n.t('machine.admin.refresh_failed'));
                    $('#refresh_mactracker').prop('disabled', false);
                }
            })
            .fail(function() {
                // Handle AJAX failure
                $('#mactracker_status').removeClass('text-success').addClass('text-danger')
                    .text(i18n.t('machine.admin.refresh_failed'));
                $('#refresh_mactracker').prop('disabled', false);
            });
    });
    
    // Handle purge cache button
    $('#purge_cache').click(function() {
        if (confirm(i18n.t('machine.admin.confirm_purge'))) {
            // Disable button during purge
            $(this).prop('disabled', true);
            
            // Step 1: Show purging message
            $('#purge_status').removeClass('text-danger').addClass('text-success')
                .text(i18n.t('machine.admin.purging')).show();
            
            // Step 2: Call purge endpoint
            $.getJSON(appUrl + '/module/machine/purge_cache')
                .done(function(data) {
                    if (data && data.success) {
                        // Step 3: Wait 1.5 seconds to ensure user sees the purging message
                        setTimeout(function() {
                            // Step 4: Update the UI with success message and re-enable button
                            $('#cache_file_count').text('0');
                            $('#cache_size').text('0 B');
                            $('#purge_status').text(i18n.t('machine.admin.purge_success'));
                            $('#purge_cache').prop('disabled', false);
                            
                        }, 1500); // 1.5 second delay to ensure "purging" message is visible
                    } else {
                        // Handle error
                        $('#purge_status').removeClass('text-success').addClass('text-danger')
                            .text(i18n.t('machine.admin.purge_failed'));
                        $('#purge_cache').prop('disabled', false);
                    }
                })
                .fail(function() {
                    // Handle AJAX failure
                    $('#purge_status').removeClass('text-success').addClass('text-danger')
                        .text(i18n.t('machine.admin.purge_failed'));
                    $('#purge_cache').prop('disabled', false);
                });
        }
    });
});
</script>

<?php $this->view('partials/foot'); ?> 