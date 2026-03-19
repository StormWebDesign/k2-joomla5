<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Component\K2\Site\Helper\RouteHelper;

/**
 * K2 Itemlist Model for Site
 *
 * @since  3.0.0
 */
class ItemlistModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $_context = 'com_k2.itemlist';

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   3.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'published', 'a.published',
                'access', 'a.access',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'featured', 'a.featured',
                'hits', 'a.hits',
                'catid', 'a.catid',
                'category_title', 'c.name',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $app = Factory::getApplication();
        $params = $app->getParams();

        // List state information
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $params->get('num_leading_items', 10), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // Filtering
        $this->setState('filter.published', 1);
        $this->setState('filter.trash', 0);

        // Set access filter
        $this->setState('filter.access', true);

        // Set language filter
        $this->setState('filter.language', $app->getLanguageFilter());

        // Category filter
        $catid = $app->input->getInt('id', $params->get('category', 0));
        $this->setState('filter.category_id', $catid);

        // Task-based filtering
        $task = $app->input->get('task', '');
        $this->setState('filter.task', $task);

        // Tag filter
        $tag = $app->input->getString('tag', '');
        $this->setState('filter.tag', $tag);

        // User filter
        $userId = $app->input->getInt('id', 0);
        if ($task === 'user') {
            $this->setState('filter.author_id', $userId);
        }

        // Date filters
        $year = $app->input->getInt('year', 0);
        $month = $app->input->getInt('month', 0);
        $day = $app->input->getInt('day', 0);

        $this->setState('filter.year', $year);
        $this->setState('filter.month', $month);
        $this->setState('filter.day', $day);

        // Search filter
        $searchword = $app->input->getString('searchword', '');
        $this->setState('filter.search', $searchword);

        // Featured filter
        $featured = $params->get('itemlistFeaturedItems', '');
        $this->setState('filter.featured', $featured);

        // Ordering
        $orderby = $params->get('catOrdering', 'ordering');

        switch ($orderby) {
            case 'date':
                $ordering = 'a.created';
                $direction = 'ASC';
                break;
            case 'rdate':
                $ordering = 'a.created';
                $direction = 'DESC';
                break;
            case 'alpha':
                $ordering = 'a.title';
                $direction = 'ASC';
                break;
            case 'ralpha':
                $ordering = 'a.title';
                $direction = 'DESC';
                break;
            case 'order':
            case 'ordering':
                $ordering = 'a.ordering';
                $direction = 'ASC';
                break;
            case 'rorder':
                $ordering = 'a.ordering';
                $direction = 'DESC';
                break;
            case 'hits':
                $ordering = 'a.hits';
                $direction = 'DESC';
                break;
            case 'rand':
                $ordering = 'RAND()';
                $direction = '';
                break;
            case 'best':
                $ordering = 'a.rating';
                $direction = 'DESC';
                break;
            case 'modified':
                $ordering = 'a.modified';
                $direction = 'DESC';
                break;
            case 'publishUp':
                $ordering = 'a.publish_up';
                $direction = 'DESC';
                break;
            default:
                $ordering = 'a.ordering';
                $direction = 'ASC';
                break;
        }

        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);

        // Store params
        $this->setState('params', $params);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   3.0.0
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.author_id');
        $id .= ':' . $this->getState('filter.tag');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');

        return parent::getStoreId($id);
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery  A DatabaseQuery object
     *
     * @since   3.0.0
     */
    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();

        $query->select([
            'a.*',
            'c.name AS category_title',
            'c.alias AS category_alias',
            'c.access AS category_access',
            'c.extraFieldsGroup AS category_extra_fields_group',
            'u.name AS author_name',
            'u.username AS author_username',
        ])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        // Filter by trash state
        $trash = $this->getState('filter.trash');

        if (is_numeric($trash)) {
            $query->where($db->quoteName('a.trash') . ' = :trash')
                ->bind(':trash', $trash, ParameterType::INTEGER);
        }

        // Filter by category state
        $query->where([
            $db->quoteName('c.published') . ' = 1',
            $db->quoteName('c.trash') . ' = 0',
        ]);

        // Filter by access level
        if ($this->getState('filter.access')) {
            $groups = $user->getAuthorisedViewLevels();
            $query->where([
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                $db->quoteName('c.access') . ' IN (' . implode(',', $groups) . ')',
            ]);
        }

        // Filter by category
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId) && $categoryId > 0) {
            $params = $this->getState('params');
            $includeChildren = $params ? $params->get('catCatalogMode', 0) : 0;

            if ($includeChildren) {
                $categoryIds = $this->getCategoryChildren($categoryId);
                $categoryIds[] = $categoryId;
                $query->where($db->quoteName('a.catid') . ' IN (' . implode(',', array_map('intval', $categoryIds)) . ')');
            } else {
                $query->where($db->quoteName('a.catid') . ' = :catid')
                    ->bind(':catid', $categoryId, ParameterType::INTEGER);
            }
        }

        // Filter by author
        $authorId = $this->getState('filter.author_id');

        if (is_numeric($authorId) && $authorId > 0) {
            $query->where($db->quoteName('a.created_by') . ' = :authorId')
                ->bind(':authorId', $authorId, ParameterType::INTEGER);
        }

        // Filter by tag
        $tag = $this->getState('filter.tag');

        if (!empty($tag)) {
            $query->join('INNER', $db->quoteName('#__k2_tags_xref', 'tx'), $db->quoteName('tx.itemID') . ' = ' . $db->quoteName('a.id'))
                ->join('INNER', $db->quoteName('#__k2_tags', 't'), $db->quoteName('t.id') . ' = ' . $db->quoteName('tx.tagID'))
                ->where([
                    $db->quoteName('t.name') . ' = :tag',
                    $db->quoteName('t.published') . ' = 1',
                ])
                ->bind(':tag', $tag);
        }

        // Filter by date
        $year = $this->getState('filter.year');
        $month = $this->getState('filter.month');
        $day = $this->getState('filter.day');

        if ($year) {
            $query->where('YEAR(' . $db->quoteName('a.created') . ') = :year')
                ->bind(':year', $year, ParameterType::INTEGER);
        }

        if ($month) {
            $query->where('MONTH(' . $db->quoteName('a.created') . ') = :month')
                ->bind(':month', $month, ParameterType::INTEGER);
        }

        if ($day) {
            $query->where('DAY(' . $db->quoteName('a.created') . ') = :day')
                ->bind(':day', $day, ParameterType::INTEGER);
        }

        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . trim($search) . '%';
            $query->where('(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.introtext') . ' LIKE :search2 OR ' . $db->quoteName('a.fulltext') . ' LIKE :search3)')
                ->bind(':search1', $search)
                ->bind(':search2', $search)
                ->bind(':search3', $search);
        }

        // Filter by featured
        $featured = $this->getState('filter.featured');

        if ($featured === '1') {
            $query->where($db->quoteName('a.featured') . ' = 1');
        } elseif ($featured === '0') {
            $query->where($db->quoteName('a.featured') . ' = 0');
        }

        // Filter by publish dates
        $query->where([
            '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
            '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
        ])
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now);

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->where([
                $db->quoteName('a.language') . ' IN (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')',
                $db->quoteName('c.language') . ' IN (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')',
            ]);
        }

        // Ordering
        $ordering = $this->getState('list.ordering', 'a.ordering');
        $direction = $this->getState('list.direction', 'ASC');

        if ($ordering === 'RAND()') {
            $query->order('RAND()');
        } else {
            $query->order($db->escape($ordering) . ' ' . $db->escape($direction));
        }

        return $query;
    }

    /**
     * Get category children
     *
     * @param   integer  $categoryId  The category id
     *
     * @return  array  Array of child category ids
     *
     * @since   3.0.0
     */
    protected function getCategoryChildren($categoryId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__k2_categories'))
            ->where([
                $db->quoteName('parent') . ' = :parentId',
                $db->quoteName('published') . ' = 1',
                $db->quoteName('trash') . ' = 0',
            ])
            ->bind(':parentId', $categoryId, ParameterType::INTEGER);

        $db->setQuery($query);
        $children = $db->loadColumn();

        $result = [];

        foreach ($children as $childId) {
            $result[] = $childId;
            $result = array_merge($result, $this->getCategoryChildren($childId));
        }

        return $result;
    }

    /**
     * Method to get an array of data items with full preparation
     *
     * @return  array  An array of prepared items
     *
     * @since   3.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (empty($items)) {
            return [];
        }

        $params = $this->getState('params');

        foreach ($items as &$item) {
            $item = $this->prepareItem($item);
        }

        return $items;
    }

    /**
     * Prepare item data
     *
     * @param   object  $item  The item object
     *
     * @return  object  The prepared item
     *
     * @since   3.0.0
     */
    protected function prepareItem($item)
    {
        // Create a category object
        $item->category = new \stdClass();
        $item->category->id = $item->catid;
        $item->category->name = $item->category_title;
        $item->category->alias = $item->category_alias;
        $item->category->access = $item->category_access;
        $item->category->link = Route::_(RouteHelper::getCategoryRoute($item->catid));

        // Create author object
        $item->author = new \stdClass();
        $item->author->name = $item->author_name ?: $item->created_by_alias;
        $item->author->username = $item->author_username;
        $item->author->id = $item->created_by;
        $item->author->link = Route::_(RouteHelper::getUserRoute($item->created_by));

        // Create item link
        $item->link = Route::_(RouteHelper::getItemRoute($item->id, $item->catid));

        // Get tags
        $item->tags = $this->getItemTags($item->id);

        // Process images
        $item = $this->prepareItemImages($item);

        // Prepare text
        $item->text = $item->introtext;
        $item->rawTitle = $item->title;

        // Event placeholders
        $item->event = new \stdClass();
        $item->event->BeforeDisplay = '';
        $item->event->AfterDisplay = '';
        $item->event->AfterDisplayTitle = '';
        $item->event->BeforeDisplayContent = '';
        $item->event->AfterDisplayContent = '';
        $item->event->K2BeforeDisplay = '';
        $item->event->K2AfterDisplay = '';
        $item->event->K2AfterDisplayTitle = '';
        $item->event->K2BeforeDisplayContent = '';
        $item->event->K2AfterDisplayContent = '';

        // Comments count
        $item->numOfComments = $this->getCommentsCount($item->id);

        // Item params
        $params = ComponentHelper::getParams('com_k2');
        $itemParams = new Registry($item->params);
        $params->merge($itemParams);
        $item->params = $params;

        return $item;
    }

    /**
     * Process item images
     *
     * @param   object  $item  The item
     *
     * @return  object  The item with image data
     *
     * @since   3.0.0
     */
    protected function prepareItemImages($item)
    {
        $params = ComponentHelper::getParams('com_k2');
        $imageFilenamePrefix = md5('Image' . $item->id);
        $imagePathPrefix = Uri::root(true) . '/media/k2/items/cache/' . $imageFilenamePrefix;

        // Image timestamp
        $imageTimestamp = '';
        $dateModified = $item->modified ?: '';

        if ($params->get('imageTimestamp', 1) && $dateModified) {
            $imageTimestamp = '?t=' . date('Ymd_His', strtotime($dateModified));
        }

        // Check if generic image exists
        $genericPath = JPATH_SITE . '/media/k2/items/cache/' . $imageFilenamePrefix . '_Generic.jpg';

        if (file_exists($genericPath)) {
            $item->imageGeneric = $imagePathPrefix . '_Generic.jpg' . $imageTimestamp;
            $item->imageXSmall = $imagePathPrefix . '_XS.jpg' . $imageTimestamp;
            $item->imageSmall = $imagePathPrefix . '_S.jpg' . $imageTimestamp;
            $item->imageMedium = $imagePathPrefix . '_M.jpg' . $imageTimestamp;
            $item->imageLarge = $imagePathPrefix . '_L.jpg' . $imageTimestamp;
            $item->imageXLarge = $imagePathPrefix . '_XL.jpg' . $imageTimestamp;

            $item->image = $item->imageMedium;
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
     * Get item tags
     *
     * @param   integer  $itemId  The item id
     *
     * @return  array  Array of tag objects
     *
     * @since   3.0.0
     */
    protected function getItemTags($itemId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(['t.id', 't.name'])
            ->from($db->quoteName('#__k2_tags', 't'))
            ->join('INNER', $db->quoteName('#__k2_tags_xref', 'x'), $db->quoteName('x.tagID') . ' = ' . $db->quoteName('t.id'))
            ->where([
                $db->quoteName('x.itemID') . ' = :itemId',
                $db->quoteName('t.published') . ' = 1',
            ])
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        $db->setQuery($query);
        $tags = $db->loadObjectList();

        foreach ($tags as &$tag) {
            $tag->link = Route::_(RouteHelper::getTagRoute($tag->name));
        }

        return $tags;
    }

    /**
     * Get comments count for an item
     *
     * @param   integer  $itemId  The item id
     *
     * @return  integer  Number of comments
     *
     * @since   3.0.0
     */
    protected function getCommentsCount($itemId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('COUNT(*)')
            ->from($db->quoteName('#__k2_comments'))
            ->where([
                $db->quoteName('itemID') . ' = :itemId',
                $db->quoteName('published') . ' = 1',
            ])
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Get category
     *
     * @param   integer  $categoryId  The category id
     *
     * @return  object|null  The category object
     *
     * @since   3.0.0
     */
    public function getCategory($categoryId = null)
    {
        $categoryId = $categoryId ?: $this->getState('filter.category_id');

        if (!$categoryId) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__k2_categories'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $categoryId, ParameterType::INTEGER);

        $db->setQuery($query);
        $category = $db->loadObject();

        if ($category) {
            $category->link = Route::_(RouteHelper::getCategoryRoute($category->id));

            // Get params
            $params = new Registry($category->params);
            $category->params = $params;
        }

        return $category;
    }

    /**
     * Get author's latest items
     *
     * @param   integer  $excludeId  Item id to exclude
     * @param   integer  $limit      Number of items
     * @param   integer  $authorId   Author id
     *
     * @return  array  Array of item objects
     *
     * @since   3.0.0
     */
    public function getAuthorLatest($excludeId, $limit, $authorId)
    {
        $db = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();

        $query = $db->getQuery(true);

        $query->select([
            'a.id',
            'a.title',
            'a.alias',
            'a.catid',
            'c.alias AS categoryalias',
        ])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where([
                $db->quoteName('a.published') . ' = 1',
                $db->quoteName('a.trash') . ' = 0',
                $db->quoteName('c.published') . ' = 1',
                $db->quoteName('c.trash') . ' = 0',
                $db->quoteName('a.id') . ' != :excludeId',
                $db->quoteName('a.created_by') . ' = :authorId',
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                $db->quoteName('c.access') . ' IN (' . implode(',', $groups) . ')',
                '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
                '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
            ])
            ->bind(':excludeId', $excludeId, ParameterType::INTEGER)
            ->bind(':authorId', $authorId, ParameterType::INTEGER)
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now)
            ->order($db->quoteName('a.created') . ' DESC');

        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList();
    }

    /**
     * Get related items by tags
     *
     * @param   integer  $itemId  Current item id
     * @param   array    $tags    Array of tag objects
     * @param   object   $params  Item params
     *
     * @return  array  Array of related items
     *
     * @since   3.0.0
     */
    public function getRelatedItems($itemId, $tags, $params)
    {
        if (empty($tags)) {
            return [];
        }

        $db = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();
        $limit = $params->get('itemRelatedLimit', 5);

        $tagIds = [];

        foreach ($tags as $tag) {
            $tagIds[] = (int) $tag->id;
        }

        $query = $db->getQuery(true);

        $query->select([
            'DISTINCT a.id',
            'a.title',
            'a.alias',
            'a.catid',
            'c.alias AS categoryalias',
        ])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('INNER', $db->quoteName('#__k2_tags_xref', 'x'), $db->quoteName('x.itemID') . ' = ' . $db->quoteName('a.id'))
            ->where([
                $db->quoteName('a.published') . ' = 1',
                $db->quoteName('a.trash') . ' = 0',
                $db->quoteName('c.published') . ' = 1',
                $db->quoteName('c.trash') . ' = 0',
                $db->quoteName('a.id') . ' != :itemId',
                $db->quoteName('x.tagID') . ' IN (' . implode(',', $tagIds) . ')',
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                $db->quoteName('c.access') . ' IN (' . implode(',', $groups) . ')',
                '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
                '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
            ])
            ->bind(':itemId', $itemId, ParameterType::INTEGER)
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now)
            ->order($db->quoteName('a.created') . ' DESC');

        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList();
    }
}
