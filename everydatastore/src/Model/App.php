<?php

namespace EveryDataStore\Model;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\DataStore;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\ClassInfo;

/** EveryDataStore v1.5
 *
 * This class defines an App model and its appearance in the database as well as
 * in the EveryDataStore 'Apps' page.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property bool $Active Activity status of the App
 * @property string $Slug Unique identifier of the App
 * @property string $Title Name of the App
 * @property string $ShortDescription Compendious description of the App
 * @property string $Description In-depth description of the App
 * @property string $Author Name of the author of the App
 * @property string $Website Link towards the website of the App
 * @property string $Version Number indicating a version of the App
 * @property string $Type Type of the App
 *
 */

class App extends DataObject implements PermissionProvider {

    private static $table_name = 'App';
    private static $singular_name = 'Apps';
    private static $plural_name = 'App';
    private static $db = [
        'Active' => 'Boolean',
        'Slug' => 'Varchar(110)',
        'Title' => 'Varchar(40)',
        'ShortDescription' => 'Varchar(255)',
        'Description' => 'Text',
        'Author' => 'Varchar(40)',
        'Website' => 'Varchar(255)',
        'Version' => 'Varchar(5)',
        'Type' => 'Enum("Code,Form,Virtuell")'
    ];

    private static $default_sort = "\"Title\"";

    private static $has_one = [
        'InstalledBy' => Member::class,
        'Logo' => Image::class
    ];


    private static $belongs_many_many = [
        'DataStores' => DataStore::class
    ];

    private static $summary_fields = [
        'Title',
        'Icon' => 'Icon',
        'ShortDescription',
        'Author',
        'Website',
        'Version',
        'Installed'
    ];

    private static $searchable_fields = [
        'Title' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'ShortDescription' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Description' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Author' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ]
    ];

    private static $default_records = [
        ["Active" => 1, "Title" => "Every Numbering", "Slug" => "everynumbering","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "This app allows users to define a unique automatically generated values for RecordSetItems, i.e., entries of a RecodSet.", "Description" => "This app allows users to define a unique automatically generated values for RecordSetItems, i.e., entries of a RecodSet.", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Note Manager", "Slug" => "everynotemanager","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "The EveryNoteManager is EveryDataStore's app that enables adding new or remove existing notes of individual RecordSetItems.", "Description" => "The EveryNoteManager is EveryDataStore's app that enables adding new or remove existing notes of individual RecordSetItems. This app is available to admins for installation under the following path: Administration / Apps", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Multi Item Deletion", "Slug" => "everymultiitemdeletion","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "This app allows front end users to delete multiple RecordSet items by selecting checkboxes in the result list.", "Description" => "This app allows front end users to delete multiple RecordSet items by selecting checkboxes in the result list.", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Notify Template", "Slug" => "everynotifytemplate","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "The EveryNotifyTemplate app allows users to design different styles of templates that can be filled out with data from EveryDataStore RecordSetItem.", "Description" => "The EveryNotifyTemplate app allows users to design different styles of templates that can be filled out with data from EveryDataStore RecordSetItem. Therefore, print templates can be used to create a template for each RecordSet, but also to create report templates that will combine data from many different sources, i.e., different RecordSets and RecordSetItems.", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Widget", "Slug" => "everywidget","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "Widgets are useful and visually appealing interface components for an easy access to certain information.", "Description" => "Widgets are useful and visually appealing interface components for an easy access to certain information. Widgets are placed on the Dashboard. They can be activated or deactivated and arranged as desired.", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Version History", "Slug" => "everyversionhistory","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "The “EveryVersionHistory” is EveryDataStore's app that manages versions of RecordSets and RecordSetItems.", "Description" => "The EveryVersionHistory shows information about all different versions of the Database and RecordSetItem, i.e., every time when an update happens within one Database and RecordSetItem, a new version is created.", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every EveryItemExportImport", "Slug" => "everyitemexportimport","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "EveryItemExportImport allows use to import and export RecorSetItems and ModelItems", "Description" => "EveryItemExportImport allows use to import and export RecorSetItems and ModelItems", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Filemanager", "Slug" => "everyfilemanager","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "EveryFileManager app is interfac for managing the EveryDataStore file system that allows users to uploading documents, images and other types of files to RecordSetItems", "Description" => "EveryFileManager app is interfac for managing the EveryDataStore file system that allows users to uploading documents, images and other types of files to RecordSetItems", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Translator", "Slug" => "everytranslator","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "This App tranlates RecordSet form elements like labels, Info, default values, section name etc.", "Description" => "EveryTranslator is EveryDataStore's app / plugin that manages languages and translates RecordSet form elements like label, Info, default values, section name etc. This app is available to admins for installation under the following menu: Administration/Apps", "Type" => "Code", "Version" => "1"],
        ["Active" => 1, "Title" => "Every Defaul Folders", "Slug" => "everydefaultfolder","Author" => "EveryDataStore GmbH", "Website" => "https://www.everydatastore.de", "ShortDescription" => "This App EveryDefaulFolders creates for each RecordSetItem the same folder tree structure", "Description" => "Should all RecordSetItems of a particular RecordSet have the same folder tree structure in their file manager, i.e., in the Documents tab, this structure can be set up in the Default Folders option under Administration menu by adding a new default folder", "Type" => "Code", "Version" => "1"]
    ];

    public function fieldLabels($includerelations = true) {
         $labels = parent::fieldLabels(true);
        if (!empty(self::$summary_fields)) {
            $labels = EveryDataStoreHelper::getNiceFieldLabels($labels, 'EveryDataStore\Model\App', self::$summary_fields);
        }
        return $labels;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.'._t($this->owner->ClassName . '.MAIN', 'Main'), TextField::create('Title', _t($this->owner->ClassName . '.TITLE', 'Title')));
        return $fields;
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->Slug && $this->Title) {
            $this->Slug = strtolower(str_replace(' ', '', $this->Title));
        }
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
    }

    protected function onBeforeDelete() {
        parent::onBeforeDelete();
    }

    protected function onAfterDelete() {
        parent::onAfterDelete();
    }


    /**
     * Describes if an app is installed or not
     * @return string Yes | No
     */
    public function Installed() {
        if (EveryDataStoreHelper::getMember()) {
            $apps = EveryDataStoreHelper::getCurrentDataStore()->Apps();
            $app = $apps->filter(['AppActive' => true, 'AppSlug' => strtolower($this->Slug)])->first();
            if ($app) {
                return 1;
            }
            return 0;
        }
    }

    /**
     * Gives the Icon-URL
     * @return string Logo - URL
     */
    public function Icon() {
        if ($this->LogoID > 0) {
            return [
                'Slug' => $this->Logo()->Slug,
                'Size' => $this->Logo()->getSize(),
                'Name' => $this->Logo()->Name,
                'Title' => $this->Logo()->Title,
                'ThumbnailURL' => $this->Logo()->getThumbnailURL(),
                'ProtectedURL' => $this->Logo()->getProtectedURL(),
            ];
        }
    }

    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
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
     * This function should return true if the current user can install apps.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canInstall() {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("INSTALL", $this));
    }

    /**
     * This function should return true if the current user can deinstall apps.
     * @see Permission code VIEW_CLASSSHORTNAME e.g. CREATE_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @param array $context Context argument for canCreate()
     * @return bool True if the the member is allowed to do this action
     */
    public function canDeinstall() {
        return EveryDataStoreHelper::checkPermission(EveryDataStoreHelper::getNicePermissionCode("DEINSTALL", $this));
    }

    /**
     * Return a map of permission codes for the DataObject and they can be mapped with Members, Groups or Roles
     * @return array
     */
    public function providePermissions() {
        return array(
            EveryDataStoreHelper::getNicePermissionCode("CREATE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.CREATE', "CREATE"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("EDIT", $this) => [
                'name' => _t('SilverStripe\Security\Permission.EDIT', "EDIT"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("VIEW", $this) => [
                'name' => _t('SilverStripe\Security\Permission.VIEW', "VIEW"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("DELETE", $this) => [
                'name' => _t('SilverStripe\Security\Permission.DELETE', "DELETE"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ],  EveryDataStoreHelper::getNicePermissionCode("INSTALL", $this) => [
                'name' => _t('SilverStripe\Security\Permission.INSTALL', "INSTALL"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ],
            EveryDataStoreHelper::getNicePermissionCode("DEINSTALL", $this) => [
                'name' => _t('SilverStripe\Security\Permission.DEINSTALL', "DEINSTALL"),
                'category' => _t(__Class__ . '.PERMISSIONS_CATEGORY', ClassInfo::shortname($this)),
                'sort' => 1
            ]);
    }
}
