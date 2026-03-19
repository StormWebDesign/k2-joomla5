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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

/**
 * Users list controller class.
 *
 * @since  3.0.0
 */
class UsersController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_USERS';

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since   3.0.0
     */
    public function getModel($name = 'User', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Move users to a different group.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function move()
    {
        $this->checkToken();

        $ids = (array) $this->input->get('cid', [], 'int');
        $ids = ArrayHelper::toInteger($ids);
        $groupId = $this->input->getInt('group');

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('COM_K2_NO_USERS_SELECTED'), 'warning');
        } elseif (!$groupId) {
            $this->app->enqueueMessage(Text::_('COM_K2_SELECT_A_GROUP'), 'warning');
        } else {
            $model = $this->getModel();

            if ($model->move($ids, $groupId)) {
                $this->setMessage(Text::plural('COM_K2_N_USERS_MOVED', count($ids)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_k2&view=users', false));
    }
}
