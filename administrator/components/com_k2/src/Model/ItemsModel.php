<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * K2 Items List Model
 *
 * @since  3.0.0
 */
class ItemsModel extends ListModel
{
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
                'access', 'a.access', 'access_level',
                'ordering', 'a.ordering',
                'featured', 'a.featured',
                'featured_ordering', 'a.featured_ordering',
                'language', 'a.language',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'modified', 'a.modified',
                'hits', 'a.hits',
                'catid', 'a.catid', 'category_title',
                'author', 'author_name',
                'trash', 'a.trash',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\QueryInterface
     *
     * @since   3.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $user = Factory::getApplication()->getIdentity();

        // Select fields
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('a.alias'),
                    $db->quoteName('a.catid'),
                    $db->quoteName('a.published'),
                    $db->quoteName('a.access'),
                    $db->quoteName('a.ordering'),
                    $db->quoteName('a.featured'),
                    $db->quoteName('a.featured_ordering'),
                    $db->quoteName('a.language'),
                    $db->quoteName('a.hits'),
                    $db->quoteName('a.created'),
                    $db->quoteName('a.created_by'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.trash'),
                    $db->quoteName('a.checked_out'),
                    $db->quoteName('a.checked_out_time'),
                ]
            )
        );

        $query->from($db->quoteName('#__k2_items', 'a'));

        // Join category
        $query->select($db->quoteName('c.name', 'category_title'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'));

        // Join author
        $query->select($db->quoteName('u.name', 'author_name'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));

        // Join access level
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

        // Join checked out user
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Filter by trash state
        $trash = $this->getState('filter.trash');
        if (is_numeric($trash)) {
            $query->where($db->quoteName('a.trash') . ' = :trash')
                ->bind(':trash', $trash, ParameterType::INTEGER);
        } else {
            $query->where($db->quoteName('a.trash') . ' = 0');
        }

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.published') . ' IN (0, 1)');
        }

        // Filter by category
        $categoryId = $this->getState('filter.category_id');
        if (is_numeric($categoryId)) {
            $query->where($db->quoteName('a.catid') . ' = :catid')
                ->bind(':catid', $categoryId, ParameterType::INTEGER);
        }

        // Filter by access level
        $access = $this->getState('filter.access');
        if (is_numeric($access)) {
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by author
        $authorId = $this->getState('filter.author_id');
        if (is_numeric($authorId)) {
            $query->where($db->quoteName('a.created_by') . ' = :authorId')
                ->bind(':authorId', $authorId, ParameterType::INTEGER);
        }

        // Filter by language
        $language = $this->getState('filter.language');
        if ($language) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Filter by featured
        $featured = $this->getState('filter.featured');
        if (is_numeric($featured)) {
            $query->where($db->quoteName('a.featured') . ' = :featured')
                ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :searchId')
                    ->bind(':searchId', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('a.title') . ' LIKE :search OR ' . $db->quoteName('a.alias') . ' LIKE :search2)')
                    ->bind([':search' => $search, ':search2' => $search]);
            }
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDir = $this->state->get('list.direction', 'DESC');

        if ($orderCol === 'a.ordering' || $orderCol === 'category_title') {
            $query->order($db->quoteName('c.name') . ' ' . $db->escape($orderDir))
                ->order($db->quoteName('a.ordering') . ' ' . $db->escape($orderDir));
        } else {
            $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDir));
        }

        return $query;
    }

    /**
     * Method to get a store id based on the model configuration state.
     *
     * @param   string  $id  An identifier string to generate the store id.
     *
     * @return  string  A store id.
     *
     * @since   3.0.0
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.author_id');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . $this->getState('filter.trash');

        return parent::getStoreId($id);
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
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        $app = Factory::getApplication();

        // List state
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        $access = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', '', 'cmd');
        $this->setState('filter.access', $access);

        $categoryId = $app->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '', 'cmd');
        $this->setState('filter.category_id', $categoryId);

        $authorId = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id', '', 'cmd');
        $this->setState('filter.author_id', $authorId);

        $language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string');
        $this->setState('filter.language', $language);

        $featured = $app->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '', 'string');
        $this->setState('filter.featured', $featured);

        $trash = $app->getUserStateFromRequest($this->context . '.filter.trash', 'filter_trash', '0', 'string');
        $this->setState('filter.trash', $trash);

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to change the featured state of items.
     *
     * @param   array    $pks    A list of the primary keys to change.
     * @param   integer  $value  The value of the featured state.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function featured($pks, $value = 0)
    {
        $pks = ArrayHelper::toInteger($pks);
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__k2_items'))
            ->set($db->quoteName('featured') . ' = :featured')
            ->whereIn($db->quoteName('id'), $pks)
            ->bind(':featured', $value, ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }
}
