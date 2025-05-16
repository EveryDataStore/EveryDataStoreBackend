<?php
namespace EveryDataStore\Extension;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\DataStore;
use EveryDataStore\Model\Note;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\RecordSet\RecordSetItem;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\PermissionProvider;

/** EveryDataStore/EveryDataStore v1.5
 *
 * This extension overwrites some methods of the Folder model, its relations and its permissions
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique Identifier
 * @property datetime $DeletedDate Folder deletion date
 *
 */
class FolderExtension extends DataExtension implements PermissionProvider {

    private static $table_name = 'File';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'DeletedDate' => 'Datetime'
    ];
    private static $has_one = [
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
    ];
    private static $has_many = [
        'Notes' => Note::class
    ];
    private static $belongs_to = [
        'RecordSet' => RecordSet::class,
        'RecordSetItem' => RecordSetItem::class,
        'DataStore' => DataStore::class,
    ];
    private static $extensions = [
        Versioned::class
    ];

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();

        if (!$this->owner->Slug) {
            $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug('SilverStripe\Assets\Folder');
        }

        if (!$this->owner->CreatedByID) {
            $this->owner->CreatedByID = $member->ID;
        }

        $this->owner->UpdatedByID = $member->ID;
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        EveryDataStoreHelper::deleteAllVersions($this->owner->ID, self::$table_name);
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

        return EveryDataStoreHelper::checkPermission('VIEW_FOLDER');
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

        return EveryDataStoreHelper::checkPermission('EDIT_FOLDER');
    }


    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('DELETE_FOLDER');
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission('CREATE_FOLDER');
    }

    /**
     * Return a map of permission codes for the DataObject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            'CREATE_FOLDER' => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATEs"),
                'category' => 'Folder',
                'sort' => 1
            ],
            'EDIT_FOLDER' => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => 'Folder',
                'sort' => 1
            ],
            'VIEW_FOLDER' => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => 'Folder',
                'sort' => 1
            ],
            'DELETE_FOLDER' => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => 'Folder',
                'sort' => 1
            ]);
    }
}
