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
use Joomla\Database\ParameterType;

/**
 * K2 Categories List Model
 *
 * @since  3.0.0
 */
class CategoriesModel extends ListModel
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
                'name', 'a.name',
                'alias', 'a.alias',
                'published', 'a.published',
                'access', 'a.access', 'access_level',
                'ordering', 'a.ordering',
                'language', 'a.language',
                'parent', 'a.parent',
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

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.published'),
            $db->quoteName('a.access'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.language'),
            $db->quoteName('a.parent'),
            $db->quoteName('a.trash'),
            $db->quoteName('a.extraFieldsGroup'),
            $db->quoteName('a.checked_out'),
            $db->quoteName('a.checked_out_time'),
        ]);

        $query->from($db->quoteName('#__k2_categories', 'a'));

        // Join access level
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

        // Join checked out user
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join parent category
        $query->select($db->quoteName('p.name', 'parent_title'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('a.parent'));

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
        }

        // Filter by access level
        $access = $this->getState('filter.access');
        if (is_numeric($access)) {
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by language
        $language = $this->getState('filter.language');
        if ($language) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Filter by parent
        $parentId = $this->getState('filter.parent');
        if (is_numeric($parentId)) {
            $query->where($db->quoteName('a.parent') . ' = :parent')
                ->bind(':parent', $parentId, ParameterType::INTEGER);
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
                $query->where('(' . $db->quoteName('a.name') . ' LIKE :search OR ' . $db->quoteName('a.alias') . ' LIKE :search2)')
                    ->bind([':search' => $search, ':search2' => $search]);
            }
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'a.ordering');
        $orderDir = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDir));

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
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.parent');
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
    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        $access = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', '', 'cmd');
        $this->setState('filter.access', $access);

        $language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string');
        $this->setState('filter.language', $language);

        $parent = $app->getUserStateFromRequest($this->context . '.filter.parent', 'filter_parent', '', 'cmd');
        $this->setState('filter.parent', $parent);

        $trash = $app->getUserStateFromRequest($this->context . '.filter.trash', 'filter_trash', '0', 'string');
        $this->setState('filter.trash', $trash);

        parent::populateState($ordering, $direction);
    }

    /**
     * Rebuild the nested set tree.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   3.0.0
     */
    public function rebuild()
    {
        // This is a simplified rebuild - K2 categories don't use nested set
        // Just reorder by parent and ordering
        return true;
    }
}
