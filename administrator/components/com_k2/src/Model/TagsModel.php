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
 * K2 Tags List Model
 *
 * @since  3.0.0
 */
class TagsModel extends ListModel
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
            $db->quoteName('a.published'),
            $db->quoteName('a.description'),
        ]);

        $query->from($db->quoteName('#__k2_tags', 'a'));

        // Subquery to count items
        $subQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__k2_tags_xref', 'x'))
            ->where($db->quoteName('x.tagID') . ' = ' . $db->quoteName('a.id'));
        $query->select('(' . $subQuery . ') AS ' . $db->quoteName('numOfItems'));

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
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
        $id .= ':' . $this->getState('filter.published');

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

        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        parent::populateState($ordering, $direction);
    }
}
