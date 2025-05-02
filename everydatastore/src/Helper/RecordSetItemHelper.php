<?php

namespace EveryDataStore\Helper;

/** EveryDataStore/EveryDataStore v1.5
 *
 * This class manages navigation, order, as well as relations between RecordSetItems
 */

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Helper\RecordSetItemDataHelper;
use EveryDataStore\Model\RecordSet\RecordSetItem;
use EveryDataStore\Model\RecordSet\RecordSetItemData;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;

class RecordSetItemHelper extends EveryDataStoreHelper {

    /**
     * This function navigates through RecordSetItems and returns the next item in the given order
     *
     * @param string $itemID
     * @param string $recordSetSlug
     * @param string $orderColumn
     * @param string $orderDirection
     * @return DataObject
     */
    public static function getNextItem($itemID, $recordSetSlug, $orderColumn, $orderDirection) {
        $sort = $orderColumn !== 'Created' ? self::getRecordSetItemSort($orderColumn, $orderDirection) : "$orderColumn $orderDirection";
        return RecordSetItem::get()->filter(array('ID:GreaterThan' => $itemID, 'RecordSet.Slug' => $recordSetSlug, 'ItemData.ID:GreaterThan' => 0, 'Version:GreaterThan' => 0))->Sort("Created ASC")->First();
    }

    /**
     * This function navigates through RecordSetItems and returns the next item in the given order
     *
     * @param string $itemID
     * @param string $recordSetSlug
     * @param string $orderColumn
     * @param string $orderDirection
     * @return DataObject
     */
    public static function getPrevItem($itemID, $recordSetSlug, $orderColumn, $orderDirection) {
        $sort = $orderColumn !== 'Created' ? self::getRecordSetItemSort($orderColumn, $orderDirection) : "$orderColumn $orderDirection";
        return RecordSetItem::get()->filter(array('ID:LessThan' => $itemID, 'RecordSet.Slug' => $recordSetSlug, 'ItemData.ID:GreaterThan' => 0, 'Version:GreaterThan' => 0))->Sort("Created DESC")->First();
    }

    /**
     * This function defines the order column and order direction
     *
     * @param string $orderColumn
     * @param string $orderDirection
     * @return string
     */
    public static function getRecordSetItemSort($orderColumn, $orderDirection = 'ASC') {
        $Sort = "CASE " .
                "WHEN `itemdata_formfield_settings_FormFieldSetting`.`Title` = 'label' and `itemdata_formfield_settings_FormFieldSetting`.`Value` = '" . strip_tags($orderColumn) . "' THEN `itemdata_formfield_settings_FormFieldSetting`.`Value` = '" . strip_tags($orderColumn) . "' " .
                "ELSE `RecordSetItem`.`Created`" .
                "END";

        return "$Sort $orderDirection";
    }

    /**
     * This function returns an array of FormField labels' key-value pairs
     * in case 'RelationData' => 'RecordSet'
     *
     * @param array $niceRecordSetItem
     * @param array $recordSetItemsbyRecord
     * @return array
     */
    public static function getNiceRecordSetItemByRecordRelation($niceRecordSetItem, $recordSetItemsbyRecord) {
        foreach ($recordSetItemsbyRecord as $recordSetItem) {
            $formField = DataObject::get('EveryDataStore\Model\RecordSet\Form\FormField')->filter(['Slug' => $recordSetItem->FormFieldSlug])->first();
            if ($formField) {
                $label = $formField->getLabel();
                $niceRecordSetItem[$label] = array(
                    'Slug' => $recordSetItem->RecordSetItemSlug,
                    'Label' => $label,
                    'RelationType' => $formField->getRelationFieldType(),
                    'RelationData' => $recordSetItem->RelationData,
                    'RelationDataName' => $recordSetItem->RelationDataName,
                    'FormFieldSlug' => $recordSetItem->FormFieldSlug,
                    'ItemData' => RecordSetItemHelper::getNiceRecordSetItemData($recordSetItem->ItemData())
                    );
            }
        }
        return $niceRecordSetItem;
    }

    /**
     * This function returns an array of FormField labels' key-value pairs
     * in case 'RelationData' => 'Model'
     *
     * @param array $niceRecordSetItem
     * @param string $recordSetItemID
     * @return array
     */
    public static function getNiceRecordSetItemByModelRelation($niceRecordSetItem, $recordSetItemID) {
        $sqlQuery = new SQLSelect();
        $sqlQuery->setFrom('RecordSetItem_RecordItems');
        $sqlQuery->selectField('*');
        $sqlQuery->addWhere(array('RecordSetItemID = ?' => $recordSetItemID, 'RelationData' => 'Model'));
        $recordSetItems = $sqlQuery->execute();
        if (!empty($recordSetItems)) {
            foreach ($recordSetItems as $recordSetItem) {
                $modelClassName = Config::inst()->get('API_Namespace_Class_Map', $recordSetItem['RelationDataName']);
                $object = $modelClassName ? DataObject::get($modelClassName)->filter(['Slug' => $recordSetItem['RecordSetItemSlug']])->first() : '';
                $formField = DataObject::get('EveryDataStore\Model\RecordSet\Form\FormField')->filter(['Slug' => $recordSetItem['FormFieldSlug']])->first();
                if ($object && $formField) {
                    $label = $formField->getLabel();
                    $niceRecordSetItem[$label] = array(
                        'Slug' => $recordSetItem['RecordSetItemSlug'],
                        'Label' => $label,
                        'RelationType' => $formField->getRelationFieldType(),
                        'RelationData' => $recordSetItem['RelationData'],
                        'RelationDataName' => $recordSetItem['RelationDataName'],
                        'FormFieldSlug' => $recordSetItem['FormFieldSlug'],
                        'ItemData' => self::getViewFieldsValues($object)
                    );
                }
            }
        }
        return $niceRecordSetItem;
    }

    /**
     * This function gives an array of key-value pairs of RecordSetItem's data
     *
     * @param array $itemData
     * @return array
     */
    public static function getNiceRecordSetItemData($itemData) {
        $ret = [];
        foreach ($itemData as $data) {
            $ret[] = array(
                'Value' => $data->Value(),
                'Label' => $data->FormField()->getLabel(),
                'FormFieldSlug' => $data->FormField()->Slug,
                'FormFieldTypeSlug' => $data->FormField()->getTypeSlug()
            );
        }

        return $ret;
    }

    /**
     * This function returns RecordSetItems data that corresponds to specified version
     *
     * @param string $recordSetItemID
     * @param integer $version
     * @return array
     */
    public static function getVersionendItemData($recordSetItemID, $version) {
        $ret = [];
        $itemData = RecordSetItemData::get()->filter(['RecordSetItemID' => $recordSetItemID])->First();
        foreach ($itemData as $data) {
            $versionedData = Versioned::get_version('EveryDataStore\Model\RecordSet\RecordSetItemData', $data->ID, $version);
            if ($versionedData) {
                $ret[] = array(
                    'Value' => $versionedData->Value(),
                    'Label' => $versionedData->FormField()->getLabel(),
                    'FormFieldSlug' => $versionedData->FormField()->Slug,
                    'FormFieldTypeSlug' => $versionedData->FormField()->getTypeSlug()
                );
            }
        }
        return $ret;
    }

    /**
     * This function duplicates a Record
     *
     * @param DataObject $item
     * @param array $ignorFormFields
     */
    public static function dupplicateRecordSetItem($item, $ignorFormFields = []) {
            $new_item = new RecordSetItem();
            $new_item->RecordSetID = $item->RecordSetID;
            $new_item->write();
            self::dupplicateRecordSetItemData($new_item, $item, $ignorFormFields);
            self::dupplicateRecordSetItemItems($new_item, $item);
    }

    /**
     * This function duplicates RecordSetItems of a specified Record as $item
     *
     * @param DataObject $new_item
     * @param DataObject $item
     */
    public static function dupplicateRecordSetItemItems($new_item, $item){
        if ($item && $item->RecordItems()->Count() > 0) {
            foreach ($item->RecordItems() as $rm) {
                $new_item->RecordItems()->add($rm);
            }
        }
    }

    /**
     * This function copies ID values in a duplicated RecordSetItem
     *
     * @param string $new_item_id
     * @param DataObject $item
     * @param array $ignorFormFields
     */
    public static function dupplicateRecordSetItemData($new_item_id, $item, $ignorFormFields = []){
        if ($item && $item->ItemData()->Count() > 0) {
            foreach ($item->RecordItems() as $id) {
                if (!in_array($id->FormFieldID, $ignorFormFields)) {
                    self::dupplicateItemData($id, $new_item_id);
                }
            }
        }
    }

    /**
     * This function creates a duplicated item data and
     * copies data from $item_data to the newly created RecordSetItemData
     *
     * @param DataObject $item_data
     * @param string $new_item_id
     */
    private static function dupplicateItemData($item_data, $new_item_id) {
        $new_item_data = new RecordSetItemData();
        $new_item_data->RecordSetItemID = $new_item_id;
        $new_item_data->FormFieldID = $item_data->FormFieldID;
        $new_item_data->Value = $item_data->Value;
        $new_item_data->write();
    }
    
    /**
     * Returns true if member has permission to edit, view or delete RecordSetItem.
     * @param DataObject $recordSetItem
     * @param DataObject $member
     * @return boolean
     */
    public static function canDoCreatedBy($recordSetItem, $member = null){
        $memberID = $member ? $member->ID : self::getMemberID();
        $PermissionRecordSets = Config::inst()->get('EveryDataStore\Model\RecordSet\RecordSetItem', 'PermissionRecordSets');
  
        if($PermissionRecordSets && in_array($recordSetItem->RecordSet()->Slug, $PermissionRecordSets)){
            if($memberID == $recordSetItem->CreatedBy()->ID){
                return true;
            }
            return false;
        }
        return true;
    }
    
    /**
     * Mapping RecordSetItem fields with the file fields.
     * @param DataObject $recordSetItem
     * @param array $config
     */
    public static function mapRecordSetItemFieldsWithFileFields($recordSetItem, $config) {
        $uploadFieldItemData = $recordSetItem->ItemData()->filter(['FormField.Slug' => $config['MappingFields']['UploadField']])->first();
        if ($uploadFieldItemData) {
            $uploadFieldValue = RecordSetItemDataHelper::getUploadFieldValue($uploadFieldItemData);
            if ($uploadFieldValue) {
                foreach ($uploadFieldValue[0] as $k => $v) {
                    if (isset($config['MappingFields'][$k])) {
                        $formField = DataObject::get('EveryDataStore\Model\RecordSet\Form\FormField')->filter(['Slug' => $config['MappingFields'][$k]])->first();
                        $itemData = $formField ? $recordSetItem->ItemData()->filter(['FormFieldID' => $formField->ID])->first() : null;
                        if (!$itemData) {
                            $itemData = new RecordSetItemData();
                            $itemData->FormFieldID = $formField->ID;
                            $itemData->RecordSetItemID = $recordSetItem->ID;
                        }
                        $itemData->Value = $v;
                        $itemData->write();
                    }
                }
            }
        }
    }
}
