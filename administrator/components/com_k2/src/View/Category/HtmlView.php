<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Category edit view class.
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The \Joomla\CMS\Form\Form object.
     *
     * @var    \Joomla\CMS\Form\Form
     * @since  3.0.0
     */
    protected $form;

    /**
     * The active item.
     *
     * @var    object
     * @since  3.0.0
     */
    protected $item;

    /**
     * The model state.
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.0.0
     */
    protected $state;

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
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

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
        Factory::getApplication()->input->set('hidemainmenu', true);

        $isNew = ($this->item->id == 0);
        $canDo = ContentHelper::getActions('com_k2');

        $title = $isNew ? Text::_('COM_K2_NEW_CATEGORY') : Text::_('COM_K2_EDIT_CATEGORY');
        ToolbarHelper::title($title, 'folder-open k2');

        if ($canDo->get('core.create') || $canDo->get('core.edit')) {
            ToolbarHelper::apply('category.apply');
            ToolbarHelper::saveGroup(
                [
                    ['save', 'category.save'],
                    ['save2new', 'category.save2new'],
                ],
                'btn-success'
            );
        }

        ToolbarHelper::cancel('category.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
