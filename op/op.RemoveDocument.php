<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");
include("../inc/inc.Extension.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1]);

/* Check if the form data comes for a trusted request */
if(!checkFormKey('removedocument')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

if($settings->_enableFullSearch) {
	if(!empty($settings->_luceneClassDir))
		require_once($settings->_luceneClassDir.'/Lucene.php');
	else
		require_once('SeedDMS/Lucene.php');

	$index = SeedDMS_Lucene_Indexer::open($settings->_luceneDir);
} else {
	$index = null;
}

/* save this for notification later on */
$nl =	$document->getNotifyList();
$folder = $document->getFolder();
$docname = $document->getName();

$controller->setParam('document', $document);
$controller->setParam('index', $index);
if(!$controller->run()) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("error_occured"));
}

if ($notifier){
	$subject = "document_deleted_email_subject";
	$message = "document_deleted_email_body";
	$params = array();
	$params['name'] = $docname;
	$params['folder_path'] = $folder->getFolderPathPlain();
	$params['username'] = $user->getFullName();
	$params['sitename'] = $settings->_siteName;
	$params['http_root'] = $settings->_httpRoot;
	$notifier->toList($user, $nl["users"], $subject, $message, $params);
	foreach ($nl["groups"] as $grp) {
		$notifier->toGroup($user, $grp, $subject, $message, $params);
	}
}

add_log_line("?documentid=".$documentid);

header("Location:../out/out.ViewFolder.php?folderid=".$folder->getID());

?>
