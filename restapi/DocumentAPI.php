<?php

include 'ExtendedAPI.php';

class DocumentAPI extends ExtendedAPI {

    /**
     * Handles an POST request to create/add new documents. It expects a multipart 
     * request where the details of the document are provided in a JSON encoded field
     * named "data" and one or more files are uploaded in a field called "file".
     * 
     * JSON Strcuture:
     * data: {
     *     name: <string, name of the document, required>,
     *     comment: <string, comment describing the document, optional>,
     *     keywords: <string, list of keywords, optional>,
     *     categories: <array of integers, list of categories, optional>,
     *     expires: <string, english parsable date, optional>,
     *     version: <integer, version number, defaults to 1, optional>,
     *     versionComment: <string, comment for this version, optional>,
     *     useDocumentComment: <integer|boolean, flag forcing the use of the doc comment in the version field, optional>,
     *     workflow: <integer, workflow type, optional>,
     *     folder: <integer, identifies the folder this document is to be added to, required>,
     *     attributes: <array, list of custom attribute values, optional>
     * }
     * 
     * @param type $dms
     * @param type $user
     * @param type $settings
     */
    public static function add($dms, $user, $settings) {
        //TODO: not handling, $attributes_version = array();

        $data = json_decode($_POST['data']);
        if (!$data || empty($data->name) || empty($data->folder) || empty($data->sequence)) {
            self::error(400, 'Missing required data.');
        }

        $folder = $dms->getFolder((int) $data->folder);
        if (!is_object($folder)) {
            self::error(400, 'Invalid folder ID.');
        }

        if ($folder->getAccessMode($user) < M_READWRITE) {
            self::error(400, 'Given folder is not writable by this user.');
        }

        if (checkQuota() < 0) {
            self::error(400, 'Quota limit reached.');
        }

        // Get the list of reviewers and approvers for this document.
        $reviewers = array('i' => array(), 'g' => array());
        $approvers = array('i' => array(), 'g' => array());

        // add mandatory reviewers/approvers
        $docAccess = $folder->getReadAccessList($settings->_enableAdminRevApp, $settings->_enableOwnerRevApp);
        foreach ($user->getMandatoryReviewers() as $r) {
            if ($r['reviewerUserID'] != 0) {
                foreach ($docAccess["users"] as $usr) {
                    if ($usr->getID() == $r['reviewerUserID']) {
                        $reviewers["i"][] = $r['reviewerUserID'];
                        break;
                    }
                }
            } else if ($r['reviewerGroupID'] != 0) {
                foreach ($docAccess["groups"] as $grp) {
                    if ($grp->getID() == $r['reviewerGroupID']) {
                        $reviewers["g"][] = $r['reviewerGroupID'];
                        break;
                    }
                }
            }
        }

        foreach ($user->getMandatoryApprovers() as $r) {
            if ($r['approverUserID'] != 0) {
                foreach ($docAccess["users"] as $usr) {
                    if ($usr->getID() == $r['approverUserID']) {
                        $approvers["i"][] = $r['approverUserID'];
                        break;
                    }
                }
            } else if ($r['approverGroupID'] != 0) {
                foreach ($docAccess["groups"] as $grp) {
                    if ($grp->getID() == $r['approverGroupID']) {
                        $approvers["g"][] = $r['approverGroupID'];
                        break;
                    }
                }
            }
        }

        $fileCount = count($_FILES["file"]["tmp_name"]);
        for ($i = $fileCount; --$i >= 0;) {
            if ($_FILES["file"]["size"][$i] == 0 || $_FILES['file']['error'][$i] != 0) {
                self::error(500, 'File uploading failed on file ' . $_FILES['file']['name'][$i] . '.');
            }
        }

        // Prepare data before looking into uploaded files
        $name = $data->name;
        $attributes = array();
        if (!empty($data->attributes) && is_array($data->attributes)) {
            foreach ($data->attributes as $attrId => $attribute) {
                if (($attrDefinition = $dms->getAttributeDefinition($attrId))) {
                    if ($attrDefinition->getRegex()) {
                        if (!preg_match($attrDefinition->getRegex(), $attribute)) {
                            self::error(400, 'Attribute fails to match regex.');
                        }
                    }
                }
            }
        }

        if (!$workflow = $user->getMandatoryWorkflow()) {
            if (!empty($data->workflow)) {
                $workflow = $dms->getWorkflow($data->workflow);
            } else {
                $workflow = null;
            }
        }

        $comment = !empty($data->comment) ? $data->comment : null;
        $keywords = !empty($data->keywords) ? $data->keywords : null;
        $categories = array();
        if (empty($data->categories)) {
            foreach ($data->categories as $catId) {
                $categories[] = $dms->getDocumentCategory($catId);
            }
        }

        $expires = false;
        if (!empty($data->expires)) {
            $expires = date('Y-m-d', strtotime($data->expires));
        }

        $version = (!empty($data->version) && (int) $data->version >= 1) ? (int) $data->version : 1;
        $sequence = floatval($data->sequence);

        $versionComment = '';
        if (!empty($data->useDocumentComment) && $data->useDocumentComment) {
            $versionComment = !empty($data->commment);
        } else if (!empty($data->versioComment)) {
            $versionComment = $data->versionComment;
        }

        // Handle uploaded files and insert into database
        for ($i = $fileCount; --$i >= 0;) {
            $tempName = $_FILES["file"]["tmp_name"][$i];
            $fileType = $_FILES["file"]["type"][$i];
            $fileName = $_FILES["file"]["name"][$i];
            $fileBaseName = basename($fileName);

            $extension = '.';
            if (($lastDotIndex = strrpos($fileBaseName, '.')) !== false) {
                $extension = substr($fileName, $lastDotIndex);
            }

            if (!$settings->_enableDuplicateDocNames) {
                if ($folder->hasDocumentByName($fileBaseName)) {
                    self::error(400, 'File already exists and duplicate names flag is off.');
                }
            }

            if (isset($GLOBALS['SEEDDMS_HOOKS']['addDocument'])) {
                foreach ($GLOBALS['SEEDDMS_HOOKS']['addDocument'] as $hookObj) {
                    if (method_exists($hookObj, 'preAddDocument')) {
                        $hookObj->preAddDocument(array('name' => &$name, 'comment' => &$comment));
                    }
                }
            }

            $result = $folder->addDocument($name, $comment, $expires, $user, $keywords
                    , $categories, $tempName, $fileBaseName, $fileType, $fileType
                    , $sequence, $reviewers, $approvers, $version, $versionComment
                    , $attributes, array(), $workflow);

            if (is_array($result)) {
                $document = $result[0];
                if (isset($GLOBALS['SEEDDMS_HOOKS']['addDocument'])) {
                    foreach ($GLOBALS['SEEDDMS_HOOKS']['addDocument'] as $hookObj) {
                        if (method_exists($hookObj, 'postAddDocument')) {
                            $hookObj->postAddDocument($document);
                        }
                    }
                }
                if ($settings->_enableFullSearch) {
                    if (!empty($settings->_luceneClassDir)) {
                        require_once($settings->_luceneClassDir . '/Lucene.php');
                    } else {
                        require_once('SeedDMS/Lucene.php');
                    }

                    $index = SeedDMS_Lucene_Indexer::open($settings->_luceneDir);
                    if ($index) {
                        SeedDMS_Lucene_Indexer::init($settings->_stopWordsFile);
                        $index->addDocument(new SeedDMS_Lucene_IndexedDocument($dms, $document, isset($settings->_convcmd) ? $settings->_convcmd : null, true));
                    }
                }
            } else {
                self::error(500, 'Unable to add document to database.');
            }
        }
    }

}
