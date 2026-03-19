<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;

/**
 * K2 Route Helper
 *
 * @since  3.0.0
 */
abstract class RouteHelper
{
    /**
     * Lookup array for menu items
     *
     * @var    array
     * @since  3.0.0
     */
    protected static $lookup = [];

    /**
     * Get the item route
     *
     * @param   integer  $id      Item id
     * @param   integer  $catid   Category id
     * @param   string   $language Language tag
     *
     * @return  string  The item route
     *
     * @since   3.0.0
     */
    public static function getItemRoute($id, $catid = 0, $language = null)
    {
        // Create the link
        $link = 'index.php?option=com_k2&view=item&id=' . $id;

        if ($catid > 0) {
            $link .= '&catid=' . $catid;
        }

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        // Find a menu item
        $menuItem = self::findItemMenuItem($id, $catid, $language);

        if ($menuItem) {
            $link .= '&Itemid=' . $menuItem;
        } else {
            // Try to find a category menu item
            $categoryMenuItem = self::findCategoryMenuItem($catid, $language);

            if ($categoryMenuItem) {
                $link .= '&Itemid=' . $categoryMenuItem;
            }
        }

        return $link;
    }

    /**
     * Get the category route
     *
     * @param   integer  $id        Category id
     * @param   string   $language  Language tag
     *
     * @return  string  The category route
     *
     * @since   3.0.0
     */
    public static function getCategoryRoute($id, $language = null)
    {
        if ($id < 1) {
            return '';
        }

        $link = 'index.php?option=com_k2&view=itemlist&task=category&id=' . $id;

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        // Find a menu item
        $menuItem = self::findCategoryMenuItem($id, $language);

        if ($menuItem) {
            $link .= '&Itemid=' . $menuItem;
        }

        return $link;
    }

    /**
     * Get the tag route
     *
     * @param   string  $tag       Tag name
     * @param   string  $language  Language tag
     *
     * @return  string  The tag route
     *
     * @since   3.0.0
     */
    public static function getTagRoute($tag, $language = null)
    {
        $link = 'index.php?option=com_k2&view=itemlist&task=tag&tag=' . urlencode($tag);

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        // Find a menu item
        $menuItem = self::findTagMenuItem($tag, $language);

        if ($menuItem) {
            $link .= '&Itemid=' . $menuItem;
        }

        return $link;
    }

    /**
     * Get the user route
     *
     * @param   integer  $id        User id
     * @param   string   $language  Language tag
     *
     * @return  string  The user route
     *
     * @since   3.0.0
     */
    public static function getUserRoute($id, $language = null)
    {
        $link = 'index.php?option=com_k2&view=itemlist&task=user&id=' . $id;

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        // Find a menu item
        $menuItem = self::findUserMenuItem($id, $language);

        if ($menuItem) {
            $link .= '&Itemid=' . $menuItem;
        }

        return $link;
    }

    /**
     * Get the date route
     *
     * @param   integer  $year      Year
     * @param   integer  $month     Month (optional)
     * @param   integer  $day       Day (optional)
     * @param   integer  $catid     Category id (optional)
     * @param   string   $language  Language tag
     *
     * @return  string  The date route
     *
     * @since   3.0.0
     */
    public static function getDateRoute($year, $month = 0, $day = 0, $catid = 0, $language = null)
    {
        $link = 'index.php?option=com_k2&view=itemlist&task=date&year=' . $year;

        if ($month > 0) {
            $link .= '&month=' . $month;
        }

        if ($day > 0) {
            $link .= '&day=' . $day;
        }

        if ($catid > 0) {
            $link .= '&catid=' . $catid;
        }

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    /**
     * Get the search route
     *
     * @param   string  $searchword  Search term
     * @param   string  $language    Language tag
     *
     * @return  string  The search route
     *
     * @since   3.0.0
     */
    public static function getSearchRoute($searchword, $language = null)
    {
        $link = 'index.php?option=com_k2&view=itemlist&task=search&searchword=' . urlencode($searchword);

        // Add language filter
        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    /**
     * Find a menu item for an item
     *
     * @param   integer  $itemId    Item id
     * @param   integer  $catid     Category id
     * @param   string   $language  Language tag
     *
     * @return  integer|null  Menu item id or null
     *
     * @since   3.0.0
     */
    protected static function findItemMenuItem($itemId, $catid, $language = null)
    {
        $app = Factory::getApplication();

        if (!$app instanceof SiteApplication) {
            return null;
        }

        $menus = $app->getMenu();
        $items = $menus->getItems('component', 'com_k2');
        $language = $language ?: '*';

        // First, look for an item-specific menu item
        foreach ($items as $item) {
            if (
                isset($item->query['view']) && $item->query['view'] === 'item' &&
                isset($item->query['id']) && (int) $item->query['id'] === (int) $itemId
            ) {
                if ($item->language === '*' || $item->language === $language) {
                    return $item->id;
                }
            }
        }

        return null;
    }

    /**
     * Find a menu item for a category
     *
     * @param   integer  $catid     Category id
     * @param   string   $language  Language tag
     *
     * @return  integer|null  Menu item id or null
     *
     * @since   3.0.0
     */
    protected static function findCategoryMenuItem($catid, $language = null)
    {
        $app = Factory::getApplication();

        if (!$app instanceof SiteApplication) {
            return null;
        }

        $menus = $app->getMenu();
        $items = $menus->getItems('component', 'com_k2');
        $language = $language ?: '*';

        foreach ($items as $item) {
            if (
                isset($item->query['view']) && $item->query['view'] === 'itemlist' &&
                isset($item->query['task']) && $item->query['task'] === 'category' &&
                isset($item->query['id']) && (int) $item->query['id'] === (int) $catid
            ) {
                if ($item->language === '*' || $item->language === $language) {
                    return $item->id;
                }
            }
        }

        // Check parent categories
        $parentCatid = self::getCategoryParent($catid);

        if ($parentCatid) {
            return self::findCategoryMenuItem($parentCatid, $language);
        }

        // Return any K2 menu item as fallback
        foreach ($items as $item) {
            if ($item->language === '*' || $item->language === $language) {
                return $item->id;
            }
        }

        return null;
    }

    /**
     * Find a menu item for a tag
     *
     * @param   string  $tag       Tag name
     * @param   string  $language  Language tag
     *
     * @return  integer|null  Menu item id or null
     *
     * @since   3.0.0
     */
    protected static function findTagMenuItem($tag, $language = null)
    {
        $app = Factory::getApplication();

        if (!$app instanceof SiteApplication) {
            return null;
        }

        $menus = $app->getMenu();
        $items = $menus->getItems('component', 'com_k2');
        $language = $language ?: '*';

        foreach ($items as $item) {
            if (
                isset($item->query['view']) && $item->query['view'] === 'itemlist' &&
                isset($item->query['task']) && $item->query['task'] === 'tag' &&
                isset($item->query['tag']) && $item->query['tag'] === $tag
            ) {
                if ($item->language === '*' || $item->language === $language) {
                    return $item->id;
                }
            }
        }

        // Return any K2 menu item as fallback
        foreach ($items as $item) {
            if ($item->language === '*' || $item->language === $language) {
                return $item->id;
            }
        }

        return null;
    }

    /**
     * Find a menu item for a user
     *
     * @param   integer  $userId    User id
     * @param   string   $language  Language tag
     *
     * @return  integer|null  Menu item id or null
     *
     * @since   3.0.0
     */
    protected static function findUserMenuItem($userId, $language = null)
    {
        $app = Factory::getApplication();

        if (!$app instanceof SiteApplication) {
            return null;
        }

        $menus = $app->getMenu();
        $items = $menus->getItems('component', 'com_k2');
        $language = $language ?: '*';

        foreach ($items as $item) {
            if (
                isset($item->query['view']) && $item->query['view'] === 'itemlist' &&
                isset($item->query['task']) && $item->query['task'] === 'user' &&
                isset($item->query['id']) && (int) $item->query['id'] === (int) $userId
            ) {
                if ($item->language === '*' || $item->language === $language) {
                    return $item->id;
                }
            }
        }

        // Return any K2 menu item as fallback
        foreach ($items as $item) {
            if ($item->language === '*' || $item->language === $language) {
                return $item->id;
            }
        }

        return null;
    }

    /**
     * Get the parent category id
     *
     * @param   integer  $catid  Category id
     *
     * @return  integer  Parent category id or 0
     *
     * @since   3.0.0
     */
    protected static function getCategoryParent($catid)
    {
        static $parents = [];

        if (!isset($parents[$catid])) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select($db->quoteName('parent'))
                ->from($db->quoteName('#__k2_categories'))
                ->where($db->quoteName('id') . ' = ' . (int) $catid);

            $db->setQuery($query);
            $parents[$catid] = (int) $db->loadResult();
        }

        return $parents[$catid];
    }
}
