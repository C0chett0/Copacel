<?php

namespace C0chett0\CopacelEws;

class ContactEntry extends AbstractEntry
{
    private $name;
    private $id;

    /**
     * ContactEntry constructor.
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
        foreach ($addressBook['contacts'] as $contact) {
            if( $contact['name'] == $name) {
                $id = $contact['id'];
            }
        }

        return new ContactEntry($name, $id);
    }

    public static function create($name)
    {

        return new ContactEntry($name, $id);
    }

}