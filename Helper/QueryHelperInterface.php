<?php
declare(strict_types=1);
/**
 *
 * This file is part of the LankerdGroundworkBundle package.
 *
 * <https://github.com/lankerd/GroundworkBundle//>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Lankerd\GroundworkBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface DataHelperInterface
 *
 * @package Lankerd\GroundworkBundle\Helper
 * @author julianlankerd@gmail.com
 */
interface QueryHelperInterface
{
    /**
     * @param object $entity
     * @param bool   $flush
     */
    public function persist(object $entity, bool $flush = true): void;

    /**
     * @param object $entity
     */
    public function remove(object $entity): void;

    /**
     * @param string $entityPath
     *
     * @return mixed
     */
    public function getEntityRepository(string $entityPath);

    /**
     * @param string $entityPath
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getClassMetadata(string $entityPath);
}
