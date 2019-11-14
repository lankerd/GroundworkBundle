<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lankerd\GroundworkBundle\Helper;

use LogicException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Doctrine\Common\Inflector\Inflector;

/**
 * Handles object
 *
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */
class DataHelper
{
    /*Set a universal*/
    public const ENTITY_NAMESPACE = 'App\\Entity\\';
    public const FORM_NAMESPACE = 'App\\Form\\';
    public const SYMFONY_FORM_NAME_TAIL = 'Type';

    /**
     * Will provide a singularized string
     *
     * @param string $word
     *
     * @return string
     */
    public function singularize(string $word): string
    {
        return Inflector::singularize($word);
    }

    /**
     * Checks a string for a given phrase at the exact end of a string.
     * This was explicitly designed with looking at Getters and Setters
     * in an entity.
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    /**
     * This will quickly become legacy,
     * and unused, but for the moment
     * it'll be used to ensure one value
     * was assigned to a variable.
     *
     * @param array  $data
     * @param string $subjectsName
     *
     * @return object
     */
    public function hasOneValue(array $data, string $subjectsName = 'Entity') : object
    {
        /*Check if there are many objects that have been returned to the return*/
        if (count($data) !== 1) {
            throw new RuntimeException(
                'There was an issue retrieving the primary Entity. Expected to find 1 record. Found: '. count($data).'. Further specify information in: '.$subjectsName
            );
        }
        return $data[0];
    }

    /**
     * This will grab all properties of the provided entity
     * and return an array of the properties with their
     * associated methods that can be accessed to sort through.
     *
     * @param object $object
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getObjectProperties(object $object) : array
    {
        /**
         * Initalize objectProperties array in
         * order to have a place to store the
         * property names
         */
        $objectProperties = [];

        /**
         * Create a reflection of the object
         * that has been provided. This will
         * allow us to access all information
         * pertinent to the object.
         */
        try {
            $objectReflection = new ReflectionClass($object);
        } catch (ReflectionException $e) {
            throw $e;
        }


        $objectReflectionMethods = $objectReflection->getMethods();
        /**
         * Loop through all of the objects
         * properties, and store the name
         * of each property into the
         * $objectProperties array.
         */
        foreach ($objectReflection->getProperties() as $property) {
            /*Grab the property name*/
            $propertyName = $property->getName();
            $singularizedPropertyName = $this->singularize(ucfirst($propertyName));

            $methodNames = [];
            foreach ($objectReflectionMethods as $method) {
                $methodName = $method->getName();
                $singularizedMethodName = $this->singularize(ucfirst($methodName));
                if ($this->endsWith( $singularizedMethodName, $singularizedPropertyName)){
                    $methodNames[] = $methodName;
                }
            }

            /**
             * For those who don't know, when
             * inserting data into an array
             * in PHP without ever defining an array's
             * keys is called auto-incremented keys.
             * That is exactly what's happening below,
             * we are telling PHP to auto create a key
             * as we fill the array with the value that
             * has been provided!
             */
            $objectProperties[$singularizedPropertyName] = $methodNames;
        }

        /**
         * Finally we will return the
         * array of properties that have
         * been associated to the object.
         */
        return $objectProperties;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function getEntityPath(): string
    {
        try {
            $entityName = str_replace('Controller', '', (new ReflectionClass($this))->getShortName());
        } catch (ReflectionException $e) {
            throw $e;
        }

        $entityPath = self::ENTITY_NAMESPACE.$entityName;

        if (!class_exists($entityPath)) {
            throw new LogicException($entityPath.' does not exist!');
        }

        return $entityPath;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function getFormPath(): string
    {
        $entityName = $this->getEntityPath();

        $formPath = self::FORM_NAMESPACE.$entityName;

        if (!class_exists($formPath.self::SYMFONY_FORM_NAME_TAIL)) {
            if (!class_exists($formPath)) {
                throw new LogicException('Neither \''.$formPath.self::SYMFONY_FORM_NAME_TAIL.'\' or \''.$formPath.'\' does not seem exist! Perhaps change the %lankerd_groundwork.form.path%');
            }
        }

        return $formPath;
    }

    /**
     * @param object
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function getEntity(): object
    {
        $entityPath = $this->getEntityPath();

        return new $entityPath;
    }
}
