<?php
use \S9\Cf;

require_once APP_ROOT.'/setup/myapp/mytmpl_plugins.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

Cf::s('myapp.web_setting', array(
	'group'=>array(
		'default'=>array(
			'default_path'=>'/top/index',
			'page'=>array('controls'=>'top', 'to'=>'/page/show'),
			'error'=>array('default'=>array('class'=>'view', 'path'=>'/error/error', 'frame'=>'main')),
		),
	),

	'siteusage'=>array(
		'order'=>array('group'),
		'group'=>array('default')
	),
	'mytmpl'=>array(
		'namespaces'=>array('\\MyApp\\mytmpl')
	),
));

Cf::s('myapp.mailsetting', array(
	'mail_template_root'=>APP_ROOT.'/views/mail',
	'default_common'=>array(
		'owner_email'=>'your-email',
		'owner_email_full'=>'youe-name<your-email>'
	)
));


Cf::s('db.date_format', '%Y-%m-%d %H:%i:%s');

