$(document).on('appReady appUpdate', function(e, lang) {

    $.getJSON(appUrl + '/module/machine/report/' + serialNumber, function(data) {
        // Set properties based on class
        $.each(data, function(prop, val) {
            $('.machine-'+prop).text(val);
        });

        // Convert computer_model to link to everymac.com
        var mmodel = $('.machine-machine_model').text();
        $('.machine-machine_model')
            .html($('<a>', {
                target: '_blank',
                href: 'https://www.everymac.com/ultimate-mac-lookup/?search_keywords=' + mmodel,
                title: 'Open this model on everymac.com',
                class: 'btn btn-default btn-sm',
                text: mmodel
            }));

        // Handle machine description and Mactracker linking
        var mdesc = $('.machine-machine_desc:first').text().trim();
        var displayText = mdesc.length > 38 ? mdesc.slice(0, 37) + '…' : mdesc;

        // Cache Mactracker data to avoid repeated fetches
        if (!window.mactracker_data) {
            // Get data from server
            $.getJSON(appUrl + '/module/machine/get_mactracker_data', function(response) {
                if (response.error) {
                    console.error('Error loading Mactracker data:', response.error);
                    return;
                }

                // Create lookup map for faster access
                window.mactracker_data = {};
                // Process the YAML data
                for (const [modelName, data] of Object.entries(response.data)) {
                    if (data && data.MactrackerUUID) {
                        window.mactracker_data[modelName.trim()] = data.MactrackerUUID.trim();
                    }
                }
                updateMactrackerLink(mdesc);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading Mactracker data:', errorThrown);
            });
        } else {
            updateMactrackerLink(mdesc);
        }

        // Set computer name with tooltip
        $('.mr-computer_name_input')
            .val(data.computer_name)
            .attr('title', data.computer_name)
            .data('placement', 'bottom')
            .tooltip();

        // Format OS Version
        $('.machine-os_version').text(mr.integerToVersion(data.os_version));

        // Format uptime
        if (data.uptime > 0) {
            var uptime = moment((data.timestamp - data.uptime) * 1000);
            $('.machine-uptime').html(
                $('<time>', {
                    title: i18n.t('boot_time') + ': ' + uptime.format('LLLL'),
                    text: uptime.fromNow(true)
                })
            );
        } else {
            $('.machine-uptime').text(i18n.t('unavailable'));
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Error fetching machine data:', errorThrown);
    });
});

function updateMactrackerLink(mdesc) {
    const uuid = window.mactracker_data[mdesc];
    if (uuid) {
        // Store the original machine description text
        var originalText = $('.machine-machine_desc .machine-desc-text').text();
        var colorCircle = $('#device-color-circle').html();
        
        $('.machine-machine_desc').html(
            $('<div>', { 
                class: 'machine-desc-text',
                style: 'position: relative; display: inline-block;'
            }).append(
                $('<a>', {
                    target: '_self',
                    href: 'mactracker://' + uuid,
                    title: 'Open this model in Mactracker',
                    class: 'btn btn-default',
                    style: 'padding-right: 25px;',
                    text: originalText || mdesc
                })
            ).append(
                $('<span>', {
                    id: 'device-color-circle',
                    style: 'position: absolute; top: 50%; right: 5px; transform: translateY(-50%);',
                    html: colorCircle
                })
            )
        );
    }
}

$(document).on('appReady appUpdate', function(e, lang) {
    // Get reportdata
    $.getJSON( appUrl + '/module/reportdata/report/' + serialNumber, function( data ) {

        // Set properties based on class
        $.each(data, function(prop, val){
            $('.reportdata-'+prop).text(val);
        });
        
        // Registration date
        var msecs = moment(data.reg_timestamp * 1000);
        $('.reportdata-reg_date').html('<time title="'+msecs.format('LLLL')+'" >'+msecs.fromNow()+'</time>');

        // Check-in date
        var msecs = moment(data.timestamp * 1000);
        $('.reportdata-check-in_date').html('<time title="'+msecs.format('LLLL')+'" >'+msecs.fromNow()+'</time>');

        // Remote IP
        $('.reportdata-remote_ip').text(data.remote_ip);

        // Get machinegroup name
        $.getJSON(appUrl + '/unit/get_machine_groups', function( data ){
            var machine_group = parseInt($('.machine-machine_group').text())
            var name = data.find(x => parseInt(x.groupid) === machine_group).name;
            $('.machine-machine_group').text(name)
        })

        // Status
        var machineStatus = { '0': 'in_use', '1': 'archived'};
        $('.reportdata-archive_status').text(
            i18n.t('machine.status.' + machineStatus[$('.reportdata-archive_status').text()])
        );

    });
});
