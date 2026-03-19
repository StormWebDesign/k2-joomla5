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
use Joomla\Database\DatabaseDriver;

/**
 * K2 User Table
 *
 * @since  3.0.0
 */
class UserTable extends Table
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
        parent::__construct('#__k2_users', 'id', $db);
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

        // Ensure gender has valid value
        if (!in_array($this->gender, ['m', 'f', 'n'])) {
            $this->gender = 'n';
        }

        return true;
    }
}
