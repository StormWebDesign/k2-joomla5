<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

/**
 * K2 Component class
 *
 * @since  3.0.0
 */
class K2Component extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    RouterServiceInterface
{
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

    /**
     * K2 version constant
     *
     * @var    string
     * @since  3.0.0
     */
    public const VERSION = '3.0.0';

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function boot(ContainerInterface $container): void
    {
        // Define K2 version constant for backward compatibility
        if (!defined('K2_CURRENT_VERSION')) {
            define('K2_CURRENT_VERSION', self::VERSION);
        }

        // Define K2 Joomla version constant (always 50 for Joomla 5)
        if (!defined('K2_JVERSION')) {
            define('K2_JVERSION', '50');
        }
    }

    /**
     * Returns the table name for the category service
     *
     * @return  string  The table name
     *
     * @since   3.0.0
     */
    public function getTableNameForSection(string $section = null): string
    {
        return '#__k2_categories';
    }

    /**
     * Returns the category context
     *
     * @param   string|null  $section  The section
     *
     * @return  string  The context
     *
     * @since   3.0.0
     */
    protected function getStateColumnForSection(string $section = null): string
    {
        return 'published';
    }

    /**
     * Returns the count items method name
     *
     * @param   string  $section  The section
     *
     * @return  array  The count items data
     *
     * @since   3.0.0
     */
    public function countItems(array $items, string $section = null): void
    {
        $db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');

        foreach ($items as $item) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__k2_items')
                ->where($db->quoteName('catid') . ' = ' . (int) $item->id)
                ->where($db->quoteName('trash') . ' = 0');

            $db->setQuery($query);

            $item->count_published = $db->loadResult();
            $item->count_unpublished = 0;
            $item->count_archived = 0;
            $item->count_trashed = 0;
        }
    }
}
