<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;

/**
 * Category controller class.
 *
 * @since  3.0.0
 */
class CategoryController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_CATEGORY';

    /**
     * Method to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowAdd($data = [])
    {
        return Factory::getApplication()->getIdentity()->authorise('core.create', 'com_k2');
    }

    /**
     * Method to check if you can edit a record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
        $user = Factory::getApplication()->getIdentity();

        if (!$recordId) {
            return $user->authorise('core.edit', 'com_k2');
        }

        return $user->authorise('core.edit', 'com_k2.category.' . $recordId);
    }

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.0.0
     */
    public function save($key = null, $urlVar = null)
    {
        $result = parent::save($key, $urlVar);

        if ($result) {
            $this->processCategoryImage();
        }

        return $result;
    }

    /**
     * Process category image upload.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processCategoryImage()
    {
        $files = $this->input->files->get('jform', [], 'array');
        $data = $this->input->post->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);

        if (!isset($files['image']) || empty($files['image']['name']) || !$id) {
            return;
        }

        $file = $files['image'];

        if ($file['error'] !== 0) {
            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
        $ext = strtolower(File::getExt($file['name']));

        if (!in_array($ext, $allowedExtensions)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_K2_INVALID_IMAGE_TYPE'), 'error');
            return;
        }

        $savePath = JPATH_ROOT . '/media/k2/categories/';

        if (!Folder::exists($savePath)) {
            Folder::create($savePath);
        }

        $filename = File::makeSafe($file['name']);

        if (File::upload($file['tmp_name'], $savePath . $filename)) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->update('#__k2_categories')
                ->set($db->quoteName('image') . ' = ' . $db->quote($filename))
                ->where($db->quoteName('id') . ' = ' . $id);
            $db->setQuery($query);
            $db->execute();
        }
    }
}
