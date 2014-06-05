<?php
//    MyDMS. Document Management System
//    Copyright (C) 2012 Uwe Steinmann
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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");

require_once("../inc/inc.Utils.php");
require_once("../inc/inc.ClassSession.php");
include("../inc/inc.ClassPasswordStrength.php");
include("../inc/inc.ClassPasswordHistoryManager.php");

/* Load session */
if (isset($_COOKIE["mydms_session"])) {
	$dms_session = $_COOKIE["mydms_session"];
	$session = new SeedDMS_Session($db);
	if(!$resArr = $session->load($dms_session)) {
		echo json_encode(array('error'=>1));
		exit;
	}

	/* Update last access time */
	$session->updateAccess($dms_session);

	/* Load user data */
	$user = $dms->getUser($resArr["userID"]);
	if (!is_object($user)) {
		echo json_encode(array('error'=>1));
		exit;
	}
	$dms->setUser($user);
	if($user->isAdmin()) {
		if($resArr["su"]) {
			$user = $dms->getUser($resArr["su"]);
		}
	}
	include $settings->_rootDir . "languages/" . $resArr["language"] . "/lang.inc";
} else {
	$user = null;
}

$command = $_REQUEST["command"];
switch($command) {
	case 'checkpwstrength': /* {{{ */
		$ps = new Password_Strength();
		$ps->set_password($_REQUEST["pwd"]);
		if($settings->_passwordStrengthAlgorithm == 'simple')
			$ps->simple_calculate();
		else
			$ps->calculate();
		$score = $ps->get_score();
		if($settings->_passwordStrength) {
			if($score >= $settings->_passwordStrength) {
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>1));
			} else {
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>0));
			}
		} else {
			echo json_encode(array('error'=>0, 'strength'=>$score));
		}
		break; /* }}} */

	case 'sessioninfo': /* {{{ */
		if($user) {
			echo json_encode($resArr);
		}	
		break; /* }}} */

	case 'searchdocument': /* {{{ */
		if($user) {
			$query = $_GET['query'];

			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x1, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['docs'] as $hit) {
					$result[] = $hit->getID().'#'.$hit->getName();
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

	case 'searchfolder': /* {{{ */
		if($user) {
			$query = $_GET['query'];

			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x2, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['folders'] as $hit) {
					$result[] = $hit->getID().'#'.$hit->getName();
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

	case 'subtree': /* {{{ */
		if($user) {
			if(empty($_GET['node']))
				$nodeid = $settings->_rootFolderID;
			else
				$nodeid = (int) $_GET['node'];
			if(empty($_GET['showdocs']))
				$showdocs = false;
			else
				$showdocs = true;
			if(empty($_GET['orderby']))
				$orderby = $settings->_sortFoldersDefault;
			else
				$orderby = $_GET['orderby'];

			$folder = $dms->getFolder($nodeid);
			if (!is_object($folder)) return '';
			
			$subfolders = $folder->getSubFolders($orderby);
			$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, M_READ);
			$tree = array();
			foreach($subfolders as $subfolder) {
				$loadondemand = $subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs);
				$level = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>$loadondemand, 'is_folder'=>true);
				if(!$subfolder->hasSubFolders())
					$level['children'] = array();
				$tree[] = $level;
			}
			if($showdocs) {
				$documents = $folder->getDocuments($orderby);
				$documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);
				foreach($documents as $document) {
					$level = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
					$tree[] = $level;
				}
			}

			echo json_encode($tree);
	//		echo json_encode(array(array('label'=>'test1', 'id'=>1, 'load_on_demand'=> true), array('label'=>'test2', 'id'=>2, 'load_on_demand'=> true)));
		}
		break; /* }}} */

	case 'addtoclipboard': /* {{{ */
		if($user) {
			if (isset($_GET["id"]) && is_numeric($_GET["id"]) && isset($_GET['type'])) {
				switch($_GET['type']) {
					case "folder":
						$session->addToClipboard($dms->getFolder($_GET['id']));
						break;
					case "document":
						$session->addToClipboard($dms->getDocument($_GET['id']));
						break;
				}
			}
			$view = UI::factory($theme, '', array('dms'=>$dms, 'user'=>$user));
			if($view) {
				$view->setParam('refferer', '');
				$content = $view->menuClipboard($session->getClipboard());
				header('Content-Type: application/json');
				echo json_encode($content);
			} else {
			}
		}
		break; /* }}} */

	case 'testmail': /* {{{ */
		if($user && $user->isAdmin()) {
			if($user->getEmail()) {
				include("../inc/inc.ClassEmail.php");

				$emailobj = new SeedDMS_Email($settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword);
				$params = array();

				if($emailobj->toIndividual($settings->_smtpSendFrom, $user, "testmail_subject", "testmail_body", $params)) {
					echo json_encode(array("error"=>0, "msg"=>"Sending email succeded"));
				} else {
					echo json_encode(array("error"=>1, "msg"=>"Sending email failed"));
				}
			} else {
				echo json_encode(array("error"=>1, "msg"=>"No email address"));
			}
		}
		break; /* }}} */

	case 'movefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('movefolder', 'GET')) {
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mfolder = $dms->getFolder($_REQUEST['folderid']);
				if($mfolder) {
					if ($mfolder->getAccessMode($user) >= M_READ) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user) >= M_READWRITE) {
								if($mfolder->setParent($folder)) {
									header('Content-Type', 'application/json');
									echo json_encode(array('success'=>true, 'message'=>'Folder moved', 'data'=>''));
								} else {
									header('Content-Type', 'application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
								}
							} else {
								header('Content-Type', 'application/json');
								echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
							}
						} else {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
						}
					} else {
						header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'movedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('movedocument', 'GET')) {
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mdocument = $dms->getDocument($_REQUEST['docid']);
				if($mdocument) {
					if ($mdocument->getAccessMode($user) >= M_READ) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user) >= M_READWRITE) {
								if($mdocument->setFolder($folder)) {
									header('Content-Type', 'application/json');
									echo json_encode(array('success'=>true, 'message'=>'Document moved', 'data'=>''));
								} else {
									header('Content-Type', 'application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
								}
							} else {
								header('Content-Type', 'application/json');
								echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
							}
						} else {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
						}
					} else {
						header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('removefolder', 'GET')) {
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$folder = $dms->getFolder($_REQUEST['id']);
				if($folder) {
					if ($folder->getAccessMode($user) >= M_READWRITE) {
						if($folder->remove()) {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''.$_REQUEST['formtoken']));
						} else {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error removing folder', 'data'=>''));
						}
					} else {
						header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('removedocument', 'GET')) {
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user) >= M_READWRITE) {
						if($document->remove()) {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''.$_REQUEST['formtoken']));
						} else {
							header('Content-Type', 'application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error removing document', 'data'=>''));
						}
					} else {
						header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'submittranslation': /* {{{ */
		if($settings->_showMissingTranslations) {
			if($user && !empty($_POST['phrase'])) {
				if($fp = fopen('/tmp/newtranslations.txt', 'a+')) {
					fputcsv($fp, array(date('Y-m-d H:i:s'), $user->getLogin(), $_POST['key'], $_POST['lang'], $_POST['phrase']));
					fclose($fp);
				}
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>true, 'message'=>'Thank you for your contribution', 'data'=>''));
			}	else {
				header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'Missing translation', 'data'=>''));
			}
		}
		break; /* }}} */

}
?>
