<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * K2 Item Table
 *
 * @since  3.0.0
 */
class ItemTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database driver object.
     *
     * @since   3.0.0
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__k2_items', 'id', $db);

        $this->setColumnAlias('published', 'published');
    }

    /**
     * Method to perform sanity checks on the Table instance properties to ensure they are safe to store in the database.
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database.
     *
     * @since   3.0.0
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Check for valid title
        if (trim($this->title) === '') {
            $this->setError(Text::_('COM_K2_ERROR_ITEM_TITLE_REQUIRED'));
            return false;
        }

        // Check for valid category
        if (empty($this->catid)) {
            $this->setError(Text::_('COM_K2_ERROR_ITEM_CATEGORY_REQUIRED'));
            return false;
        }

        // Generate alias if empty
        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // Handle params
        if (is_array($this->params)) {
            $registry = new Registry($this->params);
            $this->params = $registry->toString();
        }

        // Handle metadata
        if (is_array($this->metadata)) {
            $registry = new Registry($this->metadata);
            $this->metadata = $registry->toString();
        }

        // Handle plugins
        if (is_array($this->plugins)) {
            $registry = new Registry($this->plugins);
            $this->plugins = $registry->toString();
        }

        // Set publish_up to current date if not set
        if (empty($this->publish_up)) {
            $this->publish_up = Factory::getDate()->toSql();
        }

        // Set created to current date if not set
        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }

        // Set created_by if not set
        if (empty($this->created_by)) {
            $this->created_by = Factory::getApplication()->getIdentity()->id;
        }

        return true;
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        // Set modified
        if ($this->id) {
            $this->modified = $date->toSql();
            $this->modified_by = $user->id;
        }

        return parent::store($updateNulls);
    }

    /**
     * Method to delete a row from the database table by primary key value.
     *
     * @param   mixed  $pk  An optional primary key value to delete.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function delete($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = $pk ?? $this->$k;

        if ($pk) {
            $db = $this->getDbo();

            // Delete tag references
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__k2_tags_xref'))
                ->where($db->quoteName('itemID') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $db->execute();

            // Delete comments
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__k2_comments'))
                ->where($db->quoteName('itemID') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $db->execute();

            // Delete attachments
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__k2_attachments'))
                ->where($db->quoteName('itemID') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $db->execute();
        }

        return parent::delete($pk);
    }
}
