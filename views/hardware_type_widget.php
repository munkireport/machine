<?php 

$this->viewWidget(
[
	"type" => "bargraph",
	"widget_id" => "hardware-type-widget",
	"api_url" => "/module/machine/hw",
	"i18n_title" => 'machine.hardware_type_title',
	"icon" => "fa-desktop",
	"listing_link" => "/show/listing/machine/hardware",
	"margin" => "{top: 20, right: 10, bottom: 20, left: 90}",
]);
