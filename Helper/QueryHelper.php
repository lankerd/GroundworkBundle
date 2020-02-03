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
use Exception;
use RuntimeException;


/**
 * Class QueryHelper
 *
 * @package Lankerd\GroundworkBundle\Helper
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
class QueryHelper implements QueryHelperInterface
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * QueryHelper constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function remove(object $entity): void
    {
        $entityManager = $this->entityManager;

        try {
            $entityManager->remove($entity);
            $entityManager->flush();
        } catch (Exception $e) {
            /**
             * Throw a new exception to inform sender of the error.
             *
             * If an exception is thrown (an error is found), it will stop the process,
             * and show the error that occurred in the "try" brackets.
             * Instead of showing the exact error that occurred in the exception,
             * we're gonna over-generalize the error, because you never know when
             * something nefarious may be afoot.
             */
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @param object $entity
     *
     * @param bool $flush
     * @return void
     */
    public function persist(object $entity, bool $flush = true): void
    {
        $entityManager = $this->entityManager;

        /**
         * Encapsulate attempt to store data
         * into database with try-catch. This
         * will ensure in the case of an error
         * we will catch the exception, and throw
         * it back as a suitable response.
         */
        try {
            /**
             * Persist() will make an instance of the entity
             * available for doctrine to submit to the Database.
             */
            $entityManager->persist($entity);
            if ($flush){
                /**
                 * Using Flush() causes write operations against the
                 * database to be executed. Which means if you
                 * used Persist($object) before flushing,
                 * You'll end up inserting a new record
                 * into the Database.
                 */
                $entityManager->flush();
            }
        } catch (Exception $e) {
            /**
             * Throw a new exception to inform sender of the error.
             *
             * If an exception is thrown (an error is found), it will stop the process,
             * and show the error that occurred in the "try" brackets.
             * Instead of showing the exact error that occurred in the exception,
             * we're gonna over-generalize the error, because you never know when
             * something nefarious may be afoot.
             */
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @param string $entityPath
     *
     * @return mixed
     * @throws \Exception
     */
    public function getEntityRepository(string $entityPath)
    {
        /**
         * Placing this into a try-catch is definitely a wise call
         * considering we are stuffing kinda questionable stuff into the
         * findBy.
         *
         * Query for the $primaryEntity, so that we can begin to set relationships
         * to the $primaryEntity. This is the target we will be setting database
         * relationships towards.
         */
        try {
            $entityRepository = $this->entityManager->getRepository($entityPath);
        } catch (Exception $e) {
            throw $e;
        }
        return $entityRepository;
    }

    /**
     * @param string $entityPath
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata(string $entityPath)
    {
        $entityManager = $this->entityManager;
        /**
         * this method return a class metadata object of given entity path
         */
        return $entityManager->getClassMetadata($entityPath);
    }
}
