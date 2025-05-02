<?php
namespace EveryDataStore\Extension;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\DataStore;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FieldList;

/** EveryDataStore/EveryDataStore v1.5
 *
 * This extension overwrites some methods of Group model, its relations and its permissions
 *
 * <b>Properties</b>
 *
 * @property string $Slug Unique identifier
 */
class GroupExtension extends DataExtension implements PermissionProvider {

    private static $db = [
        'Slug' => 'Varchar(110)',
        'Name' => 'Varchar(255)',
    ];

    private static $has_one = [
        'CreatedBy' => Member::class,
        'UpdatedBy' => Member::class,
        'DataStore' => DataStore::class,
    ];

    private static $summary_fields = [
        'Name',
        'Title',
        'Description'
    ];

    private static $searchable_fields = [
        'Name' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ]
    ];

    private static $FrontendTapedForm = true;
    private static $default_sort = "\"Name\"";
    /**
     * This function returns all user defined searchable field labels that exist on Group page
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
     * This function updates the default CMS fields for a Group DataObject
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->RemoveByName('Members', 'Main');
        $fields->RemoveByName(['ParentID']);
        $fields->addFieldToTab('Root.'._t('Global'. '.MAIN', 'Main'), ReadonlyField::create('Slug', 'Slug'));

        $fields->addFieldToTab('Root.'._t('Global'. '.MAIN', 'Main'), TextField::create('Name', _t($this->owner->ClassName . '.NAME', 'Name')));
        $fields->addFieldToTab('Root.'._t('Global'. '.MAIN', 'Main'), TextField::create('Description', _t('SilverStripe\\Security\\Group.Description', 'Description')));

}



    /**
     * This function customizes saving-behaviour for each DataObject
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if ($member) {
            if (!$this->owner->Slug) {
                $this->owner->Slug = EveryDataStoreHelper::getAvailableSlug('SilverStripe\Security\Group');
            }

            if (!$this->owner->CreatedByID) {
                $this->owner->CreatedByID = $member->ID;
            }
            
            $this->owner->DataStoreID = $member->CurrentDataStoreID;
            $this->owner->UpdatedByID = $member->ID;
            if($this->owner->Title !== 'Administrators' && $this->owner->Name){
                $this->owner->Title = strtolower(str_replace(' ', '-', EveryDataStoreHelper::getCurrentDataStore()->Title).'-'.$this->owner->Name);
            }
        }else{
           $this->owner->Title = $this->owner->ID;
        }
        
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        if ($this->owner->Members()->Count() < 2) {
            $this->owner->Members()->add(1);
        }
    }

    /**
     * This function customizes after-saving-behavior for each DataObject
     */
    public function onAfterDelete() {
        parent::onAfterDelete();
        if ($this->owner->Permissions()) {
            foreach ($this->owner->Permissions() as $permission) {
                $permission->delete();
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
        return EveryDataStoreHelper::checkPermission('VIEW_GROUP');
    }

    /**
     * This function should return true if the current user can edit an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. EDIT_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission('EDIT_GROUP');
    }

    /**
     * This function should return true if the current user can delete an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. DELTETE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('DELETE_GROUP');
    }

    /**
     * This function should return true if the current user can create new object of this class.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission('CREATE_GROUP');
    }

    /**
     * Return a map of permission codes for the DataObject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            'CREATE_GROUP' => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => 'Group',
                'sort' => 1
            ],
            'EDIT_GROUP' => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => 'Group',
                'sort' => 1
            ],
            'VIEW_GROUP' => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => 'Group',
                'sort' => 1
            ],
            'DELETE_GROUP' => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => 'Group',
                'sort' => 1
            ]);
    }
}
