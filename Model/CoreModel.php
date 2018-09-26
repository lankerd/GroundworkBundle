<?php

namespace Lankerd\GroundworkBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use UserBundle\Entity\ShippingAddress;
use UserBundle\Utility\ShippingAddressHandler;

/**
 * Class CoreModel
 *
 * Use this class for ALL services! It accommodates for
 * the permission heartbeat! preventing any invalid/non-existent user sessions
 * from ever accessing other models/services, which means the
 * Controllers will be protected too (if models/services are being correctly used)!
 *
 * NOTE: I highly suggest extending off of the @CoreFunctionModel!
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
     * @return string
     */
    protected function checkEntity($entity)
    {
        try {
            $entity->getId();
        } catch (\Exception $e) {
            return "It appears there was no Entity provided!";
        }
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
     * @param object $entity
     * @param string $fileName
     * @param string $service
     *
     * @return mixed
     * @throws \Exception
     */
//    public function generateEntity($entity, $fileName = 'billing_address.csv', $service = 'billing_address')
//    {
//        $addresses = $this->readCSV($this->containerAware->getParameter('import_directory').$fileName);
//        $entityClass = $this->containerAware->get($service)->create();
//        foreach ($entityClass->properties as $propertyKey => $property) {
//            dump($propertyKey);
//            die;
//        }
//        return $entityClass;
//    }

    /**
     * @param array $data
     * @param null  $parentEntity
     */
    protected function processEntity(array $data, $parentEntity = null ){
        foreach ($data as $datum) {
            /*We'll try to keep track of what already exists*/
            $updateFlag = 0;

            $entityClass = $this->create();
            /*initialize the properties for us to scan*/
            $entityClass->getProperties($entityClass);

            foreach ($entityClass->properties as $propertyKey => $property) {
//
//                if (strpos($propertyKey, "id") !== false){
////                    dump($this->repo->findBy(['id' => $property]));
////                    die;
//                    if (!empty($this->repo->findBy(['id' => $property]))){
//                        $updateFlag = 1;
//                    }
//                }


                /**
                 * If the Property has a relationship,
                 * we will need to start up the import
                 * for the inverse side in order for
                 * the import to be complete.
                 */

                if ($parentEntity != null) {
                    foreach ($parentEntity->properties as $parentPropertyKey => $parentProperty) {
                        $serviceKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parentPropertyKey));
                        if (in_array($serviceKey, $this->options[0]['serviceListing'])){
                            $addMethod = $this->camelCase('add'.ucwords($parentPropertyKey));
                            $setMethod = $this->camelCase('set'.ucwords($parentPropertyKey));

                            if (in_array($addMethod, $parentEntity->getMethods($parentEntity))){
                                $parentEntity->$addMethod($entityClass);
                            }

//                            if (in_array($setMethod, $parentEntity->getMethods($parentEntity))){
//                                dump($var)
//                                //$parentEntity->$setMethod($entityClass);
//                            }
                        }
                    }
                }

                if(!is_object($property)){
                    foreach ($datum as $datumKey => $singleRow){
                        if ($datumKey == $propertyKey ){
                            /*Insert the fields into */
                            $setMethod = $this->camelCase('set'.ucwords($propertyKey));
                            $entityClass->$setMethod($singleRow);
                        }
                    }
                }

                if(is_object($property)){

                    $addMethod = $this->camelCase('add'.ucwords($propertyKey));
                    $getMethod = $this->camelCase('get'.ucwords($propertyKey));


                    if (in_array("addUser", $entityClass->getMethods($entityClass))){
                        $users = $this->containerAware->get('fos_user.user_manager')->findUsers();
                        $user = $users[mt_rand(1, count($users))];
                        $entityClass->addUser($user);
                    }

                    if (in_array("setFuelType", $entityClass->getMethods($entityClass))){
                        $fuelTypes = $this->containerAware->get('fuel_type')->repo->findAll();
                        $fuelType = $fuelTypes[mt_rand(1, count($fuelTypes))];
                        $entityClass->setFuelType($fuelType);
                    }

                    $serviceKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyKey));
                    /*This essentially means that the path and service both exist*/
                    if (in_array($serviceKey, $this->options[0]['serviceListing'])){

                        /*Handles DB Relationship service loading*/
                        $childEntityService = $this->containerAware->get($serviceKey);
                        $childEntityService->setOptions($this->options[0]);
                        //$childEntityService->parentEntity = $entityClass;
                        /*Begin a slight recursive loop in child class*/
                        $childEntityService->readCSV($this->options[0]['importPath'].$serviceKey.'.csv', $entityClass);
                    }
                }
            }


            /*Submit the record to be stored*/
            if ($updateFlag == 0) {
                $this->orm->persist($entityClass);
                $this->orm->flush();
            } else {
                $this->orm->flush();
            }
        }
    }

    /**
     * @param $fullPath
     *
     * @return array
     * @throws \Exception
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
//        return $records;
        $this->processEntity($records, $parentEntity);
    }

}