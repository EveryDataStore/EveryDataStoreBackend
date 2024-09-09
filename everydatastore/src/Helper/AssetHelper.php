<?php

namespace EveryDataStore\Helper;

use EveryDataStore\Helper\EveryDataStoreHelper;
use SilverStripe\Security\Group;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\Storage\FileHashingService;
use SilverStripe\Assets\Storage\AssetStore;

/** EveryDataStore v1.0
 *
 * This helper class defines methods to manage assets such as folders and files.
 * It deals with the creation, deletion, upload, naming, navigation , etc. of assets.
 *
 */
class AssetHelper extends EveryDataStoreHelper {

    /**
     * This function adds a folder to the Record or RecordSetItem and
     * defines access and edit permissions
     *
     * @param DataObject $parentFolder
     * @param string $folderName
     * @return DataObject
     */
    public static function createFolder($folderName, $parentFolder = null) {
        $member = self::getMember();
        if ($member) {
            $Folder = $parentFolder ? Folder::find_or_make(strtolower($parentFolder->Filename . '/' . $folderName)) : Folder::find_or_make(strtolower($folderName));
            if ($Folder) {
                $AdministratorsGroup = Group::get()->filter(array('Title' => 'Administrators'))->First();
                $editorGroups = $member->CurrentDataStore()->Groups()->filter(array(
                    'Permissions.Code' => array('CREATE_FILE', 'EDIT_FILE', 'VIEW_FILE', 'DELETE_FILE')
                ));

                if ($editorGroups) {
                    $Folder->CanEditType = 'OnlyTheseUsers';
                    foreach ($editorGroups as $editorGroup) {
                        $Folder->EditorGroups()->add($editorGroup);
                    }
                }

                $viewerGroups = $member->CurrentDataStore()->Groups()->filter(array(
                    'Permissions.Code' => array('VIEW_FILE')
                ));

                if ($viewerGroups) {
                    $Folder->CanViewType = 'OnlyTheseUsers';
                    foreach ($viewerGroups as $viewerGroup) {
                        $Folder->ViewerGroups()->add($viewerGroup);
                    }
                }

                if ($AdministratorsGroup) {
                    $Folder->EditorGroups()->add($AdministratorsGroup);
                    $Folder->ViewerGroups()->add($AdministratorsGroup);
                }

                return $Folder;
            }
        }
    }

    /**
     * This function inspects if folder exists
     * @param integer $folderID
     * @return boolean
     */
    public static function isFolderExists($folderID) {
        return Versioned::get_by_stage("SilverStripe\Assets\Folder", Versioned::LIVE)->byID($folderID) ? true : false;
    }

    /**
     * This function returns EveryDataStore default asset directory
     * @return DataObject
     */
    public static function getAssetRootDir() {
        $assetDirName = Config::inst()->get('SilverStripe\Assets\File', 'root_dir_name') ?  Config::inst()->get('SilverStripe\Assets\File', 'root_dir_name'): 'everydatastore';
        $dirObject = Versioned::get_by_stage("SilverStripe\Assets\Folder", Versioned::LIVE)->filter(['Name' => $assetDirName, 'ParentID' => 0])->first();
        $assetDir = !$dirObject ? Folder::find_or_make(strtolower($assetDirName)): $dirObject;
        if (!$assetDir->Slug) {
            $assetDir->Slug = EveryDataStoreHelper::getAvailableSlug("SilverStripe\Assets\Folder");
            $assetDir->write();
        }
        return $assetDir;
    }

    /**
     * This function creates a file from base64 string
     * @param string $base64
     * @param DataObject $parentFolder
     * @param string $foldername
     * @param string $filename
     * @return array
     */
    public static function createFileFromBase64($base64, $parentFolder, $foldername, $filename){       
        $base64File =  self::createFileFromBase64InTmp($base64, $foldername, $filename);
        $fileHash = self::createFileHash($base64File);
        $folder = self::createFolder($foldername, $parentFolder);
        $retFile = [];
        $data = explode(',', $base64);
        $extension = explode(';', explode('/', $data[0])[1])[0];
        $fileClass = $extension ? self::getFileClassByExtension($extension) : 'SilverStripe\Assets\File';

        $file = Injector::inst()->create($fileClass);
        $file->NameClass = $fileClass;
        $file->ParentID = $folder->ID;
        $file->Name = $filename . '.' . $extension;
        $file->Title = $filename . $extension;
        $file->FileFilename = $folder->Filename . $filename . '.' . $extension;
        $file->FileHash = $fileHash;
        $fileID = $file->writeWithoutVersion();

        $recordSet = Versioned::get_by_stage($fileClass, Versioned::LIVE)->byID($fileID);
        $output = $folder->Filename.substr($fileHash, 0, 10).'/'.$filename . '.' . $extension;
        $recordSet = self::createFileFromLocale($recordSet, $base64File, $output);
        $recordSet->writeWithoutVersion();
        $recordSet->publishSingle();


        $retFile['ParentID'] = $recordSet->ParentID;
        $retFile['ID'] = $recordSet->ID;
        $retFile['Slug'] = $recordSet->Slug;
        $retFile['Name'] = $recordSet->Name;
        $retFile['Size'] = $recordSet->getSize();
        $retFile['ClassName'] = $recordSet->ClassName;
        $retFile['ProtectedURL'] = $recordSet->getProtectedURL();
        $retFile['ThumbnailURL'] = $recordSet->getThumbnailURL();
        return $retFile;

    }

    /**
     * Create a tmp file from the base64 string.
     * @param string $base64
     * @param string $foldername
     * @param string $filename
     * @return string
     */
    public static function createFileFromBase64InTmp($base64, $foldername, $filename) {
        $data = explode(',', $base64);
        $extension = explode(';', explode('/', $data[0])[1])[0];
        $tmp_dir = self::createDirIfNotExists(ASSETS_PATH . '/.protected/base64/'.self::getCurrentDataStore()->Title.'/'.$foldername.'/'.date("Ymdhis").'/');
        $output_file = $extension ? $tmp_dir . $filename . '.' . $extension : null;
        $of = fopen($output_file, 'w');
        if($of){
            fwrite($of, base64_decode($data[1]));
            fclose($of);
            return $output_file;
        }
    }

    /**
     * This functions creates SilverStripe file from local file
     * @param DataObject $fileObject
     * @param string $localFilePath
     * @param string $outputFilePath
     */
    public static function createFileFromLocale($fileObject, $localFilePath, $outputFilePath) {
        $fileObject->setFromLocalFile($localFilePath, $outputFilePath);
        $fileObject->publishSingle();
        return $fileObject;
    }


    /**
     * This function creates file hash
     * @param string $path
     * @return string
     */

    public static function createFileHash($path) {
        $niceProtectedPath = str_replace(ASSETS_PATH . '/.protected/', '', $path);
        $assetStore = Injector::inst()->get(AssetStore::class);
        $hasher = Injector::inst()->get(FileHashingService::class);
        $filesystem = $assetStore->getProtectedFilesystem();
        return $hasher->computeFromFile($niceProtectedPath, $filesystem);
    }

    /**
     * This function returns the file class based on its extension
     * @param string $extension
     * @return string
     */
    public static function getFileClassByExtension($extension) {

        switch ($extension) {
            case 'jpg':
                return 'SilverStripe\Assets\Image';
                break;
            case 'jpeg':
                return'SilverStripe\Assets\Image';
                break;
            case 'png':
                return'SilverStripe\Assets\Image';
                break;
            case 'gif':
                return 'SilverStripe\Assets\Image';
                break;
            default:
                return 'SilverStripe\Assets\File';
        }
    }

    /**
     * This function checks whether a directory on the path exists
     * and creates one if it does not already exist
     *
     * @param string $dirPath
     * @return string
     */
    public static function createDirIfNotExists($dirPath) {
        $nicePath = str_replace(' ', '', $dirPath);
        if (!file_exists($nicePath)) {
            mkdir($nicePath, 0755, true);
        }
        return $nicePath;
    }

    /**
     * Returns available, randomly generated asset name
     *
     * @param string $className
     * @param integer $length
     * @return string
     */
    public static function getAvailableAssetName($className, $length = 20) {
        $randomName = self::getRandomString($length);
        $asset = File::get()->filter(['Name' => $randomName, 'ClassName' => $className])->first();
        if ($asset) {
            return self::createAvailableAssetName($className);
        }
        return $randomName;
    }

    /**
     * This function checks if a string is base64
     * @param string $string
     * @return boolean
     */
    public static function isStringBase64($string) {
        $data = $string ? explode('base64,', $string) : null;
        return $data && isset($data[1]) && base64_encode(base64_decode($data[1], true)) === $data[1] ? true : false;
    }

    /**
     * This function checks for the type of deletion and
     * deletes a file according to the deletion type
     *
     * @param string $fileSlug
     * @param string $deleteType
     */
    public static function deleteFile($fileSlug, $deleteType = false) {
        $file = $fileSlug ? self::getFileBySlug($fileSlug) : '';
        if ($file) {
            if ($deleteType == 'permanently') {
                self::deleteAssetPermanently($file);
            } else {
                if ($file->CanDelete()) {
                    $file->DeletionDate = (new \DateTime())->format('Y-m-d H:i:s');
                    $file->writeWithoutVersion();
                }
            }
        }
    }

    /**
     * This function prepares files for upload and uploads them
     *
     * @param array $files
     * @param DataObject $folder
     * @return array
     */
    public static function prepareAndUploadFiles($files, $folder) {
        $retFiles = [];
        $i = 0;

        if (!empty($files) && isset($files['name']) && !empty($files['name'][0])) {
            foreach ($files['name'] as $file) {
                $tmpFile = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                );
                $retFiles[] = self::doUpload($tmpFile, $folder);
                $i++;
            }
            return $retFiles;
        }
    }

    /**
     * This function uploads a file and returns an array with file's attributes
     *
     * @param array $tmpFile
     * @param DataObject $folder
     * @return array
     */
    public static function doUpload($tmpFile, $folder) {
        $allowedFileExtensions = self::getAllowedFileExtensions();
        $allowedFileSize = self::getAllowedFileSize();
        $fileUploadError = '';
        $fileStatus = '';
        $upload = self::getUpload($allowedFileExtensions, $allowedFileSize);

        if (!$upload->validate($tmpFile)) {
            $fileStatus = 0;
            $fileUploadError .= $upload->getErrors();
        }

        $fileClass = File::get_class_for_file_extension(File::get_file_extension($tmpFile['name']));
        $file = Injector::inst()->create($fileClass);
        $uploadResult = $upload->loadIntoFile($tmpFile, $file, $folder->getFilename());

        if (!$uploadResult) {
            $fileUploadError .= _t('AssestManager.FAILED_TO_LOAD_FILE', 'Failed to load file');
            $fileStatus = 0;
            $FileID = 0;
        } else {
            $fileStatus = 1;
            $file->ParentID = $folder->ID;
            $FileID = $file->writeWithoutVersion();
            $file->publishSingle();
            $recordSet = Versioned::get_by_stage($fileClass, Versioned::LIVE)->byID($FileID);
            $recordSet->writeWithoutVersion();
            $recordSet->publishSingle();
        }

        return array(
            'ID' => $FileID,
            'Name' => $tmpFile['name'],
            'Status' => $fileStatus,
            'fileUploadError' => $fileUploadError
        );
    }

    /**
     * This function sets up the allowed file extensions and file size
     *
     * @param array $allowedExtensions
     * @param integer $allowedFileSize
     * @return DataObject
     */
    private static function getUpload($allowedExtensions, $allowedFileSize) {
        $upload = Upload::create();
        $upload->getValidator()->setAllowedExtensions(
            $allowedExtensions
        );
        $upload->getValidator()->setAllowedMaxFileSize(
            $allowedFileSize
        );

        return $upload;
    }

    /**
     * This function returns a list of allowed extensions for file upload
     * @return array
     */
    public static function getAllowedFileExtensions() {
        return self::getCurrentDataStore()->UploadAllowedExtensions;
    }

    /**
     * This function returns allowed file size for upload
     * @return integer
     */
    public static function getAllowedFileSize() {
        return self::getEveryConfig('UploadAllowedFileSize');
    }

    /**
     * This function returns the maximum number of files for upload
     * @return integer
     */
    public static function getAllowedMaxFileNumber() {
        return self::getEveryConfig('UploadAllowedFileNumber');
    }

    /**
     * This function returns a file that corresponds to the provided Slug
     * @param string $Slug
     * @return DataObject
     */
    public static function getFileBySlug($Slug) {
        $file = Versioned::get_by_stage('SilverStripe\Assets\File', Versioned::LIVE)->filter(array('Slug' => $Slug))->First();
        return $file ? $file : '';
    }

    /**
     * This function returns a folder that corresponds to the provided Slug
     * @param string $slug
     * @return DataObject
     */
    public static function getFolderBySlug($slug) {
        $folder = Versioned::get_by_stage('SilverStripe\Assets\Folder', Versioned::LIVE)->filter(array('Slug' => $slug))->First();
        return $folder ? $folder : '';
    }

    /**
     * This function returns all Notes that are related to the file
     * @param DataObject $file
     * @return array
     */
    public static function getFileNotes($file) {
        if ($file->Notes()->Count() > 0 && self::checkPermission('VIEW_NOTE')) {
            $items = [];
            foreach ($file->Notes() as $note) {
                $items[] = array(
                    'Slug' => $note->Slug,
                    'Content' => $note->Content,
                    'CreatedBy' => $note->CreatedBy()->getFullName(),
                    'Created' => self::getNiceDateTimeFormat($note->Created)
                );
            }
            return $items;
        }
    }

    /**
     * This function deletes an asset permanently
     *
     * @param DataObject $asset
     */
    public static function deleteAssetPermanently($asset) {
        if ($asset && self::checkPermission('DELETE_FILE_PERMANENTLY')) {
            $asset->deleteFromStage(Versioned::LIVE);
            $asset->deleteFromStage(Versioned::DRAFT);
            $asset->delete();
            $asset->destroy();
            $asset->deleteFile();
        }
    }

    /**
     * This function returns slugs of Groups with VIEW permission code for the provided asset
     *
     * @param DataObject $asset
     * @return array
     */
    public static function getAssetViewerGroupSlugs($asset) {
        if (!empty($asset->ViewerGroups())) {
            $slugs = [];
            foreach ($asset->ViewerGroups() as $viewerGroup) {
                $slugs[] = $viewerGroup->Slug;
            }
            return $slugs;
        }
    }

    /**
     * This function returns slugs of Groups with EDIT permission code for the provided asset
     *
     * @param DataObject $asset
     * @return array
     */
    public static function getAssetEditorGroupSlugs($asset) {
        if (!empty($asset->EditorGroups())) {
            $slugs = [];
            foreach ($asset->EditorGroups() as $editorGroup) {
                $slugs[] = $editorGroup->Slug;
            }
            return $slugs;
        }
    }

    /**
     * This function sets up permission codes for the asset
     *
     * @param DataObject $asset
     * @param string $canViewType
     * @param array $viewerGroups
     * @param array $editorGroups
     */
    public static function setAssetPermission($asset, $canViewType, $viewerGroups = false, $editorGroups = false) {
        self::resetAssetEditorGroups($asset);
        self::resetAssetViewerGroups($asset);

        if ($canViewType == "Anyone") {
            $asset->CanViewType = 'Anyone';
        } else if ($canViewType == "LoggedInUsers") {
            $asset->CanViewType = 'LoggedInUsers';
        }

        if (!empty($viewerGroups)) {
            $asset->CanViewType = 'OnlyTheseUsers';
            self::setAssetViewerGroups($asset, $viewerGroups);
        }

        if (!empty($editorGroups)) {
            $asset->CanEditType = 'OnlyTheseUsers';
            self::setAssetEditorGroups($asset, $editorGroups);
        }
        $asset->writeWithoutVersion();
    }

    /**
     * This function assigns a viewer permission to a Group
     *
     * @param DataObject $asset
     * @param array $viewerGroups
     */
    public static function setAssetViewerGroups($asset, $viewerGroups) {
        self::resetAssetViewerGroups($asset);
        foreach ($viewerGroups as $viewerGroup) {
            $groudID = is_int($viewerGroup) ? $viewerGroup : self::getOneBySlug($viewerGroup, 'SilverStripe\Security\Group')->ID;
            $asset->ViewerGroups()->add($groudID);
        }
        $asset->writeWithoutVersion();
    }

    /**
     * This function assigns an editor permission to a Group
     *
     * @param DataObject $asset
     * @param array $editorGroups
     */
    public static function setAssetEditorGroups($asset, $editorGroups) {
        self::resetAssetEditorGroups($asset);
        foreach ($editorGroups as $editorGroup) {
            $groudID = is_int($editorGroup) ? $editorGroup : self::getOneBySlug($editorGroup, 'SilverStripe\Security\Group')->ID;
            $asset->EditorGroups()->add($groudID);
        }
        $asset->writeWithoutVersion();
    }

    /**
     * This function removes all Viewer Groups for a provided asset
     *
     * @param DataObject $asset
     */
    public static function resetAssetViewerGroups($asset) {
        if ($asset->ViewerGroups()->Count() > 0) {
            foreach ($asset->ViewerGroups() as $viewerGroup) {
                $asset->ViewerGroups()->remove($viewerGroup);
            }
        }
    }

    /**
     * This function removes all Editor Groups for a provided asset
     *
     * @param DataObject $asset
     */
    public static function resetAssetEditorGroups($asset) {
        if ($asset->EditorGroups()->Count() > 0) {
            foreach ($asset->EditorGroups() as $editorGroup) {
                $asset->EditorGroups()->remove($editorGroup);
            }
        }
    }

    /**
     * This function returns a list of file attributes that shall appear in the ResultList
     *
     * @return array
     */
    public static function getFileResultListFields() {

        return array('Slug', 'Name', 'Title', 'Version', 'Created', 'LastEdited', 'CreatedBy', 'UpdatedBy');
    }

    /**
     * This function returns all file label names like Name, Filename, Created etc.
     *
     * @return array
     */
    public static function getFileResultListLabels() {
        $labels = [];
        foreach (self::getFileResultListFields() as $field) {
            $labels[] = array(
                'data' => $field
            );
        }
        return $labels;
    }
    /**
     * Thought for next release
     */
    public static function getAdditionalFormFields() {}

    /**
     * This function returns a Slug of a file that precedes the provided file
     * when files are sorted by file name in ASC order
     *
     * @param DataObject $file
     * @return string
     */
    public static function getPrevFileSlug($file) {
        $prevFile = Versioned::get_by_stage('SilverStripe\Assets\File', Versioned::LIVE)->filter(['ID:LessThan' => $file->ID, 'ParentID' => $file->ParentID])->Sort('Name', 'ASC')->First();
        return $prevFile ? $prevFile->Slug : '';
    }

    /**
     * This function returns a Slug of a file that follows the provided file
     * when files are sorted by file name in ASC order
     *
     * @param DataObject $file
     * @return string
     */
    public static function getNextFileSlug($file) {
        $nextFile = Versioned::get_by_stage('SilverStripe\Assets\File', Versioned::LIVE)->filter(['ID:GreaterThan' => $file->ID, 'ParentID' => $file->ParentID])->Sort('Name', 'ASC')->First();
        return $nextFile ? $nextFile->Slug : '';
    }

    /**
     * This function checks whether the parent folder is in the same dataStore
     *
     * @param string $Slug
     * @return boolean
     */
    public static function checkisParentInResponsitory($Slug) {

        $DataStoreFolderID = self::getCurrentDataStore()->FolderID;

        $Folder = Folder::get()->filter(array('Slug' => $Slug))->First();
        if ($Folder) {
            if ($Folder->ID == $DataStoreFolderID) {
                return true;
            } else {
                return self::checkisParentInResponsitory($Folder->Parent()->Slug);
            }
        }
    }

    /**
     * This function checks whether the RecordSetItem is in a Folder with provided Slug
     *
     * @param string $Slug
     * @param string $recordSetItemFolderID
     * @return boolean
     */
    public static function checkisRecordSetItemFolder($Slug, $recordSetItemFolderID) {
        $Folder = Folder::get()->filter(array('Slug' => $Slug))->First();
        if ($Folder) {
            if ($Folder->ID == $recordSetItemFolderID) {
                return true;
            } else {
                return self::checkisRecordSetItemFolder($Folder->Parent()->Slug, $recordSetItemFolderID);
            }
        }
    }

    /**
     * This function returns a list of permissions for a provided asset
     *
     * @param DataObject $asset
     * @return array
     */
    public static function getAssetPermissions($asset) {
        $Permissions = [];
        if ($asset->ClassName == 'SilverStripe\Assets\File' || $asset->ClassName == 'SilverStripe\Assets\Image') {
            $Permissions = array(
                'CREATE_FILE' => $asset->CanCreate(),
                'EDIT_FILE' => $asset->CanEdit(),
                'VIEW_FILE' => $asset->CanView(),
                'DELETE_FILE' => $asset->CanDelete(),
                'DELETE_FILE_PERMANENTLY' => self::checkPermission('DELETE_FILE_PERMANENTLY'),
                'VIEW_NOTE' => self::checkPermission('VIEW_NOTE'),
                'CREATE_NOTE' => self::checkPermission('CREATE_NOTE'),
                'DELETE_NOTE' => self::checkPermission('DELETE_NOTE')
            );
        } else {
            $Permissions = array(
                'CREATE_FOLDER' => $asset->CanCreate(),
                'EDIT_FOLDER' => $asset->CanEdit(),
                'VIEW_FOLDER' => $asset->CanView(),
                'DELETE_FOLDER' => $asset->CanDelete()
            );
        }

        return $Permissions;
    }

    /**
     * This function returns IconCls for a provided extension of a file
     *
     * @param string $extension
     * @return string
     */
    public static function getFileIconCls($extension) {
        $IconCls = '';

        switch ($extension) {
            case 'pdf':
                $IconCls = 'fa-file-pdf-o';
                break;
            case 'doc':
                $IconCls = 'fa fa-file-word-o';
                break;
            case 'docx':
                $IconCls = 'fa fa-file-word-o';
                break;
            case 'txt':
                $IconCls = 'fa fa-file-o';
                break;
            case 'zip':
                $IconCls = 'fa fa-file-archive-o';
                break;
            case 'rar':
                $IconCls = 'fa fa-file-archive-o';
                break;
            case 'tar':
                $IconCls = 'fa fa-file-archive-o';
                break;
            default:
                $IconCls = 'fa fa-file-o';
                return $IconCls;
        }
    }

    /**
     * This function creates a new local file and sets it to the backend
     *
     * @param DataObject $fileLocal
     * @param string $folderPath
     * @param string $filename
     * @return DataObject
     */
    public static function setAssetFromLocal($fileLocal, $folderPath, $filename) {
        $folder = Folder::find_or_make($folderPath);
        $file = new File();
        $file->setFromLocalFile($fileLocal, $folder->Filename . $filename);
        $file->write();
        return $file;
    }

    /**
     * This function returns the directory size
     *
     * @param string $directory
     * @return integer
     */
    public static function getDirSize($directory) {
        $io = popen('/usr/bin/du -sk ' . $directory, 'r');
        $size = fgets($io, 4096);
        $size = substr($size, 0, strpos($size, "\t"));
        pclose($io);
        return $size * 1024;
    }
}
