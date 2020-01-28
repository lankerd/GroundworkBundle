<?php
declare(strict_types=1);

namespace Lankerd\GroundworkBundle\Handler;

use Lankerd\GroundworkBundle\Helper\DataHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DataHandler
 *
 * @package Lankerd\GroundworkBundle\Handler
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
class RequestHandler
{
    /**
     * @var \Lankerd\GroundworkBundle\Helper\DataHelperInterface
     */
    protected $dataHelper;

    /**
     * @var array
     */
    protected $globalIdentifiers;

    /**
     * DataHandler constructor.
     *
     * @param \Lankerd\GroundworkBundle\Helper\DataHelperInterface $dataHelper
     */
    public function __construct(DataHelperInterface $dataHelper)
    {
        $this->dataHelper = $dataHelper;
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
            if ($action === "create"){
                $this->create($entities);
            }
        }
    }

    /**
     * @param array $entities
     */
    public function create(array $entities)
    {
        $dataHelper = $this->dataHelper;

        foreach ($entities as $entityName => $entityInformation) {
            $fullEntityNamespace = $dataHelper::ENTITY_NAMESPACE.$entityName;
            $entity = new $fullEntityNamespace();
            $entityProperties = $dataHelper->getObjectProperties($entity);
            foreach ($entityInformation as $information) {
                dump($information, $entityProperties);
                die;
            }

            //$entityRepository = $queryHelper->getEntityRepository('App:'.$entityName)->findBy($value);

            $this->globalIdentifiers[$entityName];

        }
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
}