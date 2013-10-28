<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2011 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa-files for the canonical source repository
 */

/**
 * Nodes Model
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Files
 */
class ComFilesModelNodes extends ComFilesModelDefault
{
    /**
     * A container object
     *
     * @var ComFilesDatabaseRowContainer
     */
    protected $_container;

    /**
     * Reset the cached container object if container changes
     * @param string $name
     */
    public function onStateChange($name)
    {
        if ($name === 'container') {
            unset($this->_container);
        }
    }

    /**
     * Returns the current container row
     *
     * @return ComFilesDatabaseRowContainer
     * @throws UnexpectedValueException
     */
    public function getContainer()
    {
        if(!isset($this->_container))
        {
            //Set the container
            $container = $this->getObject('com:files.model.containers')->slug($this->getState()->container)->getItem();

            if (!is_object($container) || $container->isNew()) {
                throw new UnexpectedValueException('Invalid container');
            }

            $this->_container = $container;
        }

        return $this->_container;
    }

    public function getItem()
    {
        if (!isset($this->_item))
        {
            $state = $this->getState();

            $this->_item = $this->getRow(array(
                'data' => array(
            		'container' => $state->container,
                    'folder' 	=> $state->folder,
                    'name' 		=> $state->name
                )
            ));
        }

        return parent::getItem();
    }

    public function getRow(array $options = array())
    {
        $identifier         = clone $this->getIdentifier();
        $identifier->path   = array('database', 'row');
        $identifier->name   = KStringInflector::singularize($this->getIdentifier()->name);

        return $this->getObject($identifier, $options);
    }

    public function getRowset(array $options = array())
    {
        $identifier         = clone $this->getIdentifier();
        $identifier->path   = array('database', 'rowset');

        return $this->getObject($identifier, $options);
    }

    protected function _getPath()
    {
        $state = $this->getState();

        $path = $this->getContainer()->path;

        if (!empty($state->folder) && $state->folder != '/') {
            $path .= '/'.ltrim($state->folder, '/');
        }

        return $path;
    }

	public function getList()
	{
		if (!isset($this->_list))
		{
			$state = $this->getState();
			$type = !empty($state->types) ? (array) $state->types : array();

			$list = $this->getObject('com://admin/files.database.rowset.nodes');

			// Special case for limit=0. We set it to -1
			// so loop goes on till end since limit is a negative value
			$limit_left = $state->limit ? $state->limit : -1;
			$offset_left = $state->offset;
			$total = 0;

			if (empty($type) || in_array('folder', $type))
			{
                $folders_model = $this->getObject('com://admin/files.model.folders');
				$folders_model->setState($state->getValues());

				$folders = $folders_model->getList();

				foreach ($folders as $folder)
				{
					if (!$limit_left) {
						break;
					}
					$list->insert($folder);
					$limit_left--;
				}

				$total += $folders_model->getTotal();
				$offset_left -= $total;
			}

			if ((empty($type) || (in_array('file', $type) || in_array('image', $type))))
			{
				$data = $state->getValues();
				$data['offset'] = $offset_left < 0 ? 0 : $offset_left;
				$files_model = $this->getObject('com://admin/files.model.files')->setState($data);
				$files = $files_model->getList();

				foreach ($files as $file)
				{
					if (!$limit_left) {
						break;
					}
					$list->insert($file);
					$limit_left--;
				}

				$total += $files_model->getTotal();
			}

			$this->_total = $total;

			$this->_list = $list;
		}

		return parent::getList();
	}
}
