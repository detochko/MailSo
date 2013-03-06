<?php

namespace MailSo\Mail;

/**
 * @category MailSo
 * @package Mail
 */
class MailClient
{
	/**
	 * @var \MailSo\Log\Logger
	 */
	private $oLogger;

	/**
	 * @var \MailSo\Imap\ImapClient
	 */
	private $oImapClient;

	/**
	 * @access private
	 */
	private function __construct()
	{
		$this->oLogger = null;

		$this->oImapClient = \MailSo\Imap\ImapClient::NewInstance();
		$this->oImapClient->SetTimeOuts(5, 30);
	}

	/**
	 * @return \MailSo\Mail\MailClient
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @param string $sServerName
	 * @param int $iPort = 143
	 * @param int $iSecurityType = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function Connect($sServerName, $iPort = 143,
		$iSecurityType = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT)
	{
		$this->oImapClient->Connect($sServerName, $iPort, $iSecurityType);
		return $this;
	}

	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\LoginException
	 */
	public function Login($sLogin, $sPassword)
	{
		$this->oImapClient->Login($sLogin, $sPassword);
		return $this;
	}

	/**
	 * @param string $sXOAuth2Token
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\LoginException
	 */
	public function LoginWithXOauth2($sXOAuth2Token)
	{
		$this->oImapClient->LoginWithXOauth2($sXOAuth2Token);
		return $this;
	}

	/**
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 */
	public function Logout()
	{
		$this->oImapClient->Logout();
		return $this;
	}

	/**
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 */
	public function Disconnect()
	{
		$this->oImapClient->Disconnect();
		return $this;
	}

	/**
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 */
	public function LogoutAndDisconnect()
	{
		return $this->Logout()->Disconnect();
	}

	/**
	 * @return bool
	 */
	public function IsConnected()
	{
		return $this->oImapClient->IsConnected();
	}

	/**
	 * @return bool
	 */
	public function IsLoggined()
	{
		return $this->oImapClient->IsLoggined();
	}

	/**
	 * @return string
	 */
	private function getEnvelopeOrHeadersRequestString()
	{
//		return \MailSo\Imap\Enumerations\FetchType::ENVELOPE;

		return \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK;

		return \MailSo\Imap\Enumerations\FetchType::BuildBodyCustomHeaderRequest(array(
			\MailSo\Mime\Enumerations\Header::RETURN_PATH,
			\MailSo\Mime\Enumerations\Header::RECEIVED,
			\MailSo\Mime\Enumerations\Header::MIME_VERSION,
			\MailSo\Mime\Enumerations\Header::MESSAGE_ID,
			\MailSo\Mime\Enumerations\Header::FROM_,
			\MailSo\Mime\Enumerations\Header::TO_,
			\MailSo\Mime\Enumerations\Header::CC,
			\MailSo\Mime\Enumerations\Header::BCC,
			\MailSo\Mime\Enumerations\Header::SENDER,
			\MailSo\Mime\Enumerations\Header::REPLY_TO,
			\MailSo\Mime\Enumerations\Header::IN_REPLY_TO,
			\MailSo\Mime\Enumerations\Header::DATE,
			\MailSo\Mime\Enumerations\Header::SUBJECT,
			\MailSo\Mime\Enumerations\Header::X_MSMAIL_PRIORITY,
			\MailSo\Mime\Enumerations\Header::IMPORTANCE,
			\MailSo\Mime\Enumerations\Header::X_PRIORITY,
			\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
			\MailSo\Mime\Enumerations\Header::REFERENCES,
			\MailSo\Mime\Enumerations\Header::X_DRAFT_INFO,
		), true);
	}

	/**
	 * @param string $sFolderName
	 * @param array $aIndexRange
	 * @param bool $bIndexIsUid
	 * @param string $sMessageFlag
	 * @param bool $bSetAction = true
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 * @throws \MailSo\Mail\Exceptions\Exception
	 */
	public function MessageSetFlag($sFolderName, $aIndexRange, $bIndexIsUid, $sMessageFlag, $bSetAction = true)
	{
		$this->oImapClient->FolderSelect($sFolderName);

		$oFolderInfo = $this->oImapClient->FolderCurrentInformation();
		if (!$oFolderInfo || !$oFolderInfo->IsFlagSupported($sMessageFlag))
		{
			throw new \MailSo\Mail\Exceptions\RuntimeException('Message flag is not supported.');
		}

		$sStoreAction = $bSetAction
			? \MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT
			: \MailSo\Imap\Enumerations\StoreAction::REMOVE_FLAGS_SILENT
		;

		$sIndexRange = implode(',', $aIndexRange);
		$this->oImapClient->MessageStoreFlag($sIndexRange, $bIndexIsUid, array($sMessageFlag), $sStoreAction);
	}

	/**
	 * @param string $sFolderName
	 * @param array $aIndexRange
	 * @param bool $bIndexIsUid
	 * @param bool $bSetAction = true
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageSetFlagged($sFolderName, $aIndexRange, $bIndexIsUid, $bSetAction = true)
	{
		$this->MessageSetFlag($sFolderName, $aIndexRange, $bIndexIsUid,
			\MailSo\Imap\Enumerations\MessageFlag::FLAGGED, $bSetAction);
	}

	/**
	 * @param string $sFolderName
	 * @param array $aIndexRange
	 * @param bool $bIndexIsUid
	 * @param bool $bSetAction = true
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageSetSeen($sFolderName, $aIndexRange, $bIndexIsUid, $bSetAction = true)
	{
		$this->MessageSetFlag($sFolderName, $aIndexRange, $bIndexIsUid,
			\MailSo\Imap\Enumerations\MessageFlag::SEEN, $bSetAction);
	}

	/**
	 * @param string $sFolderName
	 * @param string $sFolderHash
	 * @param string $sUid
	 * @param mixed $oCacher = null
	 *
	 * @return \MailSo\Mail\Message|false
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageThreadInfo($sFolderName, $sFolderHash, $sUid, $oCacher)
	{
		$aResult = array(
			array(),
			array()
		);

		$aThreads = $this->MessageListTreadsMap($sFolderName, $sFolderHash, $oCacher);
		if (isset($aThreads[$sUid]) && is_array($aThreads[$sUid]))
		{
			$aResult[0] = $aThreads[$sUid];

			$oMessageCollection = MessageCollection::NewInstance();
			$oMessageCollection->FolderName = $sFolderName;
			$oMessageCollection->FolderHash = $sFolderHash;
			$oMessageCollection->Offset = 0;
			$oMessageCollection->Limit = 999;
			$oMessageCollection->Search = '';

			$this->MessageListByRequestIndexOrUids($oMessageCollection, $aThreads[$sUid], true);

//			$oMessageCollection->ForeachList(function (/* @var $oMessage \MailSo\Mail\Message */ $oMessage) use ($aThreads) {
//				$oMessage->SetThreads(isset($aThreads[$oMessage->Uid()]) && is_array($aThreads[$oMessage->Uid()]) ?
//					$aThreads[$oMessage->Uid()] : null);
//			});

			$aResult[1] = $oMessageCollection;
		}

		return $aResult;
	}

	/**
	 * @param string $sFolderName
	 * @param int $iIndex
	 * @param bool $bIndexIsUid = true
	 * @param string $sTextMimeIndex = ''
	 * @param mixed $oCacher = null
	 *
	 * @return \MailSo\Mail\Message|false
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function Message($sFolderName, $iIndex, $bIndexIsUid = true, $sTextMimeIndex = '', $oCacher = null)
	{
		if (!\MailSo\Base\Validator::RangeInt($iIndex, 1))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderExamine($sFolderName);

		$oBodyStructure = null;
		$oMessage = false;

		if (0 === strlen($sTextMimeIndex))
		{
			$aFetchResponse = $this->oImapClient->Fetch(array(\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iIndex, $bIndexIsUid);
			if (0 < count($aFetchResponse))
			{
				$oBodyStructure = $aFetchResponse[0]->GetFetchBodyStructure();
				$oTextPart = $oBodyStructure ? $oBodyStructure->SearchHtmlOrPlainPart() : null;
				$sTextMimeIndex = $oTextPart ? $oTextPart->PartID() : '';
			}
		}

		$aFetchItems = array(
			\MailSo\Imap\Enumerations\FetchType::INDEX,
			\MailSo\Imap\Enumerations\FetchType::UID,
			\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
			\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
			\MailSo\Imap\Enumerations\FetchType::FLAGS,
			$this->getEnvelopeOrHeadersRequestString()
		);

		if (0 < strlen($sTextMimeIndex))
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sTextMimeIndex.']';
		}

		if (!$oBodyStructure)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE;
		}

		$aFetchResponse = $this->oImapClient->Fetch($aFetchItems, $iIndex, $bIndexIsUid);
		if (0 < count($aFetchResponse))
		{
			$oMessage = \MailSo\Mail\Message::NewFetchResponseInstance(
				$sFolderName, $aFetchResponse[0], $oBodyStructure);
		}

//		if ($oCacher && $oMessage instanceof \MailSo\Mail\Message)
//		{
//			$sUidNext = '';
//			$iMessageCount = 0;
//			$iMessageUnseenCount = 0;
//
//			$this->initFolderValues($sFolderName, $iMessageCount, $iMessageUnseenCount, $sUidNext);
//
//			$aThreadsData = $this->MessageThreadInfo($oMessage->Folder(),
//				self::GenerateHash($sFolderName, $iMessageCount, $iMessageUnseenCount, $sUidNext),
//				$oMessage->Uid(), $oCacher);
//
//			$oMessage->SetThreads($aThreadsData[0]);
//			$oMessage->SetThreadsColleaction($aThreadsData[1]);
//		}

		return $oMessage;
	}

	/**
	 * @param mixed $mCallback
	 * @param string $sFolderName
	 * @param int $iIndex
	 * @param bool $bIndexIsUid = true,
	 * @param string $sMimeIndex = ''
	 *
	 * @return bool
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageMimeStream($mCallback, $sFolderName, $iIndex, $bIndexIsUid = true, $sMimeIndex = '')
	{
		if (!is_callable($mCallback))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderExamine($sFolderName);

		$sFileName = '';
		$sContentType = '';
		$sMailEncodingName = '';

		$sMimeIndex = trim($sMimeIndex);
		$aFetchResponse = $this->oImapClient->Fetch(array(
			0 === strlen($sMimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
				: \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sMimeIndex.'.MIME]'
		), $iIndex, $bIndexIsUid);

		if (0 < count($aFetchResponse))
		{
			$sMime = $aFetchResponse[0]->GetFetchValue(
				0 === strlen($sMimeIndex)
					? \MailSo\Imap\Enumerations\FetchType::BODY_HEADER
					: \MailSo\Imap\Enumerations\FetchType::BODY.'['.$sMimeIndex.'.MIME]'
			);

			if (0 < strlen($sMime))
			{
				$oHeaders = \MailSo\Mime\HeaderCollection::NewInstance()->Parse($sMime);

				if (0 < strlen($sMimeIndex))
				{
					$sFileName = $oHeaders->ParameterValue(
						\MailSo\Mime\Enumerations\Header::CONTENT_DISPOSITION,
						\MailSo\Mime\Enumerations\Parameter::FILENAME);

					if (0 === strlen($sFileName))
					{
						$sFileName = $oHeaders->ParameterValue(
							\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
							\MailSo\Mime\Enumerations\Parameter::NAME);
					}

					$sMailEncodingName = $oHeaders->ValueByName(
						\MailSo\Mime\Enumerations\Header::CONTENT_TRANSFER_ENCODING);

					$sContentType = $oHeaders->ValueByName(
						\MailSo\Mime\Enumerations\Header::CONTENT_TYPE);
				}
				else
				{
					$sSubject = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SUBJECT);

					$sFileName = 0 === strlen($sSubject) ? (string) $iIndex : $sSubject;
					$sFileName .= '.eml';

					$sContentType = 'message/rfc822';
				}
			}
		}

		$aFetchResponse = $this->oImapClient->Fetch(array(
			array(\MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sMimeIndex.']',
				function ($sParent, $sLiteralAtomUpperCase, $rImapLiteralStream) use ($mCallback, $sMimeIndex, $sMailEncodingName, $sContentType, $sFileName)
				{
					if (0 < strlen($sLiteralAtomUpperCase))
					{
						if (is_resource($rImapLiteralStream) && 'FETCH' === $sParent)
						{
							$rMessageMimeIndexStream = (0 === strlen($sMailEncodingName))
								? $rImapLiteralStream
								: \MailSo\Base\StreamWrappers\Binary::CreateStream($rImapLiteralStream,
									\MailSo\Base\StreamWrappers\Binary::GetInlineDecodeOrEncodeFunctionName(
										$sMailEncodingName, true));

							call_user_func($mCallback, $rMessageMimeIndexStream, $sContentType, $sFileName, $sMimeIndex);
						}
					}
				}
			)), $iIndex, $bIndexIsUid);

		return ($aFetchResponse && 1 === count($aFetchResponse));
	}

	/**
	 * @param string $sFolder
	 * @param array $aIndexRange
	 * @param bool $bIndexIsUid
	 * @param bool $bUseExpunge = true
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageDelete($sFolder, $aIndexRange, $bIndexIsUid, $bUseExpunge = true)
	{
		if (0 === strlen($sFolder) || !is_array($aIndexRange) || 0 === count($aIndexRange))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderSelect($sFolder);

		$sIndexRange = implode(',', $aIndexRange);

		$this->oImapClient->MessageStoreFlag($sIndexRange, $bIndexIsUid,
			array(\MailSo\Imap\Enumerations\MessageFlag::DELETED),
			\MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT
		);

		if ($bUseExpunge)
		{
			$this->oImapClient->MessageExpunge($bIndexIsUid ? $sIndexRange : '', $bIndexIsUid);
		}

		return $this;
	}

	/**
	 * @param string $sFromFolder
	 * @param string $sToFolder
	 * @param array $aIndexRange
	 * @param bool $bIndexIsUid
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageMove($sFromFolder, $sToFolder, $aIndexRange, $bIndexIsUid)
	{
		if (0 === strlen($sFromFolder) || 0 === strlen($sToFolder) ||
			!is_array($aIndexRange) || 0 === count($aIndexRange))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderSelect($sFromFolder);

		$this->oImapClient->MessageCopy($sToFolder, implode(',', $aIndexRange), $bIndexIsUid);

		return $this->MessageDelete($sFromFolder, $aIndexRange, $bIndexIsUid, true);
	}

	/**
	 * @param resource $rMessageStream
	 * @param int $iMessageStreamSize
	 * @param string $sFolderToSave
	 * @param array $aAppendFlags = null
	 * @param int $iUid = null
	 *
	 * @return \MailSo\Mail\MailClient
	 */
	public function MessageAppendStream($rMessageStream, $iMessageStreamSize, $sFolderToSave, $aAppendFlags = null, &$iUid = null)
	{
		if (!is_resource($rMessageStream) || 0 === strlen($sFolderToSave))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->MessageAppendStream(
			$sFolderToSave, $rMessageStream, $iMessageStreamSize, $aAppendFlags, $iUid);

		return $this;
	}

	/**
	 * @param string $sMessageFileName
	 * @param string $sFolderToSave
	 * @param array $aAppendFlags = null
	 * @param int &$iUid = null
	 *
	 * @return \MailSo\Mail\MailClient
	 */
	public function MessageAppendFile($sMessageFileName, $sFolderToSave, $aAppendFlags = null, &$iUid = null)
	{
		if (!@is_file($sMessageFileName) || !@is_readable($sMessageFileName))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$iMessageStreamSize = filesize($sMessageFileName);
		$rMessageStream = fopen($sMessageFileName, 'rb');

		$this->MessageAppendStream($rMessageStream, $iMessageStreamSize, $sFolderToSave, $aAppendFlags, $iUid);

		if (is_resource($rMessageStream))
		{
			@fclose($rMessageStream);
		}

		return $this;
	}

	/**
	 * @param string $sFolderName
	 * @param int $iCount
	 * @param int $iUnseenCount
	 * @param string $sUidNext
	 *
	 * @return void
	 */
	protected function initFolderValues($sFolderName, &$iCount, &$iUnseenCount, &$sUidNext)
	{
		$aFolderStatus = $this->oImapClient->FolderStatus($sFolderName, array(
			\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES,
			\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN,
			\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT
		));

		$iCount = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES])
			? (int) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES] : 0;

		$iUnseenCount = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN])
			? (int) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN] : 0;

		$sUidNext = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT])
			? (string) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT] : '0';
	}

	/**
	 * @param string $sFolder
	 * @param int $iCount
	 * @param int $iUnseenCount
	 * @param string $sUidNext
	 *
	 * @return string
	 */
	public static function GenerateHash($sFolder, $iCount, $iUnseenCount, $sUidNext)
	{
		$iUnseenCount = 0;
		return md5($sFolder.'-'.$iCount.'-'.$iUnseenCount.'-'.$sUidNext);
	}

	/**
	 * @param string $sFolderName
	 * @param string $sPrevUidNext
	 * @param string $sCurrentUidNext
	 *
	 * @return array
	 */
	private function getFolderNextMessageInformation($sFolderName, $sPrevUidNext, $sCurrentUidNext)
	{
		$aNewMessages = array();

		if (0 < strlen($sPrevUidNext) && (string) $sPrevUidNext !== (string) $sCurrentUidNext)
		{
			$this->oImapClient->FolderExamine($sFolderName);

			$aFetchResponse = $this->oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::FLAGS,
				\MailSo\Imap\Enumerations\FetchType::BuildBodyCustomHeaderRequest(array(
					\MailSo\Mime\Enumerations\Header::FROM_,
					\MailSo\Mime\Enumerations\Header::SUBJECT,
					\MailSo\Mime\Enumerations\Header::CONTENT_TYPE
				))
			), $sPrevUidNext.':*', true);

			if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
			{
				foreach ($aFetchResponse as /* @var $oFetchResponse \MailSo\Imap\FetchResponse */ $oFetchResponse)
				{
					$aFlags = array_map('strtolower', $oFetchResponse->GetFetchValue(
						\MailSo\Imap\Enumerations\FetchType::FLAGS));

					if (in_array(\strtolower(\MailSo\Imap\Enumerations\MessageFlag::RECENT), $aFlags))
					{
						$sUid = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
						$sHeaders = $oFetchResponse->GetHeaderFieldsValue();

						$oHeaders = \MailSo\Mime\HeaderCollection::NewInstance()->Parse($sHeaders);

						$sContentTypeCharset = $oHeaders->ParameterValue(
							\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
							\MailSo\Mime\Enumerations\Parameter::CHARSET
						);

						$sCharset = '';
						if (0 < strlen($sContentTypeCharset))
						{
							$sCharset = $sContentTypeCharset;
						}

						if (0 < strlen($sCharset))
						{
							$oHeaders->SetParentCharset(\MailSo\Base\Enumerations\Charset::ISO_8859_1);
						}

						$aNewMessages[] = array(
							'Folder' => $sFolderName,
							'Uid' => $sUid,
							'Subject' => $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SUBJECT),
							'From' => $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::FROM_)
						);
					}
				}
			}
		}

		return $aNewMessages;
	}

	/**
	 * @param string $sFolderName
	 * @param string $sPrevUidNext = ''
	 * @param array $aUids = ''
	 *
	 * @return string
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function FolderInformation($sFolderName, $sPrevUidNext = '', $aUids = array())
	{
		$aFlags = array();
		if (is_array($aUids) && 0 < count($aUids))
		{
			$this->oImapClient->FolderExamine($sFolderName);

			$aFetchResponse = $this->oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::FLAGS
			), implode(',', $aUids), true);

			if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
			{
				foreach ($aFetchResponse as $oFetchResponse)
				{
					$sUid = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
					$aFlags[(is_numeric($sUid) ? (int) $sUid : 0)] =
						$oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::FLAGS);
				}
			}
		}

		$iCount = 0;
		$iUnseenCount = 0;
		$sUidNext = '0';

		$this->initFolderValues($sFolderName, $iCount, $iUnseenCount, $sUidNext);

		$aResult = array(
			'Folder' => $sFolderName,
			'Hash' => self::GenerateHash($sFolderName, $iCount, $iUnseenCount, $sUidNext),
			'MessageCount' => $iCount,
			'MessageUnseenCount' => $iUnseenCount,
			'UidNext' => $sUidNext,
			'Flags' => $aFlags,
			'NewMessages' => $this->getFolderNextMessageInformation($sFolderName, $sPrevUidNext, $sUidNext)
		);

		return $aResult;
	}

	/**
	 * @param string $sFolderName
	 *
	 * @return string
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function FolderHash($sFolderName)
	{
		$iCount = 0;
		$iUnseenCount = 0;
		$sUidNext = '0';

		$this->initFolderValues($sFolderName, $iCount, $iUnseenCount, $sUidNext);

		return self::GenerateHash($sFolderName, $iCount, $iUnseenCount, $sUidNext);
	}

	/**
	 * @param string $sSearch
	 *
	 * @return string
	 */
	private function escapeSearchString($sSearch)
	{
		return ('ssl://imap.gmail.com' === strtolower($this->oImapClient->GetConnectedHost())) // gmail
			? '{'.strlen($sSearch).'+}'."\r\n".$sSearch
			: $this->oImapClient->EscapeString($sSearch);
	}

	/**
	 * @param string $sDate
	 * @param int $iTimeZoneOffset
	 *
	 * @return int
	 */
	private function parseSearchDate($sDate, $iTimeZoneOffset)
	{
		$iResult = 0;
		if (0 < strlen($sDate))
		{
			$oDateTime = \DateTime::createFromFormat('Y.m.d', $sDate, \MailSo\Base\DateTimeHelper::GetUtcTimeZoneObject());
			return $oDateTime ? $oDateTime->getTimestamp() - $iTimeZoneOffset : 0;
		}

		return $iResult;
	}

	/**
	 * @param string $sSearch
	 * @param int $iTimeZoneOffset = 0
	 *
	 * @return \MailSo\Imap\SearchBuilder
	 */
	private function getSearchBuilder($sSearch, $iTimeZoneOffset = 0)
	{
		$sPattern = '/(from|to|subject|date|has):\(([^)]+)\)/';
		$oSearchBuilder = \MailSo\Imap\SearchBuilder::NewInstance();

		$sSearch = trim(preg_replace('/[\s]+/', ' ', $sSearch));
		$aResult = array();

		if (0 < strlen($sSearch))
		{
			preg_match_all($sPattern, $sSearch, $aResult);
			if (is_array($aResult) && isset($aResult[1]) && is_array($aResult[1]) && 0 < count($aResult[1]))
			{
				foreach ($aResult[1] as $iIndex => $sName)
				{
					if (isset($aResult[2][$iIndex]) && 0 < strlen($aResult[2][$iIndex]))
					{
						$sValue = $this->escapeSearchString($aResult[2][$iIndex]);
						switch ($sName)
						{
							case 'from':
								$oSearchBuilder->AddAnd('FROM', $sValue);
								break;
							case 'to':
								$oSearchBuilder->AddAnd('TO', $sValue);
								$oSearchBuilder->AddAnd('CC', $sValue);
								break;
							case 'subject':
								$oSearchBuilder->AddAnd('SUBJECT', $sValue);
								break;
							case 'has':
								if (false !== strpos($aResult[2][$iIndex], 'attachments'))
								{
									$oSearchBuilder->AddAnd('HEADER CONTENT-TYPE', '"MULTIPART/MIXED"');
								}
//								if (false !== strpos($aResult[2][$iIndex], 'flagged'))
//								{
//									$oSearchBuilder->AddAnd('FLAGGED');
//								}
//								if (false !== strpos($aResult[2][$iIndex], 'answered'))
//								{
//									$oSearchBuilder->AddAnd('ANSWERED');
//								}
								break;
							case 'date':
								$iDateStampFrom = $iDateStampTo = 0;

								$sDate = $aResult[2][$iIndex];
								$aDate = explode('/', $sDate);

								if (is_array($aDate) && 2 === count($aDate))
								{
									if (0 < strlen($aDate[0]))
									{
										$iDateStampFrom = $this->parseSearchDate($aDate[0], $iTimeZoneOffset);
									}

									if (0 < strlen($aDate[1]))
									{
										$iDateStampTo = $this->parseSearchDate($aDate[1], $iTimeZoneOffset);
										$iDateStampTo += 60 * 60 * 24;
									}
								}
								else
								{
									if (0 < strlen($sDate))
									{
										$iDateStampFrom = $this->parseSearchDate($sDate, $iTimeZoneOffset);
										$iDateStampTo = $iDateStampFrom + 60 * 60 * 24;
									}
								}

								if (0 < $iDateStampFrom)
								{
									$oSearchBuilder->AddAnd('SINCE', gmdate('j-M-Y', $iDateStampFrom));
								}

								if (0 < $iDateStampTo)
								{
									$oSearchBuilder->AddAnd('BEFORE', gmdate('j-M-Y', $iDateStampTo));
								}
								break;
						}
					}
				}

				$sSearch = preg_replace($sPattern, '', $sSearch);
				$sSearch = trim(preg_replace('/[\s]+/', ' ', $sSearch));
				if (0 < strlen($sSearch))
				{
					$oSearchBuilder->AddAnd('BODY', $this->escapeSearchString($sSearch));
				}
			}
			else
			{
				$oSearchBuilder->AddOr('TEXT', $this->escapeSearchString($sSearch));
			}
		}

		return $oSearchBuilder;
	}

	/**
	 * @param array $aThreads
	 * @return array
	 */
	private function threadArrayReverseRec($aThreads)
	{
		$aThreads = \array_reverse($aThreads);
		foreach ($aThreads as &$mItem)
		{
			if (\is_array($mItem))
			{
				$mItem = $this->threadArrayReverseRec($mItem);
			}
		}
		return $aThreads;
	}

	/**
	 * @param array $aThreads
	 * @return array
	 */
	private function threadArrayMap($aThreads)
	{
		$aNew = array();
		foreach ($aThreads as $mItem)
		{
			if (!\is_array($mItem))
			{
				$aNew[] = $mItem;
			}
			else
			{
				$mMap = $this->threadArrayMap($mItem);
				if (\is_array($mMap) && 0 < \count($mMap))
				{
					$aNew = \array_merge($aNew, $mMap);
				}
			}
		}

		\sort($aNew, SORT_NUMERIC);
		return $aNew;
	}

	/**
	 * @param array $aThreads
	 *
	 * @return array
	 */
	private function compileThreadArray($aThreads)
	{
		$aThreads = $this->threadArrayReverseRec($aThreads);

		$aResult = array();
		foreach ($aThreads as $mItem)
		{
			if (\is_array($mItem))
			{
				$aMap = $this->threadArrayMap($mItem);
				if (\is_array($aMap))
				{
					if (1 < \count($aMap))
					{
						$iMax = array_pop($aMap);
						$aResult[(int) $iMax] = $aMap;
					}
					else if (0 < \count($aMap))
					{
						$aResult[(int) $aMap[0]] = $aMap[0];
					}
				}
			}
			else
			{
				$aResult[(int) $mItem] = $mItem;
			}
		}

		\krsort($aResult, SORT_NUMERIC);
		return $aResult;
	}

	/**
	 * @param string $sFolderName
	 * @param string $sFolderHash
	 * @param mixed $oCacher
	 *
	 * @return array
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageListTreadsMap($sFolderName, $sFolderHash, $oCacher)
	{
		if ($oCacher && $oCacher->IsInited())
		{
			$sSerializedHash =
				'TreadsMap/'.
				$this->oImapClient->GetLogginedUser().'@'.
				$this->oImapClient->GetConnectedHost().':'.
				$this->oImapClient->GetConnectedPort().'/'.
				$sFolderName.'/'.$sFolderHash;

			$sSerializedUids = $oCacher->Get($sSerializedHash);
			if (!empty($sSerializedUids))
			{
				$aSerializedUids = @unserialize($sSerializedUids);
				if (is_array($aSerializedUids))
				{
					return $aSerializedUids;
				}
			}
		}

		$this->oImapClient->FolderExamine($sFolderName);

		$aThreadUids = array();
		try
		{
			$aThreadUids = $this->oImapClient->MessageSimpleThread();
		}
		catch (\MailSo\Imap\Exceptions\RuntimeException $oException)
		{
			$aThreadUids = array();
		}

		$aResult = $this->compileThreadArray($aThreadUids);

		if ($oCacher && $oCacher->IsInited() && !empty($sSerializedHash))
		{
			$oCacher->Set($sSerializedHash, serialize($aResult));
		}

		return $aResult;
	}

	/**
	 * @param \MailSo\Mail\MessageCollection &$oMessageCollection
	 * @param array $aRequestIndexOrUids
	 * @param bool $bIndexAsUid
	 *
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageListByRequestIndexOrUids(&$oMessageCollection, $aRequestIndexOrUids, $bIndexAsUid)
	{
		if (is_array($aRequestIndexOrUids) && 0 < count($aRequestIndexOrUids))
		{
			$aFetchResponse = $this->oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
				\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
				\MailSo\Imap\Enumerations\FetchType::FLAGS,
				\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE,
				$this->getEnvelopeOrHeadersRequestString()
			), implode(',', $aRequestIndexOrUids), $bIndexAsUid);

			if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
			{
				$aFetchIndexArray = array();
				$oFetchResponseItem = null;
				foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
				{
					$aFetchIndexArray[($bIndexAsUid)
						? $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID)
						: $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::INDEX)] =& $oFetchResponseItem;

					unset($oFetchResponseItem);
				}

				foreach ($aRequestIndexOrUids as $iFUid)
				{
					if (isset($aFetchIndexArray[$iFUid]))
					{
						$oMessageCollection->Add(
							Message::NewFetchResponseInstance(
								$oMessageCollection->FolderName, $aFetchIndexArray[$iFUid]));
					}
				}
			}
		}
	}

	/**
	 * @param string $sFolderName
	 * @param int $iOffset = 0
	 * @param int $iLimit = 10
	 * @param string $sSearch = ''
	 * @param string $sPrevUidNext = ''
	 * @param mixed $oCacher = null
	 * @param string $sCachePrefix = ''
	 * @param bool $bUseSortIfSupported = false
	 * @param bool $bUseThreadIfSupported = false
	 *
	 * @return \MailSo\Mail\MessageCollection
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function MessageList($sFolderName, $iOffset = 0, $iLimit = 10, $sSearch = '', $sPrevUidNext = '',
		$oCacher = null, $bUseSortIfSupported = false, $bUseThreadIfSupported = false)
	{
		$sSearch = trim($sSearch);
		if (!\MailSo\Base\Validator::RangeInt($iOffset, 0) ||
			!\MailSo\Base\Validator::RangeInt($iLimit, 0, 999) ||
			!is_string($sSearch))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderExamine($sFolderName);

		$oMessageCollection = MessageCollection::NewInstance();
		$oMessageCollection->FolderName = $sFolderName;
		$oMessageCollection->Offset = $iOffset;
		$oMessageCollection->Limit = $iLimit;
		$oMessageCollection->Search = $sSearch;

		$aThreads = array();
		$iMessageCount = 0;
		$iMessageUnseenCount = 0;
		$sUidNext = '0';
		$sSerializedHash = '';
		$bUseSortIfSupported = $bUseSortIfSupported ? $this->oImapClient->IsSupported('SORT') : false;
		$bUseThreadIfSupported = $bUseThreadIfSupported ?
			$this->oImapClient->IsSupported('THREAD=REFERENCES') || $this->oImapClient->IsSupported('THREAD=ORDEREDSUBJECT'): false;

		if (!$oCacher || !($oCacher instanceof \MailSo\Cache\CacheClient))
		{
			$oCacher = null;
		}

		$this->initFolderValues($sFolderName, $iMessageCount, $iMessageUnseenCount, $sUidNext);

		$oMessageCollection->FolderHash = self::GenerateHash($sFolderName, $iMessageCount, $iMessageUnseenCount, $sUidNext);
		$oMessageCollection->UidNext = $sUidNext;
		$oMessageCollection->NewMessages = $this->getFolderNextMessageInformation($sFolderName, $sPrevUidNext, $sUidNext);

		$bCacher = false;

		if (0 < $iMessageCount)
		{
			$bIndexAsUid = false;
			$aIndexOrUids = array();

			if (0 < strlen($sSearch) || ($bUseSortIfSupported && !$bUseThreadIfSupported))
			{
				$bIndexAsUid = true;

				$aIndexOrUids = null;

				$sSearchCriterias = $this->getSearchBuilder($sSearch)->Complete();

				if ($oCacher && $oCacher->IsInited())
				{
					$sSerializedHash =
						($bUseSortIfSupported ? 'S': 'N').'/'.
						($bUseThreadIfSupported ? 'T': 'N').'/'.
						$this->oImapClient->GetLogginedUser().'@'.
						$this->oImapClient->GetConnectedHost().':'.
						$this->oImapClient->GetConnectedPort().'/'.
						$oMessageCollection->FolderName.'/'.
						$oMessageCollection->FolderHash.'/'.
						$sSearchCriterias;

					$sSerializedUids = $oCacher->Get($sSerializedHash);
					if (!empty($sSerializedUids))
					{
						$aSerializedUids = @unserialize($sSerializedUids);
						if (is_array($aSerializedUids))
						{
							$aIndexOrUids = $aSerializedUids;
							$bCacher = true;
						}
					}
				}

				if (!is_array($aIndexOrUids))
				{
					if ($bUseThreadIfSupported)
					{
						$sSearchThread = $this->oImapClient->MessageSimpleThread($sSearchCriterias, true);
						$aThreads = $this->compileThreadArray($sSearchThread);
						$aIndexOrUids = array_keys($aThreads);
					}
					else if (!$bUseSortIfSupported)
					{
						if (!\MailSo\Base\Utils::IsAscii($sSearch))
						{
							try
							{
								$aIndexOrUids = $this->oImapClient->MessageSimpleSearch($sSearchCriterias, $bIndexAsUid, 'UTF-8');
							}
							catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
							{
								$oException = null;
								$aIndexOrUids = null;
							}
						}

						if (null === $aIndexOrUids)
						{
							$aIndexOrUids = $this->oImapClient->MessageSimpleSearch($sSearchCriterias, $bIndexAsUid);
						}
					}
					else
					{
						$aIndexOrUids = $this->oImapClient->MessageSimpleSort(array('ARRIVAL'), $sSearchCriterias, $bIndexAsUid);
					}
				}
			}
			else
			{
				if ($bUseThreadIfSupported && 1 < $iMessageCount)
				{
					$bIndexAsUid = true;
					$aThreads = $this->MessageListTreadsMap(
						$oMessageCollection->FolderName,
						$oMessageCollection->FolderHash,
						$oCacher);

					$aIndexOrUids = array_keys($aThreads);
				}
				else
				{
					$bIndexAsUid = false;
					$aIndexOrUids = array(1);
					if (1 < $iMessageCount)
					{
						$aIndexOrUids = array_reverse(range(1, $iMessageCount));
					}
				}
			}

			if ($bIndexAsUid && !$bCacher && is_array($aIndexOrUids) && $oCacher && $oCacher->IsInited() && 0 < strlen($sSerializedHash))
			{
				$oCacher->Set($sSerializedHash, serialize($aIndexOrUids));
			}

			if (0 < count($aIndexOrUids))
			{
				$oMessageCollection->MessageCount = $iMessageCount;
				$oMessageCollection->MessageUnseenCount = $iMessageUnseenCount;
				$oMessageCollection->MessageSearchCount = 0 === strlen($sSearch)
					? $oMessageCollection->MessageCount : count($aIndexOrUids);

				$iOffset = (0 > $iOffset) ? 0 : $iOffset;
				$aRequestIndexOrUids = array_slice($aIndexOrUids, $iOffset, $iLimit);

				$this->MessageListByRequestIndexOrUids($oMessageCollection, $aRequestIndexOrUids, $bIndexAsUid);
			}
		}

		if ($bUseThreadIfSupported && 0 < count($aThreads))
		{
			$oMessageCollection->ForeachList(function (/* @var $oMessage \MailSo\Mail\Message */ $oMessage) use ($aThreads) {
				$iUid = $oMessage->Uid();
				if (isset($aThreads[$iUid]) && is_array($aThreads[$iUid]))
				{
					$oMessage->SetThreads($aThreads[$iUid]);
				}
			});
		}

		return $oMessageCollection;
	}

	/**
	 * @param array $aList
	 * @param int $iUid
	 * @return array | null
	 */
	private function findMessageTreadUidsRec($aList, $iUid, $bRoot = false)
	{
		$mResult = null;
		foreach ($aList as $mItem)
		{
			if (\is_array($mItem))
			{
				$mResult = $this->findMessageTreadUidsRec($mItem, $iUid);
				if (\is_array($mResult))
				{
					break;
				}
			}
			else if ((int) $mItem === (int) $iUid)
			{
				$mResult = $bRoot ? null : $aList;
				break;
			}
		}

		return $mResult;
	}

	/**
	 * @param array $aThreadUids
	 * @param int $iUid
	 * @return array | null
	 */
	public function GetMessageTreadUids($aThreadUids, $iUid)
	{
		return $this->findMessageTreadUidsRec($aThreadUids, $iUid, true);
	}

	/**
	 * @return array|false
	 */
	public function Quota()
	{
		return $this->oImapClient->Quota();
	}

	/**
	 * @param string $sFolderName
	 * @param string $sMessageId
	 *
	 * @return int|null
	 */
	public function FindMessageUidByMessageId($sFolderName, $sMessageId)
	{
		if (0 === strlen($sMessageId))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderExamine($sFolderName);

		$sSearchCriterias = \MailSo\Imap\SearchBuilder::NewInstance()
			->AddAnd('HEADER MESSAGE-ID', $sMessageId)
			->Complete();

		$aUids = $this->oImapClient->MessageSimpleSearch($sSearchCriterias, true);

		return is_array($aUids) && 1 === count($aUids) && is_numeric($aUids[0]) ? (int) $aUids[0] : null;
	}

	/**
	 * @param string $sParent = ''
	 * @param string $sListPattern = '*'
	 *
	 * @return \MailSo\Mail\FolderCollection|false
	 */
	public function Folders($sParent = '', $sListPattern = '*')
	{
		$oFolderCollection = false;

		$aFolders = $this->oImapClient->FolderList($sParent, $sListPattern, true);
		$aSubscribedFolders = $this->oImapClient->FolderSubscribeList($sParent, $sListPattern);

		$aImapSubscribedFoldersHelper = array();
		if (is_array($aSubscribedFolders))
		{
			foreach ($aSubscribedFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aImapSubscribedFoldersHelper[] = $oImapFolder->FullNameRaw();
			}
		}

		$aMailFoldersHelper = null;
		if (is_array($aFolders))
		{
			$aMailFoldersHelper = array();

			foreach ($aFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aMailFoldersHelper[] = Folder::NewInstance($oImapFolder,
					in_array($oImapFolder->FullNameRaw(), $aImapSubscribedFoldersHelper) || $oImapFolder->IsInbox()
				);
			}
		}

		if (is_array($aMailFoldersHelper))
		{
			$oFolderCollection = FolderCollection::NewInstance();
			$oFolderCollection->InitByUnsortedMailFolderArray($aMailFoldersHelper);
		}

		$oNamespace = $this->oImapClient->GetNamespace();
		if ($oNamespace)
		{
			$oFolderCollection->SetNamespace($oNamespace->GetPersonalNamespace());
		}

		return $oFolderCollection;
	}

	/**
	 * @param string $sFolderName = ''
	 *
	 * @return \MailSo\Mail\FolderCollection|false
	 */
	public function FoldersLevel($sFolderName = '', $sDelimiter = '/')
	{
		$oFolderCollection = false;

		$sFolderLevel = 0 === strlen($sFolderName) ? '' : $sFolderName.$sDelimiter;

		$aFolders = $this->oImapClient->FolderList($sFolderLevel, '%');
		$aSubscribedFolders = $this->oImapClient->FolderSubscribeList($sFolderLevel, '%');

		$aSubFolders = $this->oImapClient->FolderList($sFolderLevel, '%/%');
		$aSubSubscribedFolders = $this->oImapClient->FolderSubscribeList($sFolderLevel, '%/%');

		$aImapSubscribedFoldersHelper = array();
		if (is_array($aSubscribedFolders))
		{
			foreach ($aSubscribedFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aImapSubscribedFoldersHelper[] = $oImapFolder->FullNameRaw();
			}
		}
		if (is_array($aSubSubscribedFolders))
		{
			foreach ($aSubSubscribedFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aImapSubscribedFoldersHelper[] = $oImapFolder->FullNameRaw();
			}
		}

		$aMailFoldersHelper = null;
		if (is_array($aFolders))
		{
			$aMailFoldersHelper = array();

			foreach ($aFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aMailFoldersHelper[] = Folder::NewInstance($oImapFolder,
					in_array($oImapFolder->FullNameRaw(), $aImapSubscribedFoldersHelper) || $oImapFolder->IsInbox()
				);
			}
		}

		if (is_array($aMailFoldersHelper) && is_array($aSubFolders))
		{
			foreach ($aSubFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aMailFoldersHelper[] = Folder::NewInstance($oImapFolder,
					in_array($oImapFolder->FullNameRaw(), $aImapSubscribedFoldersHelper) || $oImapFolder->IsInbox()
				);
			}
		}

		if (is_array($aMailFoldersHelper))
		{
			$oFolderCollection = FolderCollection::NewInstance();
			$oFolderCollection->InitByUnsortedMailFolderArray($aMailFoldersHelper);
		}

		if (0 < strlen($sFolderLevel) && $oFolderCollection)
		{
			$oLevelFolder = $oFolderCollection->GetByFullNameRaw($sFolderName);
			if ($oLevelFolder && $oLevelFolder->HasSubFolders())
			{
				$oFolderCollection = $oLevelFolder->SubFolders();
			}
			else
			{
				$oFolderCollection = false;
			}
		}

		return $oFolderCollection;
	}

	/**
	 * @param string $sFolderNameInUtf
	 * @param string $sFolderParentFullNameRaw = ''
	 * @param bool $bSubscribeOnCreation = true
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function FolderCreate($sFolderNameInUtf, $sFolderParentFullNameRaw = '', $bSubscribeOnCreation = true)
	{
		if (!\MailSo\Base\Validator::NotEmptyString($sFolderNameInUtf, true) ||
			!is_string($sFolderParentFullNameRaw))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$sFolderNameInUtf = trim($sFolderNameInUtf);

		$aFolders = $this->oImapClient->FolderList('', 0 === strlen(trim($sFolderParentFullNameRaw)) ? 'INBOX' : $sFolderParentFullNameRaw);
		if (!is_array($aFolders) || !isset($aFolders[0]))
		{
			// TODO
			throw new \MailSo\Mail\Exceptions\RuntimeException(
				0 === strlen(trim($sFolderParentFullNameRaw))
					? 'Can not get folder delimiter'
					: 'Can not create folder in non-existen parent folder');
		}

		$sDelimiter = $aFolders[0]->Delimiter();
		if (0 < strlen($sDelimiter) && 0 < strlen(trim($sFolderParentFullNameRaw)))
		{
			$sFolderParentFullNameRaw .= $sDelimiter;
		}

		$sFullNameRawToCreate = \MailSo\Base\Utils::ConvertEncoding($sFolderNameInUtf,
			\MailSo\Base\Enumerations\Charset::UTF_8,
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

		if (0 < strlen($sDelimiter) && false !== strpos($sFullNameRawToCreate, $sDelimiter))
		{
			// TODO
			throw new \MailSo\Mail\Exceptions\RuntimeException(
				'New folder name contain delimiter');
		}

		$sFullNameRawToCreate = $sFolderParentFullNameRaw.$sFullNameRawToCreate;

		$this->oImapClient->FolderCreate($sFullNameRawToCreate);

		if ($bSubscribeOnCreation)
		{
			$this->oImapClient->FolderSubscribe($sFullNameRawToCreate);
		}

		return $this;
	}

	/**
	 * @param string $sPrevFolderFullNameRaw
	 * @param string $sNewTopFolderNameInUtf
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function FolderRename($sPrevFolderFullNameRaw, $sNewTopFolderNameInUtf)
	{
		if (0 === strlen($sPrevFolderFullNameRaw) || 0 === strlen($sNewTopFolderNameInUtf))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$aFolders = $this->oImapClient->FolderList('', $sPrevFolderFullNameRaw);
		if (!is_array($aFolders) || !isset($aFolders[0]))
		{
			// TODO
			throw new \MailSo\Mail\Exceptions\RuntimeException('Can not rename non-existen folder');
		}

		$sDelimiter = $aFolders[0]->Delimiter();
		$iLast = strrpos($sPrevFolderFullNameRaw, $sDelimiter);
		$sFolderParentFullNameRaw = false === $iLast ? '' : substr($sPrevFolderFullNameRaw, 0, $iLast + 1);

		$aSubscribeFolders = $this->oImapClient->FolderSubscribeList($sPrevFolderFullNameRaw, '*');
		if (is_array($aSubscribeFolders) && 0 < count($aSubscribeFolders))
		{
			foreach ($aSubscribeFolders as /* @var $oFolder \MailSo\Imap\Folder */ $oFolder)
			{
				$this->oImapClient->FolderUnSubscribe($oFolder->FullNameRaw());
			}
		}

		$sNewFolderFullNameRaw = \MailSo\Base\Utils::ConvertEncoding($sNewTopFolderNameInUtf,
			\MailSo\Base\Enumerations\Charset::UTF_8,
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

		if (0 < strlen($sDelimiter) && false !== strpos($sNewFolderFullNameRaw, $sDelimiter))
		{
			// TODO
			throw new \MailSo\Mail\Exceptions\RuntimeException(
				'new folder name contain delimiter');
		}

		$sNewFolderFullNameRaw = $sFolderParentFullNameRaw.$sNewFolderFullNameRaw;

		$this->oImapClient->FolderRename($sPrevFolderFullNameRaw, $sNewFolderFullNameRaw);

		if (is_array($aSubscribeFolders) && 0 < count($aSubscribeFolders))
		{
			foreach ($aSubscribeFolders as /* @var $oFolder \MailSo\Imap\Folder */ $oFolder)
			{
				$sFolderFullNameRawForResubscrine = $oFolder->FullNameRaw();
				if (0 === strpos($sFolderFullNameRawForResubscrine, $sPrevFolderFullNameRaw))
				{
					$sNewFolderFullNameRawForResubscrine = $sNewFolderFullNameRaw.
						substr($sFolderFullNameRawForResubscrine, strlen($sPrevFolderFullNameRaw));

					$this->oImapClient->FolderSubscribe($sNewFolderFullNameRawForResubscrine);
				}
			}
		}

		return $this;
	}

	/**
	 * @param string $sFolderFullNameRaw
	 * @param bool $bUnsubscribeOnDeletion = true
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Mail\Exceptions\RuntimeException
	 */
	public function FolderDelete($sFolderFullNameRaw, $bUnsubscribeOnDeletion = true)
	{
		if (0 === strlen($sFolderFullNameRaw) || 'INBOX' === $sFolderFullNameRaw)
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->FolderExamine($sFolderFullNameRaw);

		$aIndexOrUids = $this->oImapClient->MessageSimpleSearch('ALL');
		if (0 < count($aIndexOrUids))
		{
			throw new \MailSo\Mail\Exceptions\NonEmptyFolder();
		}

		$this->oImapClient->FolderExamine('INBOX');

		if ($bUnsubscribeOnDeletion)
		{
			$this->oImapClient->FolderUnSubscribe($sFolderFullNameRaw);
		}

		$this->oImapClient->FolderDelete($sFolderFullNameRaw);

		return $this;
	}

	/**
	 * @param string $sFolderFullNameRaw
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function FolderClear($sFolderFullNameRaw)
	{
		$this->oImapClient->FolderSelect($sFolderFullNameRaw);

		$oFolderInformation = $this->oImapClient->FolderCurrentInformation();
		if ($oFolderInformation && 0 < $oFolderInformation->Exists) // STATUS?
		{
			$this->oImapClient->MessageStoreFlag('1:*', false,
				array(\MailSo\Imap\Enumerations\MessageFlag::DELETED),
				\MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT
			);

			$this->oImapClient->MessageExpunge();
		}

		return $this;
	}

	/**
	 * @param string $sFolderFullNameRaw
	 * @param bool $bSubscribe
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function FolderSubscribe($sFolderFullNameRaw, $bSubscribe)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oImapClient->{($bSubscribe) ? 'FolderSubscribe' : 'FolderUnSubscribe'}($sFolderFullNameRaw);

		return $this;
	}

	/**
	 * @param \MailSo\Log\Logger $oLogger
	 *
	 * @return \MailSo\Mail\MailClient
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public function SetLogger($oLogger)
	{
		if (!($oLogger instanceof \MailSo\Log\Logger))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$this->oLogger = $oLogger;
		$this->oImapClient->SetLogger($this->oLogger);

		return $this;
	}
}
