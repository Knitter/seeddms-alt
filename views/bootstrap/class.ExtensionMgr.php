<?php
/**
 * Implementation of ExtensionMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for ExtensionMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ExtensionMgr extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$httproot = $this->params['httproot'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentContainerStart();
		echo "<table class=\"table table-condensed\">\n";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";	
		print "<th>".getMLText('name')."</th>\n";	
		print "<th>".getMLText('version')."</th>\n";	
		print "<th>".getMLText('author')."</th>\n";	
		print "</tr></thead>\n";
		foreach($GLOBALS['EXT_CONF'] as $extname=>$extconf) {
			echo "<tr>";
			echo "<td>";
			if($extconf['icon'])
				echo "<img src=\"".$httproot."ext/".$extname."/".$extconf['icon']."\">";
			echo "</td>";
			echo "<td>".$extconf['title']."<br /><small>".$extconf['description']."</small></td>";
			echo "<td>".$extconf['version']."<br /><small>".$extconf['releasedate']."</small></td>";
			echo "<td><a href=\"mailto:".$extconf['author']['email']."\">".$extconf['author']['name']."</a><br /><small>".$extconf['author']['company']."</small></td>";
			echo "</tr>\n";
		}
		echo "</table>\n";
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
