<?php
use \S9\Cf;

Cf::merge('myapp.web_setting', array(
	'domain'=>'example.com',
	'issecure'=>false,
));

