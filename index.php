<?php

require './src/Weather.php';
require './src/Exceptions/Exception.php';
require './src/Exceptions/InvalidArgumentException.php';


$w = new \Jero\Weather\Weather('f46c94283419ed0dbb4e7e2f6bc4041b');

$cityInfo = $w->getWeather('深圳');

var_dump($cityInfo);