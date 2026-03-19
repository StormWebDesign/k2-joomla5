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
use Joomla\Database\DatabaseDriver;

/**
 * K2 Comment Table
 *
 * @since  3.0.0
 */
class CommentTable extends Table
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
        parent::__construct('#__k2_comments', 'id', $db);

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

        // Check for valid comment text
        if (trim($this->commentText) === '') {
            $this->setError(Text::_('COM_K2_ERROR_COMMENT_TEXT_REQUIRED'));
            return false;
        }

        // Check for valid item
        if (empty($this->itemID)) {
            $this->setError(Text::_('COM_K2_ERROR_COMMENT_ITEM_REQUIRED'));
            return false;
        }

        // Set comment date if not set
        if (empty($this->commentDate)) {
            $this->commentDate = Factory::getDate()->toSql();
        }

        return true;
    }
}
