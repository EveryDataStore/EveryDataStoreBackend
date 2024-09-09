<?php

namespace EveryDataStore\Control;

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\Assets\File;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Assets\Storage\AssetStoreRouter;
use SilverStripe\Core\Config\Config;

/** EveryDataStore v1.0
 *
 * This class performs router and accessibility checks for Files
 * to ensure the correct response for HTTP requests
 *
 */


class EveryDataStoreProtectedFileController extends Controller
{

    /**
     * Designated router
     *
     * @var AssetStoreRouter
     */
    protected $handler = null;

    public function init() {
        parent::init();
    }
    
    /**
     * @return AssetStoreRouter
     */
    public function getRouteHandler()
    {
        return $this->handler;
    }

    /**
     * @param AssetStoreRouter $handler
     * @return $this
     */
    public function setRouteHandler(AssetStoreRouter $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    private static $url_handlers = array(
        '$Filename' => "handleFile"
    );

    private static $allowed_actions = array(
        'handleFile'
    );

    /**
     * Provide a response for the given file request
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleFile(HTTPRequest $request)
    {
        $filename = $this->parseFilename($request);
        // Deny requests to private file
        if (!$this->isValidFilename($filename)) {
            return $this->httpError(400, $filename." not found");
        }
        $hash = $request->getVar('hash');
        if ($hash) {
            if (!self::validiateHash($hash, $request)) {
                 return $this->httpError(401, "The file hash is not valid. Make sure that the viewer member login 'asset_viewer_member' in the everydatastore.yml is correct ");
            }
 
            if(!EveryDataStoreHelper::checkPermission('VIEW_FILE')){
                $member = EveryDataStoreHelper::getMember();
                return $this->httpError(403, "The user ".$member->Email." does not have permission to view this file:".$filename);
            }
        }

        // Pass through to backend
        return $this->getRouteHandler()->getResponseFor($filename);
    }

    /**
     * Check if the given filename is safe to pass to the route handler.
     * This should block direct requests to assets/.protected/ paths
     *
     * @param $filename
     * @return bool True if the filename is allowed
     */
    public function isValidFilename($filename)
    {

        // Block hidden files
        return !preg_match('#(^|[\\\\/])\\..*#', $filename);
    }

    /**
     * Get the file component from the request
     *
     * @param HTTPRequest $request
     * @return string
     */
    protected function parseFilename(HTTPRequest $request)
    {
        $filename = '';
        $next = $request->param('Filename');
        while ($next) {
            $filename = $filename ? File::join_paths($filename, $next) : $next;
            $next = $request->shift();
        }
        if ($extension = $request->getExtension()) {
            $filename = $filename . "." . $extension;
        }
        return $filename;
    }

    /**
     * This function validates the file hash
     * @param string $hash
     * @param array $request
     * @return boolean
     */
    private static function validiateHash($hash, $request) {
        $file = File::get()->filter(['FileHash' => $hash])->first();
        if (!$file) return false;
        
        if (!EveryDataStoreHelper::validiateLogin($request, Config::inst()->get('asset_viewer_member', 'email'), Config::inst()->get('asset_viewer_member', 'password'))) return false;
        return true;
    }

}
