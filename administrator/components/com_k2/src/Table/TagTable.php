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
 * K2 Tag Table
 *
 * @since  3.0.0
 */
class TagTable extends Table
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
        parent::__construct('#__k2_tags', 'id', $db);

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
            $this->setError(Text::_('COM_K2_ERROR_TAG_NAME_REQUIRED'));
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

            // Delete tag references
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__k2_tags_xref'))
                ->where($db->quoteName('tagID') . ' = ' . (int) $pk);
            $db->setQuery($query);
            $db->execute();
        }

        return parent::delete($pk);
    }
}
