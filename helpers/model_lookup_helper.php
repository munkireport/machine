<?php

use munkireport\lib\Request;

function machine_model_lookup($serial)
{
    // VMs have mixed case serials sometime
    if (strtoupper($serial) != $serial) {    
        return "Virtual Machine";
    }

    $options = [
        'query' => [
            'page' => 'categorydata',
            'serialnumber' => $serial
        ]
    ];

    $client = new Request();
    $result = $client->get('https://km.support.apple.com/kb/index', $options);

    if ( ! $result) {
        return 'model_lookup_failed';
    }

    try {
        $categorydata = json_decode($result);
        if(isset($categorydata->name)){
            return $categorydata->name;
        }
        else{
            return 'unknown_model';
        }
    } catch (Exception $e) {
        return 'model_lookup_failed';
    }

}
