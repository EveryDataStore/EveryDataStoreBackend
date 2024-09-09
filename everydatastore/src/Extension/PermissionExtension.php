<?php

namespace EveryDataStore\Extension;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;

/** EveryDataStore/EveryDataStore v1.0
 *
 * This extension overwrites of the Permission model, its relations and its permissions
 *
 *
 */

class PermissionExtension extends DataExtension {


    /**
     * This function should return true if the current user can view an object
     * @see Permission code VIEW_CLASSSHORTNAME e.g. VIEW_MEMBER
     * @param Member $member The member whose permissions need checking. Defaults to the currently logged in user.
     * @return bool True if the the member is allowed to do the given action
     */
    public function canView($member = null) {
        return true;
    }
}
