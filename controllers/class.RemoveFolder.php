<?php
/**
 * Implementation of RemoveFolder controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_RemoveFolder extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$folder = $this->params['folder'];
		$index = $this->params['index'];

		/* Get the document id and name before removing the document */
		$foldername = $folder->getName();
		$folderid = $folder->getID();

		if(!$this->callHook('preRemoveFolder')) {
		}

		$result = $this->callHook('removeFolder', $folder);
		if($result === null) {
			/* Register a callback which removes each document from the fulltext index
			 * The callback must return true other the removal will be canceled.
			 */
			if($settings->_enableFullSearch) {
				if(!empty($settings->_luceneClassDir))
					require_once($settings->_luceneClassDir.'/Lucene.php');
				else
					require_once('SeedDMS/Lucene.php');

				$index = SeedDMS_Lucene_Indexer::open($settings->_luceneDir);
				function removeFromIndex($index, $document) {
					if($hits = $index->find('document_id:'.$document->getId())) {
						$hit = $hits[0];
						$index->delete($hit->id);
						$index->commit();
					}
					return true;
				}
				$dms->setCallback('onPreRemoveDocument', 'removeFromIndex', $index);
			}
			if (!$folder->remove()) {
				return false;
			} else {

				if(!$this->callHook('postRemoveFolder')) {
				}

			}
		} else
			return $result;

		return true;
	}
}
