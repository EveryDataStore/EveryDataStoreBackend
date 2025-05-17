<?php

namespace EveryDataStore\Control;
use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\Control\Controller;


/** EveryDataStore v1.5
 * This class is the default backend controller
 */

class EveryDataStoreController extends Controller {
    /**
     * This function redirects to the login page if the user is not currently logged in
     */
    public $csvdata = null;
    public function init() {
        parent::init();
        if (!EveryDataStoreHelper::getMember()) {
            $this->redirect('Security/login');
        }
    }
}   
