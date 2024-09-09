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
use SilverStripe\Assets\File;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Security;


/**
 * EveryDataStore v1.0
 * This class contains tasks to keep the EverDataStore database and file system clean.
 *
 **/

class EveryDataStoreTasks extends BuildTask {
    private static $segment = 'EveryDataStoreTask';
    protected $title = 'EveryDataStore Tasks';
    protected $description = 'EveryDataStore Tasks';

    public function run($request) {
        
        if (Director::is_cli() && !EveryDataStoreTaskHelper::getMemberByEmailAndPassword(Config::inst()->get('cron_member', 'email'), Config::inst()->get('cron_members', 'password'), $request)) {
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
            echo '<li style="margin-bottom:10px"><a href="?action=importDemoData">Import EveryDataStore Demo Data</a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=deleteDraftRecordSetItems">Delete Draft RecordSetItems</a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=emptyTmpDir">Empty Tmp Directory: '.Config::inst()->get('everydatastore_tmp_dir_path').' </a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=createWidgetJsonFile">Create Widget JSON File</a></li>';
            echo '<li style="margin-bottom:10px"><a href="?action=deleteUnusedTranslations">Delete Unused Translations</a></li>';
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
            case 'importDemoData':
                $this->importDemoData();
                break;
            case 'updateAlleRecordsetItems':
                $this->updateAllRecordsetItemsAfterImport();
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
                $this->createWidgetJSONFileContent($widgets, $d);
                $widgetsCount += $widgets->Count();
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
     * Import demo data from json file in /everydatastore/demodata/repm.json
     */
    private function importDemoData() {
        $demoDataFile = Config::inst()->get('demo_data_file_path') ? Config::inst()->get('demo_data_file_path') : 'NOTHING';
        if (is_file($demoDataFile) && pathinfo($demoDataFile, PATHINFO_EXTENSION) == 'json') {
            $data = file_get_contents($demoDataFile);
            if (EveryDataStoreTaskHelper::json_validator($data)) {
                EveryDataStoreTaskHelper::doImportDemoData(json_decode($data, true));
            }
        }
    }

    /**
     * Helper function to create widget json file
     * @param DatObject $widgets
     * @param DataObject $datastore
     */
    private function createWidgetJSONFileContent($widgets, $datastore) {
        $wj = File::get()->filter(['Name' => 'widgets.json', 'ParentID' => $datastore->FolderID])->first();
        if (!$wj) {
            $file = Injector::inst()->create('\SilverStripe\Assets\File');
            $file->setFromString('{}', $datastore->Folder()->Filename . 'widgets.json');
            $file->writeWithoutVersion();
        }
        
        $items = [];
        foreach ($widgets as $w) {
            $items[$w->Slug] = EveryWidgetHelper::{$w->Type . 'Data'}($w);
        }

        file_put_contents( ASSETS_PATH . '/.protected/' . $datastore->Folder()->Filename . 'widgets.json', json_encode($items));
        echo '<p>Widget file has been created: '. ASSETS_PATH . '/.protected/' . $datastore->Folder()->Filename . 'widgets.json'.'</p>';
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
