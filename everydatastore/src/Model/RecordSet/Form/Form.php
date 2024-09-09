<?php

namespace EveryDataStore\Model\RecordSet\Form;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\RecordSet\Form\FormSection;
use EveryDataStore\Model\RecordSet\Form\FormField;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/** EveryDataStore v1.0
 *
 * This class defines a RecordForm, i.e., a Form for creating a RecordSet,
 * as well as its relations.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier of the RecordForm
 *
 */

class Form extends DataObject {

    private static $table_name = 'Form';
    private static $db = [
        'Slug' => 'Varchar(110)'
    ];
    private static $has_one = [
        'RecordSet' => RecordSet::class
    ];
    private static $has_many = [
        'Sections' => FormSection::class
    ];

    private static $extensions = [
        Versioned::class
    ];

    private static $owns = [
        'Sections'
    ];

    private static $owned_by = [
        'RecordSet'
    ];
    private static $many_many = [];
    private static $default_sort = "ID DESC";

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->Slug) {
            $this->Slug = EveryDataStoreHelper::getAvailableSlug(__CLASS__);
        }
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        EveryDataStoreHelper::deleteOneFromLiveTable($this->ID, self::$table_name);
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        if ($this->Sections()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->Sections());
        }
        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
    }

    public function getRecordResultlistFields() {
        $ret = FormField::get()->filter(array('ID' => $this->getRecordResultlistFormFieldIDs()));
        return $ret;
    }

    public function Fields() {
        $Fields = [];
        $Sections = $this->Sections();
        foreach ($Sections as $Section) {
            if ($Section->Columns()) {
                foreach ($Section->Columns() as $Column) {
                    if ($Column->FormFields()) {
                        foreach ($Column->FormFields() as $Field) {
                            $Fields[] = array_merge(\EveryDataStoreAPI\Helper\CustomRecordSetHelper::getFormDataFormFieldData($Field), ['ShowInResultlist' => $Field->showInResultlist(),
                                'Active' => $Field->getActive(),
                                'Label' => $Field->getLabel()]);
                        }
                    }
                }
            }
        }
        return $Fields;
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("VIEW", RecordSet::class));
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("EDIT", RecordSet::class));
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("DELETE", RecordSet::class));
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("CREATE", RecordSet::class));
    }
}
