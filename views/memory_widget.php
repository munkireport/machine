<?php 

$this->viewWidget(
[
	"type" => "bargraph",
	"widget_id" => "memory-widget",
	"api_url" => "/module/machine/get_memory_stats",
	"i18n_title" => 'machine.memory.title',
	"icon" => "fa-lightbulb-o",
	"listing_link" => "/show/listing/machine/hardware",
	"label_modifier" => "label + ' GB'",
	"search_component" => "encodeURIComponent('memory = ') + parseInt(label) + 'GB'",
	"margin" => "{top: 20, right: 10, bottom: 20, left: 70}",
]);
