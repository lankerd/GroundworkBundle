<?php

namespace Lankerd\GroundworkBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use PDO;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Class CoreModel
 *
 * Use this class for ALL services! It accommodates for
 * the permission heartbeat! preventing any invalid/non-existent user sessions
 * from ever accessing other models/services, which means the
 * Controllers will be protected too (if models/services are being correctly used)!
 *
 * @package CoreBundle\Model
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
abstract class CoreModel
{
    protected $class;
    protected $orm;
    protected $repo;
    protected $user;
    protected $roleCheck;
    protected $flashBag;
    protected $entityManager;
    protected $coreData;
    protected $dataCollection;
    protected $options;
    protected $containerAware;
    protected $parentEntity;
    protected $stack;
    protected $migrationConditionArguments;

    /**
     * CoreModel constructor.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager                                 $orm
     * @param                                                                            $class *ExampleCompany\ExampleBundle\Entity\Example*
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker        $authorizationChecker
     * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBag                   $flashBag
     * @param \Doctrine\ORM\EntityManager                                                $entityManager
     * @param \Symfony\Component\DependencyInjection\ContainerInterface                  $containerAware
     */

    public function __construct(ObjectManager $orm, $class, AuthorizationChecker $authorizationChecker, FlashBag $flashBag, EntityManager $entityManager, ContainerInterface $containerAware)
    {
        $this->containerAware = $containerAware;
        $this->entityManager = $entityManager->createQueryBuilder();
        $this->orm   = $orm;
        $this->repo  = $orm->getRepository($class);
        $metaData    = $orm->getClassMetadata($class);
        $this->class = $metaData->getName();
        $this->roleCheck = $authorizationChecker;
        $this->flashBag = $flashBag;
    }

    /**
     * This will allow for us to bridge
     * back over to the handler and take
     * in any possible conditions the core
     * migration should adhere to during migration!
     */
    abstract protected function migrationConditions();

    /**
     * We'll check and see if the entity does exist
     * by simply checking to see if the provided entity
     * has an Id we can grab.
     *
     * NOTE: This is for internally ensuring that
     * nothing strange gets thrown into the CoreModel
     * from some extension.
     *
     *
     * @param $entity object
     * @return boolean
     */
    protected function checkEntity($entity)
    {
        try {
            $entity->getId();
        } catch (\Exception $e) {
            return "It appears there was no Entity provided!";
        }
        return true;
    }

    /**
     * @param       $str
     * @param array $noStrip
     *
     * @return mixed|null|string|string[]
     */
    public function camelCase($str, array $noStrip = [])
    {
        $string = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $str);
        $string = trim($string);

        // non-alpha and non-numeric characters become spaces
        $string = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $string);
        $string = trim($string);
        // uppercase the first character of each word
        $string = ucwords($string);
        $string = str_replace(" ", "", $string);
        $string = lcfirst($string);

        return $string;
    }

    /**
     * @param array  $data
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function processEntity(array $data)
    {
        $this->entityManager->getEntityManager()->getConfiguration()->setSQLLogger(null);
        foreach ($data as $key => $datum) {
            /*Clears doctrine out every 25 queries*/
            if ($key % 25 == 0) {
                $this->entityManager->getEntityManager()->flush(); $this->entityManager->getEntityManager()->clear();
            }

            $entityClass = $this->create(); //Create the Entity

            $tableFields = '';
            $doctrineFieldAliases = '';
            $doctrineAliasArguments = array();
            $entityFieldTypes = array();

            foreach ($entityClass->getClassReflection()->getProperties() as $property) {
                $property->setAccessible(true);
                if (!is_object($property->getValue($entityClass))){
                    /*Grab the field type of each property in the class*/
                    if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
                        list(, $type) = $matches;
                        $entityFieldTypes[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName()))] = $type;
                    }
                }
            }

            foreach ($entityClass->getProperties() as $propertyKey => $property) {
                $prettyProperty= strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property));
                if ($propertyKey == 0 || $propertyKey == 1) {
                    if ($property == "id"){
                        /*Skip the id property because the "id" should always be auto-incremented*/
                    }else{
                        if (isset($datum[ucfirst($property)])){
                            $doctrineAliasArguments[$prettyProperty] = $datum[ucfirst($property)];
                        }else{
                            $doctrineAliasArguments[$prettyProperty] = null;
                        }

                        $tableFields .= '`'.$prettyProperty.'`';
                        $doctrineFieldAliases .= ':'.$prettyProperty;
                    }
                }else{
                    if (isset($datum[ucfirst($property)])){
                        $doctrineAliasArguments[$prettyProperty] = $datum[ucfirst($property)];
                    }else{
                        $doctrineAliasArguments[$prettyProperty] = null;
                    }
                    $tableFields .= ', `'.$prettyProperty.'`';
                    $doctrineFieldAliases .= ', :'.$prettyProperty.'';
                }
            }

            $sql = "SET FOREIGN_KEY_CHECKS=0; -- to disable them";
            $sql .= "REPLACE INTO `".$this->getOptions()[0]['currentService']."` (".$tableFields.") VALUES (".$doctrineFieldAliases.")";
            $sql .= "SET FOREIGN_KEY_CHECKS=1; -- to re-enable them";
            $em = $this->entityManager->getEntityManager();
            $stmt = $em->getConnection()->prepare($sql);
            foreach ($doctrineAliasArguments as $argumentKey => $argument) {
                $trimmedArgument = trim($argument);
                if (in_array($argumentKey, array_keys($entityFieldTypes))){
                    if (strstr($entityFieldTypes[$argumentKey], 'int')){
                        if (!empty($trimmedArgument)){
                            $integerArgument = (integer) $trimmedArgument;
                            $stmt->bindParam(':'.$argumentKey, $integerArgument, ParameterType::INTEGER);
                        }else{
                            $nullField = null;
                            $stmt->bindValue(':'.$argumentKey, $nullField, ParameterType::NULL);
                        }
                    }
                    if (strstr($entityFieldTypes[$argumentKey], '\DateTime')){
                        if (!empty($trimmedArgument)){
                            $date = \DateTime::createFromFormat ('Y-m-d H:i:s.u', $trimmedArgument);
                            $date = $date->format('Y-m-d H:i:s');
                            $stmt->bindParam(':'.$argumentKey, $date, ParameterType::LARGE_OBJECT);
                        }
                    }
                    if (strstr($entityFieldTypes[$argumentKey], 'string')){
                        /*Check for weird encoding issues they might arise*/
                        if(mb_detect_encoding($trimmedArgument, 'UTF-8', true) == false){
                            /**
                             * Mies well just clear the issues. PHP can barely
                             * handle the kind of encoding issues
                             * most people are going to have.
                             */
                            $trimmedArgument = mb_convert_encoding($trimmedArgument, 'UTF-8', 'pass');
                        }
                        $stmt->bindValue(':'.$argumentKey, $trimmedArgument);
                    }
                }
            }
            $stmt->execute();
        }
    }

    /**
     * @param      $fullPath
     * @param null $parentEntity
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function readCSV($fullPath, $parentEntity = null)
    {
        $csv = Reader::createFromPath($fullPath);
        $records = array();

        try {
            $csv->setHeaderOffset(0);
        } catch (Exception $e) {
            throw new $e;
        }

        $stmt = (new Statement());
        $rows = $stmt->process($csv);

        foreach ($rows as $row) {
            $records[] = $row;
        }
        /*Now that the data has been broken up into coherent sets, let's begin to import the records */
        $this->processEntity($records, $parentEntity);
    }

    /**
     * Used to quickly create a new class,
     * complete fluff function, does not serve
     * a critical role and will probably be removed
     * if found to be too circumstantial.
     *
     * TODO: Remove this function if it begins to seem like unnecessary bloat.
     *
     * @return mixed
     */
    public function create () {
        $class = $this->class;
        return new $class;
    }

    /**
     * @param $options
     *
     * @return mixed
     */
    public function setOptions($options)
    {
        $this->options[] = $options;
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getMigrationConditionArguments()
    {
        return $this->migrationConditionArguments;
    }

    /**
     * @param mixed $migrationConditionArguments
     */
    public function setMigrationConditionArguments($migrationConditionArguments): void
    {
        $this->migrationConditionArguments[] = $migrationConditionArguments;
    }

}