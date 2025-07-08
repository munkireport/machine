<?php

use CFPropertyList\CFPropertyList;
use munkireport\processors\Processor;
use munkireport\lib\Request;


class Machine_processor extends Processor
{
    /**
     * Process data sent by postflight
     *
     * @param string data
     * @author abn290
     **/
    public function run($plist)
    {
        $parser = new CFPropertyList();
        $parser->parse($plist, CFPropertyList::FORMAT_XML);
        $mylist = $parser->toArray();

        $mylist['serial_number'] = $this->serial_number;

        // Set default computer_name
        if (! isset($mylist['computer_name']) or trim($mylist['computer_name']) == '') {
            $mylist['computer_name'] = 'No name';
        }

        // Fix Apple Silicon processor count - processor count is the first number
        if (isset($mylist['number_processors'])) {
            $mylist['number_processors'] = preg_replace('/^[^0-9]*(\d+).*/', '$1', $mylist['number_processors']);
        }

        // Convert memory string (4 GB) to int
        if (isset($mylist['physical_memory'])) {
            $mylist['physical_memory'] = intval($mylist['physical_memory']);
        }

        // Convert OS version to int
        if (isset($mylist['os_version'])) {
            $digits = explode('.', $mylist['os_version']);
            $mult = 10000;
            $mylist['os_version'] = 0;
            foreach ($digits as $digit) {
                $mylist['os_version'] += $digit * $mult;
                $mult = $mult / 100;
            }
        }

        // Dirify buildversion
        if (isset($mylist['buildversion'])) {
            $mylist['buildversion'] = preg_replace('/[^A-Za-z0-9]/', '', $mylist['buildversion']);
        }

        // Retrieve machine record (if existing)
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $this->serial_number)
                ->firstOrFail();
        } catch (\Throwable $th) {
            $machine = new Machine_model();
        }

        // Check if we need to retrieve model from Apple, but only for Intel Macs
        if ($this->should_run_model_description_lookup($machine) && $mylist['cpu_arch'] !== "arm64"){
            $mylist['machine_desc'] = $this->model_lookup($this->serial_number);
        } else if ($this->should_run_model_description_lookup($machine) && $mylist['cpu_arch'] == "arm64"){
            // Otherwise return a generic model ID for Apple Silicon Macs to make sure the device icon doesn't break
            $mylist['machine_desc'] = $mylist['machine_name'];
        }

        $machine->fill($mylist)->save();
    }

    function should_run_model_description_lookup($machine)
    {
        return ( 
            ! $machine['machine_desc'] or 
            in_array($machine['machine_desc'], ['model_lookup_failed', 'unknown_model'])
        );
    }

    /**
     * Run machine lookup at Apple
     *
     **/
    public function model_lookup($serial_number)
    {
        $out = ['error' => '', 'model' => ''];
        try {
             // VMs have mixed case serials sometime
            if (strtoupper($serial_number) != $serial_number) {
                return "Virtual Machine";

            } else {
                // This method only works for non-randomized serial numbers (mid-2021 and older)
                $client = new Request();
                $options = ['http_errors' => false];
                $result = (string) $client->get("https://support-sp.apple.com/sp/product?cc=".substr($serial_number, -4), $options);

                if ( ! $result || strpos($result, '<configCode>') === false ){
                    return null;

                } else {
                    // Turn the result into an object and save in the database
                    return json_decode(json_encode(simplexml_load_string($result)),1)["configCode"];
                }
            }

        } catch (\Throwable $th) {
            print_r("Error looking up machine description from Apple.");
            return null;
        }
    }
}