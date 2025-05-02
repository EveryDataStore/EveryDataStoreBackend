<?php
namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\Menu;
use EveryNotifyTemplate\Model\EveryNotifyTemplate;
use EveryDataStore\Model\RecordSet\Form\FormSection;
use EveryDataStore\Model\RecordSet\Form\FormFieldSetting;
use EveryDataStore\Helper\AssetHelper;
use SilverStripe\Security\Security;

/**
 * EveryDataStore v1.5
*/
class EveryDataStoreTaskHelper extends EveryDataStoreHelper {
   
    /**
     * This function checks if a menu item has a translation. 
     * @param string $translation
     * @return boolean
     */
    public static function isMenuTranslation($translation){
        $menu = Menu::get()->filter(['Title' => $translation])->first();
        return $menu ? true: false;
    }
    
     /**
     * This function checks if a section has a translation. 
     * @param string $translation
     * @return boolean
     */
    public static function isSectionTranslation($translation){
        $section = FormSection::get()->filter(['Title' => $translation])->first();
        return $section ? true: false;
    }
    
     /**
     * This function checks if a FieldSetting has a translation. 
     * @param string $translation
     * @return boolean
     */
    public static function isFieldSettingTranslation($translation){
        $formFieldSetting = FormFieldSetting::get()->filter(['Value' => $translation])->first();
        return $formFieldSetting ? true: false;
    }
    
     /**
     * This function checks if a EveryNotifyTemplate has a translation. 
     * @param string $translation
     * @return boolean
     */
    public static function isNotifyTemplateTranslation($translation) {
        $everyNotifyTemplates = EveryNotifyTemplate::get();
        $content = '';
        $searchString = '{{t_' . $translation . '}}';
        foreach ($everyNotifyTemplates as $template) {
            $content .= $template->Content;
        }
        $pos = strpos($content, $searchString);
        if ($pos !== false) {
            return true;
        }
        return false;
    }
   
    /**
     * This function updates all RecordSets and RecordSetItems after import data
     */
    public static function updateAllRecordsetItemsAfterImport() {
        $recordSets = \EveryDataStore\Model\RecordSet\RecordSet::get();
        $member = Security::getCurrentUser();
        if ($member) {
            foreach ($recordSets as $rs) {
                if (!AssetHelper::isFolderExists($rs->FolderID) && $rs->AllowUpload) {
                    $rs->FolderID = AssetHelper::createFolder($rs->Title, $member->CurrentDataStore()->Folder())->ID;
                }
                $rs->writeWithoutVersion();
                echo 'Update RecordSet: ' . $rs->ID . ', FolderID' . $rs->FolderID . '<br>';

                if ($rs->Items()->Count() > 0) {
                    foreach ($rs->Items() as $item) {
                        if (!AssetHelper::isFolderExists($item->FolderID) && $item->RecordSet()->AllowUpload && $item->Version > 0) {
                            $item->FolderID = AssetHelper::createFolder($item->Slug, $item->RecordSet()->Folder())->ID;
                        }
                        $item->writeWithoutVersion();
                        echo 'Update RecordSetItem: ' . $item->ID . ', FolderID' . $item->FolderID . '<br>';
                    }
                }
            }
        }
    }

}
