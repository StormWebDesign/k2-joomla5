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
use Joomla\Utilities\ArrayHelper;

/**
 * Extra Fields list controller class.
 *
 * @since  3.0.0
 */
class ExtrafieldsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_EXTRAFIELDS';

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
    public function getModel($name = 'Extrafield', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Save ordering via AJAX.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function saveOrderAjax()
    {
        $this->checkToken();

        $pks = $this->input->post->get('cid', [], 'array');
        $order = $this->input->post->get('order', [], 'array');

        $pks = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);

        $model = $this->getModel();
        $return = $model->saveorder($pks, $order);

        if ($return) {
            echo "1";
        }

        $this->app->close();
    }
}
