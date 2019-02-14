<?php

namespace Lankerd\GroundworkBundle\Entity;

use ReflectionClass;

/**
 * Groundwork Entity
 */
trait GroundworkEntityTrait
{
    /**
     * Get non-object Properties of a class
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
     * @param bool $isFormatted
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getProperties($isFormatted = false, $allowObjects = false)
    {
        $propertyNames = array();
        foreach ($this->getClassReflection()->getProperties() as $property) {
            $property->setAccessible(true);
            if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
                list(, $type) = $matches;
                /**/

                if ($allowObjects){
                    if (!$isFormatted){
                        if (strstr($type, 'int') || strstr($type, 'string') || strstr($type, 'boolean') || strstr($type, '\DateTime') || strstr($type, 'float') || strstr($type, '\CrystalFlashBundle\Entity\StatementHeader')){
                            $propertyNames[] = $property->getName();
                        }
                    }else{
                        $propertyNames[strtolower(preg_replace('/(?<!^)[A-Z0-9]/', '_$0', $property->getName()))] = $type;
                    }
                }else{
                    if (!$isFormatted){
                        if (strstr($type, 'int') || strstr($type, 'string') || strstr($type, 'boolean') || strstr($type, '\DateTime') || strstr($type, 'float')){
                            $propertyNames[] = $property->getName();
                        }
                    }else{
                        $propertyNames[strtolower(preg_replace('/(?<!^)[A-Z0-9]/', '_$0', $property->getName()))] = $type;
                    }
                }
            }
        }
        return $propertyNames;
    }


    public function freePropertiesValues()
    {
        $propertyNames = null;

        foreach ($this->getClassReflection()->getProperties() as $property) {
            $property->setAccessible(true);
        }

        return $propertyNames;
    }

    /**
     * @param string $filter
     *
     * POSSIBLE VALUES:
     * 0 = NON-OBJECT-VALUES
     * 1 = ALL-VALUES
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getPropertyValues($filter = 0)
    {
        $propertyValues = array();

        foreach ($this->getClassReflection()->getProperties() as $property) {
            $property->setAccessible(true);
            if ($filter == 0){
                if (!is_object($property->getValue($this))){
                    $propertyValues[$property->getName()] = $property->getValue($this);
                }
            } else{
                $propertyValues[$property->getName()] = $property->getValue($this);
            }
        }

        return $propertyValues;
    }

    /**
     * @deprecated Use "$classPlaceholder->getClassReflection()->getMethods()"
     * @return array
     * @throws \ReflectionException
     */
    public function getMethods()
    {
        $methodNames = array();

        foreach ($this->getClassReflection()->getMethods() as $method) {
            $methodNames[] = $method->getName();
        }

        return $methodNames;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getObjectsOfClass()
    {
        $objects = array();
        foreach ($this->getClassReflection()->getProperties() as $property) {
            if (is_object($property)) {
                $objects[] = $property;
            }
        }
        return $objects;
    }

    /**
     * @return ReflectionClass
     * @throws \ReflectionException
     */
    public function getClassReflection()
    {
        return (new ReflectionClass($this));
    }
}
