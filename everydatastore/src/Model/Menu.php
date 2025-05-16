<?php

namespace EveryDataStore\Model;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\RecordSet;
use EveryDataStore\Model\DataStore;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;

/** EveryDataStore v1.5
 *
 * This class defines a Menu model and its appearance in the database
 * as well as in the EveryDataStore 'Menu' page.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier of the Menu
 * @property string $Title Name of the Menu item
 * @property bool $Active Activity status of the Menu
 * @property bool $AdminMenu Specifies whether the Menu item belongs to a Administration Menu
 * @property bool $UserMenu Specifies whether the Menu item belongs to User Menu
 * @property string $Controller Specifies whether the menu is concerned with
 *           RecordSets, workflow, User Account or some of the options listed in Administration Menu.
 * @property string $Action Takes value corresponding to $Controller (see User Documentation / Menu)
 * @property string $ActionID Slug of the page that opens after clicking on the menu item
 * @property string $ActionOtherID
 * @property string $Icon Name of the icon, i.e., a representative figure or image for the Menu
 * @property string $MobileAppIcon Name of the icon for the mobile app, i.e., a representative figure or image for the Menu
 * @property string $BadgeEndpoint
 * @property integer $Sort Specifies the position, i.e., the order of the Menu item
 *
 */

class Menu extends DataObject implements PermissionProvider {

    private static $table_name = 'Menu';
    private static $singular_name = 'Menu';
    private static $plural_name = 'Menu';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'Title' => 'Varchar(40)',
        'Active' => 'Boolean',
        'AdminMenu' => 'Boolean',
        'UserMenu' => 'Boolean',
        'Controller' => 'Varchar(40)',
        'Action' => 'Varchar(40)',
        'ActionID' => 'Varchar(110)',
        'ActionOtherID' => 'Varchar(100)',
        'Icon' => 'Varchar(50)',
        'MobileAppIcon' => 'Varchar(50)',
        'BadgeEndpoint' => 'Varchar(255)',
        'Sort' => 'Int(11)',
    ];
    private static $default_sort = "Sort ASC";
    private static $has_one = [
        'DataStore' => DataStore::class,
        'Parent' => self::class
    ];

    private static $has_many = [
        'Children' => Menu::class
    ];

    private static $belongs_to = [
        'RecordSet' => RecordSet::class
    ];


    private static $summary_fields = [
        'Active',
        'Title',
        'Controller',
        'Action',
        'AdminMenu',
        'UserMenu',
        'Icon',
        'Sort'
    ];


    private static $searchable_fields = [
        'Title' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Controller' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Action' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
    ];

    private static $default_records = [
            'Title' => 'My Menu',
            'Active' => 1,
            'AdminMenu' => false,
            'UserMenu' => false,
            'Controller' => '',
            'Action' => '',
            'ActionID' => '',
            'ActionOtherID' => '',
            'Icon' => 'fa fa-asterisk',
            'BadgeEndpoint' => '',
            'Sort' => 1
     ];

    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels(true);
        if (!empty(self::$summary_fields)) {
            $labels = EveryDataStoreHelper::getNiceFieldLabels($labels, __CLASS__, self::$summary_fields);
        }
        return $labels;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', ReadonlyField::create('Slug', 'Slug', $this->Slug));
        $fields->addFieldToTab('Root.Main', TextField::create('Title', _t(__Class__ .'.TITLE', 'Title')));
        $fields->addFieldToTab('Root.Main', CheckboxField::create('Active', _t(__Class__ .'.ACTIVE', 'Active')));
        $fields->addFieldToTab('Root.Main', CheckboxField::create('AdminMenu', _t(__Class__ .'.ADMINMENU', 'Admin menu')));
        $fields->addFieldToTab('Root.Main', CheckboxField::create('UserMenu', _t(__Class__ .'.USERMENU', 'User menu')));
        $fields->addFieldToTab('Root.Main', TextField::create('Controller', _t(__Class__ .'.CONTROLLER', 'Controller')));
        $fields->addFieldToTab('Root.Main', TextField::create('Action', _t(__Class__ .'.ACTION', 'Action')));
        $fields->addFieldToTab('Root.Main', TextField::create('ActionID', _t(__Class__ .'.ACTION_ID', 'Action ID')));
        $fields->addFieldToTab('Root.Main', TextField::create('ActionOtherID', _t(__Class__ .'.ACTION_OTHER_ID', 'Action other ID')));
        $fields->addFieldToTab('Root.Main', TextField::create('Icon', _t(__Class__ .'.ICON', 'Icon')));
        $fields->addFieldToTab('Root.Main', DropdownField::create('MobileAppIcon', _t(__Class__ .'.MOBILEAPPICON', 'Mobile App Icon'), $this->niceMobileAppIcons()));
        $fields->addFieldToTab('Root.Main', TextField::create('BadgeEndpoint', _t(__Class__ .'.BADGEENDPOINT', 'Badge Endpoint')));
        $fields->addFieldToTab('Root.Main', TextField::create('Sort', _t(__Class__ .'.SORT', 'Sort')));

        $fields->addFieldToTab('Root.Main', DropdownField::create('ParentID', _t(__Class__ .'.PARENT', 'Parent'), Menu::get()->filter(['DataStoreID' => EveryDataStoreHelper::getCurrentDataStoreID()])->Map(EveryDataStoreHelper::getMapField(), 'Title')->toArray())->setEmptyString(_t('Global.SELECTONE', 'Select one'))
        );

        $fields->removeFieldFromTab('Root.Main', 'DataStoreID');
        $fields->removeByName('Children');
        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if ($this->Sort == '' || $this->Sort < 1 ) {
            $this->Sort = DB::prepared_query(
                'SELECT MAX("Sort") + 1 FROM "' . self::$table_name . '" WHERE "' . self::$table_name . '"."ParentID" = ?', array($this->ParentID)
            )->value();
        }

        if (!$this->owner->Slug) {
            $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug(__CLASS__);
        }

        if (!$this->owner->DataStoreID) {
            $this->owner->DataStoreID =  EveryDataStoreHelper::getCurrentDataStoreID();
        }
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        if ($this->Children()->Count() > 0) {
            $this->Children()->Count()->removeAll();
        }
        parent::onAfterDelete();
    }

    public function Badge() {
        if ($this->BadgeEndpoint) {
            $URL = $this->BadgeEndpoint;
            if (strpos($this->BadgeEndpoint, 'http') == false) {
                $URL = Director::absoluteBaseURL() . $URL . '?apitoken=' . EveryDataStoreHelper::getNiceAPIToken();
            }
            try {
                $json = file_get_contents($URL);
                if ($json) {
                    return [
                        'Result' => json_decode($json),
                        'Endpoint' => $URL];
                }
            } catch (Exception $exc) {
                return $exc->getTraceAsString();
            }
        }
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        
        if($this->RecordSet() && !$this->RecordSet()->canView()){
            return false;
        }
        
        $this->Title = EveryDataStoreHelper::_t($this->Title);
        $member = EveryDataStoreHelper::getMember();
        if ($member) {
            if ($this->Controller == 'record' && $this->Action != null && $this->ActionID != null) {
                $recordSetPermission = Permission::get()->filter(array('Code' => $this->ActionID))->first();
                if ($recordSetPermission) {
                    return Permission::checkMember($member, $this->ActionID) ? true: false;
                }
            }

            if ($this->UserMenu == 1) {
                    return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("VIEW", $this));
            }


            if ($this->AdminMenu == 1) {
               if (EveryDataStoreHelper::checkPermission('ADMIN') ||
                   EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("CREATE", $this)) ||
                   EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("EDIT", $this))){
                    return true;
                }
                return false;
            }
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
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("EDIT", $this));
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("DELETE", $this));
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("CREATE", $this));
    }

    /**
     * Return a map of permission codes for the Dataobject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            EveryDataStoreHelper::getNicePermissionCode("CREATE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("EDIT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("VIEW", $this) => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("DELETE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],EveryDataStoreHelper::getNicePermissionCode("PRIMERY_MENU", $this) => [
                'name' => _t('SilverStripe\Security\Permission.PRIMERY_MENU', "PRIMERY_MENU"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("ADMIN_MENU", $this) => [
                'name' => _t('SilverStripe\Security\Permission.ADMIN_MENU', "ADMIN_MENU"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("USER_MENU", $this) => [
                'name' => _t('SilverStripe\Security\Permission.USER_MENU', "USER_MENU"),
                'category' => ClassInfo::shortname($this),
                'sort' => 1
            ],
            );
    }
    
    public function niceMobileAppIcons() {
        $mobileAppIcons = Config::inst()->get('menu_mobile_app_icon');
        $ret = [];
        if ($mobileAppIcons) {
            foreach ($mobileAppIcons as $key => $value) {
                $ret[$value] = $value;
            }
        }
        
        return $ret;
    }

}
