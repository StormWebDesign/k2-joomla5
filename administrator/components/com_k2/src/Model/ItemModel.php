<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * K2 Item Model
 *
 * @since  3.0.0
 */
class ItemModel extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     * @since  3.0.0
     */
    public $typeAlias = 'com_k2.item';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_ITEM';

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     *
     * @since   3.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_k2.item', 'item', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   3.0.0
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_k2.edit.item.data', []);

        if (empty($data)) {
            $data = $this->getItem();

            // Prime some default values
            if ($this->getState('item.id') == 0) {
                $app = Factory::getApplication();
                $data->set('catid', $app->input->get('catid', $app->getUserState('com_k2.items.filter.category_id'), 'int'));
            }
        }

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  \Joomla\CMS\Object\CMSObject|boolean  Object on success, false on failure.
     *
     * @since   3.0.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // Load tags
            $item->tags = $this->getItemTags($item->id);

            // Parse extra fields
            if (!empty($item->extra_fields)) {
                $item->extra_fields = json_decode($item->extra_fields);
            }

            // Parse params
            if (!empty($item->params)) {
                $item->params = new Registry($item->params);
            }

            // Parse metadata
            if (!empty($item->metadata)) {
                $item->metadata = new Registry($item->metadata);
            }

            // Parse plugins
            if (!empty($item->plugins)) {
                $item->plugins = new Registry($item->plugins);
            }
        }

        return $item;
    }

    /**
     * Get item tags.
     *
     * @param   integer  $itemId  The item ID.
     *
     * @return  array  Array of tag IDs.
     *
     * @since   3.0.0
     */
    protected function getItemTags($itemId)
    {
        if (!$itemId) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('tagID'))
            ->from($db->quoteName('#__k2_tags_xref'))
            ->where($db->quoteName('itemID') . ' = ' . (int) $itemId);

        $db->setQuery($query);

        return $db->loadColumn();
    }

    /**
     * Method to validate the form data.
     *
     * @param   \Joomla\CMS\Form\Form  $form   The form to validate against.
     * @param   array                   $data   The data to validate.
     * @param   string                  $group  The name of the field group to validate.
     *
     * @return  array|boolean  Array of filtered data if valid, false otherwise.
     *
     * @since   3.0.0
     */
    public function validate($form, $data, $group = null)
    {
        // Filter input
        if (isset($data['introtext'])) {
            $filter = InputFilter::getInstance();
            $data['introtext'] = $filter->clean($data['introtext'], 'html');
        }

        if (isset($data['fulltext'])) {
            $filter = InputFilter::getInstance();
            $data['fulltext'] = $filter->clean($data['fulltext'], 'html');
        }

        return parent::validate($form, $data, $group);
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  The Table object
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function prepareTable($table)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        // Set created date
        if (!(int) $table->created) {
            $table->created = $date->toSql();
        }

        // Set created by
        if (empty($table->created_by)) {
            $table->created_by = $user->id;
        }

        // Set modified date
        if ($table->id) {
            $table->modified = $date->toSql();
            $table->modified_by = $user->id;
        }

        // Generate alias
        if (empty($table->alias)) {
            $table->alias = $table->title;
        }
        $table->alias = OutputFilter::stringURLSafe($table->alias);

        // Check for unique alias
        $table->alias = $this->generateUniqueAlias($table);

        // Set ordering
        if (empty($table->id)) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('ordering') . ')')
                ->from($db->quoteName('#__k2_items'))
                ->where($db->quoteName('catid') . ' = ' . (int) $table->catid);
            $db->setQuery($query);
            $max = $db->loadResult();
            $table->ordering = $max + 1;
        }
    }

    /**
     * Generate a unique alias for an item.
     *
     * @param   Table  $table  The table object.
     *
     * @return  string  The unique alias.
     *
     * @since   3.0.0
     */
    protected function generateUniqueAlias($table)
    {
        $db = $this->getDatabase();
        $alias = $table->alias;
        $i = 2;

        while (true) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_items'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));

            if ($table->id) {
                $query->where($db->quoteName('id') . ' != ' . (int) $table->id);
            }

            $db->setQuery($query);
            $count = $db->loadResult();

            if ($count == 0) {
                break;
            }

            $alias = $table->alias . '-' . $i;
            $i++;
        }

        return $alias;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, False on error.
     *
     * @since   3.0.0
     */
    public function save($data)
    {
        $app = Factory::getApplication();
        $table = $this->getTable();
        $pk = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('item.id');
        $isNew = true;

        // Load the row if editing
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
        }

        // Bind data
        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        // Prepare the table
        $this->prepareTable($table);

        // Check the table
        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        // Trigger before save plugins
        PluginHelper::importPlugin('k2');
        $result = $app->triggerEvent('onBeforeK2Save', [&$table, $isNew]);

        if (in_array(false, $result, true)) {
            $this->setError($table->getError());
            return false;
        }

        // Store the table
        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        // Trigger after save plugins
        $app->triggerEvent('onAfterK2Save', [&$table, $isNew]);

        // Update state
        $this->setState('item.id', $table->id);

        return true;
    }

    /**
     * Delete an attachment.
     *
     * @param   integer  $id  The attachment ID.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function deleteAttachment($id)
    {
        $db = $this->getDatabase();

        // Get attachment info
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__k2_attachments'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        $attachment = $db->loadObject();

        if ($attachment) {
            // Delete file
            $filePath = JPATH_ROOT . '/media/k2/attachments/' . $attachment->filename;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // Delete database record
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__k2_attachments'))
                ->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($query);
            $db->execute();
        }

        return true;
    }

    /**
     * Method to get the table.
     *
     * @param   string  $name    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   3.0.0
     */
    public function getTable($name = 'Item', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($name, $prefix, $config);
    }
}
