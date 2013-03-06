<?php

namespace MailSo\Smtp;

/**
 * @category MailSo
 * @package Smtp
 */
class SmtpClient extends \MailSo\Net\NetClient
{
	/**
	 * @var bool
	 */
	private $bIsLoggined;

	/**
	 * @var bool
	 */
	private $bHelo;

	/**
	 * @var bool
	 */
	private $bRcpt;

	/**
	 * @var bool
	 */
	private $bMail;

	/**
	 * @var bool
	 */
	private $bData;

	/**
	 * @var bool
	 */
	private $bIsStartTSLSupported;

	/**
	 * @var array
	 */
	private $aAuthTypes;

	/**
	 * @var int
	 */
	private $iRequestTime;

	/**
	 * @var array
	 */
	private $aResults;

	/**
	 * @access protected
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->bIsLoggined = false;
		$this->bIsStartTSLSupported = false;
		$this->aAuthTypes = array();

		$this->iRequestTime = 0;
		$this->aResults = array();

		$this->bHelo = false;
		$this->bRcpt = false;
		$this->bMail = false;
		$this->bData = false;
	}

	/**
	 * @return \MailSo\Smtp\SmtpClient
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @param string $sServerName
	 * @param int $iPort = 25
	 * @param int $iSecurityType = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\ResponseException
	 */
	public function Connect($sServerName, $iPort = 25,
		$iSecurityType = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT)
	{
		$this->iRequestTime = microtime(true);

		parent::Connect($sServerName, $iPort, $iSecurityType);
		$this->validateResponse(220);

		return $this;
	}

	/**
	 * @param string $sEhloHost
	 */
	private function preLoginStartTLSAndEhloProcess($sEhloHost)
	{
		if ($this->bIsLoggined)
		{
			$this->writeLogException(
				new Exceptions\RuntimeException('Already authenticated for this session'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		if ($this->bHelo)
		{
			$this->writeLogException(
				new Exceptions\RuntimeException('Cannot issue EHLO/HELO to existing session'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->ehloOrHelo($sEhloHost);

		if (\MailSo\Net\Enumerations\ConnectionSecurityType::UseStartTLS(
			$this->bIsStartTSLSupported, $this->iSecurityType))
		{
			$this->sendRequestWithCheck('STARTTLS', 220);
			if (!@stream_socket_enable_crypto($this->rConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			{
				$this->writeLogException(
					new Exceptions\RuntimeException('Cannot enable TLS'),
					\MailSo\Log\Enumerations\Type::WARNING, true);
			}

			$this->ehloOrHelo($sEhloHost);
		}
		else if (\MailSo\Net\Enumerations\ConnectionSecurityType::STARTTLS === $this->iSecurityType)
		{
			$this->writeLogException(
				new \MailSo\Net\Exceptions\SocketUnsuppoterdSecureConnectionException('STARTTLS is not supported'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->bHelo = true;
	}

	/**
	 * @param string $sLogin = ''
	 * @param string $sPassword = ''
	 * @param string $sEhloHost = '127.0.0.1'
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Login($sLogin = '', $sPassword = '', $sEhloHost = '127.0.0.1')
	{
		$this->preLoginStartTLSAndEhloProcess($sEhloHost);

		if ('' !== $sLogin && '' !== $sPassword)
		{
			$sLogin = trim($sLogin);
			$sPassword = $sPassword;

			if (in_array('PLAIN', $this->aAuthTypes))
			{
				try
				{
					$this->sendRequestWithCheck('AUTH', 334, 'PLAIN');
				}
				catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
				{
					$this->writeLogException(
						new \MailSo\Smtp\Exceptions\LoginBadMethodException(
							$oException->GetResponses(), '', 0, $oException),
						\MailSo\Log\Enumerations\Type::NOTICE, true);
				}

				try
				{
					$this->sendRequestWithCheck(base64_encode("\0".$sLogin."\0".$sPassword), 235, '', true);
				}
				catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
				{
					$this->writeLogException(
						new \MailSo\Smtp\Exceptions\LoginBadCredentialsException(
							$oException->GetResponses(), '', 0, $oException),
						\MailSo\Log\Enumerations\Type::NOTICE, true);
				}
			}
			else if (in_array('LOGIN', $this->aAuthTypes))
			{
				try
				{
					$this->sendRequestWithCheck('AUTH', 334, 'LOGIN');
				}
				catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
				{
					$this->writeLogException(
						new \MailSo\Smtp\Exceptions\LoginBadMethodException(
							$oException->GetResponses(), '', 0, $oException),
						\MailSo\Log\Enumerations\Type::NOTICE, true);
				}

				try
				{
					$this->sendRequestWithCheck(base64_encode($sLogin), 334, '');
					$this->sendRequestWithCheck(base64_encode($sPassword), 235, '', true);
				}
				catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
				{
					$this->writeLogException(
						new \MailSo\Smtp\Exceptions\LoginBadCredentialsException(
							$oException->GetResponses(), '', 0, $oException),
						\MailSo\Log\Enumerations\Type::NOTICE, true);
				}
			}
			else
			{
				$this->writeLogException(
					new \MailSo\Smtp\Exceptions\LoginBadMethodException(),
					\MailSo\Log\Enumerations\Type::NOTICE, true);
			}
		}

		$this->bIsLoggined = true;

		return $this;
	}

	/**
	 * @param string $sXOAuth2Token
	 * @param string $sEhloHost = '127.0.0.1'
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function LoginWithXOauth2($sXOAuth2Token, $sEhloHost = '127.0.0.1')
	{
		$this->preLoginStartTLSAndEhloProcess($sEhloHost);

		if (in_array('XOAUTH2', $this->aAuthTypes))
		{
			try
			{
				$this->sendRequestWithCheck('AUTH', 235, 'XOAUTH2 '.trim($sXOAuth2Token));
			}
			catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
			{
				$this->writeLogException(
					new \MailSo\Smtp\Exceptions\LoginBadCredentialsException(
						$oException->GetResponses(), '', 0, $oException),
					\MailSo\Log\Enumerations\Type::NOTICE, true);
			}
		}
		else
		{
			$this->writeLogException(
				new \MailSo\Smtp\Exceptions\LoginBadMethodException(),
				\MailSo\Log\Enumerations\Type::NOTICE, true);
		}

		$this->bIsLoggined = true;

		return $this;
	}

	/**
	 * @param string $sFrom
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function MailFrom($sFrom)
	{
		if (!$this->bIsLoggined)
		{
			$this->writeLogException(
				new Exceptions\RuntimeException('A valid session has not been started'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->sendRequestWithCheck('MAIL', 250, 'FROM:<'.$sFrom.'>');

		$this->bMail = true;
		$this->bRcpt = false;
		$this->bData = false;

		return $this;
	}

	/**
	 * @param string $sTo
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Rcpt($sTo)
	{
		if (!$this->bMail)
		{
			$this->writeLogException(
				new Exceptions\RuntimeException('No sender reverse path has been supplied'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->sendRequestWithCheck('RCPT', array(250, 251), 'TO:<'.$sTo.'>');

		$this->bRcpt = true;
		return $this;
	}

	/**
	 * @param string $sTo
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function MailTo($sTo)
	{
		return $this->Rcpt($sTo);
	}

	/**
	 * @param string $sData
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Data($sData)
	{
		if (!\MailSo\Base\Validator::NotEmptyString($sData, true))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$rDataStream = \MailSo\Base\ResourceRegistry::CreateMemoryResourceFromString($sData);
		unset($sData);
		$this->DataWithStream($rDataStream);
		\MailSo\Base\ResourceRegistry::CloseMemoryResource($rDataStream);
		return $this;
	}

	/**
	 * @param resource $rDataStream
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function DataWithStream($rDataStream)
	{
		if (!is_resource($rDataStream))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		if (!$this->bRcpt)
		{
			$this->writeLogException(
				new Exceptions\RuntimeException('No recipient forward path has been supplied'),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->sendRequestWithCheck('DATA', 354);

		$this->writeLog('Message data.', \MailSo\Log\Enumerations\Type::NOTE);

		$iTimer = 0;
		while (!feof($rDataStream))
		{
			$sBuffer = fgets($rDataStream);
			if (false !== $sBuffer)
			{
				if (0 === strpos($sBuffer, '.'))
				{
					$sBuffer = '.'.$sBuffer;
				}

				$this->sendRaw(rtrim($sBuffer, "\r\n"), false);

				\MailSo\Base\Utils::ResetTimeLimit($iTimer);
				continue;
			}
			else if (!feof($rDataStream))
			{
				$this->writeLogException(
					new Exceptions\RuntimeException('Cannot read input resource'),
					\MailSo\Log\Enumerations\Type::ERROR, true);
			}

			break;
		}

		$this->sendRequestWithCheck('.', 250);

		$this->bData = true;
		return $this;
	}

	/**
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Rset()
	{
		$this->sendRequestWithCheck('RSET', array(250, 220));

		$this->bMail = false;
		$this->bRcpt = false;
		$this->bData = false;
		return $this;
	}

	/**
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Vrfy($sUser)
	{
		$this->sendRequestWithCheck('VRFY', array(250, 251, 252), $sUser);
		return $this;
	}

	/**
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Noop()
	{
		$this->sendRequestWithCheck('NOOP', 250);
		return $this;
	}

	/**
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	public function Logout()
	{
		if ($this->bIsLoggined)
		{
			$this->sendRequestWithCheck('QUIT', 221);
		}

		$this->bIsLoggined = false;
		$this->bHelo = false;
		$this->bMail = false;
		$this->bRcpt = false;
		$this->bData = false;
		return $this;
	}

	/**
	 * @param string $sCommand
	 * @param string $sAddToCommand = ''
	 * @param bool $bSecureLog = false
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 */
	private function sendRequest($sCommand, $sAddToCommand = '', $bSecureLog = false)
	{
		if (!\MailSo\Base\Validator::NotEmptyString($sCommand, true))
		{
			$this->writeLogException(
				new \MailSo\Base\Exceptions\InvalidArgumentException(),
				\MailSo\Log\Enumerations\Type::ERROR, true);
		}

		$this->IsConnected(true);

		$sCommand = trim($sCommand);
		$sRealCommand = $sCommand.(0 === strlen($sAddToCommand) ? '' : ' '.$sAddToCommand);

		$sFakeCommand = ($bSecureLog) ? '**********' : '';

		$this->iRequestTime = microtime(true);
		$this->sendRaw($sRealCommand, true, $sFakeCommand);
		return $this;
	}

	/**
	 * @param string $sCommand
	 * @param int|array $mExpectCode
	 * @param string $sAddToCommand = ''
	 * @param bool $bSecureLog = false
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	private function sendRequestWithCheck($sCommand, $mExpectCode, $sAddToCommand = '', $bSecureLog = false)
	{
		$this->sendRequest($sCommand, $sAddToCommand, $bSecureLog);
		return $this->validateResponse($mExpectCode);
	}

	/**
	 * @param string $sHost
	 * @return void
	 */
	private function ehloOrHelo($sHost)
	{
		try
		{
			$this->ehlo($sHost);
		}
		catch (\Exception $oException)
		{
			$this->helo($sHost);
		}
		catch (\Exception $oException)
		{
			throw $oException;
		}

		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return void
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	private function ehlo($sHost)
	{
		$this->sendRequestWithCheck('EHLO', 250, $sHost);

		foreach ($this->aResults as $sLine)
		{
			$aMatch = array();
			if (preg_match('/[\d]+[ \-](.+)$/', $sLine, $aMatch) && isset($aMatch[1]) && 0 < strlen($aMatch[1]))
			{
				$sLine = trim($aMatch[1]);
				if (0 === strpos($sLine, 'AUTH '))
				{
					$sAuthLine = substr($sLine, 5);
					if (0 < strlen($sAuthLine))
					{
						$this->aAuthTypes = explode(' ', $sAuthLine);
					}
				}
				else if (0 === strpos($sLine, 'STARTTLS'))
				{
					$this->bIsStartTSLSupported = true;
				}
			}
		}
	}

	/**
	 * @param string $sHost
	 *
	 * @return void
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Smtp\Exceptions\Exception
	 */
	private function helo($sHost)
	{
		$this->sendRequestWithCheck('HELO', 250, $sHost);
		$this->aAuthTypes = array();
		$this->bIsStartTSLSupported = false;
	}

	/**
	 * @param int|array $mExpectCode
	 *
	 * @return string
	 *
	 * @throws \MailSo\Smtp\Exceptions\ResponseException
	 */
	private function validateResponse($mExpectCode)
	{
		if (!is_array($mExpectCode))
		{
			$mExpectCode = array((int) $mExpectCode);
		}
		else
		{
			$mExpectCode = array_map('intval', $mExpectCode);
		}

		$aParts = array('', '');
		$this->aResults = array();
		do
		{
			$this->getNextBuffer();
			$aParts = preg_split('/([\s-]+)/', $this->sResponseBuffer, 2, PREG_SPLIT_DELIM_CAPTURE);

			if (!is_array($aParts) || 3 !== count($aParts)
				|| !is_numeric($aParts[0]))
			{
				if (!in_array((int) $aParts[0], $mExpectCode))
				{
					$this->writeLogException(
						new Exceptions\NegativeResponseException(trim(
							(0 < count($this->aResults) ? implode("\r\n", $this->aResults)."\r\n" : '').
							$this->sResponseBuffer)), \MailSo\Log\Enumerations\Type::ERROR, true);
				}
				else
				{
					$this->writeLogException(
						new Exceptions\ResponseException(trim(
							(0 < count($this->aResults) ? implode("\r\n", $this->aResults)."\r\n" : '').
							$this->sResponseBuffer)), \MailSo\Log\Enumerations\Type::ERROR, true);
				}
			}

			$this->aResults[] = $this->sResponseBuffer;
		}
		while (0 === strpos($aParts[1], '-'));

		$this->writeLog((microtime(true) - $this->iRequestTime),
			\MailSo\Log\Enumerations\Type::TIME);

		return $aParts[2];
	}

	/**
	 * @return string
	 */
	protected function getLogName()
	{
		return 'SMTP';
	}

	/**
	 * @param \MailSo\Log\Logger $oLogger
	 *
	 * @return \MailSo\Smtp\SmtpClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function SetLogger($oLogger)
	{
		parent::SetLogger($oLogger);

		return $this;
	}
}
