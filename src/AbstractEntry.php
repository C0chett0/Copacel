<?php

namespace C0chett0\CopacelEws;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;

class AbstractEntry
{
    protected static function getAddressbook()
    {
        $request = new FindItemType();
        $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::DEFAULT_PROPERTIES;

        $folder_id = new DistinguishedFolderIdType();
        $folder_id->Id = DistinguishedFolderIdNameType::CONTACTS;
        $request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

        return self::parseResponses(Client::getInstance()->FindItem($request));
    }

    private static function parseResponses($response)
    {
        $result = [];
        $response_messages = $response->ResponseMessages->FindItemResponseMessage;
        foreach ($response_messages as $response_message) {
            if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
                $code = $response_message->ResponseCode;
                $message = $response_message->MessageText;

                //ToDo Gestion d'erreur
                continue;
            }

            $lists = $response_message->RootFolder->Items->DistributionList;
            foreach ($lists as $list) {
                $result['lists'][] = [
                    'name' => $list->FileAs,
                    'id' => $list->ItemId->Id
                ];
            }

            $contacts = $response_message->RootFolder->Items->Contact;
            foreach ($contacts as $contact) {
                $result['contacts'][] = [
                    'firstname' => $contact->CompleteName->FirstName,
                    'lastname' => $contact->CompleteName->LastName,
                    'emails' => (!is_null($contact->EmailAddresses)) ? array_pluck($contact->EmailAddresses->Entry, "_") : [],
                    'addresses' => (!is_null($contact->PhysicalAddresses)) ? array_map(function ($entry) {
                        return $entry->Street . " " . $entry->PostalCode . " " . $entry->City;
                    }, $contact->PhysicalAddresses->Entry) : [],
                    'phones' => (!is_null($contact->PhoneNumbers)) ? array_pluck($contact->PhoneNumbers->Entry, "_") : [],
                    'company' => $contact->CompanyName,
                    'factory' => $contact->OfficeLocation,
                    'job' => $contact->JobTitle,
                    'id' => $contact->ItemId->Id
                ];
            }
        }

        return $result;
    }
}