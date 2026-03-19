<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Module\K2Content\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * K2 Content Module Dispatcher
 *
 * @since  3.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   3.0.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $helper = $this->getHelperFactory()->getHelper('K2ContentHelper');
        $data['items'] = $helper->getItems($data['params'], $this->getApplication());

        return $data;
    }
}
