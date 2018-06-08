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
    /**
     * @TODO This will be removed soon!
    */
    public function indexAction()
    {
        return $this->render('@LankerdGroundwork/Dashboard/index.html.twig');
    }
}