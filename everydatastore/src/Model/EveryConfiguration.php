<?php

namespace EveryDataStore\Model;

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;

/** EveryDataStore v1.0
 *
 * This class defines a Configuration model and its appearance in the database
 * as well as in the EveryDataStore 'Configurations' page.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier of the Configuration
 * @property string $Title Name of the Configuration
 * @property string $Value Functionality of the Configuration
 *
 */


class EveryConfiguration extends DataObject implements PermissionProvider {
    private static $table_name = 'EveryConfiguration';
    private static $singular_name = 'EveryConfiguration';
    private static $plural_name = 'EveryConfigurations';
    private static $db = [
        'Slug' => 'Varchar(110)',
        'Title' => 'Varchar(100)',
        'Value' => 'Text'
    ];
    private static $default_sort = "\"Title\"";
    private static $has_one = [
        'DataStore' => DataStore::class
    ];
    private static $has_many = [];
    private static $belongs_to = [];
    private static $summary_fields = [
        'Title',
        'Value',
        'Created'
    ];
    private static $searchable_fields = [
        'Title' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Value' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Created' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ]
    ];

    private static $default_records = [
            'Title' => 'My Title',
            'Value' => 'My Value'
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
        $fields->addFieldToTab('Root.Main', TextField::create('Title', _t(__Class__ . '.TITLE', 'Titless')));
        $fields->addFieldToTab('Root.Main', TextareaField::create('Value', _t(__Class__ . '.VALUE', 'Valuess')));
        $fields->removeFieldFromTab('Root.Main', 'DataStoreID');
        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if (!$this->owner->Slug) {
            $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug(__Class__);
        }

        if (!$this->owner->DataStoreID && $member) {
            $this->owner->DataStoreID = $member->CurrentDataStoreID;
        }
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        return true;
        //return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("VIEW", $this));
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("EDIT", $this), $member);
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("DELETE", $this), $member);
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("CREATE", $this), $member);
    }

    /**
     * Return a map of permission codes for the DataObject and they can be mapped with Members, Groups or Roles
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
        ]);
    }
}
