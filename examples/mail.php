<?php

	include '../lib/MailSo/MailSo.php';

	echo '<pre>';
	$oLogger = \MailSo\Log\Logger::SingletonInstance()
		->Add(\MailSo\Log\Drivers\Inline::NewInstance("\r\n", true))
	;

	$oData = null;

	try
	{
		$oMailClient = \MailSo\Mail\MailClient::NewInstance()->SetLogger($oLogger);

		$oData = $oMailClient
			->Connect('imap.gmail.com', 993, \MailSo\Net\Enumerations\ConnectionSecurityType::SSL)
			->Login('test@gmail.com', 'test')
			->MessageList('INBOX')
		;

		$oMailClient->LogoutAndDisconnect();
	}
	catch (Exception $e)
	{
		var_dump($e);
	}

	$oLogger->WriteDump($oData);
	$oLogger->WriteDump(\MailSo\Base\Loader::Statistic());
