<?php
/**
 * Implementation of preview documents
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing creation of preview images for documents.
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Preview_Previewer {
	/**
	 * @var string $cacheDir location in the file system where all the
	 *      cached data like thumbnails are located. This should be an
	 *      absolute path.
	 * @access public
	 */
	public $previewDir;

	/**
	 * @var integer $width maximum width/height of resized image
	 * @access protected
	 */
	protected $width;

	function __construct($previewDir, $width=40) {
		if(!is_dir($previewDir)) {
			if (!SeedDMS_Core_File::makeDir($previewDir)) {
				$this->previewDir = '';
			} else {
				$this->previewDir = $previewDir;
			}
		} else {
			$this->previewDir = $previewDir;
		}
		$this->width = intval($width);
	}

	/**
	 * Retrieve the physical filename of the preview image on disk
	 *
	 * @param object $object document content or document file
	 * @param integer $width width of preview image
	 * @return string file name of preview image
	 */
	protected function getFileName($object, $width) { /* }}} */
		$document = $object->getDocument();
		$dir = $this->previewDir.'/'.$document->getDir();
		switch(get_class($object)) {
			case "SeedDMS_Core_DocumentContent":
				$target = $dir.'p'.$object->getVersion().'-'.$width.'.png';
				break;
			case "SeedDMS_Core_DocumentFile":
				$target = $dir.'f'.$object->getID().'-'.$width.'.png';
				break;
			default:
				return false;
		}
		return $target;
	} /* }}} */

	public function createPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$document = $object->getDocument();
		$dir = $this->previewDir.'/'.$document->getDir();
		if(!is_dir($dir)) {
			if (!SeedDMS_Core_File::makeDir($dir)) {
				return false;
			}
		}
		$file = $document->_dms->contentDir.$object->getPath();
		if(!file_exists($file))
			return false;
		$target = $this->getFileName($object, $width);
		if($target !== false && !file_exists($target)) {
			$cmd = '';
			switch($object->getMimeType()) {
				case "image/png":
				case "image/gif":
				case "image/jpeg":
				case "image/jpg":
				case "image/svg+xml":
					$cmd = 'convert -resize '.$width.'x '.$file.' '.$target;
					break;
				case "application/pdf":
				case "application/postscript":
					$cmd = 'convert -density 18 -resize '.$width.'x '.$file.'[0] '.$target;
					break;
				case "text/plain":
					$cmd = 'convert -resize '.$width.'x '.$file.'[0] '.$target;
					break;
				case "application/x-compressed-tar":
					$cmd = 'tar tzvf '.$file.' | convert -resize '.$width.'x text:-[0] '.$target;
					break;
			}
			if($cmd) {
				exec($cmd);
			}
			return true;
		}
		return true;
			
	} /* }}} */

	public function hasPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$target = $this->getFileName($object, $width);
		if($target && file_exists($target)) {
			return true;
		}
		return false;
	} /* }}} */

	public function getPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
		if($target && file_exists($target)) {
			readfile($target);
		}
	} /* }}} */

	public function deletePreview($document, $object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
	} /* }}} */
}
?>
