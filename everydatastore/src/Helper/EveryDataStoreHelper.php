<?php

namespace EveryDataStore\Helper;

use EveryDataStore\Model\RecordSet\RecordSetItem;
use EveryTranslator\Helper\EveryTranslatorHelper;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Core\Environment;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Assets\File;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Security\Group;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Versioned\Versioned;

/** EveryDataStore v1.0
 *
 * This is the main helper class of EveryDataStore, it contains many different methods to manage
 * software security and database queries.
 *
 */

class EveryDataStoreHelper {

    /**
     * This function retrieves the currently logged in member
     * @return DataObject
     */
    public static function getMember() {
        return Security::getCurrentUser();
    }

    /**
     * This function gives the ID of the currently logged in member
     * @return integer
     */
    public static function getMemberID() {
        $member = self::getMember();
        if ($member) {
            return $member->ID;
        }
    }

    /**
     * This function retrieves the api token of the currently logged in member
     * @return string
     */
    public static function getAPIToken() {
        $member = self::getMember();
        if ($member) {
            return $member->RESTFulToken;
        }
    }

    public static function getNiceAPIToken() {
        return self::getAPIToken();
    }

    public static function getSecurityID() {
        return SecurityToken::getSecurityID();
    }

    /**
     * This function retrieves the current dataStore of the currently logged in member
     * @return DataObject
     */
    public static function getCurrentDataStore() {
        $member = self::getMember();
        if ($member) {
            return $member->CurrentDataStore();
        }
    }

    /**
     * This function retrieves ID of the current dataStore of the currently logged in member
     * @return integer
     */
    public static function getCurrentDataStoreID() {
        $member = self::getMember();
        if ($member) {
            return $member->CurrentDataStoreID;
        }
    }

    /**
     * This function retrieves admin ID of the currently active dataStore
     * @return integer
     */
    public static function getCurrentDataStoreAdminID() {
        $member = self::getMember();
        if ($member) {
            return $member->CurrentDataStore()->AdminID;
        }
    }

    /**
     * This function retrieves an object with member's last login attempt information
     * @return DataObject
     */
    public static function getMemberLastLogin() {
        $LoginAttempt = LoginAttempt::getByEmail(self::getMember()->Email)
            ->sort('Created', 'DESC')
            ->limit(2);
        return $LoginAttempt ? $LoginAttempt->Last(): null;
    }

    /**
     * This function retrieves member's language code
     * @return string
     */
    public static function getMemberLanguageCode() {
        $member =  self::getMember();
        if ($member) {
             return explode('_', $member->Locale)[0];
         }
    }

    /**
     * This function retrieves member's language code
     * @return string
     */
    public static function getMemberLocale() {
        $member =  self::getMember();
        if ($member) {
             return $member->Locale;
         }
    }
    
    /**
     * This function retrieves a member object with provided credentials
     * @param string $email
     * @param string $password
     * @param HTTPRequest $request
     * @param string $token
     * @return DataObject
     */
    public static function getMemberByEmailAndPassword($email, $password, $request, $token = null) {
        $login['Email'] = $email;
        $login['Password'] = $password;
        $login['Active'] = true;
        if ($token)
            $login['RESTFulToken'] = $token;
        return Injector::inst()->get(MemberAuthenticator::class)->authenticate($login, $request);
    }

    /**
     * This function creates default member for EveryDataStore.
     * Email and password can be set in everydatastore.yml
     * @param string $configName 
     * @return Int
     */
    public static function createDefaultMember($configName) {
            $defaultMember = new Member();
            $defaultMember->Active = true;
            $defaultMember->FirstName = str_replace('_member','',$configName);
            $defaultMember->Surname = 'Member';
            $defaultMember->Email = Config::inst()->get($configName, 'email');
            $defaultMember->Password = Config::inst()->get($configName, 'password');
            $defaultMember->AdminID = $defaultMember->ID;
            $defaultMember->CurrentDataStoreID = 1;
            $defaultMember->Slug = self::getAvailableSlug('SilverStripe\Security\Member');
            $defaultMemberID = $defaultMember->write();
            $defaultMember->AdminID = $defaultMemberID;
            $defaultMember->write();
            return $defaultMember;
    }
    
    /**
     * This function creates default admin for EveryDataStore.
     * Username and password can be set in .env
     * @return Int
     */
    public static function createDefaultAdmin() {
            $defaultMember = new Member();
            $defaultMember->Active = true;
            $defaultMember->FirstName = 'Default';
            $defaultMember->Surname = 'Admin';
            $defaultMember->Email = Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
            $defaultMember->Password = Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
            $defaultMember->CurrentDataStoreID = 1;
            $defaultMember->Slug = self::getAvailableSlug('SilverStripe\Security\Member');
            $defaultMemberID = $defaultMember->write();
            $defaultMember->AdminID = $defaultMemberID;
            $defaultMember->write();
            return $defaultMember;
    }
    
    /**
     * This function returns default member
     * Default member email is stored in everydatastore.yml
     * @return DataObject
     */
    public static function getDefaultMember($configName) {
       return Member::get()->filter(['Email' => Config::inst()->get($configName, 'email')])->first();
    }
    
     /**
     * This function returns default admin
     * Username and password can be set in .env
     * @return DataObject
     */
    public static function getDefaultAdmin() {
       return Member::get()->filter(['Email' => Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')])->first();
    }
    

    /**
     * This function checks whether a user with provided credentials exists
     * @param HTTPRequest $request
     * @param string $user
     * @param string $pwd
     * @return boolean True if the the member exists
     */
    public static function validiateLogin($request, $user = null, $pwd = null){
        $user = $user ? $user : $request->getVar('user');
        $pwd  = $pwd ? $pwd :  $request->getVar('pwd');
        $member = $user && $pwd ? self::getMemberByEmailAndPassword($user, $pwd, $request, null): null;
        if($member){
            Config::nest();
            Config::modify()->set(Member::class, 'session_regenerate_id', true);
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false, $request);
            Config::unnest();
            return true;
        }
    }

    /**
     * This function checks whether a member is allowed to do the given action
     * @param string $action
     * @return boolean
     */
    public static function checkPermission($action) {
        $member = self::getMember();
        if ($member) {
            if (Permission::check('ADMIN')) {
                return true;
            }

            if (Permission::check($action, 'any', $member)) {
                return true;
            }
        }

        return $member ? Permission::check($action, 'any', $member) : false;
    }

    /**
     * This function builds nice permission code of each class or object
     * @param string $code
     * @param DataObject $ref
     * @return string
     */
    public static function getNicePermissionCode($code, $ref) {
        return strtoupper($code . '_' . ClassInfo::shortname($ref));
    }

    /**
     * This function retrieves a DataObject that corresponds to the provided Slug and class name
     * @param string $slug
     * @param DataObject $class
     * @return  DataObject
     */
    public static function getOneBySlug($slug, $class) {
        return self::getItemByClassAndSlug($slug, $class);
    }

    /**
     * This function retrieves a DataObject by given DataObject id, class and stage (Stage or Live)
     * @param integer $id
     * @param string $class
     * @param string $stage
     * @return DataObject
     */
    public static function getOneByStage($id, $class, $stage){
       return Versioned::get_one_by_stage($class, $stage, "\"ID\" = $id");
    }

    /**
     * Deletes a DataObject by given DataObject id, class and stage (Stage or Live)
     * @param integer $id
     * @param string $class
     * @param string $stage
     */
    public static function deleteOneFromStage($id, $class, $stage){
        $item = self::getOneByStage($id, $class, $stage);
        if ($item) {
            $item->deleteFromStage($stage);
        }
    }

     /**
     * Deletes all versions of a DataObject
     * @param integer $id
     * @param string $db_table
     */
    public static function deleteAllVersions($id, $db_table){
        DB::query("delete from ".$db_table."_Versions where RecordID=".$id);
    }

     /**
     * Deletes a DataObject from {$db_table}_Live
     * @param integer $id
     * @param string $db_table
     */
    public static function deleteOneFromLiveTable($id, $db_table){
        DB::query("delete from ".$db_table."_Live where ID=".$id);
    }
    /**
     * Counts Records in the current dataStore
     * @return  integer
     */
    public static function getCurrentDataStoreRecordSetItemsCount() {
        $RecordSetID = [];
        foreach(self::getCurrentDataStore()->Records() as $recordSet){
            $RecordSetID[] = $recordSet->ID;
        }

        return RecordSetItem::get()->filter([
                'RecordSet.ID' => $RecordSetID, 'Version:GreaterThan' => 0 ]
                )->Count();
    }

    /**
     * Deletes an item by given DataObject classname
     * @param array $params
     * @param string $class
     */
    public static function deleteItem($params, $class) {
        $slug = isset($params['ID']) ? $params['ID'] : 0;
        $securityID = isset($params['OtherID']) ? $params['OtherID'] : 0;
        if ($securityID == self::getSecurityID()) {
            $item = self::getItemByClassAndSlug($slug, $class);
            if ($item) {
                if(EveryDataStoreHelper::isVersioned($item->ClassName)){
                    EveryDataStoreHelper::deleteOneFromStage($item->ID, $item->ClassName, 'Stage');
                }
                $item->delete();
            }
        }
    }

    /**
     * Deletes multiple items of a DataObject
     * @param dataobject $objs
     */
    public static function deleteDataObjects($objs){
        foreach($objs as $obj){
            if(EveryDataStoreHelper::isVersioned($obj->ClassName)){
                EveryDataStoreHelper::deleteOneFromStage($obj->ID, $obj->ClassName, 'Stage');
            }
            $obj->delete();
        }
    }


    /**
     * This function checks whether the created slug already exists in the database
     * @param string $class
     * @param DataObject $obj
     * @return boolean
     */
    public static function slugExists($class, $obj){
        return DataObject::get($class)->filter(['Slug' => $obj->Slug, 'ID' => $obj->ID])->First() ? true : false;
    }

    /**
     * Produces a slug if a class doesn't already exist
     * @param string $class
     * @param string $slug
     * @return string
     */
    public static function getAvailableSlug($class, $slug = false) {

        if (!$slug) {
            $slugLength = Config::inst()->get('SilverStripe\ORM\DataObject', 'Object_Slug_Length');
            $l = $slugLength ? $slugLength : 50;
            $slug = self::getRandomString($l);
        }

        $found = DataObject::get($class)->filter(['Slug' => $slug])->First();

        if ($found) {
            return self::getAvailableSlug($class, false);
        }

        return $slug;
    }

    /**
     * This function produces a random string of length $length
     * @param integer $length
     * @return string
     */
    public static function getRandomString($length) {
        $RandomString = new RandomGenerator();
        return substr($RandomString->randomToken(), 0, $length);
    }

    /**
     * This function gives the available languages
     * @return array
     */
    public static function getAvaibleLanguages() {
        $ret = [];
        $languages = Config::inst()->get('Frontend_Languages');
        foreach($languages as $val){
            $ret[key($val)] =$val[key($val)];
        }
        return $ret;
    }

    /**
     * Get FormField property by given name and DataObject settings
     * @param dataobject $settings
     * @param string $name
     * @return string
     */
    public static function getFormFieldSetting($settings, $name) {
        $setting = $settings->filter(array('Title' => $name))->first();
        return $setting ? $setting->Value : null;
    }

    /**
     * Returns formatted date
     * @param string $date
     * @return date
     */
    public static function getNiceDateFormat($date) {
        return date_format(date_create($date), self::getEveryConfig('DateFormat'));
    }

    /**
     * Returns formatted time
     * @param string $time
     * @return datetime
     */
    public static function getNiceTimeFormat($time) {
        return date_format(date_create($time), self::getEveryConfig('TimeFormat'));
    }

      /**
     * Returns formatted date time
     * @param string $dateTime
     * @return datetime
     */
    public static function getNiceDateTimeFormat($dateTime) {
        return date_format(date_create($dateTime), self::getEveryConfig('DateTimeFormat'));
    }

    /**
     * Returns date interval
     * @param integer $interval
     * @param string $unit (y,d,w,h or s)
     * @param char $operator
     * @return datetime
     */
    public static function getNiceDateByInterval($interval, $unit, $operator = '+'){
        return date('Y-m-d H:i:s', strtotime($operator. $interval . ' '.$unit));
    }

    /**
     * this function returns formatted number
     * @param int|decimal $number
     * @return type
     */
    public static function getNiceNumberFormat($number){
        $formatConfig = self::getEveryConfig('number_format');
        $decimal = $formatConfig && isset($formatConfig['decimal']) ? $formatConfig['decimal']: Config::inst()->get('number_format','decimal');
        $decimal_separator = $formatConfig && isset($formatConfig['decimal_separator']) ? $formatConfig['decimal_separator']: Config::inst()->get('number_format','decimal_separator');
        $thousand_separator = $formatConfig && isset($formatConfig['thousand_separator']) ? $formatConfig['thousand_separator']: Config::inst()->get('number_format','thousand_separator');

        return number_format(
               floatval ($number),
               $decimal,
               $decimal_separator,
               $thousand_separator);
    }

    /**
     * This function retrieves a dataObject with corresponding class name and slug
     * @param string $slug
     * @param string $class
     * @return DataObject
     */
    public static function getItemByClassAndSlug($slug, $class) {
        if ($class == 'Group') {
            return Group::get()->filter(array('Slug' => $slug, 'DataStoreID' => self::getCurrentDataStoreID()))->first();
        } elseif ($class == 'Member') {
            return Member::get()->filter(array('Slug' => $slug, 'CurrentDataStoreID' => self::getCurrentDataStoreID()))->first();
        } elseif ($class == 'File') {
            return File::get()->filter(array('Slug' => $slug, 'DataStoreID' => self::getCurrentDataStoreID()))->first();
        } else {
            return $class::get()->filter(array('Slug' => $slug, 'DataStoreID' => self::getCurrentDataStoreID()))->first();
        }
    }

    /**
     * This function gets EveryConfiguration by given Title
     * @param string $title
     * @return string||null
     */
    public static function getEveryConfig($title) {
        $getCurrentDataStore = self::getCurrentDataStore();
        $everyConfig = $getCurrentDataStore && $title ? $getCurrentDataStore->Configurations()->filter(['Title' => $title])->first() : null;
        if ($everyConfig) {
            return $everyConfig && self::isJson($everyConfig->Value) ? json_decode($everyConfig->Value, true) : $everyConfig->Value;
        }
        return null;
    }

    /**
     *
     * @param string $className e.g. EveryDataStore\Model\Menu;
     * @param integer $fields e.g. ['Title' => 'abc']
     */
    public static function createDataObject($className, $fields) {
        $object = Injector::inst()->create($className);
        if($object && !empty($fields)){
            foreach($fields as $key => $val){
                $object->$key = $val;
            }
            return $object->write();
        }
    }


    /**
     * Checks if an object has a method
     * @param dataobject $obj
     * @param string $method
     * @return bool
     */
    public static function hasMethod($obj, $method) {
       return ClassInfo::hasMethod($obj, $method);
    }

    /**
     * Initialize a RecordSetItem
     * @param string $slug
     * @return string
     */
    public static function initRecordSetItem($slug) {
        $recordSet = self::getOneBySlug($slug, 'EveryDataStore\Model\RecordSet\RecordSet');
        if ($recordSet) {
            $RecordSetItem = new RecordSetItem();
            $RecordSetItem->RecordSetID = $recordSet->ID;
            $RecordSetItem->writeWithoutVersion();
            return $RecordSetItem->Slug;
        }
    }


    /**
     * Gets SilverStripe default allowed file extensions from the class SilverStripe\Assets\File
     * @return array
     */
    public static function getNiceFileExtensions() {
        $extensions = Config::inst()->get('SilverStripe\Assets\File', 'allowed_extensions');
        foreach ($extensions as $ext) {
              $ret[$ext] = $ext;
        }
       return $ret;
    }


    /**
     * Checks if the DataObject class has Versioned extension
     * @param striks $className
     * @return boolean
     */
    public static function isVersioned($className) {
        $extensions = Config::inst()->get($className, 'extensions');
        return in_array('SilverStripe\Versioned\Versioned', $extensions ) ? true : false;
    }

    /**
     * This function returns all defined permission codes
     * @return array
     */
    public static function getNicePermissionCodes() {
        $codes = Permission::get_codes();
        $nicePermissionCodes = [];
        unset(
                $codes['App']['DELETE_APP'],
                $codes['App']['EDIT_APP'],
                $codes['App']['CREATE_APP'],
                $codes[_t(__CLASS__ . '.AdminGroup', 'Administrator')],
                $codes[_t('SilverStripe\\Security\\Permission.CONTENT_CATEGORY', 'Content permissions')],
                $codes[_t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access')],
                $codes['Other'],
                $codes[_t('SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY', 'Roles and access permissions'
                )]
        );

        foreach ($codes as $k => $v) {
            $nicePermissionCodes[] = array(
                'title' => $k,
                'permissions' => self::getNicePermissionArray($v)
            );
        }
        return $nicePermissionCodes;
    }

    /**
     * Returns EveryDataStore permission codes as an associate array
     * @param array $arr
     * @return array
     */
    private static function getNicePermissionArray($arr) {
        $niceArray = [];
        foreach ($arr as $key => $v) {
            $niceArray[$key] = $v['name'];
        }
        return $niceArray;
    }

    public static function getNiceFieldLabels($labels, $Class, $summaryFields){
        foreach($summaryFields as $label ){
           $label = str_replace('.', '', $label);
           if($label == 'Created' || $label == 'Edited' || $label == 'ID' || $label == 'Slug'){
                   $labels[$label] = _t('SilverStripe\ORM\DataObject.'.strtoupper($label), $label);
            } else {
                  $labels[$label] = _t($Class.'.'.strtoupper($label), $label);
            }
        }
        return $labels;
    }

     /**
     * builds the object values in a nice array
     * @param object $obj
     * @return array
     */
    public static function getViewFieldsValues($obj) {
            $fields = [];
            $viewFields = Config::inst()->get($obj->getClassName(), 'API_View_Fields');
            foreach ((array) $viewFields as $field) {
                if (strpos($field, '()') === false) {
                    $fields[$field] = $obj->$field;
                } else {
                    $niceMethod = str_replace('()', '', $field);
                    $fields[$niceMethod] = self::hasMethod($obj, $niceMethod) ? $obj->$niceMethod() : null;
                }
            }
            return $fields;
    }

    /**
     * Translates labels
     * @param string $label
     * @return string
     */
    public static function _t($label){
        return EveryTranslatorHelper::_t($label);
    }

    /**
     * This function converts an array to  SilverStripe-ArrayList
     * @param array $arr
     * @return ArrayList
     */
    public static function setArray2ArrayList($arr) {
        if (is_array($arr)) {
            $list = new ArrayList();
            foreach ($arr as $key => $val) {
                $list->push(new ArrayData([
                            $key => $val
                ]));
            }
            return $list;
        }
    }

    /**
     * @param array $arr
     * @return boolean
     */
    public static function is_multidim_array($arr) {
        if (!is_array($arr))
            return false;
        foreach ($arr as $elm) {
            if (!is_array($elm))
                return false;
        }
        return true;
    }

    /**
     * This function sorts an array by given string column/key and direction
     * @param array $arr
     * @param string $col
     * @param string $dir
     */
    public static function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key => $row) {
            if($key !== '' && $col = '') $sort_col[$key] = $row[$col];
        }

        if(!empty($sort_col)){
            array_multisort($sort_col, $dir, $arr);
        }
    }

    /**
     *
     * @return type
     */
    public static function getMapField(){
        return self::getMemberID() > 0 ? 'ID' : 'Slug';
    }

    /**
     * This function checks if the given string is json
     * @param string $string
     * @return boolean
     */
    public static function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * This function validates json
     * @param string $data
     * @return boolean
     */
    public static function json_validator($data) {
        if (!empty($data)) {
            return is_string($data) &&
                    is_array(json_decode($data, true)) ? true : false;
        }
        return false;
    }
    

    /**
     * This function returns all current DataStore settings
     * @param DataObject $member
     * @return array
     */

    public static function getDataStoreSettings($member) {
        $settings = [];
        $settings = self::getMemberSettings($member, $settings) ;
        $settings = self::getSiteConfigSettings($settings) ;
        $settings['PasswordMinLength'] = Config::inst()->get('SilverStripe\Security\PasswordValidator', 'min_length');
        $settings['PasswordMaxLength'] = Config::inst()->get('SilverStripe\Security\PasswordValidator', 'max_length');

        $dataStoreConfigurations = $member->CurrentDataStore()->Configurations();
        if($dataStoreConfigurations->Count() > 0){
            foreach($dataStoreConfigurations as $RepCon){
                $settings[$RepCon->Title] = $RepCon->Value;
            }
        }

        $dataStoreApps = $member->CurrentDataStore()->Apps();
        if($dataStoreApps->Count() > 0){
            $apps = [];
            foreach($dataStoreApps as $app){
                $apps[] = [
                    'Slug' => $app->Slug,
                    'Active' => $app->AppActive,
                    'IsInstalled' => $app->Installed()
                ];
            $settings['Apps'] = $apps;
            }
        }
        return $settings;
    }

    /**
     * Returns member settings
     * @param DataObject $member
     * @param array $settings
     * @return array
     */
    public static function getMemberSettings($member, $settings) {
        if ($member->Avatar() && $member->Avatar()->ID > 0) {
            $settings['Avatar'] = array(
                'Slug' => $member->Avatar()->Slug,
                'Thumbnail' => Director::absoluteBaseURL() . $member->Avatar()->Fill(100, 100)->URL . '?hash=' . $member->FileHash,
                'URL' => Director::absoluteBaseURL() . $member->Avatar()->Fill(100, 100)->URL . '?hash=' . $member->FileHash);
        }

        $settings['FirstName'] = $member->FirsName;
        $settings['Surname'] = $member->Surname;
        $settings['Email'] = $member->Email;
        $settings['Fullname'] = $member->getFullName();
        $settings['Locale'] = $member->Locale ? $member->Locale : 'en_US';
        $settings['ThemeColor'] = $member->ThemeColor ? $member->ThemeColor : 'default';
        $settings['CurrentDataStoreName'] = $member->CurrentDataStore()->Title;
        $settings['CurrentDataStoreFolderSlug'] = $member->CurrentDataStore()->Folder()->Slug;
        $settings['TimeFormat'] = $member->CurrentDataStore()->TimeFormat;
        $settings['DateFormat'] = $member->CurrentDataStore()->DateFormat;
        $settings['DateTimeFormat'] = $member->CurrentDataStore()->DateTimeFormat;
        $settings['CurrentDataStoreName'] = $member->CurrentDataStore()->Title;
        $settings['UploadAllowedExtensions'] = $member->CurrentDataStore()->getUploadAllowedFileExtensions();

        return $settings;
    }

    /**
     * Returns all SiteConfig Settings
     * @param array $settings
     * @return array
     */
    public static function getSiteConfigSettings($settings) {
        $config = SiteConfig::current_site_config();
        $configDB = Config::inst()->get('Silverstripe\SiteConfig\SiteConfig', 'db');
        if ($config && $configDB) {
                foreach ($configDB as $key => $val) {
                if ($key !== 'CanCreateTopLevelType' && $key !== 'CanEditType' && $key !== 'CanViewType' && $key !== 'Tagline') {
                   $settings[$key] = $config->{$key};
                }
            }
        }
        return $settings;
    }

    /**
     * This function returns DataObject that corresponds to the provided parameters
     *
     * @param string $className
     * @param string $slug
     * @return DataObject
     */
    public static function getObjectbySlug($className, $slug) {
        $obj = DataObject::get($className, ['Slug' => $slug])->First();
        return $obj ? $obj : null;
    }

    /**
     * This function returns ID of the DataObject that corresponds to the provided parameters
     *
     * @param string $className
     * @param string $slug
     * @return integer
     */
    public static function getObjectIDbySlug($className, $slug) {
        $obj = DataObject::get($className, ['Slug' => $slug])->First();
        return $obj ? $obj->ID : null;
    }

    /**
     * This function returns all relations of the $relationName object
     *
     * @param array $relations
     * @param string $relationName
     * @return array
     */
    public static function getObjectRelationsByName($relations, $relationName) {
        $objectRelations = [];
        foreach ($relations as $relation) {
            if ($relation['Name'] == $relationName) {
                $objectRelations[] = $relation;
            }
        }
        return $objectRelations;
    }

     /**
     * This function returns value of the request parameter
     *
     * @param string $paramName
     * @return array|string
     */
    public static function getRequestParams($request, $paramName) {
        $fields = [];
        if ($request->getVar($paramName)) {
            $fields = $request->getVar($paramName);
            if (strpos($fields, '{') > -1) {
                $fields = self::getNiceParam($request->getVar($paramName));
            }
        } else {
            if ($request->isPOST()) {
                $fields = $request->postVar($paramName);
                $fields = is_string($fields) ? json_decode($fields, true) : $fields;
                if (empty($fields)) {
                    $body = json_decode($request->getBody(), true);
                    $fields = isset($body[$paramName]) ? $body[$paramName] : null;
                    $fields = is_string($fields) ? json_decode($fields, true) : $fields;
                }
            } else if ($request->isPUT()) {
                $body = json_decode($request->getBody(), true);
                $fields = isset($body[$paramName]) ? $body[$paramName] : null;
                $fields = is_string($fields) ? json_decode($fields, true) : $fields;
            }
        }
        return $fields;
    }

    /**
     * This function returns an array with parameters as key-value pairs
     *
     * @param string $params
     * @return array
     */
    public static function getNiceParam($params) {
        $NiceParams = [];
        if ($params) {
              if (strpos($params, '}') !== false && strpos($params, '{') !== false) {
                  $params =substr($params, 1,-1);
              }
            $params = explode(',', $params);
            foreach ($params as $k => $v) {
                if (strpos($v, '=') > -1) {
                    $niceV = explode('=', $v);
                    if (strpos($niceV[1], '}') !== false && strpos($niceV[1], '{') !== false) {
                        $NiceParams[$niceV[0]] = explode(';', str_replace(['{', '}'], '', $niceV[1]));
                    } else {
                        $NiceParams[$niceV[0]] = $niceV[1];
                    }
                } else {
                    $NiceParams[$k] = $v;
                }
            }
        }
        return $NiceParams;
    }

   /**
    * This function finds one string between 2 strings
    * @copyright (c) https://stackoverflow.com/questions/5696412/how-to-get-a-substring-between-two-strings-in-php
    * @param string $string
    * @param string $start
    * @param string $end
    * @return string
    */
   public static function getStringBetween($string, $start, $end) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    
    
   /**
    * This function finds all strings between 2 strings
    * @copyright (c) https://stackoverflow.com/questions/1445506/get-content-between-two-strings-php
    * @param string $string
    * @param string $start
    * @param string $end
    * @return string
    */
    public static function getAllStringBetween($string, $start, $end) {
        $n = explode($start, $string);
        $result = Array();
        foreach ($n as $val) {
            $pos = strpos($val, $end);
            if ($pos !== false) {
                $result[] = substr($val, 0, $pos);
            }
        }
        return $result;
    }

    /**
     * Returns true if string is serialized
     * @param string $str
     * @return boolean
     */
    public static function isSerialized($str) {
            return ($str == serialize(false) || @unserialize($str) !== false);
    }
}
