<?php

namespace EveryDataStore\Task;

use EveryDataStore\Helper\EveryDataStoreTaskHelper;
use EveryDataStore\Helper\AssetHelper;
use EveryWidget\Helper\EveryWidgetHelper;
use EveryDataStore\Helper\LoggerHelper;
use EveryWidget\Model\EveryWidget;
use EveryTranslator\Model\EveryTranslator;
use EveryDataStore\Model\DataStore;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Security;


/**
 * EveryDataStore v1.5
 * This class contains tasks to keep the EverDataStore database and file system clean.
 *
 **/

class EveryDataStoreTasks extends BuildTask {
    private static $segment = 'EveryDataStoreTask';
    protected $title = 'EveryDataStore Tasks';
    protected $description = 'EveryDataStore Tasks';

    public function run($request) {
        
        if (Director::is_cli() && !EveryDataStoreTaskHelper::getMemberByEmailAndPassword(Config::inst()->get('cron_member', 'email'), Config::inst()->get('cron_member', 'password'), $request)) {
            echo "Login failed for cron user " .Config::inst()->get('cron_member', 'email')."\n";
            LoggerHelper::error("login failed for cron user ".Config::inst()->get('cron_member', 'email'), EveryDataStoreTasks::class);
            return;
        }
        
        if (!Director::is_cli() && !Security::getCurrentUser()) {
            return header("Location: ".Director::absoluteBaseURL()."/Security/login") or die();
        }
        
        if (!Director::is_cli()) {
            echo '<h3>Select a task:</h3>';
            echo '<ul style="list-style:square">';
            echo '<li style="margin-bottom:10px"><a href="?action=deleteDraftRecordSetItems">Delete Draft RecordSetItems</a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=emptyTmpDir">Empty Tmp Directory: '.Config::inst()->get('everydatastore_tmp_dir_path').' </a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=createWidgetJsonFile">Create Widget JSON File</a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=deleteUnusedTranslations">Delete Unused Translations</a></li>';
             echo '<li style="margin-bottom:10px"><a href="?action=deleteDeletedRecordSetItems">Delete Deleted RecordSetItems</a></li>';
            echo '</ul>';
        }
       
        if ($request->getVar('action') !== '') {
            echo $request->getVar('action');
           $this->doAction($request->getVar('action'));
        }
    }

    /**
     * Run task by given action
     * @param string $action
     */
    private function doAction($action){
        switch ($action) {
            case 'deleteDraftRecordSetItems':
                $this->deleteDraftRecordSetItems();
                break;
            case 'emptyTmpDir':
                $this->emptyTmpDir();
                break;
            case 'createWidgetJsonFile':
                $this->createWidgetJSONFile();
                break;
            case 'truncateVersions':
                $this->truncateVersions();
                break;
            case 'updateStorageCurrentSize':
                $this->updateStorageCurrentSize();
                break;
            case 'deleteUnusedTranslations':
                $this->deleteUnusedTranslations();
                break;
            case 'updateAlleRecordsetItems':
                $this->updateAllRecordsetItemsAfterImport();
                break;
            case 'deleteDeletedRecordSetItems':
                $this->deleteDeletedRecordSetItems();
                break;
        }
    }

    /**
     * This task job updates asset storage size of a datastore
     */
    private function updateStorageCurrentSize() {
        if (!Director::is_cli()) return false;
        $storages = DataStore::get()->filter(['Active' => true]);
        if ($storages) {
            foreach ($storages as $storage) {
                $storage->StorageCurrentSize = AssetHelper::getDirSize(ASSETS_PATH . '/.protected/' . $storage->Folder()->Filename);
                $storage->write();
            }
        }
    }

    /**
     * This task job deletes draft recordSet items
     */
    private function deleteDraftRecordSetItems() {
        $tmpItems = \EveryDataStore\Model\RecordSet\RecordSetItem::get()->filter(['Version' => 0]);
        $count = $tmpItems->Count();
        foreach ($tmpItems as $tmpItem) {
            $tmpItem->delete();
        }
        echo '<p style="color:green"> '.$count.' RecordSetItems has been deleted</p>';
    }
    
    private function deleteDeletedRecordSetItems(){
        $deletedItems = \EveryDataStore\Model\RecordSet\RecordSetItem::get()->filter(['DeletionDate:LessThanOrEqual' => date('Y-m-d')]);
        $count = $deletedItems->Count();
        foreach ($deletedItems as $deletedItem) {
            $deletedItem->delete();
        }
        echo '<p style="color:green"> '.$count.' RecordSetItems has been deleted</p>';
    }
    /**
     * This task cleans tmp directory
     */
    private function emptyTmpDir() {
        $dir = Config::inst()->get('everydatastore_tmp_dir_path');
        if (file_exists($dir)) {
            $this->delete_file($dir);
        }
    }

    /**
     * This task job creates widget json file
     */
    private function createWidgetJSONFile() {
        $ds = DataStore::get()->filter(['Active' => true]);

        $widgetsCount = 0;
        foreach ($ds as $d) {
            $widgets = EveryWidget::get()->filter(['DataStoreID' => $d->ID, 'Active' => true]);
            if ($widgets->Count() > 0) {
                if($d->FolderID > 0){
                $this->createWidgetJSONFileContent($widgets, $d);
                $widgetsCount += $widgets->Count();
                }
            }
        }
        
        if ($widgetsCount == 0) {
            $widgetsFile = \SilverStripe\Assets\File::get()->filter(['Name' => 'widgets.json'])->first();
            if ($widgetsFile) {
                $widgetsFile->delete();
                echo '<p>Widget file has been deleted: ' . $widgetsFile->Filename . '</p>';
            }
            echo '<p>No widgets where found!</p>';
        }
    }

    /**
     * Helper function to create widget json file
     * @param DatObject $widgets
     * @param DataObject $datastore
     */
    private function createWidgetJSONFileContent($widgets, $datastore) {
        $widgetsFileDir = ASSETS_PATH . '/.protected/' . str_replace(' ', '-', $datastore->Folder()->Filename);
        if (!file_exists($widgetsFileDir)) {
            mkdir($widgetsFileDir, 0777, true);
        }
        
        if (!file_exists($widgetsFileDir.'widgets.json')) {
            fopen($widgetsFileDir.'widgets.json', 'w') or die("Can't create file");
        }

        if (file_exists($widgetsFileDir.'widgets.json')) {
            $items = [];
            foreach ($widgets as $w) {
                if($w->Type !== 'timeTracker' && $w->Type !== 'uploadButton'){
                    $items[$w->Slug] = EveryWidgetHelper::{$w->Type . 'Data'}($w);
                }
            }

            file_put_contents($widgetsFileDir.'widgets.json', json_encode($items));
            echo '<p>Widget file has been created: ' . $widgetsFileDir.'widgets.json' . '</p>';
        }
    }

    /**
     * Delete all directory files
     * @param string $dir
     */
    private function delete_file($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->delete_file($file);
            } else {
                unlink($file);
                echo '<p style="color: green">The file '.$file.' has been deleted</p>';
            }
        }
        rmdir($dir);
        echo '<p style="color: green">The directory '.$dir.' has been deleted</p>';
    }
    
    /**
     * This function deletes Unused EveryTranslator items
     */
    private function deleteUnusedTranslations(){
        $items = EveryTranslator::get();
        $counter = 0;
        foreach($items as $item){
            if(
               !EveryDataStoreTaskHelper::isMenuTranslation($item->Title) &&
               !EveryDataStoreTaskHelper::isFieldSettingTranslation($item->Title) &&  
               !EveryDataStoreTaskHelper::isNotifyTemplateTranslation($item->Title) &&
               !EveryDataStoreTaskHelper::isSectionTranslation($item->Title) 
              ){
                $item->delete();
                $counter++;
               }
        }
  
        if($counter > 0) echo '<p style="color: green">'.$counter.' translations have been deleted.</p>';
        else echo '<p style="color: green">EveryTranslator items are clean</p>';
    }
    
    private function updateAllRecordsetItemsAfterImport() {
        EveryDataStoreTaskHelper::updateAllRecordsetItemsAfterImport();
    }
}
