<?php

return [
  /*
  |===============================================
  | Machine Module
  |===============================================
  |
  | By default, machine module will cache device images locally.
  | To disable caching and load images directly from Apple,
  | set `image_cache` to false.
  |
  */

  'image_cache' => env('IMAGE_CACHE', true),
]; 