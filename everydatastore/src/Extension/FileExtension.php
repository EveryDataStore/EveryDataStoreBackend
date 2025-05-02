<?php
namespace EveryDataStore\Extension;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\Note;
use SilverStripe\Control\Director;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\PermissionProvider;

/**
 * EveryDataStore/EveryDataStore v1.5
 *
 * This extension overwrites some methods of the File model, its relations and its permissions
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique Identifier
 * @property datetime $DeletionDate File deletion date
 *
 */

class FileExtension extends DataExtension implements PermissionProvider {
    private static $table_name = 'File';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'DeletionDate' => 'Datetime'
    ];
    private static $has_one = [
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
    ];
    private static $has_many = [
        'Notes' => Note::class
    ];
    private static $belongs_to = [
        'RecordSet' => 'RecordSet',
        'RecordSetItem' => 'RecordSetItem'
    ];
    private static $extensions = [
        Versioned::class
    ];

    /**
     * This function customizes saving-behavior for each DataObject
     * It sets up values for File attributes if they do not exist or updates them otherwise
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if ($member) {
            if (!$this->owner->Slug) {
                $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug('SilverStripe\Assets\File');
            }

            if (!$this->owner->CreatedByID) {
                $this->owner->CreatedByID = $member->ID;
            }

            $this->owner->UpdatedByID = $member->ID;
        }
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        EveryDataStoreHelper::deleteAllVersions($this->owner->ID, self::$table_name);
    }

    /**
     * This function returns absolute URL of the file location
     * @return string
     */
    public function absoluteURL() {
        return Director::absoluteBaseURL().$this->owner->URL;
    }

    /**
     * This function returns protected URL of the file location
     * @return string
     */
    public function getProtectedURL() {
        return $this->absoluteURL().'?hash='.$this->owner->FileHash;
    }

    /**
     *
     * @return string
     */
    public function getFileViewerLink() {
        return $this->getProtectedURL();
    }

    /**
     *  This function will return Thumbnail URL if the File is an Image else returns an Icon URL
     * @return string
     */
    public function getThumbnailURL() {
        if ($this->owner->getIsImage()) {
            return Director::absoluteBaseURL() . $this->owner->Fill(100, 100)->URL . '?hash=' . $this->owner->FileHash;
        }
        return Director::absoluteBaseURL() . $this->owner->getIcon();
    }

    /**
     *
     * @return string
     */
    public function getOwnerFileHash() {
        return $this->owner->FileHash;
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        $member = EveryDataStoreHelper::getMember();
        if (!$this->owner->CanViewType || $this->owner->CanViewType == 'Anyone') {
            return true;
        }

        if ($member && EveryDataStoreHelper::checkPermission('ADMIN')) {
            return true;
        }

        if ($this->owner->CanViewType === 'LoggedInUsers') {
            return $member ? true : false;
        }

        if ($this->owner->CanViewType === 'OnlyTheseUsers') {
            return $member && $member->inGroups($this->owner->ViewerGroups()) ? true : false;
        }

        return EveryDataStoreHelper::checkPermission('VIEW_FILE');
    }

     /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        if ($member && EveryDataStoreHelper::checkPermission('ADMIN')) {
            return true;
        }

        if ($this->owner->CanEditType === 'LoggedInUsers') {
            return $member ? true : false;
        }

        if ($this->owner->CanEditType === 'OnlyTheseUsers') {
            return $member && $member->inGroups($this->owner->EditorGroups()) ? true : false;
        }

        return EveryDataStoreHelper::checkPermission('EDIT_FILE');
    }


    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('DELETE_FILE');
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        if (EveryDataStoreHelper::getCurrentDataStore()->StorageCurrentSize < EveryDataStoreHelper::getCurrentDataStore()->StorageAllowedSize) {
            return EveryDataStoreHelper::checkPermission('CREATE_FILE');
        }
    }

    /**
     * Return a map of permission codes for the Data object and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            'CREATE_FILE' => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => 'File',
                'sort' => 1
            ],
            'EDIT_FILE' => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => 'File',
                'sort' => 1
            ],
            'VIEW_FILE' => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => 'File',
                'sort' => 1
            ],
            'DELETE_FILE' => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => 'File',
                'sort' => 1
            ],'DELETE_FILE_PERMANENTLY' => [
                'name' => _t('SilverStripe\Security\Permission.DELETEFILEPERMANENTLY', "DELETE FILE PERMANENTLY"),
                'category' => 'File',
                'sort' => 1
            ]);
    }

}
