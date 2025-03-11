<?php $this->view('partials/head'); ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h3>&nbsp;&nbsp;<span data-i18n="machine.admin.title"></span>&nbsp;&nbsp;</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
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
    
    // Handle purge cache button
    $('#purge_cache').click(function() {
        if (confirm(i18n.t('machine.admin.confirm_purge'))) {
            // Disable button during purge
            $(this).prop('disabled', true);
            
            // Show loading message
            $('#purge_status').text(i18n.t('machine.admin.purging')).show();
            
            // Call purge endpoint
            $.getJSON(appUrl + '/module/machine/purge_cache', function(data) {
                if (data && data.success) {
                    // Update status message
                    $('#purge_status').text(i18n.t('machine.admin.purge_success'));
                    
                    // Update cache information
                    $('#cache_file_count').text('0');
                    $('#cache_size').text('0 B');
                    
                    // Re-enable button after a delay
                    setTimeout(function() {
                        $('#purge_cache').prop('disabled', false);
                        $('#purge_status').fadeOut();
                    }, 3000);
                } else {
                    // Show error
                    $('#purge_status').removeClass('text-success').addClass('text-danger').text(i18n.t('machine.admin.purge_failed'));
                    
                    // Re-enable button
                    $('#purge_cache').prop('disabled', false);
                }
            }).fail(function() {
                // Show error on AJAX failure
                $('#purge_status').removeClass('text-success').addClass('text-danger').text(i18n.t('machine.admin.purge_failed'));
                
                // Re-enable button
                $('#purge_cache').prop('disabled', false);
            });
        }
    });
});
</script>

<?php $this->view('partials/foot'); ?> 