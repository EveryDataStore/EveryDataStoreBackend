<?php
namespace EveryDataStore\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;

/** EveryDataStore/EveryDataStore v1.0
 *
 * This extension overwrites some methods of the File SiteConfig
 * It provides modifications to the form in the CMS
 *
 * <b>Properties</b>
 *
 * @property integer $MaxTotalResults Maximum number of items that can exist
 * @property string $FrontendURL URL of the company's EveryDataStore dataStore
 * @property boolean $DemoMode Indicates whether dataStore is a demo or not
 *
 */
class SiteConfigExtension extends DataExtension {

    private static $db = array(
        'MaxTotalResults' =>  'Int(11)',
        'FrontendURL' => 'Varchar',
        'DemoMode' => 'Boolean',
    );

    private static $default_records = [
        ['Title' => 'EveryDataStore',
            'MaxTotalResults' => 10000,
            'FrontendURL' => 'http://localhost:4200/',
            'DemoMode' => false
            ]
    ];

    /**
     * This function updates the default CMS fields for a DataObject
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab("Root.Main", new TextField('MaxTotalResults', 'MaxTotalResults'));
        $fields->addFieldToTab("Root.Main", new TextField('FrontendURL', 'FrontendURL'));
        $fields->addFieldToTab("Root.Main", new CheckboxField('DemoMode', 'DemoMode'));
        $fields->removeFieldFromTab("Root.Main", "Tagline");
        $fields->removeFieldFromTab("Root.Main", "Theme");
        return $fields;
    }
}
