<?php 

$this->view('listings/default',
[
	"i18n_title" => 'machine.hardware_report',
	"js_link" => "module/machine/js/machine",
	"table" => [
		[
			"column" => "machine.computer_name",
			"i18n_header" => "listing.computername",
			"formatter" => "clientDetail",
			"tab_link" => "summary",
		],
		[
			"column" => "reportdata.serial_number",
			"i18n_header" => "displays_info.machineserial",
		],
		[
			"column" => "reportdata.long_username",
			"i18n_header" => "username",
		],  
		["i18n_header" => "machine.model", "column" => 'machine.machine_model'],
		["i18n_header" => "description", "column" => 'machine.machine_desc'],
		[
			"i18n_header" => "physical_memory",
			"column" => 'machine.physical_memory',
			"formatter" => "memoryFormatter",
			"filter" => "memoryFilter",
		],
		["i18n_header" => "machine.cores", "column" => 'machine.number_processors'],
		["i18n_header" => "machine.arch", "column" => 'machine.cpu_arch'],
		["i18n_header" => "machine.cpu_speed", "column" => 'machine.current_processor_speed'],
		["i18n_header" => "machine.rom_version", "column" => 'machine.boot_rom_version'],		
	]
]);
