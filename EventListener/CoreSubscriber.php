<?php

namespace Lankerd\GroundworkBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class CoreSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
            'postUpdate',
        );
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        echo "Post Update";
        $this->index($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        echo "Pre Update";
        $this->index($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        echo "Pre Persist";
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        /**
         * This Subscriber is going to probably be
         * paramount for any advanced Doctrine
         * Event handling (which there will already be a ton of)
         */
        /*if ($entity instanceof BodyStructure) {
            $entityManager = $args->getObjectManager();

        }*/
    }
}