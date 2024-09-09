<?php

namespace EveryDataStore\Model\RecordSet\Form;

use EveryDataStore\Helper\EveryDataStoreHelper;
use EveryDataStore\Model\RecordSet\Form\FormField;
use SilverStripe\ORM\DataObject;

/** EveryDataStore v1.0
 *
 * FromFields can be of different types. This class defines a FormFieldType,
 * as well as its relations to other models.
 * User permissions regarding this class are also defined here.
 *
 *
 * <b>Properties</b>
 *
 * @property string $Title Name of the field type
 * @property string $Slug Unique Identifier of the field type
 * @property string $FontIconCls
 *
 */

class FormFieldType extends DataObject {

    private static $table_name = 'FormFieldType';
    private static $db = [
        'Title' => 'Varchar(100)',
        'Slug' => 'Varchar(110)',
        'FontIconCls' => 'Varchar(20)'
    ];
    private static $has_many = [
        'FormField' => FormField::class
    ];

    private static $default_records = [
        ['Title' => 'Text field', 'Slug' => 'textfield', 'FontIconCls' => 'fa fa-text-width'],
        ['Title' => 'Textarea Field', 'Slug' => 'textareafield', 'FontIconCls' => 'fa fa-file-text-o'],
        ['Title' => 'Checkbox Field', 'Slug' => 'checkboxfield', 'FontIconCls' => 'fa fa-check-square-o'],
        ['Title' => 'Dropdown Field', 'Slug' => 'dropdownfield', 'FontIconCls' => 'fa fa-th-list'],
        ['Title' => 'ReadOnly Field', 'Slug' => 'readonlyfield', 'FontIconCls' => 'fa fa-eye-slash'],
        ['Title' => 'Relation Field', 'Slug' => 'relationfield', 'FontIconCls' => 'fa fa-link'],
        ['Title' => 'Upload Field', 'Slug' => 'uploadfield', 'FontIconCls' => 'fa fa-upload']
    ];

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
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

    public function canView($member = null) {
        return true;
    }

    public function canEdit($member = null) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }

    public function canDelete($member = null) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }

    public function canCreate($member = null, $context = []) {
        return EveryDataStoreHelper::checkPermission('ADMIN');
    }
}
