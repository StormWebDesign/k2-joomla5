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
 * Comments list controller class.
 *
 * @since  3.0.0
 */
class CommentsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_COMMENTS';

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
    public function getModel($name = 'Comment', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Mark comments as spam.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function spam()
    {
        $this->checkToken();

        $ids = (array) $this->input->get('cid', [], 'int');
        $ids = ArrayHelper::toInteger($ids);

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('COM_K2_NO_COMMENTS_SELECTED'), 'warning');
        } else {
            $model = $this->getModel();

            if ($model->spam($ids)) {
                $this->setMessage(Text::plural('COM_K2_N_COMMENTS_MARKED_AS_SPAM', count($ids)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_k2&view=comments', false));
    }

    /**
     * Report spammer.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function reportSpammer()
    {
        $this->checkToken();

        $id = $this->input->getInt('id');

        if ($id) {
            $model = $this->getModel();
            $model->reportSpammer($id);
        }

        $this->setRedirect(Route::_('index.php?option=com_k2&view=comments', false));
    }
}
