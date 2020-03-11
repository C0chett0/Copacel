<?php


namespace C0chett0\CopacelEws;


use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangesType;
use jamesiarmes\PhpEws\Enumeration\ConflictResolutionType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DictionaryURIType;
use jamesiarmes\PhpEws\Enumeration\DisposalType;
use jamesiarmes\PhpEws\Enumeration\EmailAddressKeyType;
use jamesiarmes\PhpEws\Enumeration\FileAsMappingType;
use jamesiarmes\PhpEws\Enumeration\MailboxTypeType;
use jamesiarmes\PhpEws\Enumeration\PhoneNumberKeyType;
use jamesiarmes\PhpEws\Enumeration\PhysicalAddressKeyType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\CreateItemType;
use jamesiarmes\PhpEws\Request\DeleteItemType;
use jamesiarmes\PhpEws\Request\EmptyFolderType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Request\UpdateItemType;
use jamesiarmes\PhpEws\Type\AddressListIdType;
use jamesiarmes\PhpEws\Type\AppendToItemFieldType;
use jamesiarmes\PhpEws\Type\ContactItemType;
use jamesiarmes\PhpEws\Type\DeleteItemFieldType;
use jamesiarmes\PhpEws\Type\DistributionListType;
use jamesiarmes\PhpEws\Type\EmailAddressDictionaryEntryType;
use jamesiarmes\PhpEws\Type\EmailAddressType;
use jamesiarmes\PhpEws\Type\FolderIdType;
use jamesiarmes\PhpEws\Type\ItemChangeType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\MembersListType;
use jamesiarmes\PhpEws\Type\MemberType;
use jamesiarmes\PhpEws\Type\PathToIndexedFieldType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\PhoneNumberDictionaryEntryType;
use jamesiarmes\PhpEws\Type\PhoneNumberDictionaryType;
use jamesiarmes\PhpEws\Type\PhysicalAddressDictionaryEntryType;
use jamesiarmes\PhpEws\Type\SetItemFieldType;
use jamesiarmes\PhpEws\Type\TargetFolderIdType;

class SynchroExchange implements SynchroExchangeInterface
{
    public static function createList($name, $contacts = [])
    {
        $request = new CreateItemType();
        $request->Items = new NonEmptyArrayOfAllItemsType();

        $request->SavedItemFolderId = new TargetFolderIdType();
        $request->SavedItemFolderId->FolderId = new FolderIdType();
        $request->SavedItemFolderId->FolderId->Id = Client::getListsFolderId();

        $distributionList = new DistributionListType();
        $distributionList->DisplayName = $name;

        $distributionList->Members = new MembersListType();


        foreach ($contacts as $contact) {
            $member = new MemberType();
            $member->Mailbox = new EmailAddressType();
            $member->Mailbox->EmailAddress = $contact['email'];
            $member->Mailbox->Name = $contact['name'];

            $distributionList->Members->Member[] = $member;
        }

        $request->Items->DistributionList = $distributionList;

        $response = Client::getInstance()->CreateItem($request);
        $response_messages = $response->ResponseMessages->CreateItemResponseMessage;
        foreach ($response_messages as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            } else {
                foreach ($message->Items->DistributionList as $item) {
                    return $item->ItemId->Id;
                }
            }
        }

        return false;
    }

    public static function renameList($listId, $name)
    {
        $actualMembers = self::getContacts($listId);

        self::deleteList($listId);

        return self::createList($name, $actualMembers);
    }

    public static function addContactsToList($listId, $contacts)
    {
        $request = new UpdateItemType();
        $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->ItemChanges = new NonEmptyArrayOfItemChangesType();

        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $listId;
        $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

        $field = new AppendToItemFieldType();
        $field->FieldURI = new PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = UnindexedFieldURIType::DISTRIBUTION_LIST_MEMBERS;
        $field->DistributionList = new DistributionListType();
        $field->DistributionList->Members = new MembersListType();

        foreach ($contacts as $contact) {
            $member = new MemberType();
            $member->Mailbox = new EmailAddressType();
            $member->Mailbox->EmailAddress = $contact['email'];
            $member->Mailbox->Name = $contact['name'];

            $field->DistributionList->Members->Member[] = $member;
        }
        $change->Updates->AppendToItemField[] = $field;
        $request->ItemChanges->ItemChange[] = $change;

        $response = Client::getInstance()->UpdateItem($request);
        $response_messages = $response->ResponseMessages->UpdateItemResponseMessage;

        foreach ($response_messages as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function removeContactsFromList($listId, $contacts)
    {
        $actualMembers = self::getContacts($listId);

        self::emptyList($listId);
        return self::addContactsToList(
            $listId,
                array_filter(
                    $actualMembers,
                    function ($contact) use ($contacts) {
                        return !in_array($contact['email'], $contacts);
                    })
        );
    }

    public static function deleteList($listId)
    {
        $request = new DeleteItemType();
        $request->DeleteType = DisposalType::HARD_DELETE;

        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

        $item = new AddressListIdType();
        $item->Id = $listId;
        $request->ItemIds->ItemId[] = $item;

        $response = Client::getInstance()->DeleteItem($request);
        foreach ($response->ResponseMessages->DeleteItemResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function createContact($contact)
    {
        $request = new CreateItemType();
        $request->Items = new NonEmptyArrayOfAllItemsType();

        $request->SavedItemFolderId = new TargetFolderIdType();
        $request->SavedItemFolderId->FolderId = new FolderIdType();
        $request->SavedItemFolderId->FolderId->Id = Client::getContactsFolderId();

        $exchangeContact = new ContactItemType();
        $exchangeContact->FileAsMapping = FileAsMappingType::LAST_COMMA_FIRST;
        $exchangeContact->GivenName = $contact['firstname'];
        $exchangeContact->Surname = $contact['lastname'];

        if(array_key_exists('email', $contact) && $contact['email']) {
            $exchangeEmail = new EmailAddressDictionaryEntryType();
            $exchangeEmail->Key = EmailAddressKeyType::EMAIL_ADDRESS_1;
            $exchangeEmail->_ = $contact['email'];
            $exchangeContact->EmailAddresses->Entry[] = $exchangeEmail;
        }

        if(array_key_exists('address', $contact) && $contact['address']) {
            $address = new PhysicalAddressDictionaryEntryType();
            $address->Key = PhysicalAddressKeyType::BUSINESS;
            $address->Street = $contact['address'];
            $exchangeContact->PhysicalAddresses->Entry[] = $address;
        }

        $exchangeContact->CompanyName = $contact['company'];
        $exchangeContact->OfficeLocation = $contact['factory'];
        $exchangeContact->JobTitle = $contact['job'];

        $flag = false;
        foreach (['fix', 'fax', 'mobile'] as $key) {
            switch($key) {
                case 'fix':
                    $keyType = PhoneNumberKeyType::BUSINESS_PHONE;
                    break;
                case 'fax':
                    $keyType = PhoneNumberKeyType::BUSINESS_FAX;
                    break;
                case 'mobile':
                    $keyType = PhoneNumberKeyType::MOBILE_PHONE;
                    break;
                default:
                    continue;
            }
            if(array_key_exists($key, $contact) && $contact[$key]) {
                if (!$flag) {
                    $flag = true;
                    $exchangeContact->PhoneNumbers = new PhoneNumberDictionaryType();
                }
                $phone = new PhoneNumberDictionaryEntryType();
                $phone->Key = $keyType;
                $phone->_ = $contact[$key];
                $exchangeContact->PhoneNumbers->Entry[] = $phone;
            }
        }

        $request->Items->Contact = [$exchangeContact];

        $response = Client::getInstance()->CreateItem($request);

        $response_messages = $response->ResponseMessages->CreateItemResponseMessage;
        foreach ($response_messages as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            } else {
                foreach ($message->Items->Contact as $item) {
                    return $item->ItemId->Id;
                }
            }
        }

        return false;
    }

    public static function updateContact($contactId, $contact)
    {
        $request = new UpdateItemType();
        $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->ItemChanges = new NonEmptyArrayOfItemChangesType();

        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $contactId;
        $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

        foreach ($contact as $key => $value) {
            if ($value) {
                $field = new SetItemFieldType();
            } else {
                $field = new DeleteItemFieldType();
            }
            switch ($key) {
                case 'firstname':
                    $field->FieldURI = new PathToUnindexedFieldType();
                    $field->FieldURI->FieldURI = UnindexedFieldURIType::CONTACTS_GIVEN_NAME;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->GivenName = $value;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'lastname':
                    $field->FieldURI = new PathToUnindexedFieldType();
                    $field->FieldURI->FieldURI = UnindexedFieldURIType::CONTACTS_SURNAME;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->Surname = $value;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'company':
                    $field->FieldURI = new PathToUnindexedFieldType();
                    $field->FieldURI->FieldURI = UnindexedFieldURIType::CONTACTS_COMPANY_NAME;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->CompanyName = $value;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'factory':
                    $field->FieldURI = new PathToUnindexedFieldType();
                    $field->FieldURI->FieldURI = UnindexedFieldURIType::CONTACTS_OFFICE_LOCATION;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->OfficeLocation = $value;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'job':
                    $field->FieldURI = new PathToUnindexedFieldType();
                    $field->FieldURI->FieldURI = UnindexedFieldURIType::CONTACTS_JOB_TITLE;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->JobTitle = $value;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'email':
                    $field->IndexedFieldURI = new PathToIndexedFieldType();
                    $field->IndexedFieldURI->FieldURI = DictionaryURIType::CONTACTS_EMAIL_ADDRESS;
                    $field->IndexedFieldURI->FieldIndex = EmailAddressKeyType::EMAIL_ADDRESS_1;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $exchangeEmail = new EmailAddressDictionaryEntryType();
                        $exchangeEmail->Key = EmailAddressKeyType::EMAIL_ADDRESS_1;
                        $exchangeEmail->_ = $value;
                        $field->Contact->EmailAddresses->Entry[] = $exchangeEmail;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'address':
                    $field->IndexedFieldURI = new PathToIndexedFieldType();
                    $field->IndexedFieldURI->FieldURI = DictionaryURIType::CONTACTS_PHYSICAL_ADDRESS_STREET;
                    $field->IndexedFieldURI->FieldIndex = PhysicalAddressKeyType::BUSINESS;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $address = new PhysicalAddressDictionaryEntryType();
                        $address->Key = PhysicalAddressKeyType::BUSINESS;
                        $address->Street = $contact['address'];
                        $field->Contact->PhysicalAddresses->Entry[] = $address;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'fix':
                    $field->IndexedFieldURI = new PathToIndexedFieldType();
                    $field->IndexedFieldURI->FieldURI = DictionaryURIType::CONTACTS_PHONE_NUMBER;
                    $field->IndexedFieldURI->FieldIndex = PhoneNumberKeyType::BUSINESS_PHONE;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->PhoneNumbers = new PhoneNumberDictionaryType();
                        $phone = new PhoneNumberDictionaryEntryType();
                        $phone->Key = PhoneNumberKeyType::BUSINESS_PHONE;
                        $phone->_ = $contact[$key];
                        $field->Contact->PhoneNumbers->Entry[] = $phone;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'fax':
                    $field->IndexedFieldURI = new PathToIndexedFieldType();
                    $field->IndexedFieldURI->FieldURI = DictionaryURIType::CONTACTS_PHONE_NUMBER;
                    $field->IndexedFieldURI->FieldIndex = PhoneNumberKeyType::BUSINESS_FAX;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->PhoneNumbers = new PhoneNumberDictionaryType();
                        $phone = new PhoneNumberDictionaryEntryType();
                        $phone->Key = PhoneNumberKeyType::BUSINESS_FAX;
                        $phone->_ = $contact[$key];
                        $field->Contact->PhoneNumbers->Entry[] = $phone;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
                case 'mobile':
                    $field->IndexedFieldURI = new PathToIndexedFieldType();
                    $field->IndexedFieldURI->FieldURI = DictionaryURIType::CONTACTS_PHONE_NUMBER;
                    $field->IndexedFieldURI->FieldIndex = PhoneNumberKeyType::MOBILE_PHONE;
                    if($value) {
                        $field->Contact = new ContactItemType();
                        $field->Contact->PhoneNumbers = new PhoneNumberDictionaryType();
                        $phone = new PhoneNumberDictionaryEntryType();
                        $phone->Key = PhoneNumberKeyType::MOBILE_PHONE;
                        $phone->_ = $contact[$key];
                        $field->Contact->PhoneNumbers->Entry[] = $phone;
                        $change->Updates->SetItemField[] = $field;
                    } else {
                        $change->Updates->DeleteItemField[] = $field;
                    }
                    break;
            }
        }
        $request->ItemChanges->ItemChange[] = $change;

        $response = Client::getInstance()->UpdateItem($request);
        foreach ($response->ResponseMessages->UpdateItemResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function deleteContact($contactId)
    {
        $request = new DeleteItemType();
        $request->DeleteType = DisposalType::HARD_DELETE;

        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

        $item = new ItemIdType();
        $item->Id = $contactId;
        $request->ItemIds->ItemId[] = $item;

        $response = Client::getInstance()->DeleteItem($request);
        foreach ($response->ResponseMessages->DeleteItemResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function purge()
    {
        $request = new EmptyFolderType();
        $request->DeleteType = DisposalType::HARD_DELETE;
        $request->DeleteSubFolders = false;

        $request->FolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $contactsId = new FolderIdType();
        $contactsId->Id = Client::getContactsFolderId();
        $listsId = new FolderIdType();
        $listsId->Id = Client::getListsFolderId();
        $request->FolderIds->FolderId = [
            $contactsId,
            $listsId
        ];

        $response = Client::getInstance()->EmptyFolder($request);
        foreach ($response->ResponseMessages->EmptyFolderResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function purgeContacts()
    {
        $request = new EmptyFolderType();
        $request->DeleteType = DisposalType::HARD_DELETE;
        $request->DeleteSubFolders = false;

        $request->FolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $contactsId = new FolderIdType();
        $contactsId->Id = Client::getContactsFolderId();
        $request->FolderIds->FolderId = [
            $contactsId
        ];

        $response = Client::getInstance()->EmptyFolder($request);
        foreach ($response->ResponseMessages->EmptyFolderResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public static function purgeLists()
    {
        $request = new EmptyFolderType();
        $request->DeleteType = DisposalType::HARD_DELETE;
        $request->DeleteSubFolders = false;

        $request->FolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $listsId = new FolderIdType();
        $listsId->Id = Client::getListsFolderId();
        $request->FolderIds->FolderId = [
            $listsId
        ];

        $response = Client::getInstance()->EmptyFolder($request);
        foreach ($response->ResponseMessages->EmptyFolderResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    protected static function getContacts($id)
    {
        $contacts = [];
        $request = new GetItemType();
        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::DEFAULT_PROPERTIES;
        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

        $item = new AddressListIdType();
        $item->Id = $id;
        $request->ItemIds->ItemId[] = $item;

        $response = Client::getInstance()->GetItem($request);
        $response_messages = $response->ResponseMessages->GetItemResponseMessage;
        foreach ($response_messages as $response_message) {
            if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
            foreach ($response_message->Items->DistributionList as $list) {
                if (!is_null($list->Members)) {
                    foreach ($list->Members->Member as $member) {
                        $contacts[] = [
                            'id' => $member->Key,
                            'email' => $member->Mailbox->EmailAddress,
                            'name' => $member->Mailbox->Name,
                        ];
                    }
                }
            }
        }
        return $contacts;
    }

    protected static function emptyList($id)
    {
        $request = new UpdateItemType();
        $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->ItemChanges = new NonEmptyArrayOfItemChangesType();

        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $id;
        $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

        $field = new DeleteItemFieldType();
        $field->FieldURI = new PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = UnindexedFieldURIType::DISTRIBUTION_LIST_MEMBERS;
        $field->DistributionList = new DistributionListType();
        $field->DistributionList->Members = new MembersListType();

        $change->Updates->DeleteItemField[] = $field;
        $request->ItemChanges->ItemChange[] = $change;

        $response = Client::getInstance()->UpdateItem($request);
        $response_messages = $response->ResponseMessages->UpdateItemResponseMessage;

        foreach ($response_messages as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }
}