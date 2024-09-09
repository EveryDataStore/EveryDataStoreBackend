<?php

namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;

/** EveryDataStore v1.0
 *
 * This class creates a new DefaultFolder for a Record and defines permissions
 * in accordance with Groups.
 */
class RecordSetHelper extends EveryDataStoreHelper {

    /**
     * This function creates a DefaultFolder for a Record in the current directory
     * and defines access and manipulation rights for different Groups of users.
     *
     * @param DataObject $member
     * @param string $FolderTitle
     * @return DataObject
     */
    private function createDefaultFolder($member, $FolderTitle) {
        $parentFolder = $member->CurrentDataStore()->Folder();
        $Folder = Folder::find_or_make(Config::inst()->get('SilverStripe\Assets\File', 'root_dir_name').'/'. $parentFolder->Filename . '/' . $FolderTitle);
        if ($Folder) {
            $Folder->ParentID = $parentFolder->ID;

            $AdministratorsGroup = Group::get()->filter(array('Title' => 'Administrators'))->First();
            $editorGroups = $member->Groups()->filter(array(
                'Permissions.Code' => array('CREATE_FILE', 'EDIT_FILE', 'VIEW_FILE', 'DELETE_FILE')
            ));

            if ($editorGroups) {
                $Folder->CanEditType = 'OnlyTheseUsers';
                foreach ($editorGroups as $editorGroup) {
                    $Folder->EditorGroups()->add($editorGroup);
                }
            }

            $viewerGroups = $member->Groups()->filter(array(
                'Permissions.Code' => array('VIEW_FILE')
            ));

            if ($viewerGroups) {
                $Folder->CanViewType = 'OnlyTheseUsers';
                foreach ($viewerGroups as $viewerGroup) {
                    $Folder->ViewerGroups()->add($viewerGroup);
                }
            }

            if ($AdministratorsGroup) {
                $Folder->EditorGroups()->add($AdministratorsGroup);
                $Folder->ViewerGroups()->add($AdministratorsGroup);
            }
            return $Folder->write();
        }
    }
}
