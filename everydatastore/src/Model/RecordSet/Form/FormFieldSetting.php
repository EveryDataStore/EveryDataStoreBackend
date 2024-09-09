<?php

namespace EveryDataStore\Model\RecordSet\Form;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\RecordSet\Form\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/** EveryDataStore v1.0
 *
 * Each FormField has its own group of settings. This class defines a FormFieldSetting,
 * as well as its relations to other models.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Title Name of the setting
 * @property string $Value Value of the setting
 *
 */

class FormFieldSetting extends DataObject {

    private static $table_name = 'FormFieldSetting';
    private static $singular_name = 'FormFieldSettings';
    private static $plural_name = 'FormFieldSettings';
    private static $db = [
        'Title' => 'Varchar',
        'Value' => 'Text'
    ];
    private static $has_one = [
        'FormField' => FormField::class
    ];


    private static $extensions = [
        Versioned::class
    ];


    private static $owned_by = [
        'FormField'
    ];

    private static $summary_fields = [];
    private static $field_labels = [];

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        EveryDataStoreHelper::deleteOneFromLiveTable($this->ID, self::$table_name);
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
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
