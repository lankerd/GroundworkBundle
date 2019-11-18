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
 * Interface DataHelperInterface
 *
 * @package Lankerd\GroundworkBundle\Helper
 */
interface DataHelperInterface
{
    /**
     * @param $class
     */
    public function setClassName(string $class): void;

    /**
     * @return string
     */
    public function getClassName(): string;

    /**
     * @param string $word
     *
     * @return string
     */
    public function singularize(string $word): string;

    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public function endsWith($haystack, $needle): bool;

    /**
     * @param array  $data
     * @param string $subjectsName
     *
     * @return object
     */
    public function hasOneValue(array $data, string $subjectsName = 'Entity') : object;

    /**
     * @param object $object
     *
     * @return array
     */
    public function getObjectProperties(object $object) : array;

    /**
     * @return string
     */
    public function getFormPath(): string;

    /**
     * @return object
     */
    public function getEntity(): object;
}
