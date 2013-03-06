<?php

$oPhar = new \Phar('mailso.phar', 0, 'mailso.phar');
$oPhar->buildFromDirectory(dirname(__FILE__).'/../lib/MailSo');
$oPhar->setStub('<?php

try
{
	define(\'MAILSO_LIBRARY_USE_PHAR\', true);
	require \'phar://mailso.phar/MailSo.php\';
}
catch (Exception $e)
{
	echo $e->getMessage();
	die("\r\n".\'Cannot initialize Phar\');
}

__halt_compiler();');
