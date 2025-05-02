<?php
namespace EveryDataStore\Admin;

use SilverStripe\Admin\ModelAdmin;

/** EveryDataStore v1.5
 * This class defines everyDataStore's own Model SilverStripe-Admin
 */

class EveryDataStoreAdmin extends ModelAdmin {
    private static $managed_models = [
        'EveryDataStore\Model\DataStore',
        'EveryDataStore\Model\App',
        'EveryDataStore\Model\RecordSet\Form\FormFieldType'
    ];
    private static $url_segment = 'EveryDataStore';
    private static $menu_title = 'EveryDataStore';
}
