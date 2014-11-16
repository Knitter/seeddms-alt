<?php
/**
 * Implementation of a folder in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a folder in the document management system
 *
 * A folder in SeedDMS is equivalent to a directory in a regular file
 * system. It can contain further subfolders and documents. Each folder
 * has a single parent except for the root folder which has no parent.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Folder extends SeedDMS_Core_Object {
	/**
	 * @var string name of folder
	 */
	protected $_name;

	/**
	 * @var integer id of parent folder
	 */
	protected $_parentID;

	/**
	 * @var string comment of document
	 */
	protected $_comment;

	/**
	 * @var integer id of user who is the owner
	 */
	protected $_ownerID;

	/**
	 * @var boolean true if access is inherited, otherwise false
	 */
	protected $_inheritAccess;

	/**
	 * @var integer default access if access rights are not inherited
	 */
	protected $_defaultAccess;

	/**
	 * @var array list of notifications for users and groups
	 */
	protected $_readAccessList;

	/**
	 * @var array list of notifications for users and groups
	 */
	public $_notifyList;

	/**
	 * @var integer position of folder within the parent folder
	 */
	protected $_sequence;

	function SeedDMS_Core_Folder($id, $name, $parentID, $comment, $date, $ownerID, $inheritAccess, $defaultAccess, $sequence) { /* {{{ */
		parent::__construct($id);
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
		$this->_notifyList = array();
	} /* }}} */

	/*
	 * Get the name of the folder.
	 *
	 * @return string name of folder
	 */
	function getName() { return $this->_name; }

	/*
	 * Set the name of the folder.
	 *
	 * @param string $newName set a new name of the folder
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders SET name = " . $db->qstr($newName) . " WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;

		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders SET comment = " . $db->qstr($newComment) . " WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * Return creation date of folder
	 *
	 * @return integer unix timestamp of creation date
	 */
	function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Returns the parent
	 *
	 * @return object parent folder or false if there is no parent folder
	 */
	function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	/**
	 * Check if the folder is subfolder
	 *
	 * This function checks if the passed folder is a subfolder of the current
	 * folder. 
	 *
	 * @param object $subFolder potential sub folder
	 * @return boolean true if passes folder is a subfolder
	 */
	function isSubFolder($subfolder) { /* {{{ */
		$db = $this->_dms->getDB();

		$path = $this->getPath();
		$sfpath = $subfolder->getPath();
		/* It is a potential sub folder start with the path of the current folder.
		 * If the path differs, it can't be a sub folder.
		 */
		for($i=0; $i < count($path); $i++) { 
			if($path[$i] != $sfpath[$i])
				return false;
		}
		return true;
	} /* }}} */

	/**
	 * Set a new folder
	 *
	 * This function moves a folder from one parent folder into another parent
	 * folder. It will fail if the root folder is moved.
	 *
	 * @param object $newParent new parent folder
	 * @return boolean true if operation was successful otherwise false
	 */
	function setParent($newParent) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		/* Check if the new parent is the folder to be moved or even
		 * a subfolder of that folder
		 */
		if($this->isSubFolder($newParent)) {
			return false;
		}

		// Update the folderList of the folder
		$pathPrefix="";
		$path = $newParent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		$queryStr = "UPDATE tblFolders SET parent = ".$newParent->getID().", folderList='".$pathPrefix."' WHERE id = ". $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_parentID = $newParent->getID();
		$this->_parent = $newParent;

		// Must also ensure that any documents in this folder tree have their
		// folderLists updated.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		/* Update path in folderList for all documents */
		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocuments`.`folderList` FROM `tblDocuments` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblDocuments` SET `folderList` = '".$newPath."' WHERE `tblDocuments`.`id` = '".$row["id"]."'";
			$res = $db->getResult($queryStr);
		}

		/* Update path in folderList for all documents */
		$queryStr = "SELECT `tblFolders`.`id`, `tblFolders`.`folderList` FROM `tblFolders` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblFolders` SET `folderList` = '".$newPath."' WHERE `tblFolders`.`id` = '".$row["id"]."'";
			$res = $db->getResult($queryStr);
		}

		return true;
	} /* }}} */

	/**
	 * Returns the owner
	 *
	 * @return object owner of the folder
	 */
	function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set the owner
	 *
	 * @param object new owner of the folder
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders set owner = " . $newOwner->getID() . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getDefaultAccess();
		}

		return $this->_defaultAccess;
	} /* }}} */

	function setDefaultAccess($mode) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders set defaultAccess = " . (int) $mode . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();
		foreach ($this->_notifyList["users"] as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}
		foreach ($this->_notifyList["groups"] as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}

		return true;
	} /* }}} */

	function inheritsAccess() { return $this->_inheritAccess; }

	function setInheritAccess($inheritAccess) { /* {{{ */
		$db = $this->_dms->getDB();

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE tblFolders SET inheritAccess = " . (int) $inheritAccess . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();
		foreach ($this->_notifyList["users"] as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}
		foreach ($this->_notifyList["groups"] as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}

		return true;
	} /* }}} */

	function getSequence() { return $this->_sequence; }

	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders SET sequence = " . $seq . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Check if folder has subfolders
	 * This function just checks if a folder has subfolders disregarding
	 * any access rights.
	 *
	 * @return int number of subfolders or false in case of an error
	 */
	function hasSubFolders() { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_subFolders)) {
			return count($this->subFolders);
		}
		$queryStr = "SELECT count(*) as c FROM tblFolders WHERE parent = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return $resArr[0]['c'];
	} /* }}} */

	/**
	 * Returns a list of subfolders
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each folder against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @return array list of folder objects or false in case of an error
	 */
	function getSubFolders($orderby="") { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_subFolders)) {
			$queryStr = "SELECT * FROM tblFolders WHERE parent = " . $this->_id;

			if ($orderby=="n") $queryStr .= " ORDER BY name";
			elseif ($orderby=="s") $queryStr .= " ORDER BY sequence";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_subFolders = array();
			for ($i = 0; $i < count($resArr); $i++)
//				$this->_subFolders[$i] = new SeedDMS_Core_Folder($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["parent"], $resArr[$i]["comment"], $resArr[$i]["owner"], $resArr[$i]["inheritAccess"], $resArr[$i]["defaultAccess"], $resArr[$i]["sequence"]);
				$this->_subFolders[$i] = $this->_dms->getFolder($resArr[$i]["id"]);
		}

		return $this->_subFolders;
	} /* }}} */

	/**
	 * Add a new subfolder
	 *
	 * @param string $name name of folder
	 * @param string $comment comment of folder
	 * @param object $owner owner of folder
	 * @param integer $sequence position of folder in list of sub folders.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @return object object of type SeedDMS_Core_Folder or false in case of
	 *         an error.
	 */
	function addSubFolder($name, $comment, $owner, $sequence, $attributes=array()) { /* {{{ */
		$db = $this->_dms->getDB();

		// Set the folderList of the folder
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO tblFolders (name, parent, folderList, comment, date, owner, inheritAccess, defaultAccess, sequence) ".
					"VALUES (".$db->qstr($name).", ".$this->_id.", ".$db->qstr($pathPrefix).", ".$db->qstr($comment).", ".time().", ".$owner->getID().", 1, ".M_READ.", ". $sequence.")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$newFolder = $this->_dms->getFolder($db->getInsertID());
		unset($this->_subFolders);

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				if($attribute)
					if(!$newFolder->setAttributeValue($this->_dms->getAttributeDefinition($attrdefid), $attribute)) {
						$db->rollbackTransaction();
						return false;
					}
			}
		}

		$db->commitTransaction();
		return $newFolder;
	} /* }}} */

	/**
	 * Returns an array of all parents, grand parent, etc. up to root folder.
	 * The folder itself is the last element of the array.
	 *
	 * @return array Array of parents
	 */
	function getPath() { /* {{{ */
		if (!isset($this->_parentID) || ($this->_parentID == "") || ($this->_parentID == 0) || ($this->_id == $this->_dms->rootFolderID)) {
			return array($this);
		}
		else {
			$res = $this->getParent();
			if (!$res) return false;

			$path = $this->_parent->getPath();
			if (!$path) return false;

			array_push($path, $this);
			return $path;
		}
	} /* }}} */

	/**
	 * Returns a unix file system path
	 *
	 * @return string path separated with '/'
	 */
	function getFolderPathPlain() { /* {{{ */
		$path="";
		$folderPath = $this->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		return $path;
	} /* }}} */

	/**
	 * Check, if this folder is a subfolder of a given folder
	 *
	 * @param object $folder parent folder
	 * @return boolean true if folder is a subfolder
	 */
	function isDescendant($folder) { /* {{{ */
		if ($this->_parentID == $folder->getID())
			return true;
		elseif (isset($this->_parentID)) {
			$res = $this->getParent();
			if (!$res) return false;

			return $this->_parent->isDescendant($folder);
		} else
			return false;
	} /* }}} */

	/**
	 * Check if folder has documents
	 * This function just checks if a folder has documents diregarding
	 * any access rights.
	 *
	 * @return int number of documents or false in case of an error
	 */
	function hasDocuments() { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_documents)) {
			return count($this->documents);
		}
		$queryStr = "SELECT count(*) as c FROM tblDocuments WHERE folder = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return $resArr[0]['c'];
	} /* }}} */

	/**
	 * Check if folder has document with given name
	 *
	 * @return boolean true if document exists, false if not or in case
	 * of an error
	 */
	function hasDocumentByName($name) { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_documents)) {
			return count($this->documents);
		}
		$queryStr = "SELECT count(*) as c FROM tblDocuments WHERE folder = " . $this->_id . " AND `name` = ".$db->qstr($name);
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return ($resArr[0]['c'] > 0);
	} /* }}} */

	/**
	 * Get all documents of the folder
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each document against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @return array list of documents or false in case of an error
	 */
	function getDocuments($orderby="") { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_documents)) {
			$queryStr = "SELECT * FROM tblDocuments WHERE folder = " . $this->_id;
			if ($orderby=="n") $queryStr .= " ORDER BY name";
			elseif($orderby=="s") $queryStr .= " ORDER BY sequence";

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_documents = array();
			foreach ($resArr as $row) {
//				array_push($this->_documents, new SeedDMS_Core_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], isset($row["lockUser"])?$row["lockUser"]:NULL, $row["keywords"], $row["sequence"]));
				array_push($this->_documents, $this->_dms->getDocument($row["id"]));
			}
		}
		return $this->_documents;
	} /* }}} */

	/**
	 * Count all documents and subfolders of the folder
	 *
	 * This function also counts documents and folders of subfolders, so
	 * basically it works like recursively counting children.
	 *
	 * This function checks for access rights up the given limit. If more
	 * documents or folders are found, the returned value will be the number
	 * of objects available and the precise flag in the return array will be
	 * set to false. This number should not be revelead to the
	 * user, because it allows to gain information about the existens of
	 * objects without access right.
	 * Setting the parameter $limit to 0 will turn off access right checking
	 * which is reasonable if the $user is an administrator.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @param integer $limit maximum number of folders and documents that will
	 *        be precisly counted by taken the access rights into account
	 * @return array array with four elements 'document_count', 'folder_count'
	 *        'document_precise', 'folder_precise' holding
	 *        the counted number and a flag if the number is precise.
	 */
	function countChildren($user, $limit=10000) { /* {{{ */
		$db = $this->_dms->getDB();

		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$queryStr = "SELECT id FROM tblFolders WHERE folderList like '".$pathPrefix. "%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$result = array();

		$folders = array();
		$folderids = array($this->_id);
		$cfolders = count($resArr);
		if($cfolders < $limit) {
			foreach ($resArr as $row) {
				$folder = $this->_dms->getFolder($row["id"]);
				if ($folder->getAccessMode($user) >= M_READ) {
					array_push($folders, $folder);
					array_push($folderids, $row['id']);
				}
			}
			$result['folder_count'] = count($folders);
			$result['folder_precise'] = true;
		} else {
			foreach ($resArr as $row) {
				array_push($folderids, $row['id']);
			}
			$result['folder_count'] = $cfolders;
			$result['folder_precise'] = false;
		}

		$documents = array();
		if($folderids) {
			$queryStr = "SELECT id FROM tblDocuments WHERE folder in (".implode(',', $folderids). ")";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$cdocs = count($resArr);
			if($cdocs < $limit) {
				foreach ($resArr as $row) {
					$document = $this->_dms->getDocument($row["id"]);
					if ($document->getAccessMode($user) >= M_READ)
						array_push($documents, $document);
				}
				$result['document_count'] = count($documents);
				$result['document_precise'] = true;
			} else {
				$result['document_count'] = $cdocs;
				$result['document_precise'] = false;
			}
		}

		return $result;
	} /* }}} */

	// $comment will be used for both document and version leaving empty the version_comment 
	/**
	 * Add a new document to the folder
	 * This function will add a new document and its content from a given file. 
	 * It does not check for access rights on the folder. The new documents
	 * default access right is read only and the access right is inherited.
	 *
	 * @param string $name name of new document
	 * @param string $comment comment of new document
	 * @param integer $expires expiration date as a unix timestamp or 0 for no
	 *        expiration date
	 * @param object $owner owner of the new document
	 * @param string $keywords keywords of new document
	 * @param array $categories list of category ids
	 * @param string $tmpFile the path of the file containing the content
	 * @param string $orgFileName the original file name
	 * @param string $fileType usually the extension of the filename
	 * @param string $mimeType mime type of the content
	 * @param float $sequence position of new document within the folder
	 * @param array $reviewers list of users who must review this document
	 * @param array $approvers list of users who must approve this document
	 * @param string $reqversion version number of the content
	 * @param string $version_comment comment of the content. If left empty
	 *        the $comment will be used.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @param array $version_attributes list of document version attributes.
	 *        The element key must be the id of the attribute definition.
	 * @return array/boolean false in case of error, otherwise an array
	 *        containing two elements. The first one is the new document, the
	 *        second one is the result set returned when inserting the content.
	 */
	function addDocument($name, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion=0,$version_comment="", $attributes=array(), $version_attributes=array(), $workflow=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$expires = (!$expires) ? 0 : $expires;

		// Must also ensure that the document has a valid folderList.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		$queryStr = "INSERT INTO tblDocuments (name, comment, date, expires, owner, folder, folderList, inheritAccess, defaultAccess, locked, keywords, sequence) VALUES ".
					"(".$db->qstr($name).", ".$db->qstr($comment).", " . time().", ".(int) $expires.", ".$owner->getID().", ".$this->_id.",".$db->qstr($pathPrefix).", 1, ".M_READ.", -1, ".$db->qstr($keywords).", " . $sequence . ")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$document = $this->_dms->getDocument($db->getInsertID());

//		if ($version_comment!="")
			$res = $document->addContent($version_comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers, $reqversion, $version_attributes, $workflow);
//		else $res = $document->addContent($comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers,$reqversion, $version_attributes, $workflow);

		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		if($categories) {
			$document->setCategories($categories);
		}

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				/* $attribute can be a string or an array */
				if($attribute)
					if(!$document->setAttributeValue($this->_dms->getAttributeDefinition($attrdefid), $attribute)) {
						$document->remove();
						$db->rollbackTransaction();
						return false;
					}
			}
		}

		$db->commitTransaction();
		return array($document, $res);
	} /* }}} */

	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		// Do not delete the root folder.
		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;

		foreach ($this->_subFolders as $subFolder) {
			$res = $subFolder->remove();
			if (!$res) {
				return false;
			}
		}

		foreach ($this->_documents as $document) {
			$res = $document->remove();
			if (!$res) {
				return false;
			}
		}

		//Entfernen der Datenbankeinträge
		$db->rollbackTransaction();
		$queryStr = "DELETE FROM tblFolders WHERE id =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM tblFolderAttributes WHERE folder =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM tblACLs WHERE target = ". $this->_id. " AND targetType = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblNotify WHERE target = ". $this->_id. " AND targetType = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();

		return true;
	} /* }}} */

	/**
	 * Returns a list of access privileges
	 *
	 * If the folder inherits the access privileges from the parent folder
	 * those will be returned.
	 * $mode and $op can be set to restrict the list of returned access
	 * privileges. If $mode is set to M_ANY no restriction will apply
	 * regardless of the value of $op. The returned array contains a list
	 * of {@link SeedDMS_Core_UserAccess} and
	 * {@link SeedDMS_Core_GroupAccess} objects. Even if the document
	 * has no access list the returned array contains the two elements
	 * 'users' and 'groups' which are than empty. The methode returns false
	 * if the function fails.
	 * 
	 * @param integer $mode access mode (defaults to M_ANY)
	 * @param integer $op operation (defaults to O_EQ)
	 * @return array multi dimensional array
	 */
	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getAccessList($mode, $op);
		}

		if (!isset($this->_accessList[$mode])) {
			if ($op!=O_GTEQ && $op!=O_LTEQ && $op!=O_EQ) {
				return false;
			}
			$modeStr = "";
			if ($mode!=M_ANY) {
				$modeStr = " AND mode".$op.(int)$mode;
			}
			$queryStr = "SELECT * FROM tblACLs WHERE targetType = ".T_FOLDER.
				" AND target = " . $this->_id .	$modeStr . " ORDER BY targetType";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_accessList[$mode] = array("groups" => array(), "users" => array());
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new SeedDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new SeedDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
	} /* }}} */

	/**
	 * Delete all entries for this folder from the access control list
	 *
	 * @return boolean true if operation was successful otherwise false
	 */
	function clearAccessList() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblACLs WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);
		return true;
	} /* }}} */

	/**
	 * Add access right to folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $mode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 */
	function addAccess($mode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "INSERT INTO tblACLs (target, targetType, ".$userOrGroup.", mode) VALUES 
					(".$this->_id.", ".T_FOLDER.", " . (int) $userOrGroupID . ", " .(int) $mode. ")";
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Change access right of folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $newMode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 */
	function changeAccess($newMode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "UPDATE tblACLs SET mode = " . (int) $newMode . " WHERE targetType = ".T_FOLDER." AND target = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($newMode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	function removeAccess($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "DELETE FROM tblACLs WHERE targetType = ".T_FOLDER." AND target = ".$this->_id." AND ".$userOrGroup." = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Get the access mode of a user on the folder
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * @param object $user user for which access shall be checked
	 * @return integer access mode
	 */
	function getAccessMode($user) { /* {{{ */
		if(!$user)
			return M_NONE;

		/* Admins have full access */
		if ($user->isAdmin()) return M_ALL;

		/* User has full access if he/she is the owner of the document */
		if ($user->getID() == $this->_ownerID) return M_ALL;

		/* Guest has read access by default, if guest login is allowed at all */
		if ($user->isGuest()) {
			$mode = $this->getDefaultAccess();
			if ($mode >= M_READ) return M_READ;
			else return M_NONE;
		}

		/* check ACLs */
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		foreach ($accessList["users"] as $userAccess) {
			if ($userAccess->getUserID() == $user->getID()) {
				return $userAccess->getMode();
			}
		}
		/* Get the highest right defined by a group */
		$result = 0;
		foreach ($accessList["groups"] as $groupAccess) {
			if ($user->isMemberOfGroup($groupAccess->getGroup())) {
				if ($groupAccess->getMode() > $result)
					$result = $groupAccess->getMode();
//					return $groupAccess->getMode();
			}
		}
		if($result)
			return $result;
		$result = $this->getDefaultAccess();
		return $result;
	} /* }}} */

	/**
	 * Get the access mode for a group on the folder
	 * This function returns the access mode for a given group. The algorithmn
	 * applied to get the access mode is the same as describe at
	 * {@link getAccessMode}
	 *
	 * @param object $group group for which access shall be checked
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
		$highestPrivileged = M_NONE;
		$foundInACL = false;
		$accessList = $this->getAccessList();
		if (!$accessList)
			return false;

		foreach ($accessList["groups"] as $groupAccess) {
			if ($groupAccess->getGroupID() == $group->getID()) {
				$foundInACL = true;
				if ($groupAccess->getMode() > $highestPrivileged)
					$highestPrivileged = $groupAccess->getMode();
				if ($highestPrivileged == M_ALL) /* no need to check further */
					return $highestPrivileged;
			}
		}
		if ($foundInACL)
			return $highestPrivileged;

		/* Take default access */
		return $this->getDefaultAccess();
	} /* }}} */

	/**
	 * Get a list of all notification
	 * This function returns all users and groups that have registerd a
	 * notification for the folder
	 *
	 * @param integer $type type of notification (not yet used)
	 * @return array array with a the elements 'users' and 'groups' which
	 *        contain a list of users and groups.
	 */
	function getNotifyList($type=0) { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM tblNotify WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1)
					array_push($this->_notifyList["users"], $this->_dms->getUser($row["userID"]) );
				else //if ($row["groupID"] != -1)
					array_push($this->_notifyList["groups"], $this->_dms->getGroup($row["groupID"]) );
			}
		}
		return $this->_notifyList;
	} /* }}} */

	/*
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -2: Target User / Group does not have read access.
	 *    -3: User is already subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		/* Verify that user / group exists */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Verify that user / group has read access to the document.
		//
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// FIXME: Why not check the access list first and if this returns
			// not result, then use the default access?
			// Groups are a little more complex.
			if ($this->getDefaultAccess() >= M_READ) {
				// If the default access is at least READ-ONLY, then just make sure
				// that the current group has not been explicitly excluded.
				$acl = $this->getAccessList(M_NONE, O_EQ);
				$found = false;
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if ($found) {
					return -2;
				}
			}
			else {
				// The default access is restricted. Make sure that the group has
				// been explicitly allocated access to the document.
				$acl = $this->getAccessList(M_READ, O_GTEQ);
				if (is_bool($acl)) {
					return -4;
				}
				$found = false;
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					return -2;
				}
			}
		}
		//
		// Check to see if user/group is already on the list.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO tblNotify (target, targetType, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_FOLDER . ", " .  (int) $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/*
	 * Removes notify for a user or group to folder
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @param $type type of notification (0 will delete all) Not used yet!
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -3: User is not subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function removeNotify($userOrGroupID, $isUser, $type=0) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL  $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Check to see if the target is in the database.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM tblNotify WHERE target = " . $this->_id . " AND targetType = " . T_FOLDER . " AND " . $userOrGroup . " = " .  (int) $userOrGroupID;
		/* If type is given then delete only those notifications */
		if($type)
			$queryStr .= " AND `type` = ".(int) $type;
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Get List of users and groups which have read access on the document
	 *
	 * This function is deprecated. Use
	 * {@see SeedDMS_Core_Folder::getReadAccessList()} instead.
	 */
	function getApproversList() { /* {{{ */
		return $this->getReadAccessList(0, 0);
	} /* }}} */

	/**
	 * Returns a list of groups and users with read access on the folder
	 * The list will not include any guest users,
	 * administrators and the owner of the folder unless $listadmin resp.
	 * $listowner is set to true.
	 *
	 * @param boolean $listadmin if set to true any admin will be listed too
	 * @param boolean $listowner if set to true the owner will be listed too
	 *
	 * @return array list of users and groups
	 */
	function getReadAccessList($listadmin=0, $listowner=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_readAccessList)) {
			$this->_readAccessList = array("groups" => array(), "users" => array());
			$userIDs = "";
			$groupIDs = "";
			$defAccess  = $this->getDefaultAccess();

			/* Check if the default access is < read access or >= read access.
			 * If default access is less than read access, then create a list
			 * of users and groups with read access.
			 * If default access is equal or greater then read access, then
			 * create a list of users and groups without read access.
			 */
			if ($defAccess<M_READ) {
				// Get the list of all users and groups that are listed in the ACL as
				// having read access to the folder.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have read access
				// to the folder.
				$tmpList = $this->getAccessList(M_NONE, O_LTEQ);
			}
			foreach ($tmpList["groups"] as $groupAccess) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $groupAccess->getGroupID();
			}
			foreach ($tmpList["users"] as $userAccess) {
				$user = $userAccess->getUser();
				if (!$listadmin && $user->isAdmin()) continue;
				if (!$listowner && $user->getID() == $this->_ownerID) continue;
				if ($user->isGuest()) continue;
				$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $userAccess->getUserID();
			}

			// Construct a query against the users table to identify those users
			// that have read access to this folder, either directly through an
			// ACL entry, by virtue of ownership or by having administrative rights
			// on the database.
			$queryStr="";
			/* If default access is less then read, $userIDs and $groupIDs contains
			 * a list of user with read access
			 */
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` IN (". $groupIDs .") ".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`role` != ".SeedDMS_Core_User::role_guest.") ".
					"AND ((`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.")".
					(strlen($userIDs) == 0 ? "" : " OR (`tblUsers`.`id` IN (". $userIDs ."))").
					") ORDER BY `login`";
			}
			/* If default access is equal or greate then read, $userIDs and
			 * $groupIDs contains a list of user without read access
			 */
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` NOT IN (". $groupIDs .")".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
						(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))")." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.") ".
					"UNION ".
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
					(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))").
					" ORDER BY `login`";
			}
			$resArr = $db->getResultArray($queryStr);
			if (!is_bool($resArr)) {
				foreach ($resArr as $row) {
					$user = $this->_dms->getUser($row['id']);
					if (!$listadmin && $user->isAdmin()) continue;
					if (!$listowner && $user->getID() == $this->_ownerID) continue;
					$this->_readAccessList["users"][] = $user;
				}
			}

			// Assemble the list of groups that have read access to the folder.
			$queryStr="";
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` IN (". $groupIDs .")";
				}
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` NOT IN (". $groupIDs .")";
				}
				else {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups`";
				}
			}
			if (strlen($queryStr)>0) {
				$resArr = $db->getResultArray($queryStr);
				if (!is_bool($resArr)) {
					foreach ($resArr as $row) {
						$group = $this->_dms->getGroup($row["id"]);
						$this->_readAccessList["groups"][] = $group;
					}
				}
			}
		}
		return $this->_readAccessList;
	} /* }}} */

	/**
	 * Get the internally used folderList which stores the ids of folders from
	 * the root folder to the parent folder.
	 *
	 * @return string column separated list of folder ids
	 */
	function getFolderList() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT folderList FROM tblFolders where id = ".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		return $resArr[0]['folderList'];
	} /* }}} */

	/**
	 * Checks the internal data of the folder and repairs it.
	 * Currently, this function only repairs an incorrect folderList
	 *
	 * @return boolean true on success, otherwise false
	 */
	function repair() { /* {{{ */
		$db = $this->_dms->getDB();

		$curfolderlist = $this->getFolderList();

		// calculate the folderList of the folder
		$parent = $this->getParent();
		$pathPrefix="";
		$path = $parent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		if($curfolderlist != $pathPrefix) {
			$queryStr = "UPDATE tblFolders SET folderList='".$pathPrefix."' WHERE id = ". $this->_id;
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;
		}
		return true;
	} /* }}} */

}

?>
