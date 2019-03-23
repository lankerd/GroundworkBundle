<?php

namespace Lankerd\GroundworkBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
abstract class CoreModel implements ConditionsInterface
{
    private $class;
    private $entityManager;
    private $options;
    private $migrationConditionArguments;

    /**
     * CoreModel constructor.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager                                 $orm
     * @param                                                                            $class *ExampleCompany\ExampleBundle\Entity\Example*
     * @param \Doctrine\ORM\EntityManager                                                $entityManager
     */

    public function __construct(ObjectManager $orm, $class, EntityManager $entityManager)
    {
        $this->entityManager = $entityManager->createQueryBuilder();
        $metaData    = $orm->getClassMetadata($class);
        $this->class = $metaData->getName();
    }

    /**
     * Used to retrieve all public methods
     * available in the service handler being processed
     *
     * @param $class
     *
     * @return ReflectionClass
     * @throws \ReflectionException
     */
    public function getClassReflection($class)
    {
        return (new ReflectionClass($class));
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
     * @param $string
     *
     * @return bool
     */
    private function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                           $data
     * @param string                                    $format
     * @param array                                     $unset
     * @param bool                                      $outputToScreen
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function format( Request $request, $data, $format='csv', $unset=[], $outputToScreen=false )
    {
        $serializer = new Serializer([
            new ObjectNormalizer(),
            new GetSetMethodNormalizer()
        ],[
            'csv' => new CsvEncoder(),
            'json' => new JsonEncoder(),
            'xml' => new XmlEncoder()
        ]);

        foreach($data as $k=>$record){
            foreach($record as $name=>$value){
                if ($name == 'vendorResponse'){
                    if ($this->isJSON($value)){
                        foreach (json_decode($value) as $key => $item) {
                            $data[$k][$key] = $item;
                        }
                    }
                }
                //remove unwanted fields
                if(in_array($name, $unset)){
                    unset($data[$k][$name]);
                    continue;
                }
                //if object is encountered process or remove
                if(is_object($value))
                    switch (get_class($value)):
                        case 'DateTime':
                            $value = $value->format('Y-m-d H:i:s');
                            break;
                        default:
                            unset($data[$k][$name]);
                            continue;
                    endswitch;
                $data[$k][$name] = $value;
            }
        }
        $output = $serializer->serialize( $data , $format );
        $response = new Response($output);
        if(!$outputToScreen)
            $response->headers->set('Content-Type', 'text/'.$format);
        return $response;
    }

    /**
     * @param array $data
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function processEntity(array $data)
    {
        $this->entityManager->getEntityManager()->getConfiguration()->setSQLLogger(null);
        foreach ($data as $key => $datum) {
            /*Clears doctrine out every 25 queries*/
            if ($key % 25 == 0) {
                $this->entityManager->getEntityManager()->flush();
                $this->entityManager->getEntityManager()->clear();
            }

            /** @var object $entityClass */
            $entityClass = new $this->class;

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
            /*This is going to map our tableFields and doctrineFieldAliases with the correctly associated datum*/
            foreach ($entityClass->getProperties() as $propertyKey => $property) {
                $prettyProperty= strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property));
                $datum = array_combine(array_map('trim', array_keys($datum)), $datum);
                if (isset($datum[ucfirst($property)])){
                    $doctrineAliasArguments[$prettyProperty] = $datum[ucfirst($property)];
                }else{
                    if ($property == "id") {
                        $doctrineAliasArguments[$prettyProperty] = null;
                    }
                }
                $tableFields .= ', `'.$prettyProperty.'`';
                $doctrineFieldAliases .= ', :'.$prettyProperty.'';
            }
            /*Trim off the first unnecessary comma*/
            $doctrineFieldAliases = ltrim($doctrineFieldAliases, ', ');
            $tableFields = ltrim($tableFields, ', ');
            $sql = "REPLACE INTO `".$this->getOptions()['currentService']."` (".$tableFields.") VALUES (".$doctrineFieldAliases.")";
            $em = $this->entityManager->getEntityManager();
            $stmt = $em->getConnection()->prepare($sql);
            foreach ($doctrineAliasArguments as $argumentKey => $argument) {
                $trimmedArgument = trim($argument);
                if (in_array($argumentKey, array_keys($entityFieldTypes))){
                    if (strstr($entityFieldTypes[$argumentKey], 'int')){
                        if (!empty($trimmedArgument)){
                            $integerArgument = (integer) $trimmedArgument;
                            $stmt->bindValue(':'.$argumentKey, $integerArgument, ParameterType::INTEGER);
                        }else{
                            $nullField = null;
                            $stmt->bindValue(':'.$argumentKey, $nullField, ParameterType::NULL);
                        }
                    }
                    if (strstr($entityFieldTypes[$argumentKey], '\DateTime')){
                        if (!empty($trimmedArgument)){
                            $date = \DateTime::createFromFormat ('Y-m-d H:i:s', $trimmedArgument);
                            if (false == $date){
                                /*$date = \DateTime::createFromFormat ('Y-m-d H:i:s.u', $trimmedArgument);*/
                                $date = new \DateTime((float)$trimmedArgument);
                                $date = $date->format('Y-m-d H:i:s.u');
                            }else{
                                $date = $date->format('Y-m-d H:i:s');
                            }
                            $stmt->bindValue(':'.$argumentKey, $date, ParameterType::LARGE_OBJECT);
                        }
                    }
                    if (strstr($entityFieldTypes[$argumentKey], 'string')){
                        /*Check for weird encoding issues they might arise*/
                        if(false == mb_detect_encoding($trimmedArgument, 'UTF-8', true)){
                            /**
                             * Mies well just clear the issues. PHP can barely
                             * handle the kind of encoding issues
                             * most people are going to have.
                             */
                            $trimmedArgument = mb_convert_encoding($trimmedArgument, 'UTF-8', 'pass');
                        }
                        $stmt->bindValue(':'.$argumentKey, $trimmedArgument);
                    }
                    if (strstr($entityFieldTypes[$argumentKey], 'float')){
                        if (!empty($trimmedArgument)){
                            $floatingArgument = (float) $trimmedArgument;
                            $stmt->bindValue(':'.$argumentKey, $floatingArgument, ParameterType::INTEGER);
                        }else{
                            $nullField = null;
                            $stmt->bindValue(':'.$argumentKey, $nullField, ParameterType::NULL);
                        }
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
     * @param $options
     *
     * @return mixed
     */
    public function setOptions($options)
    {
        $this->options = $options;
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