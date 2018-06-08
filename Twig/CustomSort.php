<?php

namespace Lankerd\GroundworkBundle\Twig;

class CustomSort extends \Twig_Extension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('usort', array($this, 'usortFilter')),
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
		usort($item, function ($item1, $item2) use ($objectFunction) {
			if ($item1->$objectFunction() == $item2->$objectFunction()) return 0;
			return $item1->$objectFunction() < $item2->$objectFunction() ? -1 : 1;
		});

		return $item;
	}
}