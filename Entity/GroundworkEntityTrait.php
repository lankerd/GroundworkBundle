<?php

namespace Lankerd\GroundworkBundle\Entity;

/**
 * Groundwork Entity
 */
trait GroundworkEntityTrait
{
    public $properties;
    public $methods;

    /**
     * Get Properties
     * This'll be used in order to retrieve
     * a list of properties for all Entities.
     * I do this so that there is a way to
     * actually pull all properties of the Entity
     * (Aka: the object in question), and do
     * mass object injection/manipulation, ideally
     * advanced importing/migration features
     * could benefit from a simple property
     * lister.
     *
     * @param $object
     *
     * @return array
     */
    public function getProperties($object)
    {
        $this->properties = get_object_vars($object);
        return $this->properties;
    }

    /**
     * @param $class
     *
     * @return array
     */
    public function getMethods($class)
    {
        $this->methods = get_class_methods($class);
        return $this->methods;
    }
}
