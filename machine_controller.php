<?php

use Exception;

/**
 * Machine module class
 *
 * @package munkireport
 * @author
 **/
class Machine_controller extends Module_controller
{
    protected $view_path;
    protected $module_path;

    /*** Protect methods with auth! ****/
    public function __construct()
    {
        if (! $this->authorized()) {
            die('Authenticate first.'); // Todo: return json?
        }

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
        echo "You've loaded the hardware module!";
    }

    /**
     * Admin page for machine module
     *
     * @author 
     **/
    public function admin()
    {
        $obj = new View();
        $obj->view('machine_admin', [], $this->view_path);
    }

    /**
     * Get cache information for the admin page
     *
     * @return void
     * @author 
     **/
    public function get_cache_info()
    {
        // Check if user is authorized
        if (!$this->authorized()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        // Get cache status from config - first try env directly, then fall back to conf
        $cache_enabled = env('IMAGE_CACHE', null);
        
        // Convert string values to boolean if needed
        if ($cache_enabled === 'true' || $cache_enabled === '1') {
            $cache_enabled = true;
        } elseif ($cache_enabled === 'false' || $cache_enabled === '0') {
            $cache_enabled = false;
        }
        
        // If not set in env, use conf
        if ($cache_enabled === null) {
            $cache_enabled = conf('image_cache', false);
        }

        // Set up cache directory path
        if (defined('PUBLIC_ROOT')) {
            $cache_dir = PUBLIC_ROOT . '/apple_img_cache/';
        } elseif (defined('APP_ROOT')) {
            $cache_dir = APP_ROOT . '/public/apple_img_cache/';
        } else {
            $cache_dir = '';
        }

        // Initialize response
        $response = [
            'enabled' => $cache_enabled,
            'file_count' => 0,
            'cache_size' => '0 B'
        ];

        // If cache directory exists, count files and calculate size
        if ($cache_dir && file_exists($cache_dir)) {
            $file_count = 0;
            $total_size = 0;

            // Recursive function to count files and calculate size
            $count_files = function($dir) use (&$file_count, &$total_size, &$count_files) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $count_files($path);
                    } else {
                        $file_count++;
                        $total_size += filesize($path);
                    }
                }
            };

            // Count files and calculate size
            $count_files($cache_dir);

            // Format size
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $size = $total_size;
            $unit_index = 0;
            
            while ($size > 1024 && $unit_index < count($units) - 1) {
                $size /= 1024;
                $unit_index++;
            }
            
            $formatted_size = round($size, 2) . ' ' . $units[$unit_index];

            // Update response
            $response['file_count'] = $file_count;
            $response['cache_size'] = $formatted_size;
        }

        // Return response as JSON
        jsonView($response);
    }

    /**
     * Purge the apple_img_cache directory
     *
     * @return void
     * @author 
     **/
    public function purge_cache()
    {
        // Check if user is authorized
        if (!$this->authorized()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        // Set up cache directory path
        if (defined('PUBLIC_ROOT')) {
            $cache_dir = PUBLIC_ROOT . '/apple_img_cache/';
        } elseif (defined('APP_ROOT')) {
            $cache_dir = APP_ROOT . '/public/apple_img_cache/';
        } else {
            jsonView(['success' => false, 'error' => 'Cache directory not found']);
            return;
        }

        // Check if cache directory exists
        if (!file_exists($cache_dir)) {
            jsonView(['success' => true, 'message' => 'Cache directory does not exist']);
            return;
        }

        // Recursive function to delete files and directories
        $delete_files = function($dir) use (&$delete_files) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $delete_files($path);
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        };

        try {
            // Delete all files in the cache directory
            $delete_files($cache_dir);
            
            // Return success
            jsonView(['success' => true, 'message' => 'Cache purged successfully']);
        } catch (Exception $e) {
            // Return error
            jsonView(['success' => false, 'error' => $e->getMessage()]);
        }
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
        require_once(__DIR__ . '/helpers/model_lookup_helper.php');
        $out = ['error' => '', 'model' => ''];
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $serial_number)
                ->firstOrFail();
            $machine->machine_desc = machine_model_lookup($serial_number);
            $machine->save();
            $out['model'] = $machine->machine_desc;
        } catch (\Throwable $th) {
            // Record does not exist
            $out['error'] = 'lookup_failed';
        }
        $obj = new View();
        $obj->view('json', [
            'msg' => $out
        ]);

    }

    /**
     * Run machine lookup at Apple
     *
     **/
    public function get_model_icon($serial_number)
    {
        require_once(__DIR__ . '/helpers/model_lookup_helper.php');
        $out = ['error' => '', 'url' => ''];
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $serial_number)
                ->firstOrFail();
            $machine->img_url = machine_icon_lookup($serial_number);
            $machine->save();
            $out['url'] = $machine->img_url;
        } catch (\Throwable $th) {
            // Record does not exist
            $out['error'] = 'lookup_failed';
        }
        $obj = new View();
        $obj->view('json', [
            'msg' => $out
        ]);

    }

    /**
     * Save image to cache
     *
     * @return void
     * @author 
     **/
    public function save_image_to_cache()
    {
        try {
            if (!$this->authorized()) {
                http_response_code(401);
                die(json_encode(['success' => false, 'error' => 'Unauthorized']));
            }

            $image_url = $_POST['image_url'] ?? '';
            $cache_path = $_POST['cache_path'] ?? '';

            // Validate inputs
            if (empty($image_url) || empty($cache_path)) {
                throw new Exception("Missing required parameters");
            }

            // Validate cache_path to prevent directory traversal
            if (!preg_match('/^[A-Za-z0-9]+(?:Pro|Air|mini|Studio)?\/[A-Za-z0-9,]+\-[a-z]+\/[a-zA-Z0-9_\-\.]+\.png$/', $cache_path) || strpos($cache_path, '..') !== false) {
                throw new Exception("Invalid cache path format: " . $cache_path);
            }

            // Set up paths
            if (defined('PUBLIC_ROOT')) {
                $base_cache_dir = PUBLIC_ROOT . '/apple_img_cache/';
            } elseif (defined('APP_ROOT')) {
                $base_cache_dir = APP_ROOT . '/public/apple_img_cache/';
            } else {
                throw new Exception("Application configuration error: ROOT paths not defined");
            }

            $full_cache_path = $base_cache_dir . $cache_path;
            $temp_path = $full_cache_path . '.tmp';

            // Create cache directory structure if it doesn't exist
            $dir = dirname($full_cache_path);
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create cache directory");
                }
            }

            // Download image with proper error handling
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 MunkiReport/6.0',
                    'ignore_errors' => true
                ]
            ]);

            $image_data = @file_get_contents($image_url, false, $context);
            if ($image_data === false) {
                throw new Exception("Failed to download image");
            }

            // Validate image data
            if (!$this->is_valid_image($image_data)) {
                throw new Exception("Invalid or corrupt image data");
            }

            // Write to temporary file first (atomic write)
            if (file_put_contents($temp_path, $image_data) === false) {
                throw new Exception("Failed to write temporary file");
            }

            // Set proper permissions
            if (!chmod($temp_path, 0644)) {
                unlink($temp_path);
                throw new Exception("Failed to set file permissions");
            }

            // Atomic rename
            if (!rename($temp_path, $full_cache_path)) {
                unlink($temp_path);
                throw new Exception("Failed to move file to final location");
            }

            echo json_encode([
                'success' => true,
                'path' => $cache_path
            ]);

        } catch (Exception $e) {
            // Clean up temporary file if it exists
            if (isset($temp_path) && file_exists($temp_path)) {
                @unlink($temp_path);
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate that the data is actually an image
     *
     * @param string $data Image data
     * @return boolean
     * @author 
     **/
    private function is_valid_image($data)
    {
        if (empty($data)) {
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'img_validate_');
        if (!$tmp) {
            return false;
        }

        try {
            file_put_contents($tmp, $data);
            $info = @getimagesize($tmp);
            unlink($tmp);
            return $info && $info[2] === IMAGETYPE_PNG;
        } catch (Exception $e) {
            @unlink($tmp);
            return false;
        }
    }

    /**
     * Get module configuration
     *
     * @return void
     **/
    public function get_config()
    {
        $obj = new View();
        
        if (! $this->authorized()) {
            $obj->view('json', array('msg' => 'Not authorized'));
            return;
        }

        $obj->view('json', array('msg' => array(
            'image_cache' => env('IMAGE_CACHE', false)
        )));
    }
} // END class Machine_controller
