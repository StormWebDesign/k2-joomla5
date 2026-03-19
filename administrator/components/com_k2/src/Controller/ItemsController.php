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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

/**
 * Items list controller class.
 *
 * @since  3.0.0
 */
class ItemsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_ITEMS';

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   MVCFactoryInterface  $factory  The factory.
     * @param   CMSApplication       $app      The Application for the dispatcher
     * @param   Input                $input    Input
     *
     * @since   3.0.0
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $this->registerTask('unfeatured', 'featured');
        $this->registerTask('trash', 'publish');
        $this->registerTask('restore', 'publish');
    }

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
    public function getModel($name = 'Item', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Method to toggle the featured setting of a list of items.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function featured()
    {
        // Check for request forgeries
        $this->checkToken();

        $ids = (array) $this->input->get('cid', [], 'int');
        $ids = ArrayHelper::toInteger($ids);
        $values = ['featured' => 1, 'unfeatured' => 0];
        $task = $this->getTask();
        $value = ArrayHelper::getValue($values, $task, 0, 'int');

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('COM_K2_NO_ITEMS_SELECTED'), 'warning');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Change the state of the records.
            if (!$model->featured($ids, $value)) {
                $this->app->enqueueMessage($model->getError(), 'error');
            } else {
                if ($value == 1) {
                    $this->setMessage(Text::plural('COM_K2_N_ITEMS_FEATURED', count($ids)));
                } else {
                    $this->setMessage(Text::plural('COM_K2_N_ITEMS_UNFEATURED', count($ids)));
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_k2&view=items', false));
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function saveOrderAjax()
    {
        // Check for request forgeries
        $this->checkToken();

        $pks = $this->input->post->get('cid', [], 'array');
        $order = $this->input->post->get('order', [], 'array');

        // Sanitize the input
        $pks = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);

        // Get the model
        $model = $this->getModel();

        // Save the ordering
        $return = $model->saveorder($pks, $order);

        if ($return) {
            echo "1";
        }

        // Close the application
        $this->app->close();
    }
}
