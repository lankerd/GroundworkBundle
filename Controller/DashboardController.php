<?php

namespace Lankerd\GroundworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DashboardController
 *
 * @package Lankerd\GroundworkBundle\Controller
 * @author  Julian Lankerd <julianlankerd@gmail.com>
 */
class DashboardController extends Controller
{
    public function indexAction()
    {
        return $this->render('@LankerdGroundwork/MenuItems/groundwork-menu.html.twig');
    }

}