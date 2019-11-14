<?php

namespace Lankerd\GroundworkBundle\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;


class QueryHelper
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function persistEntity(object $entity): void
    {
        $entityManager = $this->entityManager;

        /**
         * Persist() will make an instance of the entity
         * available for doctrine to submit to the Database.
         */
        $entityManager->persist($entity);

        /**
         * Using Flush() causes write operations against the
         * database to be executed. Which means if you
         * used Persist($object) before flushing,
         * You'll end up inserting a new record
         * into the Database.
         */
        $entityManager->flush();
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
}
