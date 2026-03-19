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
 * K2 Extra Fields List Model
 *
 * @since  3.0.0
 */
class ExtrafieldsModel extends ListModel
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
                'published', 'a.published',
                'type', 'a.type',
                'group', 'a.group',
                'ordering', 'a.ordering',
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
            $db->quoteName('a.type'),
            $db->quoteName('a.group'),
            $db->quoteName('a.published'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.value'),
        ]);

        $query->from($db->quoteName('#__k2_extra_fields', 'a'));

        // Join extra field group
        $query->select($db->quoteName('g.name', 'groupName'))
            ->join('LEFT', $db->quoteName('#__k2_extra_fields_groups', 'g'), $db->quoteName('g.id') . ' = ' . $db->quoteName('a.group'));

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        // Filter by group
        $group = $this->getState('filter.group');
        if (is_numeric($group)) {
            $query->where($db->quoteName('a.group') . ' = :group')
                ->bind(':group', $group, ParameterType::INTEGER);
        }

        // Filter by type
        $type = $this->getState('filter.type');
        if ($type) {
            $query->where($db->quoteName('a.type') . ' = :type')
                ->bind(':type', $type);
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
                $query->where($db->quoteName('a.name') . ' LIKE :search')
                    ->bind(':search', $search);
            }
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'a.ordering');
        $orderDir = $this->state->get('list.direction', 'ASC');

        if ($orderCol === 'a.ordering') {
            $query->order($db->quoteName('a.group') . ' ' . $db->escape($orderDir))
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
        $id .= ':' . $this->getState('filter.group');
        $id .= ':' . $this->getState('filter.type');

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

        $group = $app->getUserStateFromRequest($this->context . '.filter.group', 'filter_group', '', 'cmd');
        $this->setState('filter.group', $group);

        $type = $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'string');
        $this->setState('filter.type', $type);

        parent::populateState($ordering, $direction);
    }
}
