<?php

namespace EveryDataStore\Helper;
/** EveryDataStore v1.0
 * This class raises errors and logs diagnostic information
 */

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class LoggerHelper extends EveryDataStoreHelper {

    public static function info($info, $class = false) {
      Injector::inst()->get(LoggerInterface::class)->info($class.': '.$info);
    }

    public static function error($error, $class) {
        Injector::inst()->get(LoggerInterface::class)->error($class.': '.$error);
    }

    public static function debugg($error, $class) {
       Injector::inst()->get(LoggerInterface::class)->error($class.': '.$error);
    }
}
