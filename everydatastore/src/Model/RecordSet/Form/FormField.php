<?php

namespace EveryDataStore\Model\RecordSet\Form;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\RecordSet\Form\FormFieldType;
use EveryDataStore\Model\RecordSet\Form\FormFieldSetting;
use EveryDataStore\Model\RecordSet\Form\FormSectionColumn;
use EveryDataStore\Model\RecordSet\RecordSetItemData;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/** EveryDataStore v1.5
 *
 * This class defines a FormField, i.e., a field that can be found on the form (RecordForm) of a specific RecordSetItem,
 * as well as its relations to other models.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique Identifier of the FormField
 *
 */

class FormField extends DataObject {

    private static $table_name = 'FormField';
    private static $singular_name = 'FormField';
    private static $plural_name = 'FormFields';
    private static $db = [
        'Slug' => 'Varchar(100)',
        'Sort' => 'Int(2)'
    ];
    private static $has_one = [
        'FormFieldType' => FormFieldType::class,
        'Column' => FormSectionColumn::class,
    ];
    private static $has_many = [
        'Settings' => FormFieldSetting::class,
        'ItemData' => RecordSetItemData::class
    ];

    private static $extensions = [
        Versioned::class
    ];

    private static $owns = [
        'Settings',
        'ItemData'
    ];


    private static $owned_by = [
        'Column'
    ];

    private static $default_sort = "\"Sort\"";
    private static $summary_fields = [];
    private static $field_labels = [];

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
        if ($this->Settings()->Count() > 0) {
            EveryDataStoreHelper::deleteDataObjects($this->Settings());
        }
        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
    }

    public function getType() {
        return $this->FormFieldType()->Title;
    }

    public function getTypeSlug() {
        return $this->FormFieldType()->Slug;
    }

    public function getTextFieldType() {
       $obj = $this->Settings()->filter(array('Title' => 'type'))->first();
       return $obj ? $obj->Value: null;
    }

    public function getLabel() {
       $obj = $this->Settings()->filter(array('Title' => 'label', 'FormFieldID' => $this->ID))->first();
       return $obj ? $obj->Value: null;
    }

    public function getPlaceholder() {
       $obj = $this->Settings()->filter(array('Title' => 'placeholder', 'FormFieldID' => $this->ID))->first();
       return $obj ? $obj->Value: null;
    }

    public function getInfo() {
       $obj = $this->Settings()->filter(array('Title' => 'info', 'FormFieldID' => $this->ID))->first();
       return $obj ? $obj->Value : null;
    }

    public function getNiceLabel() {
        return $this->getLabel();
    }

    public function getActive() {
        $obj = $this->Settings()->filter(array('Title' => 'active', 'FormFieldID' => $this->ID))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRequired() {
        $obj = $this->Settings()->filter(array('Title' => 'required', 'FormFieldID' => $this->ID))->first();
        return $obj ? $obj->Value : null;
    }

    public function showInResultlist() {
        return $this->Settings()->filter(array('Title' => 'resultlist', 'Value' => 'true'))->first();
    }

    public function getRelationFieldType() {
        $obj = $this->Settings()->filter(array('Title' => 'relationtype'))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRelationRecordSlug() {
        $obj =  $this->Settings()->filter(array('Title' => 'record'))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRelationRecordTitle() {
        $obj =  RecordSet::get()->filter(array('Slug' => $this->getRelationRecordSlug()))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRelationDisplayFields() {
        $obj = $this->Settings()->filter(array('Title' => 'displayfields'))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRelationModelSlug() {
        $obj =  $this->Settings()->filter(array('Title' => 'model'))->first();
        return $obj ? $obj->Value : null;
    }

    public function getRecordSetID() {
        $obj =   $this->Column()->Section()->Form()->RecordSet();
        return $obj ? $obj->Value : null;
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
