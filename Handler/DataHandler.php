<?php
declare(strict_types=1);

namespace Lankerd\GroundworkBundle\Handler;

use DomainException;
use Exception;
use Lankerd\GroundworkBundle\Helper\DataHelperInterface;
use Lankerd\GroundworkBundle\Helper\QueryHelper;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class DataHandler
 *
 * @package Lankerd\GroundworkBundle\Handler
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
class DataHandler
{
    /**
     * @var \Lankerd\GroundworkBundle\Helper\DataHelperInterface
     */
    protected $dataHelper;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \Symfony\Component\Serializer\Serializer
     */
    protected $serializer;

    /**
     * @var \Lankerd\GroundworkBundle\Helper\QueryHelper
     */
    protected $queryHelper;

    /**
     * @var array
     */
    protected $requestData;

    /**
     * @var array
     */
    protected $globalIdentifiers;

    /**
     * @var array
     */
    protected $response;

    /**
     * DataHandler constructor.
     *
     * @param \Lankerd\GroundworkBundle\Helper\DataHelperInterface $dataHelper
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param \Symfony\Component\Serializer\Serializer $serializer
     * @param \Lankerd\GroundworkBundle\Helper\QueryHelper $queryHelper
     */
    public function __construct(
        DataHelperInterface $dataHelper,
        FormFactoryInterface $formFactory,
        Serializer $serializer,
        QueryHelper $queryHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->formFactory = $formFactory;
        $this->serializer = $serializer;
        $this->queryHelper = $queryHelper;
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function requestHandler(Request $request)
    {
        /*Store the request data into a property for re-usability purposes*/
        $this->setRequestData($request);
        /*Access the formatted request data, and store it in a variable for later*/
        $data = $this->getRequestData();
        /*Store the request data into a property for re-usability purposes*/
        $this->indexActions($data);
    }

    /**
     * This will run through an array,
     * and check to see if it has any
     * actions that are supported by the
     * system.
     */
    public function indexActions(array $data)
    {
        foreach ($data['actions'] as $action => $entities) {
            $dataHelper = $this->dataHelper;
            $queryHelper = $this->queryHelper;

            foreach ($entities as $entityName => $entityCollection) {
                $fullEntityNamespace = $dataHelper::ENTITY_NAMESPACE.ucfirst($entityName);
                foreach ($entityCollection as $entityUniqueIdentifier => $entityFields) {
                    /**
                     * GET
                     */
                    if($action === 'get'){
                        $data = $this->queryHelper->getEntityRepository($fullEntityNamespace)->findBy($entityFields);
                        $this->globalIdentifiers[$entityName][$entityUniqueIdentifier] = $data;
                    }
                    /**
                     * CREATE
                     */
                    if ($action === 'create') {
                        $entityProperties = $dataHelper->getObjectProperties($fullEntityNamespace);
                        $entity = new $fullEntityNamespace();
                        $entityMetadata = $this->queryHelper->getClassMetadata($fullEntityNamespace);
                        $associations = $entityMetadata->getAssociationNames();
//                        foreach ($entityFields as $fieldName => $fieldValue) {
//
//                            if (array_key_exists(ucfirst($fieldName), $entityProperties)) {
//                                /*This will loop through all of the entityMethods*/
//                                foreach ($entityProperties[ucfirst($fieldName)] as $method) {
//                                    if (false !== stripos($method, 'set')) {
//
//                                    }
////                                    if (DateTime::createFromFormat('Y-m-d H:i:s', $myString) !== FALSE) {
////                                        // it's a date
////                                    }
//                                    if (false !== stripos($method, 'add')) {
//                                        $entity->$method($fieldValue);
//                                    }
//                                }
//                            }
//                        }

                        /*Create form with corresponding Entity paired to it*/
                        $form = $this->formFactory->create('App\\Form\\'.ucfirst($entityName).'Type', $entity);
//                        foreach ($associations as $association) {
//                            $form->remove($association);
//                        }
                        /*Submit $data that was unpacked from the $response into the $form.*/
                        $form->submit($entityFields);

                        /*Check if the current $form has been submitted, and is valid.*/
                        if ($form->isSubmitted() && $form->isValid()) {
                            $this->globalIdentifiers[$entityName][$entityUniqueIdentifier] = $entity;
                            $this->queryHelper->persistEntity($entity);
                        }else{
                            throw new RuntimeException($form->getErrors()->current()->getMessage());
                        }

                        //$this->queryHelper->persistEntity($entity);
                    }

                    /**
                     * CONNECT
                     */
                    if ($action === 'connect') {
                        $entityProperties = $dataHelper->getObjectProperties($fullEntityNamespace);
                        $entity = $this->globalIdentifiers[$entityName][$entityUniqueIdentifier];
                        //entityName is the parent that will have stuff connected to it
//                        dump($entityName, $entityCollection);
//                        die;
                        foreach ($entityFields as $fieldName => $fieldValue) {
                            if (array_key_exists($fieldName, $this->globalIdentifiers)) {
                                /*This will loop through all of the entityMethods*/
                                foreach ($entityProperties[ucfirst($fieldName)] as $method) {
                                    if (false !== stripos($method, 'set')) {
                                        foreach ($fieldValue as $value) {
                                            $entity->$method($this->globalIdentifiers[$fieldName][$value]);
                                        }
                                    }
                                    if (false !== stripos($method, 'add')) {
                                        foreach ($fieldValue as $value) {
                                            $entity->$method($this->globalIdentifiers[$fieldName][$value]);
                                        }
                                    }
                                }
                            }else{
                                throw new RuntimeException($fieldName.' is not a valid globalIdentifier, try looking at your request and ensure');
                            }
                        }
                        $this->queryHelper->persistEntity($entity);
                    }

                    /**
                     * OUTPUT
                     */
                    if($action === 'output'){
                        $serializedData = $this->serializer->normalize($this->globalIdentifiers[$entityName][$entityFields], 'json');
                        dump($serializedData);
                        die;
                        $this->response[$entityFields] = $serializedData;
                    }

                    /**
                     * LOAD ORDER
                     */
                    if ($action === 'loadOrder'){
                        if (!array_key_exists($entityFields, $this->globalIdentifiers[$entityName])) {
                            throw new RuntimeException($entityFields.': is not a valid globalIdentifier, try looking at your request and fix the .');
                        }
                        $this->queryHelper->persistEntity($this->globalIdentifiers[$entityName][$entityFields], true);
                    }
                }
            }
        }

        $response = $this->serializer->serialize(
            $this->response,
            'json'
        );
        dump($response);
        die;
    }

    public function create(string $fullEntityNamespace, array $entities): void
    {
        $entity = new $fullEntityNamespace();

//        $dataHelper = $this->dataHelper;
//        $queryHelper = $this->queryHelper;
//
//        foreach ($entities as $entityName => $entityCollection) {
//            $fullEntityNamespace = $dataHelper::ENTITY_NAMESPACE.$entityName;
//            $entityProperties = $dataHelper->getObjectProperties($fullEntityNamespace);
//            foreach ($entityCollection as $entityUniqueIdentifier => $entityFields) {
//                $entity = new $fullEntityNamespace();
//                foreach ($entityFields as $fieldName => $fieldValue) {
//                    if (array_key_exists(ucfirst($fieldName), $entityProperties)){
//                        /*This will loop through all of the entityMethods*/
//                        foreach ($entityProperties[ucfirst($fieldName)] as $method) {
//                            if (false !== stripos($method, 'set')) {
//                                $entity->$method($fieldValue);
//                            }
//                            if (false !== stripos($method, 'add')) {
//                                $entity->$method($fieldValue);
//                            }
//                        }
//                    }
//                }
//                $this->globalIdentifiers[$entityName][$entityUniqueIdentifier] = $entity;
//            }
//        }
//        dump($this->globalIdentifiers);
//        die;
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequestData(Request $request): void
    {
        $this->requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }


    /**
     * Check to see if the specified class exists
     *
     * @param string $class
     *
     * @throws \RuntimeException
     */
    public function setClass(string $class)
    {
        $this->dataHelper->setClassName($class);
    }

    /**
     * THIS NEEDS TO BE REWRITTEN WHEN I AM MORE CONSCIOUS. TOO TIRED TO WRITE GOOD CODE.
     *
     * @param Request $request
     *
     * @return int
     * @throws \Exception
     */
    public function updateRecord(Request $request): int
    {
        $dataHelper = $this->dataHelper;

        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (empty($data['targetEntity']) || empty($data['updateValues'])) {
            throw new RuntimeException('Data that was sent is incorrectly formatted!');
        }

        /*Instantiate a new User object for us to insert the $request form data into.*/

        if (count(
                $entity = $this->queryHelper->getEntityRepository($dataHelper->getClassPath())->findBy(
                    $data['targetEntity']
                )
            ) === 1) {
            $entity = $entity[0];
        } else {
            throw new RuntimeException(
                'Data that was sent is too vague to accurately update, please supply more detail in order to narrow the search scope'
            );
        }

        /*Create form with corresponding Entity paired to it*/
        $bindingMethod = [];
        $objectMethods = $this->dataHelper->getObjectProperties($entity);
        foreach ($data['updateValues'] as $key => $updateValue) {
            if (array_key_exists(ucfirst(strtolower($key)), $objectMethods)) {
                foreach ($objectMethods[ucfirst(strtolower($key))] as $objectMethod) {
                    if (false !== stripos($objectMethod, 'set')) {
                        $entity->$objectMethod($updateValue);
                    }
                }
            }
        }

        $this->queryHelper->persistEntity($entity);

        return $entity->getId();
    }

    /**
     * julianlankerd@gmail.com, build a filtration system, and actually use $request!
     *
     * @return string
     * @throws \Exception
     */
    public function getAllValues(): string
    {
        return $this->serializer->serialize(
            $this->queryHelper->getEntityRepository($this->dataHelper->getClassName())->findAll(),
            'json'
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws Exception
     */
    public function connectRecords(Request $request): void
    {
        /*Grab the Doctrine Entity Manager so that we can process our Entity to the database.*/
        $queryHelper = $this->queryHelper;

        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        /*There should be information that can be used to find the specified "primaryEntity" in the database.*/
        $primaryEntity = $data['primaryEntity'];

        /*There should be information that can be used to find the multiple specified "secondaryEntities" in the database.*/
        $secondaryEntities = $data['secondaryEntities'];
        /**
         * Placing this into a try-catch is definitely a wise call
         * considering we are stuffing kinda questionable stuff into the
         * findBy.
         * Query for the $primaryEntity, so that we can begin to set relationships
         * to the $primaryEntity. This is the target we will be setting database
         * relationships towards.
         */
        try {
            $primaryEntity = $queryHelper->getEntityRepository('App:'.key($primaryEntity))->findBy(
                $primaryEntity[key($primaryEntity)]
            );
        } catch (Exception $e) {
            throw $e;
        }

        /*Check if there are many objects that have been returned to the return*/
        $primaryEntity = $this->dataHelper->hasOneValue($primaryEntity, 'primaryEntity');

        $objectProperties = $this->dataHelper->getObjectProperties($primaryEntity);

        foreach ($secondaryEntities as $entityName => $entityData) {
            /**
             * Check to see if there are multiple
             * of the same entity in the request.
             * NOTE: If there should be multiple
             * entities sent, the key MUST be denoted
             * with a prepended underscore (Example: "Entity, Entity_2, etc..."),
             * the value after the underscore MUST BE UNIQUE!!!
             */
            if (false !== strpos($entityName, '_')) {
                /**
                 * Break the unique underscore with identifier
                 * off of the string, and carry on using the
                 * normalized name of the entity in question.
                 */
                $entityName = explode('_', $entityName)[0];
            }

            $entityName = $this->dataHelper->singularize(ucfirst($entityName));

            /**
             * Check if any of the properties passed are an array.
             * If the property is, an exception will be thrown
             * because the functionality does not currently exist.
             * Why would someone wish to pass an array in a property?
             * Collections or associations. Someone may wish to search
             * for information to pair up based on a certain relationship.
             * Sorry for any temporary inconveniences! It'll be usable soon!
             * julianlankerd@gmail.com needs to build association functionality capable in the "connector"
             */

            foreach ($entityData as $index => $entityDatum) {
                if (is_array($entityDatum)) {
                    throw new DomainException(
                        'It appears an array was passed as a property! This functionality is not yet available, but will be coming soon!'
                    );
                }
            }

            try {
                $returnedValue = $queryHelper->getEntityRepository('App:'.$entityName)->findBy($entityData);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage());
            }

            /*Check if there are many objects that have been returned to the return*/
            $entity = $this->dataHelper->hasOneValue($returnedValue, $entityName);

            $bindingMethod = null;
            foreach ($objectProperties[ucfirst($entityName)] as $objectMethod) {
                if (false !== stripos($objectMethod, 'add')) {
                    $bindingMethod = $objectMethod;
                }

                if (false !== stripos($objectMethod, 'set')) {
                    $bindingMethod = $objectMethod;
                }
            }
            $primaryEntity->$bindingMethod($entity);
        }
        $queryHelper->persistEntity($primaryEntity);
    }

    public function createRecord(Request $request)
    {
        $queryHelper = $this->queryHelper;

        $dataHelper = $this->dataHelper;

        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        /*Instantiate a new User object for us to insert the $request form data into.*/
        $entity = $dataHelper->getEntity();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                /* $storedEntity is an entity within the json form besides the parent. */
                $childEntity = $queryHelper->getEntityRepository('App:'.ucfirst($key))->findBy($value);
                $parentEntity = $dataHelper->getObjectProperties($entity);
                if (array_key_exists(ucfirst($key), $parentEntity)) {
                    foreach ($parentEntity[ucfirst($key)] as $method) {
                        if (false !== stripos($method, 'set')) {

                            $entity->$method($childEntity[0]);
                        }
                        if (false !== stripos($method, 'add')) {
                            foreach ($childEntity as $value) {

                                $entity->$method($value);
                            }
                        }
                    }
                }
                unset($data[$key]);
            }
        }

        /*Create form with corresponding Entity paired to it*/
        $form = $this->formFactory->create($dataHelper->getFormPath(), $entity);

        /*Submit $data that was unpacked from the $response into the $form.*/
        $form->submit($data);


        /*Check if the current $form has been submitted, and is valid.*/
        if ($form->isSubmitted() && $form->isValid()) {

            $this->queryHelper->persistEntity($entity);

            return $entity->getId();
        }

        throw new RuntimeException($form->getErrors()->current()->getMessage());
    }
}