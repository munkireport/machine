<?php

use munkireport\lib\Request;

/**
 * Machine module class
 *
 * @package munkireport
 * @author
 **/
class Machine_controller extends Module_controller
{

    /*** Protect methods with auth! ****/
    public function __construct()
    {
        // if (! $this->authorized()) {
        //     die('Authenticate first.'); // Todo: return json?
        // }

        // Store module path
        $this->module_path = dirname(__FILE__) .'/';
        $this->view_path = $this->module_path . 'views/';
    }

    /**
     * Default method
     *
     * @author AvB
     **/
    public function index()
    {
        echo "You've loaded the machine module!";
    }

    /**
     * Get duplicate computernames
     *
     *
     **/
    public function get_duplicate_computernames()
    {
        $machine = Machine_model::selectRaw('computer_name, COUNT(*) AS count')
            ->filter()
            ->groupBy('computer_name')
            ->having('count', '>', 1)
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        $obj = new View();
        $obj->view('json', ['msg' => $machine]);
    }

    /**
     * Get model statistics
     *
     **/
    public function get_model_stats($summary="")
    {
        $machine = Machine_model::selectRaw('count(*) AS count, machine_desc AS label')
            ->filter()
            ->groupBy('machine_desc')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        $out = array();
        foreach ($machine as $obj) {
            $obj['label'] = $obj['label'] ? $obj['label'] : 'Unknown';
            $out[] = $obj;
        }

        // Check if we need to convert to summary (Model + screen size)
        if($summary){
            $model_list = array();
            foreach ($out as $key => $obj) {
                // Mac mini Server (Late 2012)
                $suffix = "";
                if(preg_match('/^(.+) \((.+)\)/', $obj['label'], $matches))
                {
                    $name = $matches[1];
                    // Find suffix
                    if(preg_match('/([\d\.]+-inch)/', $matches[2], $matches))
                    {
                        $suffix = ' ('.$matches[1].')';
                    }
                }
                else
                {
                    $name = $obj['label'];

                }
                if(! isset($model_list[$name.$suffix]))
                {
                    $model_list[$name.$suffix] = 0;
                }
                $model_list[$name.$suffix] += $obj['count'];

            }
            // Erase out
            $out = array();
            // Sort model list
            arsort($model_list);
            // Add entries to $out
            foreach ($model_list as $key => $count)
            {
                $out[] = array('label' => $key, 'count' => $count);
            }
        }
        $obj = new View();
        $obj->view('json', ['msg' => $out]);
    }


    /**
     * Get machine data for a particular machine
     *
     * @return void
     * @author
     **/
    public function report($serial_number = '')
    {
        jsonView(
            Machine_model::where('machine.serial_number', $serial_number)
                ->filter('groupOnly')
                ->first()
        );
    }

    /**
     * Return new clients
     *
     * @return void
     * @author
     **/
    public function new_clients()
    {
        $lastweek = time() - 60 * 60 * 24 * 7;
        $out = Machine_model::select('machine.serial_number', 'computer_name', 'reg_timestamp')
            ->where('reg_timestamp', '>', $lastweek)
            ->filter()
            ->orderBy('reg_timestamp', 'desc')
            ->get()
            ->toArray();

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    /**
     * Return json array with memory configuration breakdown
     *
     * @param string $format Format output. Possible values: flotr, none
     * @author AvB
     **/
    public function get_memory_stats($format = 'none')
    {
        $out = array();

        // Legacy loop to do sort in php
        $tmp = array();
        $machine = Machine_model::selectRaw('physical_memory, count(1) as count')
            ->filter()
            ->groupBy('physical_memory')
            ->orderBy('physical_memory', 'desc')
            ->get()
            ->toArray();
        
        foreach ($machine as $obj) {
        // Take care of mixed entries (string or int)
            if (isset($tmp[$obj['physical_memory']])) {
                $tmp[$obj['physical_memory']] += $obj['count'];
            } else {
                $tmp[$obj['physical_memory']] = $obj['count'];
            }
        }

        switch ($format) {
            case 'flotr':
                krsort($tmp);

                $cnt = 0;
                foreach ($tmp as $mem => $memcnt) {
                    $out[] = array('label' => $mem . ' GB', 'data' => array(array(intval($memcnt), $cnt++)));
                }
                break;
            
            case 'button':
                $labels = ['< 8GB' => 0, '8GB +' => 0, '16GB +' => 0];
                foreach ($tmp as $mem => $memcnt) {
                    $memcnt = intval($memcnt);
                    if( $mem < 8 ){ $labels['< 8GB'] += $memcnt;}
                    if( $mem < 16 && $mem <= 8 ){ $labels['8GB +'] += $memcnt;}
                    if( $mem >= 16 ){ $labels['16GB +'] += $memcnt;}
                }

                foreach ($labels as $label => $count) {
                    $out[] = ['label' => $label, 'count' => $count]; 
                }
                break;

            default:
                foreach ($tmp as $mem => $memcnt) {
                    $out[] = array('label' => $mem, 'count' => intval($memcnt));
                }
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    /**
     * Return json array with hardware configuration breakdown
     *
     * @author AvB
     **/
    public function hw()
    {
        $out = [];
        $machine = Machine_model::selectRaw('machine_name, count(1) as count')
            ->filter()
            ->groupBy('machine_name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        foreach ($machine as $obj) {
            $out[] = array('label' => $obj['machine_name'], 'count' => intval($obj['count']));
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    /**
     * Return json array with cpu arch
     *
     * @author tuxudo
     **/
    public function cpu_arch()
    {
        jsonView(
            Machine_model::selectRaw("COUNT(CASE WHEN `cpu_arch` = 'x86_64' THEN 1 END) AS 'Intel'")
                ->selectRaw("COUNT(CASE WHEN `cpu_arch` = 'arm64' THEN 1 END) AS 'Apple Silicon'")
                ->filter()
                ->first()
                ->toLabelCount()
        );
    }

    /**
     * Return json array with os breakdown
     *
     * @author AvB
     **/
    public function os()
    {
        $obj = new View();
        $obj->view('json', [
            'msg' => $this->_trait_stats('os_version')
        ]);
    }
    /**
     * Return json array with os build breakdown
     *
     * @author AkB
     **/
    public function osbuild()
    {
        $obj = new View();
        $obj->view('json', [
            'msg' => $this->_trait_stats('buildversion')
        ]);
    }

    private function _trait_stats($what = 'os_version'){
        $out = [];
        $machine = Machine_model::selectRaw("count(1) as count, $what")
            ->filter()
            ->groupBy($what)
            ->orderBy($what, 'desc')
            ->get()
            ->toArray();

        foreach ($machine as $obj) {
            $obj[$what] = $obj[$what] ? $obj[$what] : '0';
            $out[] = ['label' => $obj[$what], 'count' => intval($obj['count'])];
        }
        return $out;
    }

    /**
     * Run machine lookup at Apple
     *
     **/
    public function model_lookup($serial_number)
    {
        $out = ['error' => '', 'model' => ''];
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $serial_number)
                ->firstOrFail();

             // VMs have mixed case serials sometime
            if (strtoupper($serial_number) != $serial_number) {
                $machine->machine_desc = "Virtual Machine";
                $machine->save();
                $out['model'] = $machine->machine_desc;

            } else {
                // This method only works for non-randomized serial numbers (mid-2021 and older)
                $client = new Request();
                $options = ['http_errors' => false];
                $result = (string) $client->get("https://support-sp.apple.com/sp/product?cc=".substr($serial_number, -4), $options);

                if ( ! $result || strpos($result, '<configCode>') === false ){
                    if ($machine['cpu_arch'] == "arm64"){
                        $out['error'] = 'Unable to lookup Apple Silicon Macs';
                    } else {
                        $out['error'] = 'lookup_failed1';
                    }
                } else {
                    // Turn the result into an object and save in the database
                    $machine->machine_desc = json_decode(json_encode(simplexml_load_string($result)),1)["configCode"];
                    $machine->save();
                    $out['model'] = $machine->machine_desc;
                }
            }

        } catch (\Throwable $th) {
            // Record does not exist
            $out['error'] = 'lookup_failed2';
        }
        $obj = new View();
        $obj->view('json', [
            'msg' => $out
        ]);
    }

    /**
     * Run machine icon lookup at Apple
     *
     **/
    public function get_model_icon($serial_number)
    {
        $out = ['error' => '', 'url' => ''];
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $serial_number)
                ->firstOrFail();

            // Most of the work for this fix was done by @precursorca
            // modified for PHP and added here

            // Remove spaces
            $machine_name = str_replace(" ", "", $machine['machine_name']); 

            // The iMac Pro looks the same as an iMac
            if($machine['machine_model'] == "iMacPro1,1")
            {
                $machine_name = "iMac";
            }

            // VMs have mixed case serials sometime, so default to the basic Apple logo image
            if (strtoupper($serial_number) != $serial_number) {    
                $machine->img_url = ("https://km.support.apple.com/kb/securedImage.jsp?productid=".$serial_number."&size=240x240");
            } else {
                $machine->img_url = ("https://statici.icloud.com/fmipmobile/deviceImages-9.0/".urlencode($machine_name)."/".urlencode($machine['machine_model'])."/online-infobox__2x.png");
            }

            $machine->save();
            $out['url'] = $machine->img_url;
        } catch (\Throwable $th) {
            // Record does not exist
            $out['error'] = 'lookup_failed3';
        }
        $obj = new View();
        $obj->view('json', [
            'msg' => $out
        ]);

    }
} // END class Machine_controller
