<?php

namespace Lankerd\GroundworkBundle\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;

class GroundworkTwig extends \Twig_Extension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
	{
		return array(
		    new TwigFilter('usort', array($this, 'usortFilter')),
            new TwigFilter('instanceOf', array($this, 'isInstanceOf')),
		);
	}

    /**
     * @return array|\Twig_Filter[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('die', array($this, 'die')),
        );
    }

    /**
     * Okay, this has a bit more of an obscure
     * use case, but the function has proven to
     * be quite handy.
     *
     * "usortFilter()" works similarly to the
     * usort PHP function, only with the
     * added bonus of having usort handle an
     * array of objects that have multiple
     * properties to be called upon.
     *
     *
     * @param $item array
     * @param $objectFunction string
     *
     * @return mixed
     *
     * @example
     * <pre>
     * {% for suggestedBusiness in suggestedBusinesses|usort("getSupportedMiles") %}
     * // Line 33 is going to rearrange the array of objects based upon the "$supportedMiles" property
     * {% endfor %}
     * </pre>
     */
    public function usortFilter($item, $objectFunction){
		usort($item, function ( object $item1, object $item2) use ($objectFunction) {
			if ($item1->$objectFunction() == $item2->$objectFunction()) return 0;
			return $item1->$objectFunction() < $item2->$objectFunction() ? -1 : 1;
		});

		return $item;
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