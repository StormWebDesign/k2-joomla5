<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\View\Media;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

/**
 * Media manager view class.
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView
{
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
        $this->addToolbar();

        // Get params
        $params = ComponentHelper::getParams('com_media');
        $root = $params->get('file_path', 'images');

        // Connector URL with token
        $this->connectorUrl = Uri::root() . 'administrator/index.php?option=com_k2&task=media.connector&' . Session::getFormToken() . '=1';
        $this->baseUrl = Uri::root() . $root . '/';

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
        ToolbarHelper::title(Text::_('COM_K2_MEDIA_MANAGER'), 'images k2');
    }
}
