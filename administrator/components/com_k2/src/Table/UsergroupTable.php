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
use Joomla\Registry\Registry;

/**
 * K2 User Group Table
 *
 * @since  3.0.0
 */
class UsergroupTable extends Table
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
        parent::__construct('#__k2_user_groups', 'id', $db);
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
            $this->setError(Text::_('COM_K2_ERROR_USERGROUP_NAME_REQUIRED'));
            return false;
        }

        // Handle permissions
        if (is_array($this->permissions)) {
            $registry = new Registry($this->permissions);
            $this->permissions = $registry->toString();
        }

        return true;
    }
}
