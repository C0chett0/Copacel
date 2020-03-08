<?php


namespace C0chett0\CopacelEws;


interface SynchroExchangeInterface
{
    public static function createList($name);

    public static function addContactsToList($listId, $contacts);

    public static function removeContactsFromList($listId, $contacts);

    public static function deleteList($listId);

    public static function createContact($contact);

    public static function updateContact($contactId, $contact);

    public static function deleteContact($contactId);

    public static function purge();
}