<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2011 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa-files for the canonical source repository
 */

/**
 * Thumbnail Controller
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Files
 */
class ComFilesControllerThumbnail extends ComFilesControllerAbstract
{
    protected function _actionBrowse(KControllerContextInterface $context)
    { 
    	// Clone to make cacheable work since we change model states
        $model = clone $this->getModel();
  
    	// Save state data for later
        $state_data = $model->getState()->getValues();
        $state_data['types'] = array('image', 'file');

        if ($this->isDispatched())
        {
        	$files  = array();
            $nodes   = $this->getObject('com:files.model.nodes')->setState($state_data)->fetch();

        	foreach ($nodes as $entity)
        	{
        		if ($entity->isImage()) {
        			$files[] = $entity->name;
        		}
        	}
        }
        else $files = $model->getState()->filename;

		$model->reset()
		      ->setState($state_data)
		      ->filename($files);
		
		$list  = $model->fetch();

    	$found = array();
        foreach ($list as $entity) {
        	$found[] = $entity->filename;
        }

        if (count($found) !== count($files))
        {
        	$new = array();
            $nodes = isset($nodes) ? $nodes : $this->getObject('com:files.model.nodes')->setState($state_data)->fetch();
        	foreach ($nodes as $entity)
        	{
        		if ($entity->isImage() && !in_array($entity->name, $found))
        		{
	        		$result = $entity->saveThumbnail();
	        		if ($result) {
	        			$new[] = $entity->name;
	        		}
        		}
        	}
        	
        	if (count($new))
        	{
				$model->reset()
				    ->setState($state_data)
				    ->getState()->set('files', $new);
				
				$additional = $model->fetch();
				
				foreach ($additional as $entity) {
					$list->insert($entity);
				}
        	}
        }

        return $list;
    }
}
