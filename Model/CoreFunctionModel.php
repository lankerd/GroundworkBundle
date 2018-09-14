<?php

namespace Lankerd\GroundworkBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserBundle\Entity\BillingAddress;

/**
 * Class CoreFunctionModel
 * This is NOT NECESSARY, but does supply
 * basic entity manipulation functionality
 * with built in security parameters! I highly suggest
 * using this class if you don't need customized
 * CRUD (create, read, update, and delete) functions!
 *
 * @package CoreBundle\Model
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */
class CoreFunctionModel extends CoreModel
{
    /**
     * This is SUPER experimental! I am not sure if this has any practical
     * application! I figured perhaps there could be a Repository coupling generator
     * where I take multiple repository functions and couple them into
     * a singular process... But that's kinda overkill.
     *
     *
     * @param $repositoryFunction
     * @param $params
     *
     * @return null
     */
//    private function getRepositoryFunction($repositoryFunction, $params)
//    {
//        if ($repositoryFunction){
//            /*I seriously did not think this was even possible... Well, hope this helps.*/
//            if ($params){
//                $result = $this->orm->getRepository($this->class)->$repositoryFunction($params);
//            }else{
//                $result = $this->orm->getRepository($this->class)->$repositoryFunction();
//            }
//        }else{
//            $result = null;
//        }
//        return $result;
//    }


    /**
     * I plan on using this as an auto-router of sorts so
     * that generating links will be convenient.
     * allowing more weight to be pulled by the Core!
     *
     * NOTE: ALWAYS CREATE VERBOSE ROUTES! I do routing this way
     * so that it is super convenient to generate full paths in an instant!
     * Therefore all paths must look like `bundlename.entityname.action`
     *
     *
     * @return string
     */
//    public function getPath()
//    {
//        $metadata = $this->orm->getClassMetadata($this->class);
//        $beginingPathName = explode('\\', $metadata->getName(), 2)[0];
//        $bundleIntroPath = strtolower(str_replace('Bundle', '', $beginingPathName));
//        $entityPath = strtolower(str_replace('_', '', $metadata->getTableName()));
//        //$partialPath = $bundleIntroPath.'.'.$entityPath;
//        $partialPath = $entityPath;
//
//        return $partialPath;
//    }


    /**
     * TODO: Remove this function ASAP as it simply does not belong here!!!!!!
     *
     */
//    public function precisionResult( $max )
//    {
//        $array = $this->orm->getRepository($this->class)->findAll();
//        if (!empty($array)){
//            $result = [array_values($array)[0]];
//        }else{
//            $result = $array;
//        }
//        return $result;
//    }

    /**
     * @param array $data Should always hold the properties for the "BillingAddress" Entity
     */
    public function processEntity(array $data){
        foreach ($data as $datum) {
            echo "Howdy";
            die;
            $entityClass = new $this->class;

            dump($entityClass->properties);
            die;

            dump($entityClass);
            die;
            dump($entityClass);
            die;

            dump($datum);
            die;
        }
    }
}