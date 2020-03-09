<?php


namespace C0chett0\CopacelEws;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfFolderNamesType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfPathsToElementType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\FolderQueryTraversalType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\FolderIdType;
use jamesiarmes\PhpEws\Request\FindFolderType;
use jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use function getenv;
use jamesiarmes\PhpEws\Client as BaseClient;

class Client
{
    private static $instance;

    private static $listsFolderId;

    private static $contactsFolderId;

    private function __construct() {}

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new BaseClient(
                getenv('SERVER'),
                getenv('LOGIN'),
                getenv('PASSWORD'),
                getenv('VERSION')
            );
        }

        return self::$instance;
    }

    public static function getListsFolderId()
    {
        if (! self::$listsFolderId) {
            self::setFoldersIds();
        }

        return self::$listsFolderId;
    }

    public static function getContactsFolderId()
    {
        if (! self::$contactsFolderId) {
            self::setFoldersIds();
        }

        return self::$contactsFolderId;
    }

    private static function setFoldersIds()
    {
        $client = self::getInstance();
        $request = self::initRequest();

        $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $Id = new FolderIdType();
        $Id->Id = self::getRootFolderId();
        $request->ParentFolderIds->FolderId = [$Id];



        $response = $client->FindFolder($request);
        $folders = $response->ResponseMessages->FindFolderResponseMessage[0]->RootFolder->Folders->ContactsFolder;

        foreach ($folders as $folder) {
            if ($folder->DisplayName == getenv('CONTACTS_FOLDER')) {
                self::$contactsFolderId = $folder->FolderId->Id;
            }
            if ($folder->DisplayName == getenv('LISTS_FOLDER')) {
                self::$listsFolderId = $folder->FolderId->Id;
            }
        }
    }

    private static function getRootFolderId()
    {
        $client = self::getInstance();
        $request = self::initRequest();


        $request->ParentFolderIds = new NonEmptyArrayOfFolderNamesType();
        $Id = new DistinguishedFolderIdType();
        $Id->Id =  DistinguishedFolderIdNameType::PUBLIC_FOLDERS_ROOT;
        $request->ParentFolderIds->DistinguishedFolderId = [$Id];



        $response = $client->FindFolder($request);
        $folders = $response->ResponseMessages->FindFolderResponseMessage[0]->RootFolder->Folders->Folder;

        foreach ($folders as $folder) {
            if ($folder->DisplayName == getenv('ROOT_FOLDER')) {
                return $folder->FolderId->Id;
            }
        }

    }

    private static function initRequest()
    {
        $request = new FindFolderType();
        $request->Traversal = FolderQueryTraversalType::SHALLOW;
        $request->FolderShape = new FolderResponseShapeType();
        $request->FolderShape->BaseShape = DefaultShapeNamesType::ID_ONLY;
        $request->FolderShape->AdditionalProperties = new NonEmptyArrayOfPathsToElementType();
        $field = new PathToUnindexedFieldType();
        $field->FieldURI = UnindexedFieldURIType::FOLDER_DISPLAY_NAME;
        $request->FolderShape->AdditionalProperties->FieldURI = [$field];

        return $request;
    }
}