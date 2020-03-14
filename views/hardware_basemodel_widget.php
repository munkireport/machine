<?php 

$this->viewWidget(
[
	"type" => "bargraph",
	"widget_id" => "hardware-basemodel-widget",
	"api_url" => "/module/machine/get_model_stats/summary",
	"i18n_title" => 'machine.base_model_widget_title',
	"icon" => "fa-laptop",
	"listing_link" => "/show/listing/machine/hardware",
    "margin" => "{top: 20, right: 10, bottom: 20, left: 150}",
]);
