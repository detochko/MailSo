<?php

	include '../lib/MailSo/MailSo.php';

	echo '<pre>';
	$oLogger = \MailSo\Log\Logger::SingletonInstance()
		->Add(\MailSo\Log\Drivers\Inline::NewInstance("\r\n", true))
	;

	$sEmails = 'User Name1 <username1@domain.com>, User D\'Name2 <username2@domain.com>, "User Name3" <username3@domain.com>';
	$oData = \MailSo\Mime\EmailCollection::NewInstance($sEmails);

	$oLogger->WriteDump($oData);
