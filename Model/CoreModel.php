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
    public function generateEntity($entity, $fileName = 'billing_address.csv', $service = 'billing_address')
    {
        $addresses = $this->readCSV($this->containerAware->getParameter('import_directory').$fileName);
        $entityClass = $this->containerAware->get($service)->create();
        foreach ($entityClass->properties as $propertyKey => $property) {
            dump($propertyKey);
            die;
        }
        return $entityClass;
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
//    protected function processEntity(array $data){
//        foreach ($data as $datum) {
//            $entityClass = $this->create();
//
//            foreach ($entityClass->properties as $propertyKey => $property) {
//                /**
//                 * If the Property has a relationship,
//                 * we will need to start up the import
//                 * for the inverse side in order for
//                 * the import to be complete.
//                 */
//                if(is_object($property)){
//                    $addMethod = $this->camelCase('add'.ucwords($propertyKey));
//                    $getMethod = $this->camelCase('get'.ucwords($propertyKey));
//
//                    try {
//                        $getter = $entityClass->$getMethod();
//                    } catch (\Exception $e) {
//                        throw $e;
//                        continue;
//                    }
//
//                    $serviceKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyKey));
//                    $entityClass->$addMethod($this->containerAware->get($serviceKey)->create());
//                    dump($entityClass->properties);
//                    die;
//                }
//
//                foreach ($datum as $datumKey => $singleRow){
//                    if ($datumKey == $propertyKey ){
//                        /*Insert the fields into */
//                        $setMethod = $this->camelCase('set'.ucwords($propertyKey));
//                        $entityClass->$setMethod($singleRow);
//                    }
//                }
//            }
//            /*Submit the record to be stored*/
//            $this->orm->persist($entityClass);
//            $this->orm->flush();
//        }
//    }

    /**
     * This will process any imported data (Via CSV)
     * and try to figure out the service to ping off
     * consequentially collecting the entity tied to
     * the service, and finish off with the processEntity
     * function
     *
     * @param       $importPath
     * @param array $filesToImport
     *
     * @throws \Exception
     */
    public function import($importPath, array $filesToImport)
    {
        foreach ($filesToImport as $key => $fileToImport) {
            /*Strip the extension off of the filename in order to run the file in it's correct */
            $filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileToImport);

            try {
                $service = $this->containerAware->get("$filename");
            } catch (\Exception $e) {
                unset($filesToImport[$key]);
                continue;
            }

            echo "\n=============$filename=============\n";
            /*Let's remove the oncoming file*/
            unset($filesToImport[$key]);
            $this->setOptions($filesToImport);
            $this->readCSV($importPath.$fileToImport);
            echo "\n=============$filename=============\n";
        }
    }

    /**
     * @param $fullPath
     *
     * @return array
     * @throws \Exception
     */
    protected function readCSV($fullPath)
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
        return $records;
//        $this->processEntity($records);
    }

}