<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\View\Items;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Items list view class.
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items.
     *
     * @var    array
     * @since  3.0.0
     */
    protected $items;

    /**
     * The pagination object.
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  3.0.0
     */
    protected $pagination;

    /**
     * The model state.
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.0.0
     */
    protected $state;

    /**
     * Form object for search filters.
     *
     * @var    \Joomla\CMS\Form\Form
     * @since  3.0.0
     */
    public $filterForm;

    /**
     * The active search filters.
     *
     * @var    array
     * @since  3.0.0
     */
    public $activeFilters;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Add toolbar
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_k2');
        $user = Factory::getApplication()->getIdentity();
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_K2_ITEMS'), 'stack k2');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('item.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('items.publish')->listCheck(true);
            $childBar->unpublish('items.unpublish')->listCheck(true);
            $childBar->standardButton('featured')
                ->text('JFEATURE')
                ->task('items.featured')
                ->listCheck(true);
            $childBar->standardButton('unfeatured')
                ->text('JUNFEATURE')
                ->task('items.unfeatured')
                ->listCheck(true);

            if ($canDo->get('core.admin')) {
                $childBar->checkin('items.checkin');
            }

            if ($this->state->get('filter.trash') != '1') {
                $childBar->trash('items.trash')->listCheck(true);
            }
        }

        if ($this->state->get('filter.trash') == '1' && $canDo->get('core.delete')) {
            $toolbar->delete('items.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_k2');
        }

        $toolbar->help('JHELP_COMPONENTS_K2_ITEMS');
    }
}
