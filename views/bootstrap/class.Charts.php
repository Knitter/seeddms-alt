<?php
/**
 * Implementation of Charts view
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
 * Class which outputs the html page for Charts view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Charts extends SeedDMS_Bootstrap_Style {
		var $dms;
		var $folder_count;
		var $document_count;
		var $file_count;
		var $storage_size;

	function show() { /* {{{ */
		$this->dms = $this->params['dms'];
		$user = $this->params['user'];
		$rootfolder = $this->params['rootfolder'];
		$data = $this->params['data'];
		$type = $this->params['type'];

		$this->htmlAddHeader(
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.min.js"></script>'."\n".
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.pie.min.js"></script>'."\n");

		$this->htmlStartPage(getMLText("folders_and_documents_statistic"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

?>

<?php

echo "<div class=\"row-fluid\">\n";
echo "<div class=\"span4\">\n";
$this->contentHeading(getMLText("chart_selection"));
echo "<div class=\"well\">\n";
foreach(array('docsperuser', 'sizeperuser', 'docspermimetype', 'docspercategory') as $atype) {
	echo "<div><a href=\"?type=".$atype."\">".$atype."</a></div>\n";
}
echo "</div>\n";
echo "</div>\n";

echo "<div class=\"span8\">\n";
$this->contentHeading(getMLText('chart_'.$type.'_title'));
echo "<div class=\"well\">\n";

?>
	<div id="chart" style="height: 300px;" class="chart"></div>
	<script type="text/javascript">
		var data = [
<?php
foreach($data as $rec) {
//$user = $this->dms->getUser($rec['key']);
	echo '{ label: "'.$rec['key'].'", data: [[1,'.$rec['total'].']]},'."\n";
}
?>
		];
		$.plot('#chart', data, {
			series: {
				pie: { 
					show: true,
					radius: 1,
					label: {
						show: true,
						radius: 2/3,
						formatter: labelFormatter,
						threshold: 0.1,
						background: {
							opacity: 0.8
						}
					}
				}
			}
		});
	function labelFormatter(label, series) {
	console.log(series);
		return "<div style='font-size:8pt; text-align:center; padding:2px; color:black; background: white;'>" + label + "<br/>" + series.data[0][1] + " (" + Math.round(series.percent) + "%)</div>";
	}
	</script>
<?php
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";


$this->contentContainerEnd();
$this->htmlEndPage();
	} /* }}} */
}
?>
