<?php

namespace C0chett0\CopacelEws;

class DistributionList extends AbstractEntry
{
    private $name;
    private $id;

    /**
     * DistributionList constructor.
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
            if( $list['name'] == $name) {
                $id = $list['id'];
            }
        }

        return new DistributionList($name, $id);
    }

    public static function create($name)
    {

        return new DistributionList($name, $id);
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}