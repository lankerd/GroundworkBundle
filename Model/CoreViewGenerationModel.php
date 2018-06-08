<?php

namespace Lankerd\GroundworkBundle\Model;

/**
 * Class CoreViewGenerationModel
 *
 * This class was created due to pure laziness
 * it is completely unnecessary, and should ONLY be used
 * if you have basic index, edit, and new forms/pages that
 * you have no intention of customizing! This is essentially
 * a lazy man's way of building views without ever creating the
 * physical view files.
 *
 * NOTE: If you decide to use these functions, and find that the results are unsatisfactory, extend a view file off of CoreBundle/Resources/views/DefaultTemplates/*
 *
 * @package CoreBundle\Model
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */
abstract class CoreViewGenerationModel extends CoreFunctionModel
{

    /**
     * This function will generate a list of entities (if any are present)
     * then pack the results to be processed in the 'basic-index.html.twig'
     * view.
     *
     * @param null $title
     *
     * @return array
     */
    public function indexViewPacker($title = null)
	{
        /** @var object $metadata */
        $metadata = $this->orm->getClassMetadata($this->class);

	    $beginingPathName = explode('\\', $metadata->getName(), 2)[0];
	    $shavedPathName = strtolower(str_replace('Bundle', '', $beginingPathName));
        $partialPath = strtolower(str_replace('_', '', $metadata->getTableName()));

		$viewEntityListing = $this->listResults();
		$viewFieldNames = $metadata->getFieldNames();

		if ($title == null) {
            $viewTitle = $metadata->getTableName();
        } else {
            $viewTitle = $title;
        }

        $viewData = [
            'title' => $viewTitle,
            'tableHeaders' => $viewFieldNames,
            'entityProperties' => $viewEntityListing,
            'newPath' => $partialPath.'.new',
            'editPath' => $partialPath.'.edit',
            'removePath' => $partialPath.'.delete',
        ];

		return $viewData;
	}


    /**
     * @param null $title
     * @param      $entity object
     * @param      $entityTitle string
     * @param      $form object
     *
     * @return array
     */
    public function editViewPacker($title = null, $entity, $entityTitle, $form)
    {
        $viewData = [
            'title' => $title,
            'entityTitle' => $entityTitle,
            'entity' => $entity,
            'form' => $form->createView(),
        ];

        return $viewData;
    }
}