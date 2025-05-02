<?php

namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSetItem;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Folder;

/** EveryDataStore v1.5
 *
 * This class manages copying values between related DataObjects
 */

class RecordSetItemDataHelper extends EveryDataStoreHelper {

    /**
     * This function returns value as array of file attributes
     * @param DataObject $recordSetItemData
     * @return array
     */
    public static function getUploadFieldValue($recordSetItemData) {
        $ret = []; 
        foreach ($recordSetItemData->Folder()->Children() as $folder) {
            foreach ($folder->Children() as $child) {
                if ($child->ClassName !== 'SilverStripe\Assets\Folder'){
                    $ret[] = array(
                    'Slug' => $child->Slug,
                    'Created' => $child->Created,
                    'LastEdited' => $child->LastEdited,
                    'CreatedBy' => $child->CreatedBy()->getFullName(),
                    'UpdatedBy' => $child->UpdatedBy()->getFullName(),     
                    'Size' => $child->getSize(),
                    'Name' => $child->Name,
                    'Title' => $child->Title,
                    'ThumbnailURL' => $child->getThumbnailURL(),
                    'ProtectedURL' => $child->getProtectedURL(),
                    );
                }
            }
        }

        return $ret;
    }

    /**
     * This function returns value of relation field
     * @param DataObject $recordSetItemData
     * @return array
     */
    public static function getRelationFieldValue($recordSetItemData){
        if ($recordSetItemData->FormField()->getRelationFieldType() == 'HasOne') {
           
            $displayFields = unserialize($recordSetItemData->FormField()->getRelationDisplayFields());
            if ($recordSetItemData->FormField()->getRelationRecordSlug() != null) {
                return self::getHasOneValueFromRecord($recordSetItemData, $displayFields);
            }

            if ($recordSetItemData->FormField()->getRelationModelSlug() != null) {
                return self::getHasOneValueFromModel($recordSetItemData, $displayFields);
            }
        }
    }

    /**
     * This function returns values of relation field in case of a Record relation
     *
     * @param DataObject $recordSetItemData
     * @param array $displayFields
     * @return string
     */
    public static function getHasOneValueFromRecord($recordSetItemData, $displayFields) {
        $ret = '';
        $recordSetItemDataValue = is_array(unserialize($recordSetItemData->Value)) ? unserialize($recordSetItemData->Value): $recordSetItemData->Value;
        $recordSetItem = RecordSetItem::get()->filter(['Slug' => $recordSetItemDataValue])->first();
        if ($recordSetItem) {
            foreach ($displayFields as $field) {
                $itemData = $recordSetItem->ItemData()->filter(['FormField.Slug' => $field])->first();
                if ($itemData) {
                    $ret.= $itemData->Value ? $itemData->Value.' ': '';
                }
            }
        }
        return $ret;
    }

    /**
     * This function returns values of relation field in case of a Model relation
     *
     * @param DataObject $recordSetItemData
     * @param array $displayFields
     * @return string
     */
    public static function getHasOneValueFromModel($recordSetItemData, $displayFields) {
        $ret = '';
        $modelItem = self::getOneBySlug($recordSetItemData->Value, $recordSetItemData->FormField()->getRelationModelSlug());
        if ($modelItem) {
            foreach ($displayFields as $field) {
                $ret .= $modelItem->{$field} ? $modelItem->{$field} .' ': '';
            }
        }
        return $ret;
    }
}
