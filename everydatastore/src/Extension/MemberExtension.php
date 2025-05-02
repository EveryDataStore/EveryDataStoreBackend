<?php

namespace EveryDataStore\Extension;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Helper\EmailHelper;
use EveryDataStore\Model\DataStore;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\EmailField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\Data\Intl\IntlLocales;

/** EveryDataStore/EveryDataStore v1.5
 *
 * This extension overwrites some methods of the Member model, its relations and its permissions
 * It configures current dataStore according to the current member permissions and preferences
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier
 * @property boolean $Active Member's activity status
 * @property boolean $SendPasswordResetLink Initiates sending an E-Mail with a password reset link to the member
 * @property string $ThemeColor Member's chosen theme for the dataStore
 * @property string $Company Company name
 * @property string $Address Member's address
 * @property string $PostCode Post code of member's residential place
 * @property string $City City of residence
 * @property string $Country Country of residence
 * @property string $Phone Member's phone number
 *
 */
class MemberExtension extends DataExtension implements PermissionProvider {

    private static $db = array(
        'Slug' => 'Varchar(110)',
        'Active' => 'Boolean',
        'SendPasswordResetLink' => 'Boolean',
        'ThemeColor' => 'Varchar(20)',
        'Company' => 'Varchar(110)',
        'Address' => 'Varchar(110)',
        'PostCode' => 'Varchar(20)',
        'City' => 'Varchar(50)',
        'Country' => 'Varchar(50)',
        'Phone' => 'Varchar(50)',
    );
    private static $has_one = [
        'Admin' => Member::class,
        'Avatar' => Image::class,
        'CurrentDataStore' => DataStore::class,
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
    ];
    private static $belongs_many_many = array(
        'DataStores' => DataStore::class
    );

    private static $summary_fields = [
        'FirstName',
        'Surname',
        'Email',
    ];

    private static $defaults = [
        'Active' => true,
        'Locale' => 'en_US',
        'ThemeColor' => 'default',
        'AdminID' => 1,
        'CurrentDataStoreID' => 1,
    ];


    private static $indexes = [
        'MemberIndex' => ['Slug', 'Email'],
    ];

    private static $searchable_fields = [
        'FirstName' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Surname' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Email' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ]
    ];

    private static $FrontendTapedForm = true;
    private static $default_sort = "\"FirstName\"";
    /**
     * This function returns all user defined searchable field labels that exist on Member page
     * @param boolean $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels(true);
        if (!empty(self::$summary_fields)) {
            $labels = EveryDataStoreHelper::getNiceFieldLabels($labels, 'SilverStripe\Security\Member', self::$summary_fields);
        }

        return $labels;
    }


    /**
     * This function returns a full name of the member
     *
     * @return string
     */
    public function getFullName() {
        return $this->owner->FirstName . ' ' . $this->owner->Surname;
    }

    public function getMemberAdmin() {
        return $this->owner->Admin();
    }


    /**
     * This function updates the default CMS fields for a Member DataObject
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->removeFieldFromTab('Root.Main', ['RESTFulToken', 'Avatar', 'ThemeColor', 'AdminID', 'Slug', 'Active', 'SendPasswordResetLink', 'DirectGroups', 'UpdatedByID', 'CreatedByID', 'RESTFulTokenExpire', 'FailedLoginCount', 'RequiresPasswordChangeOnNextLogin']);
        $fields->RemoveByName(['Permissions', 'CurrentDataStore', 'DataStores']);
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), TextField::create('FirstName', _t($this->owner->ClassName . '.FIRSTNAME', 'Firstname')), 'Firstname');
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), TextField::create('Surname', _t($this->owner->ClassName . '.SURNAME', 'Surname')), 'Surname');
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), TextField::create('Company', _t($this->owner->ClassName . '.COMPANY', 'Company')), 'CurrentDataStore');
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main') , TextField::create('Address', _t($this->owner->ClassName . '.ADDRESS', 'Address')), 'CurrentDataStore');
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main') , TextField::create('PostCode', _t($this->owner->ClassName . '.POSTCODE', 'PostCode')));
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main') , TextField::create('City', _t($this->owner->ClassName . '.CITY', 'City')));
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main') , DropdownField::create('Country', _t($this->owner->ClassName . '.COUNTRY', 'Country'), IntlLocales::singleton()->getCountries()));
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), TextField::create('Phone', _t($this->owner->ClassName . '.PHONE', 'Phone')));
        
        
        if (EveryDataStoreHelper::checkPermission('VIEW_MEMBER') && EveryDataStoreHelper::checkPermission('CREATE_MEMBER') && EveryDataStoreHelper::checkPermission('EDIT_MEMBER')) {
            $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), ReadonlyField::create('Slug', 'Slug'), 'FirstName');
            $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), CheckboxField::create('Active', _t($this->owner->ClassName . '.ACTIVE', 'Active')), 'FirstName');
            $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), EmailField::create('Email', _t($this->owner->ClassName . '.EMAIL', 'Email')), 'Email');

            if (EveryDataStoreHelper::checkPermission('ADMIN')) {
                $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), CheckboxField::create('SendPasswordResetLink', _t($this->owner->ClassName . '.SENDPASSWORDRESETLINK', 'Send password reset link to user Email')), 'FirstName');
                $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.DATASTORES', 'Datastores'), ListboxField::create('DataStores', _t(__Class__ . '.DATASTORES', 'DataStores'), DataStore::get()->filter(['AdminID' => EveryDataStoreHelper::getCurrentDataStoreAdminID()])->Map(EveryDataStoreHelper::getMapField(), 'Title')->toArray()));
                $Groups = ListboxField::create('DirectGroups', _t(__Class__ . '.GROUPS', 'Groups'), Group::get()->Map('ID', 'Title')->toArray());
            } else {
                $Groups = ListboxField::create('Groups', _t(__Class__ . '.GROUPS', 'Groups'), Group::get()->filter(['DataStore.ID' => EveryDataStoreHelper::getCurrentDataStoreID()])->Map(EveryDataStoreHelper::getMapField(), 'Title')->toArray());
            }
            
            $fields->addFieldToTab('Root.' . _t('Global.GROUPS', 'Groups'), $Groups);
        }

        $Avatar = UploadField::create('Avatar', _t($this->owner->ClassName . '.AVATAR', 'Avatars'));
        $Avatar->setAllowedMaxFileNumber(1);
        $fields->addFieldToTab('Root.' . _t('Global.AVATAR', 'Avatar'), $Avatar, 'Active');

        
            $Avatar = UploadField::create('Avatar', _t($this->owner->ClassName . '.AVATAR', 'Avatars'));
            $Avatar->setAllowedExtensions(['png,jpg,jpeg']);
            $Avatar->setAllowedMaxFileNumber(1);
            $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), HiddenField::create('Slug', 'Slug'), 'FirstName');
            $fields->addFieldToTab('Root.' . _t('Global.AVATAR', 'Avatar'), $Avatar, 'Active');
            $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.SETTINGS', 'Settings'), DropdownField::create('Locale', _t($this->owner->ClassName . '.LANGUAGE', 'Language'), Config::inst()->get('Frontend_Languages'))->setEmptyString(_t('Global.SELECTONE', 'Select one')));
            $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.SETTINGS', 'Settings'), DropdownField::create('ThemeColor', _t($this->owner->ClassName . '.THEMECOLOR', 'Theme Color'), Config::inst()->get('Frontend_Themes'))->setEmptyString(_t('Global.SELECTONE', 'Select one')));
            
        //if (!EveryDataStoreHelper::checkPermission('ADMIN')) {
            $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.CHANGEPASSWORD', 'Change password'), PasswordField::create('OldPassword', _t($this->owner->ClassName . '.OLDPASSWORD', 'Old password'))->setAttribute('autocomplete', 'off'));
            $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.CHANGEPASSWORD', 'Change password'), PasswordField::create('Password', _t($this->owner->ClassName . '.PASSPWORD', 'Password'), ''));
            $fields->addFieldToTab('Root.' . _t($this->owner->ClassName . '.CHANGEPASSWORD', 'Change password'), PasswordField::create('ConfirmPassword', _t($this->owner->ClassName . '.CONFIRMPASSPWORD', 'Confirm password')));
        //}
    }

    /**
     * This function customizes saving-behaviour for each DataObject
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if (isset($member)) {
            if (!$this->owner->Slug) {
                $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug('SilverStripe\Security\Member');
            }

            if (!$this->owner->CreatedByID) {
                $this->owner->CreatedByID = $member->ID;
            }

            if (!$this->owner->CurrentDataStoreID) {
                $this->owner->CurrentDataStoreID = $member->CurrentDataStoreID;
            }

            $this->owner->AdminID = EveryDataStoreHelper::getCurrentDataStoreAdminID();
            $this->owner->UpdatedByID = $member->ID;
        }

       /* if ($this->owner->SendPasswordResetLink == 1) {
            $this->owner->SendPasswordResetLink = 0;
            $themember = Member::get()->ByID($this->owner->ID);
            $themember->generateAutologinTokenAndStoreHash(10);
            $AutoLoginHash = $themember->AutoLoginHash;
            EmailHelper::sendPasswordResetLink($themember, $AutoLoginHash);
        }
        * 
        */
    }

    /**
     * This function resets "SendPasswordResetLink" value after the link has been sent
     */
    public function onAfterWrite() {
        parent::onAfterWrite();
        if ($this->owner->SendPasswordResetLink == 1) {
            $themember = Member::get()->ByID($this->owner->ID);
            $themember->SendPasswordResetLink = false;
            $themember->write();
            $themember->generateAutologinTokenAndStoreHash(10);
            $AutoLoginHash = $themember->AutoLoginHash;
            EmailHelper::sendPasswordResetLink($themember, $AutoLoginHash);
        }
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
    }

    /**
     *
     * @return string
     */
    public function avatarURL() {
        if ($this->owner->Avatar() && $this->owner->Avatar()->ID > 0) {
            return $this->owner->Avatar()->getThumbnailURL();
        }
    }

    /**
     *
     * @return string
     */
    public function Icon() {
        if ($this->owner->Avatar() && $this->owner->Avatar()->ID > 0) {
           return $this->owner->Avatar()->Fit(30,30)->getAbsoluteURL().'?hash='.$this->owner->Avatar()->FileHash;
        }
    }

    /**
     * This function configures dataStore settings for the current member
     * @return array
     */
    public function Settings() {
        return EveryDataStoreHelper::getDataStoreSettings($this->owner);
    }

    /**
     * This function checks whether the current user has admin role
     * @return boolean
     */
    public function isAdmin() {
        if (Permission::checkMember(Security::getCurrentUser(), 'ADMIN')) {
            return true;
        }
    }


    /**
     * This function configures allowed actions according to the permission codes
     * for the current member
     * @param array $params
     * @return array
     */
    public function Permissions($params) {
        $permissions = EveryDataStoreHelper::getNicePermissionCodes();
        $member = $params && isset($params['Slug']) ? Member::get()->filter(['Slug' => $params['Slug']])->first() : null;
        $memberPermissions = [];
        if ($permissions) {
            foreach ($permissions as $permission) {
                if (isset($permission['title']) && isset($permission['permissions'])) {
                    $codes = [];
                    foreach ($permission['permissions'] as $key => $val) {
                        if ($key == 'CREATE_MEMBER') {
                            $codes[$key] = $member->canCreate();
                        } elseif ($key == 'CREATE_RECORD') {
                            if (EveryDataStoreHelper::getCurrentDataStore()->Records()->Count() < EveryDataStoreHelper::getCurrentDataStore()->RecordSetAllowedNumber) {
                                $codes[$key] = Permission::check($key, 'any', $member) ? true : false;
                            } else {
                                $objectRecord = Injector::inst()->create('EveryDataStore\Model\RecordSet\RecordSet');
                                $codes[$key] = $objectRecord->canCreate() ? true : false;
                            }
                        } elseif ($key == 'CREATE_RECORDITEM') {
                            if (EveryDataStoreHelper::getCurrentDataStoreRecordSetItemsCount() < EveryDataStoreHelper::getCurrentDataStore()->RecordSetItemAllowedNumber) {
                                $codes[$key] = Permission::check($key, 'any', $member) ? true : false;
                            } else {
                                $objectRecordSetItem = Injector::inst()->create('EveryDataStore\Model\RecordSet\RecordSetItem');
                                $codes[$key] = $objectRecordSetItem->canCreate() ? true : false;
                            }
                        } else {
                            $codes[$key] = Permission::check($key, 'any', $member) ? true : false;
                        }
                    }
                    $memberPermissions[$permission['title']] = $codes;
                }
            }
        }
        return $memberPermissions;
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        if(EveryDataStoreHelper::isTechnicalUser($this->owner->Email)){
            return Permission::check('ADMIN') ? true : false;
        }
        
        return EveryDataStoreHelper::checkPermission('VIEW_MEMBER');
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission('EDIT_MEMBER');
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('DELETE_MEMBER');
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission('CREATE_MEMBER');
    }

    /**
     * Return a map of permission codes for the DataObject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            'CREATE_MEMBER' => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATEs"),
                'category' => 'Member',
                'sort' => 1
            ],
            'EDIT_MEMBER' => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => 'Member',
                'sort' => 1
            ],
            'VIEW_MEMBER' => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => 'Member',
                'sort' => 1
            ],
            'DELETE_MEMBER' => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => 'Member',
                'sort' => 1
            ]);
    }
}
