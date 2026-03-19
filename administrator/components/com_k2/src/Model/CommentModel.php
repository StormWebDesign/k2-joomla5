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

/**
 * K2 Comment Model
 *
 * @since  3.0.0
 */
class CommentModel extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     * @since  3.0.0
     */
    public $typeAlias = 'com_k2.comment';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_COMMENT';

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     *
     * @since   3.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_k2.comment', 'comment', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = Factory::getApplication()->getUserState('com_k2.edit.comment.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
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
    public function getTable($name = 'Comment', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($name, $prefix, $config);
    }
}
