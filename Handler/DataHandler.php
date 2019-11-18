<?php

namespace Lankerd\GroundworkBundle\Handler;

use Doctrine\ORM\EntityManager;
use DomainException;
use Exception;
use Lankerd\GroundworkBundle\Helper\DataHelperInterface;
use Lankerd\GroundworkBundle\Helper\QueryHelper;
use ReflectionException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Serializer\Serializer;

class DataHandler
{
    protected $dataHelper;
    protected $formFactory;
    protected $serializer;
    protected $queryHelper;
    protected $classPath;

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

    public function setClass(string $class)
    {
        $this->dataHelper->setClassName($class);
    }
    
    /**
     * THIS NEEDS TO BE REWRITTEN WHEN I AM MORE CONCIOUS. TOO TIRED TO WRITE GOOD CODE.
     *
     * @param Request $request
     *
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function updateRecord(Request $request): int
    {
        $dataHelper = $this->dataHelper;
        
        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if(empty($data['targetEntity']) || empty($data['updateValues'])){
            throw new RuntimeException('Data that was sent is incorrectly formatted!');
        }

        /*Instantiate a new User object for us to insert the $request form data into.*/

        if (count($entity = $this->queryHelper->getEntityRepository($dataHelper->getClassPath())->findBy($data['targetEntity'])) === 1){
            $entity = $entity[0];
        }else{
            throw new RuntimeException('Data that was sent is too vauge to accuratly update, please supply more detail in order to narrow the search scope');
        }

        /*Create form with corresponding Entity paired to it*/
        $bindingMethod = [];
        $objectMethods = $this->dataHelper->getObjectProperties($entity);
        foreach ($data['updateValues'] as $key => $updateValue) {
            if (array_key_exists(ucfirst(strtolower($key)), $objectMethods)){
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
     * @param Request $request
     * @param         $entityPath
     * @param         $formPath
     *
     * @return int
     * @throws \ReflectionException
     */
    public function createRecord(Request $request): int
    {
        $dataHelper = $this->dataHelper;

        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        /*Instantiate a new User object for us to insert the $request form data into.*/
        $entity = $dataHelper->getEntity();

        /*Create form with corresponding Entity paired to it*/
        $form = $this->formFactory->create($dataHelper->getFormPath(), $entity);

        /*Submit $data that was unpacked from the $response into the $form.*/
        $form->submit($data);
        /*Check if the current $form has been submitted, and is valid.*/
        if ($form->isSubmitted() && $form->isValid()) {
            $this->queryHelper->persistEntity($entity);

            return $entity->getId();
        }
        throw new RuntimeException('There was an issue with the data sent!');
    }

    /**
     * julianlankerd@gmail.com, build a filtration system, and actually use $request!
     *
     * @return string
     * @throws \Exception
     */
    public function getAllValues(): string
    {
        return $this->serializer->serialize($this->queryHelper->getEntityRepository($this->dataHelper->getClassName())->findAll(), 'json');
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

        try {
            $objectProperties = $this->dataHelper->getObjectProperties($primaryEntity);
        } catch (ReflectionException $e) {
            throw $e;
        }

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
             * julianlankerd@gmail.com needs to build association functionality capable in the "coupler"
             */

            foreach ($entityData as $index => $entityDatum) {
                if (is_array($entityDatum)) {
                    throw new DomainException(
                        'It appears an array was passed as a property! This functionality is not yet available, but will be coming soon!'
                    );
                }
            }

            /**
             * Placing this into a try-catch is definitely a wise call
             * considering we are stuffing kinda questionable stuff into the
             * findBy.
             * Query for the $primaryEntity, so that we can begin to set relationships
             * to the $primaryEntity. This is the target we will be setting database
             * relationships towards.
             */
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
}
