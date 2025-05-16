<?php

namespace EveryDataStore\Model;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Helper\AssetHelper;
use EveryDataStore\Model\Menu;
use EveryDataStore\Model\App;
use EveryDataStore\Model\EveryConfiguration;
use EveryDataStore\Model\RecordSet\RecordSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Security\Group;
use SilverStripe\Assets\Folder;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ListboxField;

/**
 * EveryDataStore v1.5
 * Repository (DataStore) Model is the most important model of EverDataStore.
 * All models are independent directly/indirectly from Repository
 *
 * <b>Properties</b>
 *
 * @property string $Title Name of the repository
 * @property string $Slug EveryDataStore-ID of repository
 * @property bool $Active Activity status of the DataStore
 * @property integer $StorageAllowedSize
 * @property integer $StorageCurrentSize
 * @property string $UploadAllowedExtensions
 */

class DataStore extends DataObject implements PermissionProvider {
    private static $table_name = 'DataStore';
    private static $singular_name = 'DataStore';
    private static $plural_name = 'DataStores';
    private static $db = [
        'Active' => 'Boolean',
        'Title' => 'Varchar(100)',
        'Slug' => 'Varchar(110)',
        'StorageAllowedSize' => 'Int(11)',
        'StorageCurrentSize' => 'Int(11)',
        'UploadAllowedExtensions' => 'Varchar(500)'
    ];

    private static $default_sort = "\"Title\"";
    private static $has_one = [
        'Admin' => Member::class,
        'Folder' => Folder::Class
    ];

    private static $has_many = [
        'Menu' => Menu::class,
        'Records' => RecordSet::class,
        'Groups' => Group::class,
        'Configurations' => EveryConfiguration::class,
    ];

    private static $many_many = [
        'Members' => Member::class,
        'Apps' => App::class,
    ];

    private static $many_many_extraFields = [
        'Apps' => [
            'AppSlug' => 'Varchar(110)',
            'AppChildren' => 'Varchar(110)',
            'AppActive' => 'Boolean',
            'AppVersion' => 'Varchar(50)',
            'AppMenuID' => 'Varchar(100)',
            'AppInstalled' => 'Datetime'
            ]
    ];

     private static $default_records = [
            [
                'Active' => true,
                'AdminID' => 1,
                'Title' => 'DataStore1',
                'UploadAllowedExtensions' => '["jpeg","jpg","png","pdf"]', // Default file extensions
                'StorageAllowedSize' => 10737418240, // Bytes => 10 GB.
                'StorageCurrentSize' => 0
            ]
    ];

    private static $summary_fields = [
        'Slug' => 'Slug',
        'Title' => 'Title'
    ];

    private static $field_labels = [
        'Slug' => 'Slug',
        'Title' => 'Title'
    ];

    private static $belongs_to = [];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', new ReadonlyField('Slug', 'Slug'));
        $fields->addFieldToTab('Root.Settings', new NumericField('StorageAllowedSize', 'StorageAllowedSize'));
        $fields->addFieldToTab('Root.Settings', new ReadonlyField('StorageCurrentSize', 'StorageCurrentSize'));
        $fields->addFieldToTab('Root.Settings', new ListboxField('UploadAllowedExtensions', 'UploadAllowedExtensions', EveryDataStoreHelper::getNiceFileExtensions()));
        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->Slug) {
            $this->Slug = EveryDataStoreHelper::getAvailableSlug(__CLASS__);
        }

        if (!$this->FolderID && EveryDataStoreHelper::getMember()) {
            $this->FolderID = AssetHelper::createFolder($this->Title, AssetHelper::getAssetRootDir())->ID;
        }

        if($this->AdminID == 1 && EveryDataStoreHelper::getCurrentDataStoreAdminID()){
            $this->AdminID = EveryDataStoreHelper::getCurrentDataStoreAdminID();
        }
        
        $this->createDefaultGroups();
        $this->createDeafultMembers();
        if ($this->Menu()->Count() == 0) {
            $this->createDefaultMenus();
        }
        if ($this->Configurations()->Count() == 0) {
            $this->createDefaultConfig();
        }
        if ($this->Apps()->Count() == 0) {
            $this->installDefaultApps();
        }
        $this->setAdminCurrentDataStore();
    }
    
    /**
     * NOT WORKING!!!!!
     */
    public function onAfterWrite() {
        parent::onAfterWrite();
    }

    /**
     * Deletes DataStore relations
     */
    public function onAfterDelete() {
        parent::onAfterDelete();
        if ($this->Menu()->Count() > 0) {
                EveryDataStoreHelper::deleteDataObjects($this->Menu());
            }

            if ($this->Groups()->Count() > 0) {
                 EveryDataStoreHelper::deleteDataObjects($this->Groups());
            }

            if ($this->Configurations()->Count() > 0) {
                EveryDataStoreHelper::deleteDataObjects($this->Configurations());
            }

            if ($this->Records()->Count() > 0) {
                EveryDataStoreHelper::deleteDataObjects($this->Records());
            }

            if ($this->Members()->Count() > 0) {
                $this->Members()->removeAll();
            }

            if ($this->Apps()->Count() > 0) {
                $this->Apps()->removeAll();
            }
    }
    
    /**
     * Creates DataStore default members
     */
    private function createDeafultMembers() {
        $adminsGroup = Group::get()->filter(['Code' => strtolower($this->owner->Title . '-admins')])->first();
        if ($adminsGroup) {
            $default_admin = EveryDataStoreHelper::getDefaultAdmin('admin');
            if (!$default_admin) {
                $default_admin = EveryDataStoreHelper::createDefaultAdmin();
            }
            $adminsGroup->Members()->add($default_admin);
            $this->owner->Members()->add($default_admin->ID);

            $default_member = EveryDataStoreHelper::getDefaultMember('default_member');
            if (!$default_member) {
                $default_member = EveryDataStoreHelper::createDefaultMember('default_member');
            }
            $adminsGroup->Members()->add($default_member);
            $this->owner->Members()->add($default_member->ID);

            $asset_viewer_member = EveryDataStoreHelper::getDefaultMember('asset_viewer_member');
            if (!$asset_viewer_member) {
                $asset_viewer_member = EveryDataStoreHelper::createDefaultMember('asset_viewer_member');
            }
            $adminsGroup->Members()->add($asset_viewer_member);
            $this->owner->Members()->add($asset_viewer_member->ID);

            $cron_member = EveryDataStoreHelper::getDefaultMember('cron_member');
            if (!$cron_member) {
                $cron_member = EveryDataStoreHelper::createDefaultMember('cron_member');
            }
            $adminsGroup->Members()->add($cron_member);
            $this->owner->Members()->add($cron_member->ID);
        }
    }

    /**
     * Sets DataStore Admin
     */
    private function setAdminCurrentDataStore() {
        $Admin = EveryDataStoreHelper::getMember();
        if ($Admin && $Admin->CurrentDataStoreID != $this->ID) {
            $Admin->CurrentDataStoreID = $this->ID;
            $Admin->write();
            return $this->Members()->add($Admin->ID);
        }
    }

    /**
     * All DataStore Settings
     * @return array
     *
     */
    public function Settings() {
        return EveryDataStoreHelper::getDataStoreSettings(EveryDataStoreHelper::getMember());
    }

    /**
     * return current dataStore allowed extensions for upload
     * @return array
     */
    public function getUploadAllowedFileExtensions(){
        $ret = [];
        $allowedFileExtensions =  explode(',', str_replace(['[',']', '"'], '', $this->UploadAllowedExtensions));
        if($allowedFileExtensions){
            foreach($allowedFileExtensions as $ext){
               $ret[] = ['label' => strtoupper($ext), 'value' => '.'.$ext];
            }
        }
        return $ret;
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        if(EveryDataStoreHelper::checkPermission('ADMIN')){
            return  true;
        }
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("VIEW", $this));
    }


    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }

    /**
     * Creates default dataStore menu
     */
    private function createDefaultMenus() {
        $menuItems = array(
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_DATABASES', 'Databases'),
                'Controller' => 'record',
                'AdminMenu' => true,
                'Icon' => 'fa fa-database'
            ),array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_MEMBERS', 'Members'),
                'Controller' => 'Member',
                'AdminMenu' => true,
                'Icon' => 'fa fa-users'
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_GROUPS', 'Groups'),
                'Controller' => 'Group',
                'AdminMenu' => true,
                'Icon' => 'fa fa-layer-group',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_MENU', 'Menu'),
                'Controller' => 'Menu',
                'AdminMenu' => true,
                 'Icon' => 'fa fa-bars',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_CONFIGURATIONS', 'Configurations'),
                'Controller' => 'EveryConfiguration',
                'AdminMenu' => true,
                'Icon' => 'fa fa-tools',
            ),
             array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_APPS', 'Apps'),
                'Controller' => 'App',
                'AdminMenu' => true,
                'Icon' => 'fa fa-boxes',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_TRANSLATOR', 'Translator'),
                'Controller' => 'EveryTranslator',
                'AdminMenu' => true,
                'Icon' => 'fa fa-language',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_WIDGETS', 'Widgets'),
                'Controller' => 'EveryWidget',
                'AdminMenu' => true,
                'Icon' => 'fa fa-table',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_NUMBERING', 'Numbering'),
                'Controller' => 'EveryNumbering',
                'AdminMenu' => true,
                'Icon' => 'fa fa-list-ol',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_DEFAULTFOLDERS', 'Default Folders'),
                'Controller' => 'EveryDefaultFolder',
                'AdminMenu' => true,
                'Icon' => 'fa fa-folder',
            ),
            array(
                'Title' => _t('EveryDataStore\MODEL\MENU.ADMIN_NOTIFYTEMPLATES', 'Notify Templates'),
                'Controller' => 'EveryNotifyTemplate',
                'AdminMenu' => true,
                'Icon' => 'fa fa-palette',
            ),
             array(
                'Title' => _t('EveryDataStore\MODEL\MENU.USER_ACCOUNT', 'Account'),
                'Controller' => 'account/profile',
                'UserMenu' => true,
                'Icon' => 'fa fa-user'
            ),
        );
        $i = 1;
        foreach ($menuItems as $menuItem) {
            $menu = new Menu();
            $menu->Active = true;
            $menu->Title = $menuItem['Title'];
            $menu->Controller = $menuItem['Controller'];
            $menu->Sort = $i;
            $menu->Icon = $menuItem['Icon'];
            $menu->AdminMenu = isset($menuItem['AdminMenu']) && $menuItem['AdminMenu'] == true ? true : false ;
            $menu->UserMenu = isset($menuItem['UserMenu']) && $menuItem['UserMenu'] == true ? true : false ;
            $menu->DataStoreID = $this->owner->ID;
            $menu->write();
            $i++;
        }
    }

    /**
     * Creates default dataStore groups
     */
    private function createDefaultGroups() {
        $checkGroup = Group::get()->filter(['Title' => 'Admins', 'DataStoreID' => $this->owner->ID])->first();
        if (!$checkGroup) {
            $group = new Group();
            $group->Title = 'Admins';
            $group->Name = 'Admins';
            $group->DataStoreID = $this->owner->ID;
            $group->write();
            $RepoAdmin = $this->owner->Admin();
            $group->Members()->add($RepoAdmin);
            $permission = new Permission();
            $permission->Code = 'ADMIN';
            $permission->GroupID = $group->ID;
            $permission->Arg = 0;
            $permission->write();
            $group->Permissions()->add($permission);
        }
    }

    private function installDefaultApps(){
        $apps = App::get()->filter(['Active' => 1]);
        if($apps){
            foreach($apps as $app){
                \EveryRESTfulAPI\Helper\CustomAppHelper::setupApp($app);
            }
        }
    }

    /**
     * Creates default dataStore configurations
     */
    private function createDefaultConfig() {
        $defaultConfig = [
            'DateFormat' => 'd.m.Y',
            'DateTimeFormat' => 'd.m.Y H:m:s',
            'Timezone' => 'Europe/Berlin',
            'TimeFormat' => 'H:m:s',
            'ItemsPerPage' => 10,
            'MenuBadgeUpdateInterval' => 50000, // Milliseconds
            'UploadAllowedFileSize' => 2097152, // 2 MB.
            'UploadAllowedFileNumber' => 1,
            'number_format' => '[{
                "decimal":"2",
                "decimal_separator":",",
                "thousand_separator":",",
                }]',
            'formbuilder_unit' => '[{
		"value": "t",
		"label": "tonne",
                "symbol": "t",
                },
                "value": "kg",
		"label": "kilogram",
                "symbol": "kg",
                },
                {
		"value": "g",
		"label": "gram",
                "symbol": "g"
                },
                {
		"value": "km",
		"label": "Kilometer",
                "symbol": "km"
                },
                {
		"value": "m",
		"label": "Meter",
                "symbol": "m"
                },
                {
		"value": "cm",
		"label": "centimeter",
                "symbol": "cm"
                },
                {
		"value": "mm",
		"label": "millimeter",
                "symbol": "mm"
                }]',
            'formbuilder_currency'=> '[
                {
                        "value": "euro",
                        "label": "EUR"
                },
                {
                        "value": "usd",
                        "label": "USD"
                },
                {
                        "value": "yen",
                        "label": "YEN"
                }]'];
        foreach($defaultConfig as $k => $v){
            $config = new EveryConfiguration();
            $config->Title = $k;
            $config->Value = $v;
            $config->DataStoreID = $this->owner->ID;
            $config->write();
        }
    }

    /**
     * Return a map of permission codes for the Dataobject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            EveryDataStoreHelper::getNicePermissionCode("VIEW", $this) => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ]);
    }
}
