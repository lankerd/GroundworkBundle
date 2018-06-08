<?php

namespace Lankerd\GroundworkBundle\Model;

/**
 * Class CoreFunctionModel
 *
 * This is NOT NECESSARY, but does supply
 * basic entity manipulation functionality
 * with built in security parameters! I highly suggest
 * using this class if you don't need customized
 * CRUD (create, read, update, and delete) functions!
 *
 * @package CoreBundle\Model
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */
abstract class CoreFunctionModel extends CoreModel
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
    private function getRepositoryFunction($repositoryFunction, $params)
    {
        if ($repositoryFunction){
            /*I seriously did not think this was even possible... Well, hope this helps.*/
            if ($params){
                $result = $this->orm->getRepository($this->class)->$repositoryFunction($params);
            }else{
                $result = $this->orm->getRepository($this->class)->$repositoryFunction();
            }
        }else{
            $result = null;
        }
        return $result;
    }


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
    public function getPath()
    {
        $metadata = $this->orm->getClassMetadata($this->class);
        $beginingPathName = explode('\\', $metadata->getName(), 2)[0];
        $bundleIntroPath = strtolower(str_replace('Bundle', '', $beginingPathName));
        $entityPath = strtolower(str_replace('_', '', $metadata->getTableName()));
        //$partialPath = $bundleIntroPath.'.'.$entityPath;
        $partialPath = $entityPath;

        return $partialPath;
    }


    /**
     * TODO: Remove this function ASAP as it simply does not belong here!!!!!!
     *
     */
    public function precisionResult( $max )
    {
        $array = $this->orm->getRepository($this->class)->findAll();
        if (!empty($array)){
            $result = [array_values($array)[0]];
        }else{
            $result = $array;
        }
        return $result;
    }

    /**
     * This is going to serve as the engine's primary query helper,
     * which means that the function will check to see if any $repoFunctions
     * have been supplied, if so, the helper will grab the $repoFunctions array
     * and begin to run through the list that has been supplied. Under normal
     * circumstances you can provide one function, or no functions! The helper simply
     * adds NECESSARY permissions checks!
     *
     * ONLY use $repositoryFunction if you need a special;
     *
     * TODO: This function MUST be transitioned into being a custom DQL function!
     *
     * @param bool $repositoryFunction
     *
     *
     * @param null $params
     *
     * @return mixed
     */
    public function listResults($repositoryFunction = false, $params = null)
    {
        if ($repositoryFunction){
            $result = $this->getRepositoryFunction($repositoryFunction, $params);
        }else{
            if (!$this->isAdmin()){
                $builder = $this->entityManager->getEntityManager()->createQueryBuilder();
                $builder->select('e')
                    ->from($this->class, 'e')
                    ->where('e.enabled = 1')
//                    ->where('e.isPublic = 1')
                    ->orWhere('e.user = :user')
                    ->setParameters([
                        'user' => $this->getUser(),
                    ]);
                $result = $builder->getQuery()->getResult();
            }else{
                $result = $this->orm->getRepository($this->class)->findAll();
            }
        }
        return $result;
    }

    /**
     * This should be the default method used to delete Entities.
     *
     * @param $entity
     * @param $entityTitle
     * @return bool We return a boolean and force the controller to react upon .
     */
    public function removeEntity($entity, $entityTitle)
    {
        /*Then we stuff the $message variable before we flush (emptying) the $entity*/
        $message = "\"".$entityTitle."\"";

        /*Next we load the $entity into the CoreModel Permissions checker*/
        $hasPermission = $this->checkUserPermissions($entity);

        /*Afterwards we finish out the request*/
        if ($hasPermission){
            $em = $this->orm;
            $em->remove($entity);
            $em->flush();
            $this->flashBag->add('success', $message.' has been successfully removed!');
            return true;
        }else{
            $this->flashBag->add('warning', $message.' could not be removed!');
            return false;
        }
    }
}