<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * K2 Router
 *
 * @since  3.0.0
 */
class Router extends RouterView
{
    /**
     * The database object
     *
     * @var    DatabaseInterface
     * @since  3.0.0
     */
    protected $db;

    /**
     * K2 Router constructor
     *
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     *
     * @since   3.0.0
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);

        // Item view
        $item = new RouterViewConfiguration('item');
        $item->setKey('id');
        $this->registerView($item);

        // Itemlist view
        $itemlist = new RouterViewConfiguration('itemlist');
        $itemlist->setKey('id');
        $this->registerView($itemlist);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Build method for URLs
     *
     * @param   array  &$query  The query parameters
     *
     * @return  array  Segments for the URL
     *
     * @since   3.0.0
     */
    public function build(&$query)
    {
        $segments = [];

        // Handle view
        $view = isset($query['view']) ? $query['view'] : 'itemlist';

        // Handle task for itemlist
        $task = isset($query['task']) ? $query['task'] : '';

        // If we have a menu item for this exact query, use it
        if (isset($query['Itemid'])) {
            $menuItem = $this->menu->getItem($query['Itemid']);

            if ($menuItem) {
                // Check if menu item matches the request
                $menuQuery = $menuItem->query;

                if ($view === 'item' && isset($query['id'])) {
                    if (
                        isset($menuQuery['view']) && $menuQuery['view'] === 'item' &&
                        isset($menuQuery['id']) && (int) $menuQuery['id'] === (int) $query['id']
                    ) {
                        // Menu item is for this specific item, no segments needed
                        unset($query['view'], $query['id'], $query['catid']);
                        return $segments;
                    }
                }

                if ($view === 'itemlist' && $task === 'category' && isset($query['id'])) {
                    if (
                        isset($menuQuery['view']) && $menuQuery['view'] === 'itemlist' &&
                        isset($menuQuery['task']) && $menuQuery['task'] === 'category' &&
                        isset($menuQuery['id']) && (int) $menuQuery['id'] === (int) $query['id']
                    ) {
                        // Menu item is for this category, no segments needed
                        unset($query['view'], $query['task'], $query['id']);
                        return $segments;
                    }
                }
            }
        }

        // Build segments based on view/task
        if ($view === 'item' && isset($query['id'])) {
            $id = (int) $query['id'];
            $item = $this->getItem($id);

            if ($item) {
                // Add category segment
                if ($item->category_alias) {
                    $segments[] = $item->category_alias;
                }

                // Add item segment
                $segments[] = $item->alias ?: $id;
            } else {
                $segments[] = $id;
            }

            unset($query['view'], $query['id'], $query['catid']);
        } elseif ($view === 'itemlist') {
            if ($task === 'category' && isset($query['id'])) {
                $catid = (int) $query['id'];
                $category = $this->getCategory($catid);

                if ($category) {
                    // Build category path
                    $path = $this->getCategoryPath($catid);
                    $segments = array_merge($segments, $path);
                } else {
                    $segments[] = $catid;
                }

                unset($query['view'], $query['task'], $query['id']);
            } elseif ($task === 'tag' && isset($query['tag'])) {
                $segments[] = 'tag';
                $segments[] = $query['tag'];
                unset($query['view'], $query['task'], $query['tag']);
            } elseif ($task === 'user' && isset($query['id'])) {
                $segments[] = 'user';
                $segments[] = $query['id'];
                unset($query['view'], $query['task'], $query['id']);
            } elseif ($task === 'date') {
                $segments[] = 'date';

                if (isset($query['year'])) {
                    $segments[] = $query['year'];
                    unset($query['year']);
                }

                if (isset($query['month'])) {
                    $segments[] = $query['month'];
                    unset($query['month']);
                }

                if (isset($query['day'])) {
                    $segments[] = $query['day'];
                    unset($query['day']);
                }

                unset($query['view'], $query['task']);
            } elseif ($task === 'search') {
                $segments[] = 'search';

                if (isset($query['searchword'])) {
                    $segments[] = $query['searchword'];
                    unset($query['searchword']);
                }

                unset($query['view'], $query['task']);
            }
        }

        return $segments;
    }

    /**
     * Parse method for URLs
     *
     * @param   array  &$segments  The URL segments
     *
     * @return  array  Query parameters
     *
     * @since   3.0.0
     */
    public function parse(&$segments)
    {
        $vars = [];
        $count = count($segments);

        if ($count === 0) {
            return $vars;
        }

        // Check for special tasks
        $firstSegment = $segments[0];

        switch ($firstSegment) {
            case 'tag':
                $vars['view'] = 'itemlist';
                $vars['task'] = 'tag';

                if (isset($segments[1])) {
                    $vars['tag'] = $segments[1];
                }

                return $vars;

            case 'user':
                $vars['view'] = 'itemlist';
                $vars['task'] = 'user';

                if (isset($segments[1])) {
                    $vars['id'] = (int) $segments[1];
                }

                return $vars;

            case 'date':
                $vars['view'] = 'itemlist';
                $vars['task'] = 'date';

                if (isset($segments[1])) {
                    $vars['year'] = (int) $segments[1];
                }

                if (isset($segments[2])) {
                    $vars['month'] = (int) $segments[2];
                }

                if (isset($segments[3])) {
                    $vars['day'] = (int) $segments[3];
                }

                return $vars;

            case 'search':
                $vars['view'] = 'itemlist';
                $vars['task'] = 'search';

                if (isset($segments[1])) {
                    $vars['searchword'] = $segments[1];
                }

                return $vars;
        }

        // Try to find a category or item
        if ($count === 1) {
            // Could be a category or an item
            $alias = $segments[0];

            // First try to find as category
            $category = $this->getCategoryByAlias($alias);

            if ($category) {
                $vars['view'] = 'itemlist';
                $vars['task'] = 'category';
                $vars['id'] = $category->id;

                return $vars;
            }

            // Try as item
            $item = $this->getItemByAlias($alias);

            if ($item) {
                $vars['view'] = 'item';
                $vars['id'] = $item->id;

                return $vars;
            }
        } elseif ($count >= 2) {
            // Last segment is likely the item, previous segments are category path
            $itemAlias = array_pop($segments);

            // Build category path
            $categoryAlias = implode('/', $segments);

            // Try to find item by alias in category path context
            $item = $this->getItemByAliasAndCategoryPath($itemAlias, $segments);

            if ($item) {
                $vars['view'] = 'item';
                $vars['id'] = $item->id;

                return $vars;
            }

            // Maybe it's a nested category
            $segments[] = $itemAlias;
            $category = $this->getCategoryByPath($segments);

            if ($category) {
                $vars['view'] = 'itemlist';
                $vars['task'] = 'category';
                $vars['id'] = $category->id;

                return $vars;
            }
        }

        return $vars;
    }

    /**
     * Get an item by ID
     *
     * @param   integer  $id  The item ID
     *
     * @return  object|null  The item or null
     *
     * @since   3.0.0
     */
    protected function getItem($id)
    {
        $query = $this->db->getQuery(true);

        $query->select([
            'a.id',
            'a.alias',
            'a.catid',
            'c.alias AS category_alias',
        ])
            ->from($this->db->quoteName('#__k2_items', 'a'))
            ->join('LEFT', $this->db->quoteName('#__k2_categories', 'c'), $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('a.catid'))
            ->where($this->db->quoteName('a.id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query);

        return $this->db->loadObject();
    }

    /**
     * Get an item by alias
     *
     * @param   string  $alias  The item alias
     *
     * @return  object|null  The item or null
     *
     * @since   3.0.0
     */
    protected function getItemByAlias($alias)
    {
        $query = $this->db->getQuery(true);

        $query->select(['id', 'alias', 'catid'])
            ->from($this->db->quoteName('#__k2_items'))
            ->where($this->db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias);

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadObject();
    }

    /**
     * Get an item by alias and category path
     *
     * @param   string  $alias          The item alias
     * @param   array   $categoryPath   The category path segments
     *
     * @return  object|null  The item or null
     *
     * @since   3.0.0
     */
    protected function getItemByAliasAndCategoryPath($alias, $categoryPath)
    {
        // Get category from path
        $category = $this->getCategoryByPath($categoryPath);

        if (!$category) {
            return null;
        }

        $query = $this->db->getQuery(true);

        $query->select(['id', 'alias', 'catid'])
            ->from($this->db->quoteName('#__k2_items'))
            ->where([
                $this->db->quoteName('alias') . ' = :alias',
                $this->db->quoteName('catid') . ' = :catid',
            ])
            ->bind(':alias', $alias)
            ->bind(':catid', $category->id, ParameterType::INTEGER);

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadObject();
    }

    /**
     * Get a category by ID
     *
     * @param   integer  $id  The category ID
     *
     * @return  object|null  The category or null
     *
     * @since   3.0.0
     */
    protected function getCategory($id)
    {
        $query = $this->db->getQuery(true);

        $query->select(['id', 'alias', 'parent', 'name'])
            ->from($this->db->quoteName('#__k2_categories'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query);

        return $this->db->loadObject();
    }

    /**
     * Get a category by alias
     *
     * @param   string  $alias  The category alias
     *
     * @return  object|null  The category or null
     *
     * @since   3.0.0
     */
    protected function getCategoryByAlias($alias)
    {
        $query = $this->db->getQuery(true);

        $query->select(['id', 'alias', 'parent', 'name'])
            ->from($this->db->quoteName('#__k2_categories'))
            ->where($this->db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias);

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadObject();
    }

    /**
     * Get a category by path segments
     *
     * @param   array  $segments  The path segments
     *
     * @return  object|null  The category or null
     *
     * @since   3.0.0
     */
    protected function getCategoryByPath($segments)
    {
        $parent = 0;
        $category = null;

        foreach ($segments as $alias) {
            $query = $this->db->getQuery(true);

            $query->select(['id', 'alias', 'parent', 'name'])
                ->from($this->db->quoteName('#__k2_categories'))
                ->where([
                    $this->db->quoteName('alias') . ' = :alias',
                    $this->db->quoteName('parent') . ' = :parent',
                ])
                ->bind(':alias', $alias)
                ->bind(':parent', $parent, ParameterType::INTEGER);

            $this->db->setQuery($query, 0, 1);
            $category = $this->db->loadObject();

            if (!$category) {
                return null;
            }

            $parent = $category->id;
        }

        return $category;
    }

    /**
     * Get the category path (aliases)
     *
     * @param   integer  $id  The category ID
     *
     * @return  array  Array of category aliases
     *
     * @since   3.0.0
     */
    protected function getCategoryPath($id)
    {
        $path = [];
        $category = $this->getCategory($id);

        while ($category) {
            array_unshift($path, $category->alias);

            if ($category->parent > 0) {
                $category = $this->getCategory($category->parent);
            } else {
                break;
            }
        }

        return $path;
    }
}
