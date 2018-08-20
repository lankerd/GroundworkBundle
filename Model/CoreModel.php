<?php

namespace Lankerd\GroundworkBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;


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

    /**
     * CoreModel constructor.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager                                 $orm
     * @param                                                                            $class *ExampleCompany\ExampleBundle\Entity\Example*
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker        $authorizationChecker
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage $tokenStorage
     * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBag                   $flashBag
     * @param \Doctrine\ORM\EntityManager                                                $entityManager
     */

    public function __construct(ObjectManager $orm, $class, AuthorizationChecker $authorizationChecker, TokenStorage $tokenStorage, FlashBag $flashBag, EntityManager $entityManager)
    {
        /**
         * This condition should ensure that if a
         * "User Token" does not exist, they are
         * denied access immediately.
         *
         * NOTE: This means if anyone extends off this null user tokens will get blocked!!!
         *
         */

        if (null === $token = $tokenStorage->getToken()) {
            throw new AccessDeniedException();
        }

        $this->entityManager = $entityManager->createQueryBuilder();
        $this->orm   = $orm;
        $this->repo  = $orm->getRepository($class);
        $metaData    = $orm->getClassMetadata($class);
        $this->class = $metaData->getName();
        $this->user = $tokenStorage->getToken()->getUser();
        $this->roleCheck = $authorizationChecker;
        $this->flashBag = $flashBag;
    }


    /**
     * Instead of pulling the $user object from a controller and
     * stuffing $user through a service, we're gonna front-load our Models (AKA: Services),
     * and try to keep our Controllers thin. Therefore, ALWAYS use this method
     * of accessing user information from inside of a model!
     *
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
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
    private function checkEntity($entity)
    {
        try {
            $entity->getId();
        } catch (\Exception $e) {
            return "It appears there was no Entity provided!";
        }
    }

    /**
     * Used to check if the "user token" posses the ROLE_SUPER_ADMIN permission to access
     * certain functionality.
     *
     * @param string $role Currently this is only meant to be used to check if a subject possess a particular role
     *
     * @param null   $subject
     *
     * @return bool
     */
    public function isAdmin($role = "ROLE_SUPER_ADMIN", $subject = null)
    {
        /**
         * In the case that this is just an internalized security check,
         * which pretty much means that no one needed to set the $subject
         * parameter; we will assume that the "sessions user token" is all we need.
         *
         * *########## NOTE ##########*
         * We grab the current session's user token and extract the user object.
         * Due to how the entire data structure has been designed, we default to seeing if
         * the user in question should be accessing the current Model, which will affect whether
         * the user can access the Controller and View by proxy. Therefore,
         * when you are setting up a new Model (AKA: Service), make sure you utilize this method
         * to do all of your verification!
         *
         * *########## END NOTE ##########*
         */

        if ($subject === null){
            $subject = $this->getUser();
        }

        return $this->roleCheck->isGranted($role, $subject);
    }

    /**
     * This is the "permissions heartbeat" and it should be used
     * when permissions are ever required from another service!
     *
     *
     * @param string $role
     * @param null   $entity
     *
     * @return bool
     */
    public function checkUserPermissions($entity = null, $role = "ROLE_SUPER_ADMIN")
    {
        $this->checkEntity($entity);
        if ($this->isAdmin($role) == true || $this->getUser() == $entity->getUser()){
            return true;
        }else{
            return false;
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

}