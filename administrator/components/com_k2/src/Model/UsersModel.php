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
use Joomla\Utilities\ArrayHelper;

/**
 * K2 Users List Model
 *
 * @since  3.0.0
 */
class UsersModel extends ListModel
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
                'userID', 'a.userID',
                'userName', 'a.userName',
                'group', 'a.group',
                'gender', 'a.gender',
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
            $db->quoteName('a.userID'),
            $db->quoteName('a.userName'),
            $db->quoteName('a.gender'),
            $db->quoteName('a.description'),
            $db->quoteName('a.image'),
            $db->quoteName('a.url'),
            $db->quoteName('a.group'),
            $db->quoteName('a.ip'),
            $db->quoteName('a.hostname'),
        ]);

        $query->from($db->quoteName('#__k2_users', 'a'));

        // Join Joomla user
        $query->select([
            $db->quoteName('u.name', 'name'),
            $db->quoteName('u.username', 'username'),
            $db->quoteName('u.email', 'email'),
            $db->quoteName('u.block', 'blocked'),
        ])
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.userID'));

        // Join K2 user group
        $query->select($db->quoteName('g.name', 'groupName'))
            ->join('LEFT', $db->quoteName('#__k2_user_groups', 'g'), $db->quoteName('g.id') . ' = ' . $db->quoteName('a.group'));

        // Filter by group
        $group = $this->getState('filter.group');
        if (is_numeric($group)) {
            $query->where($db->quoteName('a.group') . ' = :group')
                ->bind(':group', $group, ParameterType::INTEGER);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.userID') . ' = :searchId')
                    ->bind(':searchId', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('u.name') . ' LIKE :search OR ' . $db->quoteName('u.username') . ' LIKE :search2 OR ' . $db->quoteName('u.email') . ' LIKE :search3)')
                    ->bind([':search' => $search, ':search2' => $search, ':search3' => $search]);
            }
        }

        // Only show users that exist in Joomla
        $query->where($db->quoteName('u.id') . ' IS NOT NULL');

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'u.name');
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
        $id .= ':' . $this->getState('filter.group');

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
    protected function populateState($ordering = 'u.name', $direction = 'asc')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $group = $app->getUserStateFromRequest($this->context . '.filter.group', 'filter_group', '', 'cmd');
        $this->setState('filter.group', $group);

        parent::populateState($ordering, $direction);
    }

    /**
     * Move users to a different K2 group.
     *
     * @param   array    $pks      The IDs of the K2 users.
     * @param   integer  $groupId  The target group ID.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function move($pks, $groupId)
    {
        $pks = ArrayHelper::toInteger($pks);

        if (empty($pks)) {
            return false;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__k2_users'))
            ->set($db->quoteName('group') . ' = :group')
            ->whereIn($db->quoteName('id'), $pks)
            ->bind(':group', $groupId, ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
}
