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
 * K2 Extra Field Groups List Model
 *
 * @since  3.0.0
 */
class ExtrafieldsgroupsModel extends ListModel
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
        ]);

        $query->from($db->quoteName('#__k2_extra_fields_groups', 'a'));

        // Subquery to count extra fields in each group
        $subQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__k2_extra_fields', 'e'))
            ->where($db->quoteName('e.group') . ' = ' . $db->quoteName('a.id'));
        $query->select('(' . $subQuery . ') AS ' . $db->quoteName('numOfFields'));

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
        $orderCol = $this->state->get('list.ordering', 'a.name');
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
    protected function populateState($ordering = 'a.name', $direction = 'asc')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        parent::populateState($ordering, $direction);
    }
}
