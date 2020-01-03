<?php

namespace C0chett0\CopacelEws;

use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;
use \jamesiarmes\PhpEws\Enumeration\ConflictResolutionType;
use \jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use \jamesiarmes\PhpEws\Enumeration\DisposalType;
use \jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use \jamesiarmes\PhpEws\Request\DeleteItemType;
use \jamesiarmes\PhpEws\Request\GetItemType;
use \jamesiarmes\PhpEws\Type\DeleteItemFieldType;
use \jamesiarmes\PhpEws\Type\ItemChangeType;
use \jamesiarmes\PhpEws\Type\ItemIdType;
use \jamesiarmes\PhpEws\Type\AppendToItemFieldType;
use \jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use \jamesiarmes\PhpEws\Type\DistributionListType;
use \jamesiarmes\PhpEws\Type\MembersListType;
use \jamesiarmes\PhpEws\Type\MemberType;
use \jamesiarmes\PhpEws\Type\EmailAddressType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangesType;
use \jamesiarmes\PhpEws\Request\UpdateItemType;
use \jamesiarmes\PhpEws\Request\CreateItemType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType;
use \jamesiarmes\PhpEws\Type\AddressListIdType;

class DistributionListEntry extends AbstractEntry
{
    private $name;
    private $id;

    /**
     * DistributionListEntry constructor.
     * @param $name
     * @param $id
     */
    protected function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
    }


    public static function find($name)
    {
        $addressBook = self::getAddressbook();
        foreach ($addressBook['lists'] as $list) {
            if ($list['name'] == $name) {
                $id = $list['id'];
            }
        }

        return new DistributionListEntry($name, $id);
    }

    public static function create($name, $contacts = [])
    {
        return new DistributionListEntry($name, self::newList($name, $contacts));
    }

    private static function newList($name, $contacts = [])
    {
        $request = new CreateItemType();
        $request->Items = new NonEmptyArrayOfAllItemsType();

        $distributionList = new DistributionListType();
        $distributionList->DisplayName = $name;

        $distributionList->Members = new MembersListType();


        foreach ($contacts as $contact) {
            $member = new MemberType();
            $member->Mailbox = new EmailAddressType();
            $member->Mailbox->EmailAddress = $contact;

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

    public function removeContacts($emails)
    {
        $actualMembers = $this->getContacts();

        $this->emptyContacts();
        $this->addContacts(
            array_column(
                array_filter(
                    $actualMembers,
                    function ($contact) use ($emails) {
                        return !in_array($contact['address'], $emails);
                    }),
                'address')
        );
    }

    public function getContacts()
    {
        $contacts = [];
        $request = new GetItemType();
        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::DEFAULT_PROPERTIES;
        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

        $item = new AddressListIdType();
        $item->Id = $this->id;
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
                            'address' => $member->Mailbox->EmailAddress
                        ];
                    }
                }
            }
        }
        return $contacts;
    }

    public function emptyContacts()
    {
        $request = new UpdateItemType();
        $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->ItemChanges = new NonEmptyArrayOfItemChangesType();

        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $this->id;
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

    public function addContacts($emails)
    {
        $request = new UpdateItemType();
        $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->ItemChanges = new NonEmptyArrayOfItemChangesType();

        $change = new ItemChangeType();
        $change->ItemId = new ItemIdType();
        $change->ItemId->Id = $this->id;
        $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

        $field = new AppendToItemFieldType();
        $field->FieldURI = new PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = UnindexedFieldURIType::DISTRIBUTION_LIST_MEMBERS;
        $field->DistributionList = new DistributionListType();
        $field->DistributionList->Members = new MembersListType();

        foreach ($emails as $email) {
            $member = new MemberType();
            $member->Mailbox = new EmailAddressType();
            $member->Mailbox->EmailAddress = $email;

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

    public function delete()
    {
        $request = new DeleteItemType();
        $request->DeleteType = DisposalType::HARD_DELETE;

        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

        $item = new AddressListIdType();
        $item->Id = $this->id;
        $request->ItemIds->ItemId[] = $item;

        $response = Client::getInstance()->DeleteItem($request);
        foreach ($response->ResponseMessages->DeleteItemResponseMessage as $message) {
            if ($message->ResponseClass != ResponseClassType::SUCCESS) {
                return false;
            }
        }

        return true;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}