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
 * K2 Category Table
 *
 * @since  3.0.0
 */
class CategoryTable extends Table
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
        parent::__construct('#__k2_categories', 'id', $db);

        $this->setColumnAlias('published', 'published');
    }

    /**
     * Method to perform sanity checks on the Table instance properties.
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

        // Check for valid name
        if (trim($this->name) === '') {
            $this->setError(Text::_('COM_K2_ERROR_CATEGORY_NAME_REQUIRED'));
            return false;
        }

        // Generate alias if empty
        if (empty($this->alias)) {
            $this->alias = $this->name;
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

        // Handle plugins
        if (is_array($this->plugins)) {
            $registry = new Registry($this->plugins);
            $this->plugins = $registry->toString();
        }

        // Ensure parent is set
        if (empty($this->parent)) {
            $this->parent = 0;
        }

        return true;
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

            // Check for child categories
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_categories'))
                ->where($db->quoteName('parent') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $childCount = $db->loadResult();

            if ($childCount > 0) {
                $this->setError(Text::_('COM_K2_ERROR_CATEGORY_HAS_CHILDREN'));
                return false;
            }

            // Check for items in category
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_items'))
                ->where($db->quoteName('catid') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $itemCount = $db->loadResult();

            if ($itemCount > 0) {
                $this->setError(Text::_('COM_K2_ERROR_CATEGORY_HAS_ITEMS'));
                return false;
            }
        }

        return parent::delete($pk);
    }
}
