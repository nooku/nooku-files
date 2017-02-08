<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2011 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/joomlatools-framework-files for the canonical source repository
 */

/**
 * Thumbnailable Controller Behavior
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Files
 */
class ComFilesControllerBehaviorThumbnailable extends KControllerBehaviorAbstract
{
    protected $_container;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('after.browse', '_setThumbnails');

        $this->_container = $config->container;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('default' => 'small', 'container' => 'fileman-images'));
        parent::_initialize($config);
    }

    protected function _afterAdd(KControllerContextInterface $context)
    {
        if ($this->_canGenerateThumbnails()) {
            $this->_generateThumbnails($context->result);
        }
    }

    protected function _canGenerateThumbnails()
    {
        $parameters = $this->getModel()->getContainer()->getParameters();

        return (bool) $parameters->thumbnails;
    }

    protected function _generateThumbnails(KModelEntityInterface $entity, $version = null)
    {
        $result = $this->getObject('com:files.model.entity.thumbnails');

        $version = (array) $version;

        $file = $this->_getFile($entity);

        if ($file->isImage())
        {
            $parameters = $this->_getContainer()->getParameters();

            $folder = $this->_getFolder($entity);
            $name   = $this->_getName($entity);

            $data = array('folder' => $folder, 'name' => $name, 'source' => $file);

            $model = $this->getObject('com:files.model.thumbnails')->container($this->_getContainer()->slug);

            if ($versions = $parameters->versions->toArray())
            {
                if ($version) {
                    $versions = array_intersect(array_keys($versions), $version);
                }

                foreach ($versions as $version)
                {
                    $data['version'] = $version;

                    $thumbnail = $model->create($data);

                    if (!$thumbnail->save()) {
                        $thumbnail = $model->name($name)->folder($folder)->version($version)->fetch();
                    }

                    if (!$thumbnail->isNew()) {
                        $result->insert($thumbnail);
                    }
                }
            }
            else
            {
                $thumbnail = $model->create($data);

                if (!$thumbnail->save()) {
                    $thumbnail = $model->name($name)->folder($folder)->fetch();
                }

                if (!$thumbnail->isNew()) {
                    $result->insert($thumbnail);
                }
            }
        }

        return $result;
    }

    protected function _getFolder(KModelEntityInterface $entity)
    {
        return $this->_getFile($entity)->folder;
    }

    protected function _getName(KModelEntityInterface $entity)
    {
        return $this->_getFile($entity)->name;
    }

    protected function _getContainer()
    {
        if (!$this->_container instanceof ComFilesModelEntityContainer)
        {
            $container = $this->getObject('com:files.model.containers')->slug($this->_container)->fetch();

            if (!$container->isNew()) {
                $this->_container = $container->top();
            }
        }

        return $this->_container;
    }

    protected function _getFile(KModelEntityInterface $entity)
    {
        return $entity;
    }

    protected function _afterDelete(KControllerContextInterface $context)
    {
        $entities = $context->result;

        foreach ($entities as $entity)
        {
            $file = $this->_getFile($entity);

            $controller = $this->getObject('com:files.controller.thumbnail')
                               ->container($this->_getContainer()->slug)
                               ->folder($file->folder)
                               ->name($file->name);

            $parameters = $this->_getContainer()->getParameters();

            if ($versions = $parameters->versions) {
                $controller->version(array_keys($versions->toArray()));
            }

            $thumbnails = $controller->browse();

            if ($thumbnails->count()) {
                $thumbnails->delete();
            }
        }
    }

	protected function _setThumbnails(KControllerContextInterface $context)
	{
        $query      = $context->getRequest()->getQuery();
        $result     = $context->result;

        if ($query->get('thumbnails', 'cmd') && $this->_canGenerateThumbnails())
        {
            $version = $this->getConfig()->default;

            foreach ($result as $entity)
            {
                $file = $this->_getFile($entity);

                if ($entity instanceof ComFilesModelEntityFile && $entity->isImage())
                {
                    $controller = $this->getObject('com:files.controller.thumbnail')
                                       ->container($this->_getContainer()->slug)
                                       ->version($version)
                                       ->folder($file->folder)
                                       ->name($file->name);

                    if ($size = $query->get('size', 'cmd')) {
                        $controller->size($size);
                    }

                    $thumbnail = $controller->browse();

                    if ($thumbnail->isNew())
                    {
                        // Try to generate it.
                        $thumbnail = $this->_generateThumbnails($entity, $version);

                        if ($thumbnail->isNew()) {
                            $thumbnail = false;
                        }
                    }

                    if ($thumbnail) {
                        $thumbnail = $thumbnail->relative_path;
                    }
                }
                else $thumbnail = false;

                $entity->thumbnail = $thumbnail;
            }
        }
	}
}