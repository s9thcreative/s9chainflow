<?php

use S9\Cf;

Cf::s('paramcheck.target', array(
	'form'=>array(
		'name','email','password','password_conf'
	)
));

Cf::s('paramcheck.logic', array(
	'form.name'=>array('label'=>'名前', 'required'=>true, 'max'=>10),
	'form.email'=>array('label'=>'メールアドレス', 'required'=>true, 'max'=>200, 'logic'=>'email'),
	'form.password'=>array('label'=>'パスワード', 'required'=>true, 'min'=>8, 'max'=>32, 'logic'=>'password', 'logic_target'=>'custom'),
	'form.password_conf'=>array('label'=>'パスワード確認', 'required'=>true, 'logic'=>'password_conf', 'logic_target'=>'custom'),
));

Cf::s('paramcheck.messages', array(
	'required'=>'は必須です',
	'min'=>'は %d文字以上です',
	'max'=>'は %d文字以内です',
	'type'=>'の形式が違います',
	'exists'=>'は既に存在します',
	'password'=>'は半角文字のみです',
	'password_conf'=>'が異なります',
));