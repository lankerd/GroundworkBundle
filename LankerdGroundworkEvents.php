<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lankerd\GroundworkBundle;

/**
 * Contains all events thrown in the FOSUserBundle.
 */
final class LankerdGroundworkEvents
{
    /**
     * The USER_CREATED event occurs when the user is created with UserManipulator.
     *
     * This event allows you to access the created user and to add some behaviour after the creation.
     *
     * @Event("FOS\UserBundle\Event\UserEvent")
     */
    const MIGRATION_OBJECT_CONDITIONALS = 'core.migration.object.conditionals';
}
