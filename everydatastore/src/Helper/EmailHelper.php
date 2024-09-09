<?php

namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\Control\Email\Email;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;

/** EveryDataStore v1.0
 *
 * This class formats Email structure and content
 *
 */

class EmailHelper extends EveryDataStoreHelper {

    /**
     * This function sends to member the password reset link (only frontend) with the auto login hash.
     * @param DataObject $member
     * @param string $autoLoginHash
     */
    public static function sendPasswordResetLink($member, $autotoken){

        $siteConfig = SiteConfig::current_site_config();
        $adminEmail = Config::inst()->get('SilverStripe\Control\Email\Email', 'admin_email');
        $resetLink = $siteConfig->FrontendURL.'setpassword/?Slug='.$member->Slug.'&Token='.$autotoken;
        $emailBody = self::getMailHeader($siteConfig->Title);
        $emailBody .= _t('SilverStripe\Security\Member.SENDRESETLINKMEMAIL', '<p>Hello {fullname},</p><p>please us the following link to set your password</p>', ['fullname' => $member->getFullName()]);
        $emailBody .= '<a href="'.$resetLink.'">'.$resetLink.'</a>';
        $emailBody .= self::getMailFooter(Config::inst()->get('SilverStripe\Control\Email\Email', 'FOOTER'));
        $email = new Email($adminEmail, $member->Email, 'Set Password', $emailBody);
        $email->send();
    }

    /**
     * Sets the mail header
     * @return string mail header
     */
    public static function getMailHeader($title){
        return '<h1>'.$title.'</h1>';
    }

    /**
     * Sets the mail footer
     * @return string mail footer
     */
    public static function getMailFooter($title){
          return '<h6>'.$title.'</h6>';
    }
}
