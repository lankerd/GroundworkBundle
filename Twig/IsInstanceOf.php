<?php

namespace Lankerd\GroundworkBundle\Twig;

class IsInstanceOf extends \Twig_Extension
{
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('instanceOf', array($this, 'isInstanceOf')),
		);
	}

    /**
     * @param $var mixed
     * @param $instance
     *
     * @return bool
     */
    public function isInstanceOf($var, $instance){
		return $var instanceof $instance;
	}
}