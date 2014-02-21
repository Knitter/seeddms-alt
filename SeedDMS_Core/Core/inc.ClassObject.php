<?php
/**
 * Implementation of an generic object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class to represent a generic object in the document management system
 *
 * This is the base class for generic objects in SeedDMS.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Object { /* {{{ */
	/**
	 * @var integer unique id of object
	 */
	protected $_id;

	/**
	 * @var array list of attributes
	 */
	protected $_attributes;

	/**
	 * @var object back reference to document management system
	 */
	public $_dms;

	function SeedDMS_Core_Object($id) { /* {{{ */
		$this->_id = $id;
		$this->_dms = null;
	} /* }}} */

	/*
	 * Set dms this object belongs to.
	 *
	 * Each object needs a reference to the dms it belongs to. It will be
	 * set when the object is created.
	 * The dms has a references to the currently logged in user
	 * and the database connection.
	 *
	 * @param object $dms reference to dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/*
	 * Return the internal id of the document
	 *
	 * @return integer id of document
	 */
	function getID() { return $this->_id; }

	/**
	 * Returns all attributes set for the object
	 *
	 * @return array list of objects of class SeedDMS_Core_Attribute
	 */
	function getAttributes() { /* {{{ */
		if (!$this->_attributes) {
			$db = $this->_dms->getDB();

			switch(get_class($this)) {
				case "SeedDMS_Core_Document":
					$queryStr = "SELECT * FROM tblDocumentAttributes WHERE document = " . $this->_id." ORDER BY `id`";
					break;
				case "SeedDMS_Core_DocumentContent":
					$queryStr = "SELECT * FROM tblDocumentContentAttributes WHERE content = " . $this->_id." ORDER BY `id`";
					break;
				case "SeedDMS_Core_Folder":
					$queryStr = "SELECT * FROM tblFolderAttributes WHERE folder = " . $this->_id." ORDER BY `id`";
					break;
				default:
					return false;
			}
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

			$this->_attributes = array();

			foreach ($resArr as $row) {
				$attrdef = $this->_dms->getAttributeDefinition($row['attrdef']);
				$attr = new SeedDMS_Core_Attribute($row["id"], $this, $attrdef, $row["value"]);
				$attr->setDMS($this->_dms);
				$this->_attributes[$attrdef->getId()] = $attr;
			}
		}
		return $this->_attributes;

	} /* }}} */

	/**
	 * Returns an attribute of the object for the given attribute definition
	 *
	 * @return array|string value of attritbute or false. The value is an array
	 * if the attribute is defined as multi value
	 */
	function getAttributeValue($attrdef) { /* {{{ */
		if (!$this->_attributes) {
			$this->getAttributes();
		}

		if (isset($this->_attributes[$attrdef->getId()])) {
			$value =  $this->_attributes[$attrdef->getId()]->getValue();
			if($attrdef->getMultipleValues()) {
				$sep = substr($value, 0, 1);
				return(explode($sep, substr($value, 1)));
			} else {
				return $value;
			}
		} else
			return false;

	} /* }}} */

	/**
	 * Set an attribute of the object for the given attribute definition
	 *
	 * @param object $attrdef definition of attribute
	 * @param array|sting $value value of attribute, for multiple values this
	 * must be an array
	 * @return boolean true if operation was successful, otherwise false
	 */
	function setAttributeValue($attrdef, $value) { /* {{{ */
		$db = $this->_dms->getDB();
		if (!$this->_attributes) {
			$this->getAttributes();
		}
		if($attrdef->getMultipleValues() && is_array($value)) {
			$sep = substr($attrdef->getValueSet(), 0, 1);
			$value = $sep.implode($sep, $value);
		}
		if(!isset($this->_attributes[$attrdef->getId()])) {
			switch(get_class($this)) {
				case "SeedDMS_Core_Document":
					$queryStr = "INSERT INTO tblDocumentAttributes (document, attrdef, value) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				case "SeedDMS_Core_DocumentContent":
					$queryStr = "INSERT INTO tblDocumentContentAttributes (content, attrdef, value) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				case "SeedDMS_Core_Folder":
					$queryStr = "INSERT INTO tblFolderAttributes (folder, attrdef, value) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				default:
					return false;
			}
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;

			$attr = new SeedDMS_Core_Attribute($db->getInsertID(), $this, $attrdef, $value);
			$attr->setDMS($this->_dms);
			$this->_attributes[$attrdef->getId()] = $attr;
			return true;
		}

		$this->_attributes[$attrdef->getId()]->setValue($value);

		return true;
	} /* }}} */

	/**
	 * Remove an attribute of the object for the given attribute definition
	 *
	 * @return boolean true if operation was successful, otherwise false
	 */
	function removeAttribute($attrdef) { /* {{{ */
		$db = $this->_dms->getDB();
		if (!$this->_attributes) {
			$this->getAttributes();
		}
		if(isset($this->_attributes[$attrdef->getId()])) {
			switch(get_class($this)) {
				case "SeedDMS_Core_Document":
					$queryStr = "DELETE FROM tblDocumentAttributes WHERE document=".$this->_id." AND attrdef=".$attrdef->getId();
					break;
				case "SeedDMS_Core_DocumentContent":
					$queryStr = "DELETE FROM tblDocumentContentAttributes WHERE content=".$this->_id." AND attrdef=".$attrdef->getId();
					break;
				case "SeedDMS_Core_Folder":
					$queryStr = "DELETE FROM tblFolderAttributes WHERE folder=".$this->_id." AND attrdef=".$attrdef->getId();
					break;
				default:
					return false;
			}
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;

			unset($this->_attributes[$attrdef->getId()]);
		}
		return true;
	} /* }}} */
} /* }}} */
?>
