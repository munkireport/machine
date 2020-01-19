<?php

use munkireport\lib\Request;

function model_description_lookup($serial)
{
    if (strpos($serial, 'VMWV') === 0) {
        return 'VMware virtual machine';
    }

    $options = [
        'query' => [
            'page' => 'categorydata',
            'serialnumber' => $serial
        ]
    ];

    $client = new Request();
    $result = $client->get('http://km.support.apple.com/kb/index', $options);

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