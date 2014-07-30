<?php
define('USE_PHP_SESSION', 0);

include("../inc/inc.Settings.php");
require_once "SeedDMS/Core.php";

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);

if(USE_PHP_SESSION) {
	session_start();
	$userobj = null;
	if(isset($_SESSION['userid']))
		$userobj = $dms->getUser($_SESSION['userid']);
	elseif($settings->_enableGuestLogin)
		$userobj = $dms->getUser($settings->_guestID);
	else
		exit;
	$dms->setUser($userobj);
} else {
	require_once("../inc/inc.ClassSession.php");
	$session = new SeedDMS_Session($db);
	if (isset($_COOKIE["mydms_session"])) {
		$dms_session = $_COOKIE["mydms_session"];
		if(!$resArr = $session->load($dms_session)) {
			/* Delete Cookie */
			setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
			if($settings->_enableGuestLogin)
				$userobj = $dms->getUser($settings->_guestID);
			else
				exit;
		}

		/* Load user data */
		$userobj = $dms->getUser($resArr["userID"]);
		if (!is_object($userobj)) {
			/* Delete Cookie */
			setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
			if($settings->_enableGuestLogin)
				$userobj = $dms->getUser($settings->_guestID);
			else
				exit;
		}
		$dms->setUser($userobj);
	}
}


require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

function doLogin() { /* {{{ */
	global $app, $dms, $userobj, $session, $settings;

	$username = $app->request()->post('user');
	$password = $app->request()->post('pass');

	$userobj = $dms->getUserByLogin($username);
	if(!$userobj || md5($password) != $userobj->getPwd()) {
		if(USE_PHP_SESSION) {
			unset($_SESSION['userid']);
		} else {
			setcookie("mydms_session", $session->getId(), time()-3600, $settings->_httpRoot);
		}
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Login failed', 'data'=>''));
	} else {
		if(USE_PHP_SESSION) {
			$_SESSION['userid'] = $userobj->getId();
		} else {
			if(!$id = $session->create(array('userid'=>$userobj->getId(), 'theme'=>$userobj->getTheme(), 'lang'=>$userobj->getLanguage()))) {
				exit;
			}

			// Set the session cookie.
			if($settings->_cookieLifetime)
				$lifetime = time() + intval($settings->_cookieLifetime);
			else
				$lifetime = 0;
			setcookie("mydms_session", $id, $lifetime, $settings->_httpRoot);
			$dms->setUser($userobj);
		}
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$userobj->getId()));
	}
} /* }}} */

function doLogout() { /* {{{ */
	global $app, $dms, $userobj, $session, $settings;

	if(USE_PHP_SESSION) {
		unset($_SESSION['userid']);
	} else {
		setcookie("mydms_session", $session->getId(), time()-3600, $settings->_httpRoot);
	}
	$userobj = null;
	$app->response()->header('Content-Type', 'application/json');
	echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
} /* }}} */

function setFullName() { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}
	$userobj->setFullName($app->request()->put('fullname'));
	echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$userobj->getFullName()));
} /* }}} */

function setEmail($id) { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}
	$userobj->setEmail($app->request()->put('fullname'));
	echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$userid));
} /* }}} */

function getLockedDocuments() { /* {{{ */
	global $app, $dms, $userobj;

	if(false !== ($documents = $dms->getDocumentsLockedByUser($userobj))) {
		$documents = SeedDMS_Core_DMS::filterAccess($documents, $userobj, M_READ);
		foreach($documents as $document) {
			$lc = $document->getLatestContent();
			$recs[] = array(
				'type'=>'document',
				'id'=>$document->getId(),
				'date'=>$document->getDate(),
				'name'=>$document->getName(),
				'mimetype'=>$lc->getMimeType(),
				'version'=>$lc->getVersion(),
				'comment'=>$document->getComment(),
				'keywords'=>$document->getKeywords(),
			);
		}
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'', 'data'=>''));
	}
} /* }}} */

function getFolder($id) { /* {{{ */
	global $app, $dms, $userobj;
	$forcebyname = $app->request()->get('forcebyname');
	if(is_numeric($id) && empty($forcebyname))
		$folder = $dms->getFolder($id);
	else {
		$parentid = $app->request()->get('parentid');
		$folder = $dms->getFolderByName($id, $parentid);
	}
	if($folder) {
		if($folder->getAccessMode($userobj) >= M_READ) {
			$app->response()->header('Content-Type', 'application/json');
			$data = array(
				'id'=>$folder->getID(),
				'name'=>$folder->getName()
			);
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$data));
		} else {
			$app->response()->status(404);
		}
	} else {
		$app->response()->status(404);
	}
} /* }}} */

function getFolderParent($id) { /* {{{ */
	global $app, $dms, $userobj;
	if($id == 0) {
		echo json_encode(array('success'=>true, 'message'=>'id is 0', 'data'=>''));
		return;
	}
	$root = $dms->getRootFolder();
	if($root->getId() == $id) {
		echo json_encode(array('success'=>true, 'message'=>'id is root folder', 'data'=>''));
		return;
	}
	$folder = $dms->getFolder($id);
	$parent = $folder->getParent();
	if($parent) {
		$rec = array('type'=>'folder', 'id'=>$parent->getId(), 'name'=>$parent->getName());
		echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$rec));
	} else {
		echo json_encode(array('success'=>false, 'message'=>'', 'data'=>''));
	}
} /* }}} */

function getFolderPath($id) { /* {{{ */
	global $app, $dms, $userobj;
	if($id == 0) {
		echo json_encode(array('success'=>true, 'message'=>'id is 0', 'data'=>''));
		return;
	}
	$folder = $dms->getFolder($id);

	$path = $folder->getPath();
	$data = array();
	foreach($path as $element) {
		$data[] = array('id'=>$element->getId(), 'name'=>htmlspecialchars($element->getName()));
	}
	echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$data));
} /* }}} */

function getFolderChildren($id) { /* {{{ */
	global $app, $dms, $userobj;
	if($id == 0) {
		$folder = $dms->getRootFolder();
		$recs = array(array('type'=>'folder', 'id'=>$folder->getId(), 'name'=>$folder->getName()));
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
	} else {
		$folder = $dms->getFolder($id);
		if($folder) {
			if($folder->getAccessMode($userobj) >= M_READ) {
				$recs = array();
				$subfolders = $folder->getSubFolders();
				$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $userobj, M_READ);
				foreach($subfolders as $subfolder) {
					$recs[] = array(
						'type'=>'folder',
						'id'=>$subfolder->getId(),
						'name'=>htmlspecialchars($subfolder->getName()),
						'comment'=>$subfolder->getComment(),
						'date'=>$subfolder->getDate(),
					);
				}
				$documents = $folder->getDocuments();
				$documents = SeedDMS_Core_DMS::filterAccess($documents, $userobj, M_READ);
				foreach($documents as $document) {
					$lc = $document->getLatestContent();
					if($lc) {
						$recs[] = array(
							'type'=>'document',
							'id'=>$document->getId(),
							'date'=>$document->getDate(),
							'name'=>htmlspecialchars($document->getName()),
							'mimetype'=>$lc->getMimeType(),
							'version'=>$lc->getVersion(),
							'comment'=>$document->getComment(),
							'keywords'=>$document->getKeywords(),
						);
					}
				}
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
			}
		} else {
			$app->response()->status(404);
		}
	}
} /* }}} */

function createFolder($id) { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}

	if($id == 0) {
		echo json_encode(array('success'=>true, 'message'=>'id is 0', 'data'=>''));
		return;
	}
	$parent = $dms->getFolder($id);
	if($parent) {
		if($name = $app->request()->post('name')) {
			$comment = $app->request()->post('comment');
			$attributes = $app->request()->post('attributes');
			$newattrs = array();
			foreach($attributes as $attrname=>$attrvalue) {
				$attrdef = $dms->getAttributeDefinitionByName($attrname);
				if($attrdef) {
					$newattrs[$attrdef->getID()] = $attrvalue;
				}
			}
			if($folder = $parent->addSubFolder($name, $comment, $userobj, 0, $newattrs)) {

				$rec = array('id'=>$folder->getId(), 'name'=>$folder->getName(), 'comment'=>$folder->getComment());
				echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$rec));
			} else {
				echo json_encode(array('success'=>false, 'message'=>'', 'data'=>''));
			}
		} else {
			echo json_encode(array('success'=>false, 'message'=>'', 'data'=>''));
		}
	} else {
		echo json_encode(array('success'=>false, 'message'=>'', 'data'=>''));
	}
} /* }}} */

function moveFolder($id) { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}

	$mfolder = $dms->getFolder($id);
	if($mfolder) {
		if ($mfolder->getAccessMode($userobj) >= M_READ) {
			$folderid = $app->request()->post('dest');
			if($folder = $dms->getFolder($folderid)) {
				if($folder->getAccessMode($userobj) >= M_READWRITE) {
					if($mfolder->setParent($folder)) {
						$app->response()->header('Content-Type', 'application/json');
						echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
					} else {
						$app->response()->header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
					}
				} else {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
				}
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
			}
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
	}
} /* }}} */

function deleteFolder($id) { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}

	if($id == 0) {
		echo json_encode(array('success'=>true, 'message'=>'id is 0', 'data'=>''));
		return;
	}
	$mfolder = $dms->getFolder($id);
	if($mfolder) {
		if ($mfolder->getAccessMode($userobj) >= M_READWRITE) {
			if($mfolder->remove()) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'Error deleting folder', 'data'=>''));
			}
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
	}
} /* }}} */

function uploadDocument($id) { /* {{{ */
	global $app, $dms, $userobj;

	if(!$userobj) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}

	if($id == 0) {
		echo json_encode(array('success'=>true, 'message'=>'id is 0', 'data'=>''));
		return;
	}
	$mfolder = $dms->getFolder($id);
	if($mfolder) {
		if ($mfolder->getAccessMode($userobj) >= M_READWRITE) {
			$docname = $app->request()->get('name');
			$origfilename = $app->request()->get('origfilename');
			$content = $app->getInstance()->request()->getBody();
			$temp = tempnam('/tmp', 'lajflk');
			$handle = fopen($temp, "w");
			fwrite($handle, $content);
			fclose($handle);
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$userfiletype = finfo_file($finfo, $temp);
			finfo_close($finfo);
			$res = $mfolder->addDocument($docname, '', 0, $userobj, '', array(), $temp, $origfilename ? $origfilename : basename($temp), '.', $userfiletype, 0);
			unlink($temp);
			if($res) {
				$doc = $res[0];
				$rec = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>true, 'message'=>'Upload succeded', 'data'=>$rec));
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'Upload failed', 'data'=>''));
			}
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
	}
} /* }}} */

function getDocument($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);
	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$lc = $document->getLatestContent();
			$app->response()->header('Content-Type', 'application/json');
			$data = array(
				'id'=>$id,
				'name'=>htmlspecialchars($document->getName()),
				'comment'=>htmlspecialchars($document->getComment()),
				'date'=>$document->getDate(),
				'mimetype'=>$lc->getMimeType(),
				'version'=>$lc->getVersion(),
				'keywords'=>htmlspecialchars($document->getKeywords()),
			);
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$data));
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
	}
} /* }}} */

function deleteDocument($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);
	if($document) {
		if ($document->getAccessMode($userobj) >= M_READWRITE) {
			if($document->remove()) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'Error removing document', 'data'=>''));
			}
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
	}
} /* }}} */

function moveDocument($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);
	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$folderid = $app->request()->post('dest');
			if($folder = $dms->getFolder($folderid)) {
				if($folder->getAccessMode($userobj) >= M_READWRITE) {
					if($document->setFolder($folder)) {
						$app->response()->header('Content-Type', 'application/json');
						echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
					} else {
						$app->response()->header('Content-Type', 'application/json');
						echo json_encode(array('success'=>false, 'message'=>'Error moving document', 'data'=>''));
					}
				} else {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
				}
			} else {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
			}
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
	}
} /* }}} */

function getDocumentContent($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$lc = $document->getLatestContent();
			$app->response()->header('Content-Type', $lc->getMimeType());
			$app->response()->header("Content-Disposition: filename=\"" . $document->getName().$lc->getFileType() . "\"");
			$app->response()->header("Content-Length: " . filesize($dms->contentDir . $lc->getPath()));
			$app->response()->header("Expires: 0");
			$app->response()->header("Cache-Control: no-cache, must-revalidate");
			$app->response()->header("Pragma: no-cache");

			readfile($dms->contentDir . $lc->getPath());
		} else {
			$app->response()->status(404);
		}
	}

} /* }}} */

function getDocumentVersions($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$recs = array();
			$lcs = $document->getContent();
			foreach($lcs as $lc) {
				$recs[] = array(
					'version'=>$lc->getVersion(),
					'date'=>$lc->getDate(),
					'mimetype'=>$lc->getMimeType(),
					'comment'=>htmlspecialchars($lc->getComment()),
				);
			}
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
		} else {
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
		}
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'No such document', 'data'=>''));
	}
} /* }}} */

function getDocumentVersion($id, $version) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$lc = $document->getContentByVersion($version);
			$app->response()->header('Content-Type', $lc->getMimeType());
			$app->response()->header("Content-Disposition: filename=\"" . $document->getName().$lc->getFileType() . "\"");
			$app->response()->header("Content-Length: " . filesize($dms->contentDir . $lc->getPath()));
			$app->response()->header("Expires: 0");
			$app->response()->header("Cache-Control: no-cache, must-revalidate");
			$app->response()->header("Pragma: no-cache");

			readfile($dms->contentDir . $lc->getPath());
		} else {
			$app->response()->status(404);
		}
	}
} /* }}} */

function getDocumentFiles($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$recs = array();
			$files = $document->getDocumentFiles();
			foreach($files as $file) {
				$recs[] = array(
					'id'=>$file->getId(),
					'name'=>$file->getName(),
					'date'=>$file->getDate(),
					'mimetype'=>$file->getMimeType(),
					'comment'=>$file->getComment(),
				);
			}
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
		} else {
			$app->response()->status(404);
		}
	}
} /* }}} */

function getDocumentFile($id, $fileid) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$file = $document->getDocumentFile($fileid);
			$app->response()->header('Content-Type', $file->getMimeType());
			$app->response()->header("Content-Disposition: filename=\"" . $document->getName().$file->getFileType() . "\"");
			$app->response()->header("Content-Length: " . filesize($dms->contentDir . $file->getPath()));
			$app->response()->header("Expires: 0");
			$app->response()->header("Cache-Control: no-cache, must-revalidate");
			$app->response()->header("Pragma: no-cache");

			readfile($dms->contentDir . $file->getPath());
		} else {
			$app->response()->status(404);
		}
	}
} /* }}} */

function getDocumentLinks($id) { /* {{{ */
	global $app, $dms, $userobj;
	$document = $dms->getDocument($id);

	if($document) {
		if ($document->getAccessMode($userobj) >= M_READ) {
			$recs = array();
			$links = $document->getDocumentLinks();
			foreach($links as $link) {
				$recs[] = array(
					'id'=>$link->getId(),
					'target'=>$link->getTarget(),
					'public'=>$link->isPublic(),
				);
			}
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
		} else {
			$app->response()->status(404);
		}
	}
} /* }}} */

function getAccount() { /* {{{ */
	global $app, $dms, $userobj;
	if($userobj) {
		$account = array();
		$account['id'] = $userobj->getId();
		$account['login'] = $userobj->getLogin();
		$account['fullname'] = $userobj->getFullName();
		$account['email'] = $userobj->getEmail();
		$account['language'] = $userobj->getLanguage();
		$account['theme'] = $userobj->getTheme();
		$account['role'] = $userobj->getRole();
		$account['comment'] = $userobj->getComment();
		$account['isguest'] = $userobj->isGuest();
		$account['isadmin'] = $userobj->isAdmin();
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$account));
	} else {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(array('success'=>false, 'message'=>'Not logged in', 'data'=>''));
	}
} /* }}} */

/**
 * Search for documents in the database
 *
 * If the request parameter 'mode' is set to 'typeahead', it will
 * return a list of words only.
 */
function doSearch() { /* {{{ */
	global $app, $dms, $userobj;

	$querystr = $app->request()->get('query');
	$mode = $app->request()->get('mode');
	if(!$limit = $app->request()->get('limit'))
		$limit = 50;
	$resArr = $dms->search($querystr, $limit);
	$entries = array();
	if($resArr['folders']) {
		foreach ($resArr['folders'] as $entry) {
			if ($entry->getAccessMode($userobj) >= M_READ) {
				$entries[] = $entry;
			}
		}
	}
	if($resArr['docs']) {
		foreach ($resArr['docs'] as $entry) {
			if ($entry->getAccessMode($userobj) >= M_READ) {
				$entries[] = $entry;
			}
		}
	}

	switch($mode) {
		case 'typeahead';
			$recs = array();
			foreach ($entries as $entry) {
			/* Passing anything back but a string does not work, because
			 * the process function of bootstrap.typeahead needs an array of
			 * strings.
			 *
			 * As a quick solution to distingish folders from documents, the
			 * name will be preceeded by a 'F' or 'D'

				$tmp = array();
				if(get_class($entry) == 'SeedDMS_Core_Document') {
					$tmp['type'] = 'folder';
				} else {
					$tmp['type'] = 'document';
				}
				$tmp['id'] = $entry->getID();
				$tmp['name'] = $entry->getName();
				$tmp['comment'] = $entry->getComment();
			 */
				if(get_class($entry) == 'SeedDMS_Core_Document') {
					$recs[] = 'D'.$entry->getName();
				} else {
					$recs[] = 'F'.$entry->getName();
				}
			}
			if($recs)
//				array_unshift($recs, array('type'=>'', 'id'=>0, 'name'=>$querystr, 'comment'=>''));
				array_unshift($recs, ' '.$querystr);
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode($recs);
			//echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
			break;
		default:
			$recs = array();
			foreach ($entries as $entry) {
				if(get_class($entry) == 'SeedDMS_Core_Document') {
					$document = $entry;
					$lc = $document->getLatestContent();
					$recs[] = array(
						'type'=>'document',
						'id'=>$document->getId(),
						'date'=>$document->getDate(),
						'name'=>$document->getName(),
						'mimetype'=>$lc->getMimeType(),
						'version'=>$lc->getVersion(),
						'comment'=>$document->getComment(),
						'keywords'=>$document->getKeywords(),
					);
				} elseif(get_class($entry) == 'SeedDMS_Core_Folder') {
					$folder = $entry;
					$recs[] = array(
						'type'=>'folder',
						'id'=>$folder->getId(),
						'name'=>$folder->getName(),
						'comment'=>$folder->getComment(),
						'date'=>$folder->getDate(),
					);
				}
			}
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
			break;
	}
} /* }}} */

/**
 * Search for documents/folders with a given attribute=value
 *
 */
function doSearchByAttr() { /* {{{ */
	global $app, $dms, $userobj;

	$attrname = $app->request()->get('name');
	$query = $app->request()->get('value');
	if(!$limit = $app->request()->get('limit'))
		$limit = 50;
	$attrdef = $dms->getAttributeDefinitionByName($attrname);
	$entries = array();
	if($attrdef) {
		$resArr = $attrdef->getObjects($query, $limit);
		if($resArr['folders']) {
			foreach ($resArr['folders'] as $entry) {
				if ($entry->getAccessMode($userobj) >= M_READ) {
					$entries[] = $entry;
				}
			}
		}
		if($resArr['docs']) {
			foreach ($resArr['docs'] as $entry) {
				if ($entry->getAccessMode($userobj) >= M_READ) {
					$entries[] = $entry;
				}
			}
		}
	}
	$recs = array();
	foreach ($entries as $entry) {
		if(get_class($entry) == 'SeedDMS_Core_Document') {
			$document = $entry;
			$lc = $document->getLatestContent();
			$recs[] = array(
				'type'=>'document',
				'id'=>$document->getId(),
				'date'=>$document->getDate(),
				'name'=>$document->getName(),
				'mimetype'=>$lc->getMimeType(),
				'version'=>$lc->getVersion(),
				'comment'=>$document->getComment(),
				'keywords'=>$document->getKeywords(),
			);
		} elseif(get_class($entry) == 'SeedDMS_Core_Folder') {
			$folder = $entry;
			$recs[] = array(
				'type'=>'folder',
				'id'=>$folder->getId(),
				'name'=>$folder->getName(),
				'comment'=>$folder->getComment(),
				'date'=>$folder->getDate(),
			);
		}
	}
	$app->response()->header('Content-Type', 'application/json');
	echo json_encode(array('success'=>true, 'message'=>'', 'data'=>$recs));
} /* }}} */

//$app = new Slim(array('mode'=>'development', '_session.handler'=>null));
$app = new \Slim\Slim(array('mode'=>'development', '_session.handler'=>null));

$app->configureMode('production', function () use ($app) {
	$app->config(array(
		'log.enable' => true,
		'log.path' => '/tmp/',
		'debug' => false
	));
});

$app->configureMode('development', function () use ($app) {
	$app->config(array(
		'log.enable' => false,
		'debug' => true
	));
});

// use post for create operation
// use get for retrieval operation
// use put for update operation
// use delete for delete operation
$app->post('/login', 'doLogin');
$app->get('/logout', 'doLogout');
$app->get('/account', 'getAccount');
$app->get('/search', 'doSearch');
$app->get('/searchbyattr', 'doSearchByAttr');
$app->get('/folder/:id', 'getFolder');
$app->post('/folder/:id/move', 'moveFolder');
$app->delete('/folder/:id', 'deleteFolder');
$app->get('/folder/:id/children', 'getFolderChildren');
$app->get('/folder/:id/parent', 'getFolderParent');
$app->get('/folder/:id/path', 'getFolderPath');
$app->post('/folder/:id/createfolder', 'createFolder');
$app->put('/folder/:id/document', 'uploadDocument');
$app->get('/document/:id', 'getDocument');
$app->delete('/document/:id', 'deleteDocument');
$app->post('/document/:id/move', 'moveDocument');
$app->get('/document/:id/content', 'getDocumentContent');
$app->get('/document/:id/versions', 'getDocumentVersions');
$app->get('/document/:id/version/:version', 'getDocumentVersion');
$app->get('/document/:id/files', 'getDocumentFiles');
$app->get('/document/:id/file/:fileid', 'getDocumentFile');
$app->get('/document/:id/links', 'getDocumentLinks');
$app->put('/account/fullname', 'setFullName');
$app->put('/account/email', 'setEmail');
$app->get('/account/locked', 'getLockedDocuments');
$app->run();

?>
