<?php

namespace MailSo\Imap;

/**
 * @category MailSo
 * @package Imap
 */
class NamespaceResult
{
	/**
	 * @var string
	 */
	private $sPersonal;

	/**
	 * @var string
	 */
	private $sPersonalDelimiter;

	/**
	 * @var string
	 */
	private $sOtherUser;

	/**
	 * @var string
	 */
	private $sOtherUserDelimiter;

	/**
	 * @var string
	 */
	private $sShared;

	/**
	 * @var string
	 */
	private $sSharedDelimiter;

	/**
	 * @access private
	 */
	private function __construct()
	{
		$this->sPersonal = '';
		$this->sPersonalDelimiter = '';
		$this->sOtherUser = '';
		$this->sOtherUserDelimiter = '';
		$this->sShared = '';
		$this->sSharedDelimiter = '';
	}

	/**
	 * @return \MailSo\Imap\NamespaceResult
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @param \MailSo\Imap\Response $oImapResponse
	 *
	 * @return \MailSo\Imap\NamespaceResult
	 */
	public function InitByImapResponse($oImapResponse)
	{
		if ($oImapResponse && $oImapResponse instanceof \MailSo\Imap\Response)
		{
			if (isset($oImapResponse->ResponseList[2][0]) &&
				is_array($oImapResponse->ResponseList[2][0]) &&
				2 <= count($oImapResponse->ResponseList[2][0]))
			{
				$this->sPersonal = $oImapResponse->ResponseList[2][0][0];
				$this->sPersonalDelimiter = $oImapResponse->ResponseList[2][0][1];
			}

			if (isset($oImapResponse->ResponseList[3][0]) &&
				is_array($oImapResponse->ResponseList[3][0]) &&
				2 <= count($oImapResponse->ResponseList[3][0]))
			{
				$this->sOtherUser = $oImapResponse->ResponseList[3][0][0];
				$this->sOtherUserDelimiter = $oImapResponse->ResponseList[3][0][1];
			}

			if (isset($oImapResponse->ResponseList[4][0]) &&
				is_array($oImapResponse->ResponseList[4][0]) &&
				2 <= count($oImapResponse->ResponseList[4][0]))
			{
				$this->sShared = $oImapResponse->ResponseList[4][0][0];
				$this->sSharedDelimiter = $oImapResponse->ResponseList[4][0][1];
			}
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function GetPersonalNamespace()
	{
		return $this->sPersonal;
	}

	/**
	 * @return string
	 */
	public function GetPersonalNamespaceDelimiter()
	{
		return $this->sPersonalDelimiter;
	}
}
