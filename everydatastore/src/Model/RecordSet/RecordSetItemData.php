<?php

namespace EveryDataStore\Model\RecordSet;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryTranslator\Helper\EveryTranslatorHelper;
use EveryDataStore\Helper\LoggerHelper;
use EveryDataStore\Helper\RecordSetItemDataHelper;
use EveryDataStore\Helper\AssetHelper;
use EveryDataStore\Model\RecordSet\RecordSetItem;
use EveryDataStore\Model\RecordSet\Form\FormField;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;


/** EveryDataStore v1.5
 *
 * This class defines a RecordSetItemData, i.e., data that belongs to a specific RecordSetItem,
 * as well as its relations to other models.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Value Value of a field of the RecordSetItem's form (RecordForm)
 *
 */

class RecordSetItemData extends DataObject {

    private static $table_name = 'RecordSetItemData';
    private static $singular_name = 'RecordSetItemData';
    private static $plural_name = 'RecordSetItemData';
    private static $db = [
        'Value' => 'Text'
    ];

    private static $has_one = [
        'RecordSetItem' => RecordSetItem::class,
        'FormField' => FormField::class,
        'Folder' => Folder::Class,
    ];

    private static $extensions = [
        Versioned::class
    ];

    private static $owns = [];

    private static $owned_by = [
        'RecordSetItem',
        'FormField'
    ];

    private static $indexes = [
        'RecordSetItemDataIndex' => ['RecordSetItemID', 'FormFieldID', 'FolderID'],
    ];

    private static $summary_fields = [];
    private static $field_labels = [];
    public function getNiceValue() {
        return $this->Value();
    }

    public function getLabel() {
        return $this->FormField()->getLabel();
    }

    public function FormFieldSlug() {
        return $this->FormField()->Slug;
    }

    public function FormFieldTypeSlug() {
        return $this->FormField()->getTypeSlug();
    }

    public function Value() {
   
    if($this->Value || $this->FolderID > 0){

      $retValue = $this->Value;
      $textFieldType = $this->FormField()->getTextFieldType();

        // check if value is a number
        if(
                $textFieldType == 'money' ||
                $textFieldType == 'unit' ||
                $textFieldType == 'decimal'
          ){
            
            $suffix = $this->FormField()->Settings()->Filter(['Title' => 'summable_suffix'])->first();
            $prefix = $this->FormField()->Settings()->Filter(['Title' => 'summable_prefix'])->first();
            
            $suffixValue = $suffix ? $suffix->Value: '';
            $prefixValue = $prefix ? $prefix->Value: '';
            if($textFieldType == 'unit' ){
                $suffixValue = ' '.$this->FormField()->Settings()->Filter(['Title' => 'unittype'])->first()->Value;
            }
            
            if($textFieldType == 'money' ){
                $suffixValue = ' '.$this->FormField()->Settings()->Filter(['Title' => 'currency'])->first()->Value;
            }
            
            
            $niceNumber = $prefixValue.RecordSetItemDataHelper::getNiceNumberFormat($this->Value).$suffixValue;
            return $niceNumber;
        }
        
        
        if($this->FolderID > 0 &&  $this->FormField()->getTypeSlug() == 'uploadfield'){
               return RecordSetItemDataHelper::getUploadFieldValue($this);
        } 
        
        
        if($this->FormField()->getTypeSlug() == 'relationfield' && $this->FormField()->getRelationFieldType() == 'HasOne'){
            return RecordSetItemDataHelper::getRelationFieldValue($this);
        }
        
        
        if(is_array(unserialize($this->Value))){
            $retValue = unserialize($this->Value);
        }
        
        if(EveryTranslatorHelper::isTranslatableRecordSet($this->RecordSetItem()->RecordSet()->Slug)){
            $retValue = EveryTranslatorHelper::_t($retValue, true);
        }
        
        return  $retValue;
      }
    }

    public function Label() {
        return $this->FormField()->getLabel();
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if ($this->FormFieldTypeSlug() == 'uploadfield') {
            $parentFolder = $this->RecordSetItem()->Folder();
            $createdFiles = [];
            if(!AssetHelper::isFolderExists($this->RecordSetItem()->FolderID)){
                        $recordSetItemFolder = AssetHelper::createFolder($this->RecordSetItem()->Slug, $this->RecordSetItem()->RecordSet()->Folder());
                        $this->RecordSetItem()->FolderID = $recordSetItemFolder->ID;
                        $this->RecordSetItem()->writeWithoutVersion();
                        $parentFolder = $recordSetItemFolder;
            }
            $this->FolderID  =   AssetHelper::createFolder($this->Label(), $parentFolder)->ID;
            if ($this->Value && is_array(unserialize($this->Value))) {
                foreach (unserialize($this->Value) as $v) {
                    $filename = AssetHelper::getAvailableAssetName('SilverStripe\Assets\Folder', 10);
                    $createdFiles[] = is_string($v) && AssetHelper::isStringBase64($v) ? AssetHelper::createFileFromBase64($v, $parentFolder, $this->Label(), $filename): null;
                }
            }
            $this->Value = '';
        }
        
        /*
        if($this->FormField()->getTextFieldType() == 'time'){
            $this->Value = date('H:i:s', strtotime($this->Value));
        }
        
        if($this->FormField()->getTextFieldType() == 'datetime'){
            $this->Value = date('Y-m-d H:i:s', strtotime($this->Value));
        }
       */
        if (is_array($this->Value)) {
            $this->Value = serialize($this->Value);
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
        if ($this->Folder() && $this->FolderID > 0) {
            AssetHelper::deleteFolder($this->FolderID);
            $this->FolderID = 0;
        }
        EveryDataStoreHelper::deleteAllVersions($this->ID, self::$table_name);
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        return \EveryDataStore\Helper\EveryDataStoreHelper::checkPermission(\EveryDataStore\Helper\EveryDataStoreHelper::getNicePermissionCode("VIEW", RecordSetItem::class));
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return RecordSetItemDataHelper::checkPermission(RecordSetItemDataHelper::getNicePermissionCode("EDIT", RecordSetItem::class));
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return RecordSetItemDataHelper::checkPermission(RecordSetItemDataHelper::getNicePermissionCode("DELETE", RecordSetItem::class));
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return RecordSetItemDataHelper::checkPermission(RecordSetItemDataHelper::getNicePermissionCode("CREATE", RecordSetItem::class));
    }
}
