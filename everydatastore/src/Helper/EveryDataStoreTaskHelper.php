<?php
namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\Queries\SQLInsert;
use EveryDataStore\Model\Menu;
use EveryNotifyTemplate\Model\EveryNotifyTemplate;
use EveryDataStore\Model\RecordSet\Form\FormSection;
use EveryDataStore\Model\RecordSet\Form\FormFieldSetting;
use EveryDataStore\Helper\AssetHelper;
use SilverStripe\Security\Security;

/**
 * EveryDataStore v1.0
*/
class EveryDataStoreTaskHelper extends EveryDataStoreHelper {
   
    /**
     * Helper methods to import demo data
     * @param array $demoData
     */
   public static function doImportDemoData($demoData) {
        foreach ($demoData as $data) {
            if (
                    $data['type'] == 'table' && isset($data['data']) && count($data['data']) > 0 
            ) {
                foreach ($data['data'] as $d) {
                    if (self::getOneBySQLSelect($data['name'], $d['ID'])) {
                        echo '<p style="color: blue">The object of ' . $record['ClassName'] . ' with the ID ' . $d['ID'] . ' has been created/updated.</p>';
                        self::updateOneBySQLUpdate($data['name'], $d);
                    } else {
                        self::insertOneBySQLInsert($data['name'], $d);
                        echo '<p style="color: green">The object of ' . $d['ClassName'] . ' with the ID ' . $d['ID'] . ' has been created/updated.</p>';
                    }
                }
            }
        }
    }

    /**
     * Gets a record by SQLSelect
     * @param string $table
     * @param string $recordID
     * @return boolean|array
     */
    public static function getOneBySQLSelect($table, $recordID){
        $sqlQuery = new SQLSelect();
        $sqlQuery->setFrom("`".$table."`");
        $sqlQuery->selectField('ID');
        $sqlQuery->addWhere(array('ID' => $recordID));
        $result = $sqlQuery->execute();
        foreach ($result as $row) {
            if ($row['ID']) return $row;
        }
        return false;
    }

    /**
     * Updates a record by SQLUpdate
     * @param string $table
     * @param string $record
     */
    public static function updateOneBySQLUpdate($table, $record){
        //if(isset($record['FolderID'])) unset ($record['FolderID']);
        //if(isset($record['FileID'])) unset ($record['FileID']);
        $update = SQLUpdate::create("`".$table."`")->addWhere(array('ID' => $record['ID']));
        $update->addAssignments($record);
        $update->execute();
    }

    /**
     * Inserts a new record by SQLInsert
     * @param string $table
     * @param string $record
     */
    public static function insertOneBySQLInsert($table, $record){
        //if(isset($record['FolderID'])) unset ($record['FolderID']);
        //if(isset($record['FileID'])) unset ($record['FileID']);
        $insert = SQLInsert::create("`".$table."`");
        $insert->addRows(array($record));
        $insert->execute();
    }
    
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
