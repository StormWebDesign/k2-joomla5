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
 * K2 Comments List Model
 *
 * @since  3.0.0
 */
class CommentsModel extends ListModel
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
                'itemID', 'a.itemID',
                'userName', 'a.userName',
                'commentDate', 'a.commentDate',
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
            $db->quoteName('a.itemID'),
            $db->quoteName('a.userID'),
            $db->quoteName('a.userName'),
            $db->quoteName('a.commentEmail'),
            $db->quoteName('a.commentURL'),
            $db->quoteName('a.commentText'),
            $db->quoteName('a.commentDate'),
            $db->quoteName('a.published'),
        ]);

        $query->from($db->quoteName('#__k2_comments', 'a'));

        // Join item
        $query->select($db->quoteName('i.title', 'itemTitle'))
            ->join('LEFT', $db->quoteName('#__k2_items', 'i'), $db->quoteName('i.id') . ' = ' . $db->quoteName('a.itemID'));

        // Join item category
        $query->select($db->quoteName('c.name', 'categoryTitle'))
            ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('i.catid'));

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        // Filter by item
        $itemId = $this->getState('filter.item_id');
        if (is_numeric($itemId)) {
            $query->where($db->quoteName('a.itemID') . ' = :itemId')
                ->bind(':itemId', $itemId, ParameterType::INTEGER);
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
                $query->where('(' . $db->quoteName('a.userName') . ' LIKE :search OR ' . $db->quoteName('a.commentText') . ' LIKE :search2)')
                    ->bind([':search' => $search, ':search2' => $search]);
            }
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'a.commentDate');
        $orderDir = $this->state->get('list.direction', 'DESC');
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
        $id .= ':' . $this->getState('filter.item_id');

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
    protected function populateState($ordering = 'a.commentDate', $direction = 'desc')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        $itemId = $app->getUserStateFromRequest($this->context . '.filter.item_id', 'filter_item_id', '', 'cmd');
        $this->setState('filter.item_id', $itemId);

        parent::populateState($ordering, $direction);
    }

    /**
     * Mark comments as spam.
     *
     * @param   array  $pks  The IDs of the comments.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function spam($pks)
    {
        $pks = ArrayHelper::toInteger($pks);

        if (empty($pks)) {
            return false;
        }

        $db = $this->getDatabase();

        // Delete comments
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__k2_comments'))
            ->whereIn($db->quoteName('id'), $pks);
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Report a spammer.
     *
     * @param   integer  $id  The user ID.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function reportSpammer($id)
    {
        // Basic implementation - can be extended for StopForumSpam integration
        $db = $this->getDatabase();

        // Delete all comments from this user
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__k2_comments'))
            ->where($db->quoteName('userID') . ' = ' . (int) $id);
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
