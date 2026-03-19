<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Module\K2Content\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * K2 Content Module Helper
 *
 * @since  3.0.0
 */
class K2ContentHelper
{
    use DatabaseAwareTrait;

    /**
     * Get items for the module
     *
     * @param   Registry         $params  Module parameters
     * @param   SiteApplication  $app     Application instance
     *
     * @return  array  Array of items
     *
     * @since   3.0.0
     */
    public function getItems(Registry $params, SiteApplication $app): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $user = $app->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();

        $query = $db->getQuery(true);

        $query->select([
            'a.*',
            'c.name AS category_name',
            'c.alias AS category_alias',
            'u.name AS author_name',
        ])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
            ->where([
                $db->quoteName('a.published') . ' = 1',
                $db->quoteName('a.trash') . ' = 0',
                $db->quoteName('c.published') . ' = 1',
                $db->quoteName('c.trash') . ' = 0',
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                $db->quoteName('c.access') . ' IN (' . implode(',', $groups) . ')',
                '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
                '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
            ])
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now);

        // Source filter
        $source = (int) $params->get('source', 1);

        if ($source === 1) {
            // Specific categories
            $categories = $params->get('category_id', []);

            if (!empty($categories)) {
                if (is_array($categories)) {
                    $categories = array_map('intval', $categories);
                    $query->where($db->quoteName('a.catid') . ' IN (' . implode(',', $categories) . ')');
                } else {
                    $catid = (int) $categories;
                    $query->where($db->quoteName('a.catid') . ' = :catid')
                        ->bind(':catid', $catid, ParameterType::INTEGER);
                }
            }
        } elseif ($source === 2) {
            // Specific items
            $itemIds = $params->get('item_ids', '');

            if (!empty($itemIds)) {
                $itemIds = array_map('intval', explode(',', $itemIds));
                $query->where($db->quoteName('a.id') . ' IN (' . implode(',', $itemIds) . ')');
            }
        }

        // Featured filter
        $featured = $params->get('itemFeatured', '');

        if ($featured !== '') {
            $featured = (int) $featured;
            $query->where($db->quoteName('a.featured') . ' = :featured')
                ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Language filter
        $languageFilter = $app->getLanguageFilter();

        if ($languageFilter) {
            $language = $app->getLanguage()->getTag();
            $query->where([
                $db->quoteName('a.language') . ' IN (' . $db->quote($language) . ',' . $db->quote('*') . ')',
                $db->quoteName('c.language') . ' IN (' . $db->quote($language) . ',' . $db->quote('*') . ')',
            ]);
        }

        // Ordering
        $ordering = $params->get('itemOrdering', 'created');
        $direction = $params->get('itemOrderingDirection', 'DESC');

        switch ($ordering) {
            case 'created':
                $query->order($db->quoteName('a.created') . ' ' . $direction);
                break;
            case 'modified':
                $query->order($db->quoteName('a.modified') . ' ' . $direction);
                break;
            case 'publishUp':
                $query->order($db->quoteName('a.publish_up') . ' ' . $direction);
                break;
            case 'hits':
                $query->order($db->quoteName('a.hits') . ' ' . $direction);
                break;
            case 'ordering':
                $query->order($db->quoteName('a.ordering') . ' ' . $direction);
                break;
            case 'title':
                $query->order($db->quoteName('a.title') . ' ' . $direction);
                break;
            case 'random':
                $query->order('RAND()');
                break;
            default:
                $query->order($db->quoteName('a.created') . ' DESC');
                break;
        }

        // Limit
        $limit = (int) $params->get('itemCount', 5);
        $db->setQuery($query, 0, $limit);

        $items = $db->loadObjectList();

        if (empty($items)) {
            return [];
        }

        // Process items
        foreach ($items as &$item) {
            $item = $this->prepareItem($item, $params);
        }

        return $items;
    }

    /**
     * Prepare an item for display
     *
     * @param   object    $item    The item object
     * @param   Registry  $params  Module parameters
     *
     * @return  object  The prepared item
     *
     * @since   3.0.0
     */
    protected function prepareItem(object $item, Registry $params): object
    {
        // Create category object
        $item->category = new \stdClass();
        $item->category->id = $item->catid;
        $item->category->name = $item->category_name;
        $item->category->alias = $item->category_alias;
        $item->category->link = Route::_('index.php?option=com_k2&view=itemlist&task=category&id=' . $item->catid);

        // Create author object
        $item->author = new \stdClass();
        $item->author->name = $item->author_name ?: $item->created_by_alias;
        $item->author->id = $item->created_by;

        // Create item link
        $item->link = Route::_('index.php?option=com_k2&view=item&id=' . $item->id);

        // Process images
        $item = $this->prepareItemImages($item, $params);

        // Process intro text
        if ($params->get('itemIntroText', 1)) {
            $wordLimit = (int) $params->get('itemIntroTextWordLimit', 50);
            $item->introtext = $this->wordLimit(strip_tags($item->introtext), $wordLimit);
        }

        return $item;
    }

    /**
     * Prepare item images
     *
     * @param   object    $item    The item object
     * @param   Registry  $params  Module parameters
     *
     * @return  object  The item with image data
     *
     * @since   3.0.0
     */
    protected function prepareItemImages(object $item, Registry $params): object
    {
        $imageFilenamePrefix = md5('Image' . $item->id);
        $imagePathPrefix = Uri::root(true) . '/media/k2/items/cache/' . $imageFilenamePrefix;
        $imageSize = $params->get('itemImageSize', 'Medium');

        // Check if generic image exists
        $genericPath = JPATH_SITE . '/media/k2/items/cache/' . $imageFilenamePrefix . '_Generic.jpg';

        if (file_exists($genericPath)) {
            $item->imageGeneric = $imagePathPrefix . '_Generic.jpg';
            $item->imageXSmall = $imagePathPrefix . '_XS.jpg';
            $item->imageSmall = $imagePathPrefix . '_S.jpg';
            $item->imageMedium = $imagePathPrefix . '_M.jpg';
            $item->imageLarge = $imagePathPrefix . '_L.jpg';
            $item->imageXLarge = $imagePathPrefix . '_XL.jpg';

            // Set the configured image size
            $imageProperty = 'image' . $imageSize;
            $item->image = $item->$imageProperty ?? $item->imageMedium;
        } else {
            $item->imageGeneric = '';
            $item->imageXSmall = '';
            $item->imageSmall = '';
            $item->imageMedium = '';
            $item->imageLarge = '';
            $item->imageXLarge = '';
            $item->image = '';
        }

        return $item;
    }

    /**
     * Limit text by word count
     *
     * @param   string  $str    Text to limit
     * @param   int     $limit  Word limit
     *
     * @return  string  Limited text
     *
     * @since   3.0.0
     */
    protected function wordLimit(string $str, int $limit = 50): string
    {
        if (StringHelper::trim($str) === '') {
            return $str;
        }

        $str = preg_replace(["/\r|\n/u", "/\t/u", "/\s\s+/u"], [' ', ' ', ' '], $str);

        preg_match('/\s*(?:\S*\s*){' . $limit . '}/u', $str, $matches);

        if (empty($matches[0])) {
            return $str;
        }

        $endChar = StringHelper::strlen($matches[0]) < StringHelper::strlen($str) ? '...' : '';

        return StringHelper::rtrim($matches[0]) . $endChar;
    }
}
