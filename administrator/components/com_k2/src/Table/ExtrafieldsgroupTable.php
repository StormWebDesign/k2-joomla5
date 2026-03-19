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
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;

/**
 * K2 Extra Field Group Table
 *
 * @since  3.0.0
 */
class ExtrafieldsgroupTable extends Table
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
        parent::__construct('#__k2_extra_fields_groups', 'id', $db);
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
            $this->setError(Text::_('COM_K2_ERROR_EXTRAFIELDSGROUP_NAME_REQUIRED'));
            return false;
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

            // Check for extra fields in this group
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_extra_fields'))
                ->where($db->quoteName('group') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $fieldCount = $db->loadResult();

            if ($fieldCount > 0) {
                $this->setError(Text::_('COM_K2_ERROR_EXTRAFIELDSGROUP_HAS_FIELDS'));
                return false;
            }
        }

        return parent::delete($pk);
    }
}
