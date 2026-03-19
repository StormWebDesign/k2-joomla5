<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * K2 Site Display Controller
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
    protected $default_view = 'itemlist';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters
     *
     * @return  static  This object to support chaining.
     *
     * @since   3.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $view = $this->input->get('view', $this->default_view);
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Check for edit form
        if ($view === 'item' && $layout === 'edit' && !$this->checkEditId('com_k2.edit.item', $id)) {
            $app->enqueueMessage(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

            return $this;
        }

        // Enable caching for non-logged in users viewing itemlist or item
        $cachable = true;

        // Don't cache if user is logged in
        if ($user->get('id')) {
            $cachable = false;
        }

        $safeurlparams = [
            'catid'      => 'INT',
            'id'         => 'INT',
            'cid'        => 'ARRAY',
            'year'       => 'INT',
            'month'      => 'INT',
            'limit'      => 'UINT',
            'limitstart' => 'UINT',
            'showall'    => 'INT',
            'return'     => 'BASE64',
            'filter'     => 'STRING',
            'filter_order' => 'CMD',
            'filter_order_Dir' => 'CMD',
            'filter_search' => 'STRING',
            'tag'        => 'STRING',
            'print'      => 'BOOLEAN',
            'lang'       => 'CMD',
            'Itemid'     => 'INT',
        ];

        return parent::display($cachable, $safeurlparams);
    }
}
