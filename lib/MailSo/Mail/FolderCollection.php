<?php

namespace MailSo\Mail;

/**
 * @category MailSo
 * @package Mail
 */
class FolderCollection extends \MailSo\Base\Collection
{
	/**
	 * @var string
	 */
	private $sNamespace;

	/**
	 * @var string
	 */
	public $FoldersHash;

	/**
	 * @access protected
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->sNamespace = '';

		$this->FoldersHash = '';
	}

	/**
	 * @return \MailSo\Mail\FolderCollection
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @param string $sFullNameRaw
	 *
	 * @return \MailSo\Mail\Folder|null
	 */
	public function &GetByFullNameRaw($sFullNameRaw)
	{
		$mResult = null;
		foreach ($this->aItems as /* @var $oFolder \MailSo\Mail\Folder */ $oFolder)
		{
			if ($oFolder->FullNameRaw() === $sFullNameRaw)
			{
				$mResult = $oFolder;
				break;
			}
		}

		return $mResult;
	}

	/**
	 * @return string
	 */
	public function GetNamespace()
	{
		return $this->sNamespace;
	}

	/**
	 * @param string $sNamespace
	 *
	 * @return \MailSo\Mail\FolderCollection
	 */
	public function SetNamespace($sNamespace)
	{
		$this->sNamespace = $sNamespace;

		return $this;
	}

	/**
	 * @param \MailSo\Mail\Folder $oFolderA
	 * @param \MailSo\Mail\Folder $oFolderB
	 *
	 * @return int
	 */
	protected function aASortHelper($oFolderA, $oFolderB)
	{
		return strnatcmp($oFolderA->FullName(), $oFolderB->FullName());
	}

	/**
	 * @param array $aUnsortedMailFolders
	 *
	 * @return void
	 */
	public function InitByUnsortedMailFolderArray($aUnsortedMailFolders)
	{
		$this->Clear();

		$aSortedByLenImapFolders = array();
		foreach ($aUnsortedMailFolders as /* @var $oMailFolder \MailSo\Mail\Folder */ &$oMailFolder)
		{
			$aSortedByLenImapFolders[$oMailFolder->FullNameRaw()] =& $oMailFolder;
			unset($oMailFolder);
		}
		unset($aUnsortedMailFolders);

		$aAddedFolders = array();
		foreach ($aSortedByLenImapFolders as /* @var $oMailFolder \MailSo\Mail\Folder */ $oMailFolder)
		{
			$sDelimiter = $oMailFolder->Delimiter();
			$aFolderExplode = explode($sDelimiter, $oMailFolder->FullNameRaw());

			if (1 < count($aFolderExplode))
			{
				array_pop($aFolderExplode);

				$sNonExistenFolderFullNameRaw = '';
				foreach ($aFolderExplode as $sFolderExplodeItem)
				{
					$sNonExistenFolderFullNameRaw .= (0 < strlen($sNonExistenFolderFullNameRaw))
						? $sDelimiter.$sFolderExplodeItem : $sFolderExplodeItem;

					if (!isset($aSortedByLenImapFolders[$sNonExistenFolderFullNameRaw]))
					{
						$aAddedFolders[$sNonExistenFolderFullNameRaw] =
							Folder::NewNonExistenInstance($sNonExistenFolderFullNameRaw, $sDelimiter);
					}
				}
			}
		}

		$aSortedByLenImapFolders = array_merge($aSortedByLenImapFolders, $aAddedFolders);
		unset($aAddedFolders);

		uasort($aSortedByLenImapFolders, array(&$this, 'aASortHelper'));

		// INBOX and Utf 7 modified sort
		$aFoot = $aTop = array();
		foreach ($aSortedByLenImapFolders as $sKey => /* @var $oMailFolder \MailSo\Mail\Folder */ &$oMailFolder)
		{
			if (0 === strpos($sKey, '&'))
			{
				$aFoot[] = $oMailFolder;
				unset($aSortedByLenImapFolders[$sKey]);
			}
			else if ('INBOX' === strtoupper($sKey))
			{
				array_unshift($aTop, $oMailFolder);
				unset($aSortedByLenImapFolders[$sKey]);
			}
			else if ('[GMAIL]' === strtoupper($sKey))
			{
				$aTop[] = $oMailFolder;
				unset($aSortedByLenImapFolders[$sKey]);
			}
		}

		$aSortedByLenImapFolders = array_merge($aTop, $aSortedByLenImapFolders, $aFoot);

// Setup system folders
//
//		$aSystemFoldersCache = array();
//		foreach ($aSortedByLenImapFolders as $sKey => /* @var $oMailFolder CCoreMailFolder */ &$oMailFolder)
//		{
//			if (C_MAIL_FOLDER_TYPE_USER === $oMailFolder->Type)
//			{
//				$oMailFolder->Type = CCoreMailFoldersHelper::GetTypeByName($oMailFolder->Name);
//				if (C_MAIL_FOLDER_TYPE_USER !== $oMailFolder->Type)
//				{
//					$oFolder->SetType = C_MAIL_FOLDER_TYPE_SET_BY_NAME;
//				}
//			}
//
//			if (in_array($oMailFolder->Type, array(
//				C_MAIL_FOLDER_TYPE_INBOX, C_MAIL_FOLDER_TYPE_SENT, C_MAIL_FOLDER_TYPE_DRAFTS,
//				C_MAIL_FOLDER_TYPE_TRASH, C_MAIL_FOLDER_TYPE_SPAM, C_MAIL_FOLDER_TYPE_VIRUS)))
//			{
//				if (isset($aSystemFoldersCache[$oMailFolder->Type]))
//				{
//					if (C_MAIL_FOLDER_TYPE_SET_BY_XLIST === $aSystemFoldersCache[$oMailFolder->Type]->SetType)
//					{
//						$oMailFolder->Type = C_MAIL_FOLDER_TYPE_USER;
//					}
//					else
//					{
//						if (C_MAIL_FOLDER_TYPE_SET_BY_XLIST === $oMailFolder->SetType)
//						{
//							$aSystemFoldersCache[$oMailFolder->Type]->Type = C_MAIL_FOLDER_TYPE_USER;
//							$aSystemFoldersCache[$oMailFolder->Type] = $oMailFolder;
//						}
//					}
//				}
//				else
//				{
//					$aSystemFoldersCache[$oMailFolder->Type] = $oMailFolder;
//				}
//			}
//		}

		foreach ($aSortedByLenImapFolders as /* @var $oMailFolder \MailSo\Mail\Folder */ &$oMailFolder)
		{
			$this->AddWithPositionSearch($oMailFolder);
			unset($oMailFolder);
		}

		unset($aSortedByLenImapFolders);
	}

	/**
	 * @param \MailSo\Mail\Folder $oMailFolder
	 *
	 * @return bool
	 */
	public function AddWithPositionSearch($oMailFolder)
	{
		$oItemFolder = null;
		$bIsAdded = false;
		$aList =& $this->GetAsArray();

		foreach ($aList as /* @var $oItemFolder \MailSo\Mail\Folder */ $oItemFolder)
		{
			if ($oMailFolder instanceof \MailSo\Mail\Folder &&
				0 === strpos($oMailFolder->FullNameRaw(), $oItemFolder->FullNameRaw().$oItemFolder->Delimiter()))
			{
				if ($oItemFolder->SubFolders(true)->AddWithPositionSearch($oMailFolder))
				{
					$bIsAdded = true;
				}

				break;
			}
		}

		if (!$bIsAdded && $oMailFolder instanceof \MailSo\Mail\Folder)
		{
			$bIsAdded = true;
			$this->Add($oMailFolder);
		}

		return $bIsAdded;
	}
}
