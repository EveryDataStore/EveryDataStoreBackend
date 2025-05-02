<?php

namespace EveryDataStore\Model\RecordSet;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSetItem;
use EveryDataStore\Model\RecordSet\Form\Form;
use EveryDataStore\Model\Menu;
use EveryDataStore\Model\DataStore;
use EveryDataStore\Model\RecordSet\Form\FormField;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Folder;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Versioned\Versioned;
use EveryDataStore\Helper\AssetHelper;

use SilverStripe\Core\ClassInfo;

/** EveryDataStore v1.5
 *
 * This class defines a RecordSet model, its structure in the database, its relations
 * as well as its appearance in the solution.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier of the RecordSet
 * @property bool $Active Activity status of the RecordSet in the DataStore
 * @property string $Title Name of the RecordSet
 * @property bool $ShowInMenu Denotes whether the RecordSet shall be displayed as a Menu item
 * @property bool $AllowUpload Denotes whether it is possible to attach files to the RecordSet
 * @property bool $FrontendTapedForm Denotes whether the elements of the form of the RecordSet
 *                shall appear in different tabs
 * @property bool $OpenFormInDialog Denotes whether the RecordSet form shall be opened in a new dialog
 * @property datetime $ChildChanged A timestamp is applied when a child (form element) has been changed
 *
 */

class RecordSet extends DataObject implements PermissionProvider {

    private static $table_name = 'RecordSet';
    private static $singular_name = 'RecordSet';
    private static $plural_name = 'RecordSets';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'Active' => 'Boolean',
        'Title' => 'Varchar(100)',
        'ShowInMenu' => 'Boolean',
        'AllowUpload' => 'Boolean',
        'FrontendTapedForm' => 'Boolean',
        'OpenFormInDialog' => 'Boolean',
        'ChildChanged' => 'Datetime'
    ];

    private static $has_one = [
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
        'DataStore' => DataStore::class,
        'Menu' => Menu::class,
        'Folder' => Folder::Class
    ];
    private static $has_many = [
        'Items' => RecordSetItem::class
    ];
    private static $many_many = [
        'Groups' => Group::class
    ];
    private static $belongs_to = [
        'Form' => Form::class . '.RecordSet'
    ];

    private static $extensions = [
        Versioned::class,
    ];

    private static $owns = [
        'Items',
        'Form'
    ];

    private static $summary_fields = [
        'Slug',
        'Title',
        'Active',
        'ShowInMenu',
        'AllowUpload',
        'Created' => 'Created',
        'LastEdited' => 'LastEdited'
    ];
    private static $default_sort = "\"Title\"";
    private static $searchable_fields = [
        'Title' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ]
    ];

    private static $indexes = [
        'RecordSetIndex' => ['Slug', 'DataStoreID'],
    ];

    //private static $cascade_duplicates = [ 'ItemData', 'RecordItems'];

    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels(true);
        if (!empty(self::$summary_fields)) {
            $labels = EveryDataStoreHelper::getNiceFieldLabels($labels, __CLASS__, self::$summary_fields);
        }

        return $labels;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab('Root.Main', 'CreatedByID');
        $fields->removeFieldFromTab('Root.Main', 'UpdatedByID');
        $fields->removeFieldFromTab('Root.Main', 'DataStoreID');
        $fields->removeFieldFromTab('Root.Main', 'Folder');
        $fields->addFieldToTab('Root.Main', new ReadonlyField('Slug', 'Slug'));
        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if ($member) {
            if (!$this->Slug) {
                $this->Slug = EveryDataStoreHelper::getAvailableSlug(__CLASS__);
            }

            if (!$this->CreatedByID) {
                $this->CreatedByID = $member->ID;
            }

            if (!$this->DataStoreID) {
                $this->DataStoreID = $member->CurrentDataStoreID;
            }

            if (!AssetHelper::isFolderExists($this->FolderID) && $this->AllowUpload) {
                $this->FolderID = AssetHelper::createFolder($this->Title, $member->CurrentDataStore()->Folder())->ID;
            }
            $this->UpdatedByID = $member->ID;
        }
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        if (!$this->Form()) {
            $recordSetForm = new Form();
            $recordSetForm->RecordSetID = $this->ID;
            $recordSetForm->write();
        }
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        EveryDataStoreHelper::deleteOneFromLiveTable($this->ID, self::$table_name);
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        if ($this->Form()->ID > 0) {
            $this->Form()->delete();
        }

        if ($this->Items()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->Items());
        }

        if ($this->Menu()->ID > 0) {
            $this->Menu()->delete();
        }

        if ($this->Folder() && $this->Folder()->FolderID > 0) {
            AssetHelper::deleteFolder($this->FolderID);
            $this->FolderID = 0;
        }

        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
    }


    public function versionHistory() {
        if ($this->Versions() && $this->Versions()->Count() > 0) {
            $versionHistory = [];
            foreach ($this->Versions() as $version) {
                $versionHistory[] = array(
                    'Slug' => $version->Slug,
                    'LastEdited' => $version->LastEdited,
                    'Version' => $version->Version,
                    'Title' => $version->Title,
                    'CreatedBy' => $version->UpdatedBy()->getFullName()
                );
            }
        }
        return $versionHistory;
    }

    public function getRecordResultlistFields($getAll = False) {
        $ids = $this->getRecordResultlistFormFieldIDs($getAll);
        if ($ids) {
            return FormField::get()->filter(array('ID' => $ids))->Sort('Column.Section.Sort ASC,Column.Sort ASC, Sort ASC');
        }

    }

    public function getRecordResultlistFormFieldIDs($getAll = False) {
        $ids = array();
        if ($this->Form()->Sections()) {
            foreach ($this->Form()->Sections() as $section) {
                if ($section->Columns()) {
                    foreach ($section->Columns() as $column) {
                        if ($column->FormFields()) {
                            foreach ($column->FormFields()->Sort("Sort ASC") as $FormField) {
                                if ($getAll == true) {
                                    if ($FormField->getActive()) {
                                        array_push($ids, $FormField->ID);
                                    }
                                } else {
                                    if ($FormField->getActive() && $FormField->showInResultlist()) {
                                        array_push($ids, $FormField->ID);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $ids;
    }

    public function getNiceItems() {
        return $this->Items()->filter(array('Version:GreaterThan' => 0));
    }

    public function getLabels() {
        return $this->getRecordResultlistFieldsToArray(true);
    }

    public function getActiveLabels() {
        return $this->getRecordResultlistFieldsToArray(true);
    }

    public function RecordResultlistLabels() {
        return $this->getRecordResultlistFieldsToArray($getAll = false);
    }

    public function getRecordResultlistFieldsToArray($getAll = false, $sort = false) {
        $fields = [];
        if (!empty($this->getRecordResultlistFields($getAll))) {
            foreach ($this->getRecordResultlistFields($getAll) as $field) {
                if ($field->getLabel()) {
                    $fields[] = array(
                        'Label' => EveryDataStoreHelper::_t($field->getLabel()),
                        'Slug' => $field->Slug
                    );
                }
            }
        }

        if($sort) EveryDataStoreHelper::array_sort_by_column($fields, 'Label');
        return $fields;
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        if(($this->Groups()->Count() > 0) && !EveryDataStoreHelper::isMemberInGroups($this->Groups())){
            return false;
        }
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("VIEW", $this), $member);
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        if(($this->Groups()->Count() > 0) && !EveryDataStoreHelper::isMemberInGroups($this->Groups())){
            return false;
        }
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("EDIT", $this), $member);
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        if(($this->Groups()->Count() > 0) && !EveryDataStoreHelper::isMemberInGroups($this->Groups())){
            return false;
        }
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("DELETE", $this), $member);
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        if(($this->Groups()->Count() > 0) && !EveryDataStoreHelper::isMemberInGroups($this->Groups())){
            return false;
        }
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("CREATE", $this), $member);
    }

    /**
     * Return a map of permission codes for the Dataobject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            EveryDataStoreHelper::getNicePermissionCode("CREATE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("EDIT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("VIEW", $this) => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("DELETE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ], EveryDataStoreHelper::getNicePermissionCode("ROLLBACK", $this) => [
                'name' => _t('SilverStripe\Security\Permission.ROLLBACK', "ROLLBACK"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],);
    }
}
