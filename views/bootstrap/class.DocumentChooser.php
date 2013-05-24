<?php
/**
 * Implementation of DocumentChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for DocumentChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DocumentChooser extends SeedDMS_Bootstrap_Style {
	var $user;
	var $form;

	function printTree($path, $level = 0) { /* {{{ */
		$folder = $path[$level];
		$subFolders = SeedDMS_Core_DMS::filterAccess($folder->getSubFolders(), $this->user, M_READ);
		$documents  = SeedDMS_Core_DMS::filterAccess($folder->getDocuments(), $this->user, M_READ);
		
		if ($level+1 < count($path))
			$nextFolderID = $path[$level+1]->getID();
		else
			$nextFolderID = -1;

		if ($level == 0) {
			print "<ul style='list-style-type: none;'>\n";
		}
		print "  <li>\n";
		print "<i class=\"";
		if ($level == 0) echo "icon-minus-sign";
		else if (count($subFolders) + count($documents) > 0) echo "icon-minus-sign";
		else $this->printImgPath("blank.png");
		print "\"></i>\n";
		if ($folder->getAccessMode($this->user) >= M_READ) {
			print "<i class=\"icon-folder-open\"></i> ".htmlspecialchars($folder->getName())."\n";
		} else
			print "<i class=\"icon-folder-open\"></i> ".htmlspecialchars($folder->getName())."\n";
		print "  </li>\n";

		print "<ul style='list-style-type: none;'>";

		for ($i = 0; $i < count($subFolders); $i++) {
			if ($subFolders[$i]->getID() == $nextFolderID)
				$this->printTree($path, $level+1);
			else {
				print "<li>\n";
				$subFolders_ = SeedDMS_Core_DMS::filterAccess($subFolders[$i]->getSubFolders(), $this->user, M_READ);
				$documents_  = SeedDMS_Core_DMS::filterAccess($subFolders[$i]->getDocuments(), $this->user, M_READ);
				
				if (count($subFolders_) + count($documents_) > 0)
					print "<a href=\"out.DocumentChooser.php?form=".$this->form."&folderid=".$subFolders[$i]->getID()."\"><i class='icon-plus-sign'></i></a> ";
				else
					print "<i class='icon-circle'></i> ";
				print "<i class=\"icon-folder-close\"></i> ".htmlspecialchars($subFolders[$i]->getName())."\n";
				print "</li>";
			}
		}
		for ($i = 0; $i < count($documents); $i++) {
			print "<li>\n";
			print "<i class='icon-circle'></i> ";
			print "<i class=\"icon-file\"></i> <a class=\"foldertree_selectable\" href=\"javascript:documentSelected(".$documents[$i]->getID().",'".str_replace("'", "\\'", htmlspecialchars($documents[$i]->getName()))."');\">".htmlspecialchars($documents[$i]->getName())."</a>";
			print "</li>";
		}

		print "</ul>\n";
		if ($level == 0) {
			print "</ul>\n";
		}
		
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$this->user = $this->params['user'];
		$folder = $this->params['folder'];
		$this->form = $this->params['form'];

		$this->htmlStartPage(getMLText("choose_target_document"));
		$this->contentContainerStart();
		$this->printNewTreeNavigation($folderid, $showtree, 1);
		$this->printTree($folder->getPath());
		$this->contentContainerEnd();
		echo "</body>\n</html>\n";
	} /* }}} */
}
?>
