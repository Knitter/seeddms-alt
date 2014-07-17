<?php

class FolderAPI {

    /**
     * Parsers a folder path and iterates all components in order to find the ID 
     * for the last (inner most) folder.
     * 
     * @param SeedDMS_Core_DatabaseAccess $db
     * @param string $path
     */
    public static function pathId($db, $path) {
        $pieces = explode('_', $path);
        $id = 1;

        while (($top = array_shift($pieces)) != null) {
            $results = $db->getResultArray(sprintf("SELECT id FROM tblFolders WHERE name = '%s' AND parent = 1", $top));
            if (is_bool($results) && !$results) {
                break;
            }

            if (count($results)) {
                $id = (int) $results[0]['id'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode((object) array('id' => $id));
    }

}
