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

/**
 * Extra Field Group controller class.
 *
 * @since  3.0.0
 */
class ExtrafieldsgroupController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_EXTRAFIELDSGROUP';

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
        return Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_k2');
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
        return Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_k2');
    }
}
