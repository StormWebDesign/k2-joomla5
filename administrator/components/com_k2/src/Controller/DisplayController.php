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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/**
 * K2 Component Controller
 *
 * @since  3.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $default_view = 'items';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  static  This object to support chaining.
     *
     * @since   3.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $view = $this->input->get('view', 'items');
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Check for edit form.
        if ($view === 'item' && $layout === 'edit' && !$this->checkEditId('com_k2.edit.item', $id)) {
            $this->setMessage(\Joomla\CMS\Language\Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_k2&view=items', false));
            return $this;
        }

        return parent::display($cachable, $urlparams);
    }
}
