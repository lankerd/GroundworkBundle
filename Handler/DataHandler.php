<?php

namespace Lankerd\GroundworkBundle\Handler;

use Doctrine\ORM\EntityManager;
use DomainException;
use Exception;
use Lankerd\GroundworkBundle\Util\ObjectHandler;
use LogicException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Serializer\Serializer;


/**
 * Class BaseController
 *
 * @package Lankerd\GroundworkBundle\Controller
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
class DataHandler
{
    /*Set a universal*/
    public const ENTITY_NAMESPACE = 'App\\Entity\\';

    protected $objectHandler;
    protected $entityManager;
    protected $formFactory;
    protected $serializer;

    public function __construct(ObjectHandler $objectHandler, EntityManager $entityManager, FormFactoryInterface $formFactory, Serializer $serializer)
    {
        $this->objectHandler = $objectHandler;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param $entityPath
     * @param $formPath
     *
     * @return bool
     */
    public function createRecord(Request $request, $entityPath, $formPath): bool
    {
        /*Grab the Doctrine Entity Manager so that we can process our Entity to the database.*/
        $entityManager = $this->entityManager;

        /*Unpack and decode data from $request in order to obtain form information.*/
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        /*Instantiate a new User object for us to insert the $request form data into.*/
        $entity = new $entityPath();

        /*Create form with corresponding Entity paired to it*/
        $form = $this->formFactory->create($formPath, $entity);

        /*Submit $data that was unpacked from the $response into the $form.*/
        $form->submit($data);
        /*Check if the current $form has been submitted, and is valid.*/
        if ($form->isSubmitted() && $form->isValid())
        {
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
                /**
                 * Using Flush() causes write operations against the
                 * database to be executed. Which means if you
                 * used Persist($object) before flushing,
                 * You'll end up inserting a new record
                 * into the Database.
                 */
                $entityManager->flush();
            } catch (Exception $e) {
                /**
                 * Throw a new exception to inform sender of the error.
                 *
                 * If an exception is thrown (an error is found), it will stop the process,
                 * and show the error that occurred in the "try" brackets.
                 * Instead of showing the exact error that occured in the exception,
                 * we're gonna over-generalize the error, because you never know when
                 * something nefarious may be afoot.
                 */
                throw new RuntimeException('There was an issue inserting the record!', $e->getCode());
            }

            /*Return a 200 status, successfully completing the transaction*/
            return 1;
        }

        /*Return a 400 status, failing to complete the transaction*/
        return 0;
    }

    /**
     * julianlankerd@gmail.com, build a filtration system, and actually use $request!
     *
     * @return string
     * @throws \ReflectionException
     */
    public function getAllValues(): ?string
    {
        try {
            $entityName = str_replace('Controller', '', (new ReflectionClass($this))->getShortName());
        } catch (ReflectionException $e) {
            throw $e;
        }

        if (!class_exists($path = self::ENTITY_NAMESPACE.$entityName)) {
            throw new LogicException($entityName.' does not exist!');
        }

        try {
            $response = [
                    $this->serializer->serialize($this->entityManager->getRepository($path)->findAll(), 'json'),
                    Response::HTTP_OK,
                    ['Content-type' => 'application/json']
                ];
        } catch (ReflectionException $e) {
            throw $e;
        }

        return new Response($response);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @throws Exception
     */
    public function connectRecords(Request $request): void
    {
        /*Grab the Doctrine Entity Manager so that we can process our Entity to the database.*/
        $entityManager = $this->entityManager;

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
         *
         * Query for the $primaryEntity, so that we can begin to set relationships
         * to the $primaryEntity. This is the target we will be setting database
         * relationships towards.
         */
        try {
            $primaryEntity = $entityManager->getRepository('App:'.key($primaryEntity))->findBy(
                $primaryEntity[key($primaryEntity)]
            );
        } catch (Exception $e) {
            throw $e;
        }

        /*Check if there are many objects that have been returned to the return*/
        $primaryEntity = $this->objectHandler->hasOneValue($primaryEntity, 'primaryEntity');

        try {
            $objectProperties = $this->objectHandler->getObjectProperties($primaryEntity);
        } catch (ReflectionException $e) {
            throw $e;
        }

        foreach ($secondaryEntities as $entityName => $entityData) {
            /**
             * Check to see if there are multiple
             * of the same entity in the request.
             *
             * NOTE: If there should be multiple
             * entities sent, the key MUST be denoted
             * with a prepended underscore (Example: "Entity, Entity_2, etc..."),
             * the value after the underscore MUST BE UNIQUE!!!
             *
             */
            if (false !== strpos($entityName, '_')) {
                /**
                 * Break the unique underscore with identifier
                 * off of the string, and carry on using the
                 * normalized name of the entity in question.
                */
                $entityName = explode('_',$entityName)[0];
            }

            $entityName = $this->objectHandler->singularize(ucfirst($entityName));

            /**
             * Check if any of the properties passed are an array.
             * If the property is an exception will be thrown
             * because the functionality does not currently exist.
             *
             * Why would someone wish to pass an array in a property?
             * Collections or associations. Someone may wish to search
             * for information to pair up based on a certain relationship.
             *
             * Sorry for any temporary inconveniences! It'll be usable soon!
             *
             * julianlankerd@gmail.com needs to build association functionality capable in the "coupler"
             */
            
            foreach ($entityData as $index => $entityDatum) {
                if (is_array($entityDatum)){
                    throw new DomainException(
                        'It appears an array was passed as a property! This functionality is not yet available, but will be coming soon!'
                    );
                }
            }
            
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
                $returnedValue = $this->entityManager->getRepository('App:'.$entityName)->findBy($entityData);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage());
            }

            /*Check if there are many objects that have been returned to the return*/
            $entity = $this->objectHandler->hasOneValue($returnedValue, $entityName);

            $bindingMethod = null;
            foreach ($objectProperties [ucfirst($entityName)] as $objectMethod) {
                if (false !== stripos($objectMethod, 'add')) {
                    $bindingMethod = $objectMethod;
                }

                if (false !== stripos($objectMethod, 'set')) {
                    $bindingMethod = $objectMethod;
                }
            }

            $primaryEntity->$bindingMethod($entity);
        }
        $entityManager->persist($primaryEntity);
        $entityManager->flush();
    }
}
