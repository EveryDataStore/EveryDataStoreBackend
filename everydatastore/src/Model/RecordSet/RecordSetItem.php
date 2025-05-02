<?php

namespace EveryDataStore\Model\RecordSet;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Helper\RecordSetItemHelper;
use EveryDataStore\Helper\AssetHelper;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\RecordSet\RecordSetItemData;
use EveryDataStore\Model\Note;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Folder;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;

/** EveryDataStore v1.5
 *
 * This class defines a RecordSetItem, its structure in the database,
 * as well as its relations to other models.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier of the RecordSetItem
 * @property datetime $DeletionDate Stores the date and time in case of deletion
 * @property string $DeletionType Stores the applied type of deletion
 *
 */

class RecordSetItem extends DataObject implements PermissionProvider {

    private static $table_name = 'RecordSetItem';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'DeletionDate' => 'Datetime',
        'DeletionType' => 'Enum("none,logically,physically")',
    ];


    private static $extensions = [
        Versioned::class
    ];


    private static $has_one = [
        'RecordSet' => RecordSet::class,
        'Folder' => Folder::Class,
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
    ];

    private static $has_many = [
        'ItemData' => RecordSetItemData::class,
        'Notes' => Note::class
    ];

    private static $many_many = [
        'RecordItems' => RecordSetItem::class
    ];

    private static $owns = [
        'ItemData'
    ];

    private static $owned_by = [
        'RecordSet'
    ];

    private static $many_many_extraFields = [
        'RecordItems' => [
          'RelationType' => 'Enum("HasOne,HasMany")',
          'RelationData' => 'Enum("Record,Model")',
          'RelationDataName' => 'Varchar(100)',
          'RecordSetItemSlug' => 'Varchar(110)',
          'FormFieldSlug' => 'Varchar(110)',
        ]
    ];

    private static $default_sort = "";
    private static $defaults = [
        'DeletionType' => 'none'
    ];
    private static $indexes = [
        'RecordSetItemIndex' => ['Slug', 'DeletionDate', 'RecordSetID'],
    ];

    private static $cascade_duplicates = [ 'ItemData', 'RecordItems'];


    public function onBeforeWrite() {
        $member = Security::getCurrentUser();
        if ($member) {
            if (!$this->Slug) {
                    $this->Slug = RecordSetItemHelper::getAvailableSlug(__CLASS__);
            }

            if (!$this->DataStoreID) {
                    $this->DataStoreID = $member->CurrentDataStoreID;
            }
            if (!$this->CreatedByID) {
                    $this->CreatedByID = $member->ID;
            }
            if ($this->ItemData()->Count() > 0) {
                    $this->UpdatedByID = $member->ID;
            }
            if (!AssetHelper::isFolderExists($this->FolderID) && $this->RecordSet()->AllowUpload && $this->Version > 0) {
                    $this->FolderID = AssetHelper::createFolder($this->Slug, $this->RecordSet()->Folder())->ID;
            }
        }
        parent::onBeforeWrite();
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        $mapRecordSetItemWithFileConfig = Config::inst()->get('EveryDataStore\Model\RecordSet\RecordSetItem', 'MapRecordSets');
        if($this->ItemData()->Count() > 0 && $mapRecordSetItemWithFileConfig && array_key_exists($this->RecordSet()->Slug, $mapRecordSetItemWithFileConfig)){
            RecordSetItemHelper::mapRecordSetItemFieldsWithFileFields($this, $mapRecordSetItemWithFileConfig[$this->RecordSet()->Slug]);
        }
        
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        EveryDataStoreHelper::deleteOneFromLiveTable($this->ID, self::$table_name);
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        if ($this->ItemData()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->ItemData());
        }

        if ($this->Notes()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->Notes());
        }

        if ($this->Folder() && $this->FolderID > 0) {
            AssetHelper::deleteFolder($this->FolderID);
            $this->FolderID = 0;
        }

        if ($this->RecordItems()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->RecordItems());
        }

        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
        
    }

    /**
     * Return all relation types of an RecordSetItem
     * @return array
     */
    public function RelationRecordItems() {
        $niceRecordSetItem = [];
        $recordSetItemsbyRecord = $this->RecordItems()->filter(['RelationData' => 'RecordSet']);
        $recordSetItemsbyModel = $this->RecordItems()->filter(['RelationData' => 'Model']);
        if($recordSetItemsbyRecord->Count()  > 0) {
            return RecordSetItemHelper::getNiceRecordSetItemByRecordRelation($niceRecordSetItem, $recordSetItemsbyRecord);
        }
        return RecordSetItemHelper::getNiceRecordSetItemByModelRelation($niceRecordSetItem, $this->ID);
    }

    /**
     * Return nice RecordItems of an RecordSetItem
     * @return DataObject
     */
    public function getRecordItems() {
        $recordItems = [];
        if ($this->RecordItems()->Count() > 0) {
            foreach ($this->RecordItems() as $rm) {
                $Fields = [];
                if ($rm->ItemData()->Count() > 0) {
                    foreach ($rm->ItemData() as $id) {
                        $Fields[] = [
                           'Field' => $id->FormField()->Slug,
                           'Value' => $id->Value()
                        ];
                    }
                }
                $recordItems[] = [
                   'Slug' => $rm->Slug,
                   'Fields' => $Fields
                ];
            }
            return $recordItems;
        }
    }

    /**
     * Return all versions of an RecordSetItem
     * @return array
     */
    public function versionHistory() {
        if ($this->Versions() && $this->Versions()->Count() > 0) {
            $versionHistory = [];
            foreach ($this->Versions() as $version) {
                $versionHistory[] = array(
                    'ID' => $version->ID,
                    'RecordID' => $version->RecordID,
                    'Slug' => $version->Slug,
                    'LastEdited' => $version->LastEdited,
                    'Version' => $version->Version,
                    'Title' => '',
                    'CreatedBy' => $version->UpdatedBy()->getFullName()
                );
            }
        }
        return $versionHistory;
    }

    /**
     * This functions returns the previous and next record item of current item
     * @return array
     */
    public function getPrevNextItems() {
        $req = \SilverStripe\Control\Controller::curr()->getRequest();
        $page = $req->getVar('Page') ? $req->getVar('Page') : 1 ;
        $orderColumn = $req->getVar('OrderColumn') ?  $req->getVar('OrderColumn') : 'Created';
        $orderOpt = $req->getVar('OrderOpt') ? $req->getVar('OrderOpt') : 'ASC';
        $nextItem = RecordSetItemHelper::getNextItem($this->ID, $this->RecordSet()->Slug, "created" , "ASC");
        $prevItem = RecordSetItemHelper::getPrevItem($this->ID, $this->RecordSet()->Slug, "created" , "DESC");
        return array(
            'ID' => $this->ID,
            'Record' => $this->RecordSetID,
            'prevItem' => $prevItem ? $prevItem->Slug : null,
            'nextItem' => $nextItem ? $nextItem->Slug : null,
            'NEXTID' => $nextItem ? $nextItem->ID : null,
            'NEXTID' => $prevItem ? $prevItem->ID : null
        );
    }

    /*
     * This function generates a ArrayList of ItemData
     * @return ArrayList
     */
    public function getRowItemData() {
        $list = new ArrayList();
        $MetaFields = $this->RecordSet()->getRecordResultlistFields();
        if ($MetaFields) {
            foreach ($MetaFields as $Metafield) {
                $ItemData = $Metafield->ItemData()->filter(array('RecordSetItemID' => $this->ID))->first();
                if ($ItemData) {
                    $list->push(new ArrayData([
                        'Value' => $ItemData->Value(),
                        'Label' => $ItemData->FormField()->getLabel(),
                        'FormFieldSlug' => $ItemData->FormField()->Slug,
                        'FormFieldTypeSlug' => $ItemData->FormField()->getType(),
                    ]));
                }
            }
        }
        return $list;
    }


    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
       if($this->RecordSet()->Groups()->Count() > 0 && !RecordSetItemHelper::isMemberInGroups($this->RecordSet()->Groups())){
            return false;
        }
        
        if(!RecordSetItemHelper::canDoCreatedBy($this, $member)){
            return false;
        }
        
        return RecordSetItemHelper::checkPermission(RecordSetItemHelper::getNicePermissionCode("VIEW", $this), $member);
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        if($this->RecordSet()->Groups()->Count() > 0 && !RecordSetItemHelper::isMemberInGroups($this->RecordSet()->Groups())){
            return false;
        }
        
        if(!RecordSetItemHelper::canDoCreatedBy($this, $member)){
            return false;
        }
        
        return RecordSetItemHelper::checkPermission(RecordSetItemHelper::getNicePermissionCode("EDIT", $this), $member);
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        if($this->RecordSet()->Groups()->Count() > 0 && !RecordSetItemHelper::isMemberInGroups($this->RecordSet()->Groups())){
            return false;
        }
        
        if(!RecordSetItemHelper::canDoCreatedBy($this, $member)){
            return false;
        }
        
        return RecordSetItemHelper::checkPermission(RecordSetItemHelper::getNicePermissionCode("DELETE", $this), $member);
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        if($this->RecordSet()->Groups()->Count() > 0 && !RecordSetItemHelper::isMemberInGroups($this->RecordSet()->Groups())){
            return false;
        }
        
        return RecordSetItemHelper::checkPermission(RecordSetItemHelper::getNicePermissionCode("CREATE", $this), $member);

    }



    /**
     * Return a map of permission codes for the Dataobject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            RecordSetItemHelper::getNicePermissionCode("CREATE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            RecordSetItemHelper::getNicePermissionCode("EDIT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            RecordSetItemHelper::getNicePermissionCode("VIEW", $this) => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            RecordSetItemHelper::getNicePermissionCode("DELETE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ], RecordSetItemHelper::getNicePermissionCode("ROLLBACK", $this) => [
                'name' => _t('SilverStripe\Security\Permission.ROLLBACK', "ROLLBACK"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ], RecordSetItemHelper::getNicePermissionCode("IMPORT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.IMPORT', "IMPORT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ], RecordSetItemHelper::getNicePermissionCode("EXPORT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.EXPORT', "EXPORT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ], RecordSetItemHelper::getNicePermissionCode("PRINT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.PRINT', "PRINT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ]);
    }
}
