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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel as BaseItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Component\K2\Site\Helper\RouteHelper;

/**
 * K2 Item Model for Site
 *
 * @since  3.0.0
 */
class ItemModel extends BaseItemModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $_context = 'com_k2.item';

    /**
     * Method to get an item.
     *
     * @param   integer  $pk  The id of the item.
     *
     * @return  object|boolean  Item data object on success, false on failure.
     *
     * @since   3.0.0
     */
    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        $pk = $pk ?: $this->getState('item.id');

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDatabase();
                $query = $db->getQuery(true);

                $query->select([
                    'a.*',
                    'c.name AS category_title',
                    'c.alias AS category_alias',
                    'c.access AS category_access',
                    'c.published AS category_published',
                    'c.trash AS category_trash',
                    'c.extraFieldsGroup AS category_extra_fields_group',
                    'u.name AS author_name',
                    'u.username AS author_username',
                    'u.email AS author_email',
                ])
                    ->from($db->quoteName('#__k2_items', 'a'))
                    ->join('LEFT', $db->quoteName('#__k2_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                    ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
                    ->where($db->quoteName('a.id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    throw new \Exception(Text::_('K2_ITEM_NOT_FOUND'), 404);
                }

                // Check access
                $user = $app->getIdentity();
                $groups = $user->getAuthorisedViewLevels();

                if (!in_array($data->access, $groups) || !in_array($data->category_access, $groups)) {
                    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
                }

                // Get item parameters
                $params = ComponentHelper::getParams('com_k2');
                $itemParams = new Registry($data->params);
                $params->merge($itemParams);
                $data->params = $params;

                // Prepare item
                $data = $this->prepareItem($data);

                $this->_item[$pk] = $data;
            } catch (\Exception $e) {
                if ($e->getCode() == 404) {
                    throw new \Exception($e->getMessage(), 404);
                }

                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
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
        $item->category->published = $item->category_published;
        $item->category->trash = $item->category_trash;
        $item->category->extraFieldsGroup = $item->category_extra_fields_group;

        // Create author object
        $item->author = new \stdClass();
        $item->author->name = $item->author_name ?: $item->created_by_alias;
        $item->author->username = $item->author_username;
        $item->author->email = $item->author_email;
        $item->author->id = $item->created_by;

        // Create category link
        $item->category->link = Route::_(RouteHelper::getCategoryRoute($item->catid));

        // Create item link
        $item->link = Route::_(RouteHelper::getItemRoute($item->id, $item->catid));

        // Get tags
        $item->tags = $this->getItemTags($item->id);

        // Get extra fields
        $item->extra_fields = $this->getItemExtraFields($item->id, $item->category_extra_fields_group);

        // Get attachments
        $item->attachments = $this->getItemAttachments($item->id);

        // Get comments count
        $item->numOfComments = $this->getCommentsCount($item->id);

        // Process images
        $item = $this->prepareItemImages($item);

        // Prepare text
        $item->text = $item->introtext;

        if (!empty($item->fulltext)) {
            $item->text .= '{K2Splitter}' . $item->fulltext;
        }

        // Raw title
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
        $item->event->K2CommentsCounter = '';
        $item->event->K2CommentsBlock = '';
        $item->event->K2UserDisplay = '';

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

            $item->image = $item->imageLarge;

            $item->imageProperties = new \stdClass();
            $item->imageProperties->filenamePrefix = $imageFilenamePrefix;
            $item->imageProperties->pathPrefix = $imagePathPrefix;
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
     * Get item extra fields
     *
     * @param   integer  $itemId   The item id
     * @param   integer  $groupId  The extra fields group id
     *
     * @return  array  Array of extra field objects
     *
     * @since   3.0.0
     */
    protected function getItemExtraFields($itemId, $groupId)
    {
        if (!$groupId) {
            return [];
        }

        $db = $this->getDatabase();

        // Get item extra fields data
        $query = $db->getQuery(true);
        $query->select($db->quoteName('extra_fields'))
            ->from($db->quoteName('#__k2_items'))
            ->where($db->quoteName('id') . ' = :itemId')
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        $db->setQuery($query);
        $extraFieldsData = $db->loadResult();

        if (empty($extraFieldsData)) {
            return [];
        }

        $extraFieldsValues = json_decode($extraFieldsData, true) ?: [];

        // Get extra fields definitions
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName('#__k2_extra_fields'))
            ->where([
                $db->quoteName('group') . ' = :groupId',
                $db->quoteName('published') . ' = 1',
            ])
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':groupId', $groupId, ParameterType::INTEGER);

        $db->setQuery($query);
        $extraFields = $db->loadObjectList();

        foreach ($extraFields as &$field) {
            $field->value = $extraFieldsValues[$field->id] ?? '';
        }

        return $extraFields;
    }

    /**
     * Get item attachments
     *
     * @param   integer  $itemId  The item id
     *
     * @return  array  Array of attachment objects
     *
     * @since   3.0.0
     */
    protected function getItemAttachments($itemId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__k2_attachments'))
            ->where($db->quoteName('itemID') . ' = :itemId')
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        $db->setQuery($query);
        $attachments = $db->loadObjectList();

        foreach ($attachments as &$attachment) {
            $attachment->link = Route::_('index.php?option=com_k2&view=item&task=download&id=' . $attachment->id);
        }

        return $attachments;
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
     * Get item comments
     *
     * @param   integer  $itemId      The item id
     * @param   integer  $limitstart  Start offset
     * @param   integer  $limit       Number of comments
     * @param   boolean  $published   Published state filter
     *
     * @return  array  Array of comment objects
     *
     * @since   3.0.0
     */
    public function getItemComments($itemId, $limitstart = 0, $limit = 10, $published = true)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            'c.*',
            'u.name AS userName',
            'u.username AS userUsername',
        ])
            ->from($db->quoteName('#__k2_comments', 'c'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('c.userID'))
            ->where($db->quoteName('c.itemID') . ' = :itemId')
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        if ($published) {
            $query->where($db->quoteName('c.published') . ' = 1');
        }

        $query->order($db->quoteName('c.commentDate') . ' DESC');

        $db->setQuery($query, $limitstart, $limit);

        return $db->loadObjectList();
    }

    /**
     * Increase hit counter
     *
     * @param   integer  $pk  The item id
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function hit($pk = 0)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $hitcount = $input->getInt('hitcount', 1);

        if ($hitcount) {
            $pk = $pk ?: $this->getState('item.id');

            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__k2_items'))
                ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
                ->where($db->quoteName('id') . ' = :pk')
                ->bind(':pk', $pk, ParameterType::INTEGER);

            $db->setQuery($query);
            $db->execute();
        }

        return true;
    }

    /**
     * Get previous item
     *
     * @param   integer  $id        Current item id
     * @param   integer  $catid     Category id
     * @param   integer  $ordering  Current ordering
     * @param   string   $orderBy   Order by field
     *
     * @return  object|null  Previous item or null
     *
     * @since   3.0.0
     */
    public function getPreviousItem($id, $catid, $ordering, $orderBy = 'ordering')
    {
        $db = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();

        $query = $db->getQuery(true);

        $query->select(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.modified'])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->where([
                $db->quoteName('a.published') . ' = 1',
                $db->quoteName('a.trash') . ' = 0',
                $db->quoteName('a.catid') . ' = :catid',
                $db->quoteName('a.id') . ' != :id',
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
                '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
            ])
            ->bind(':catid', $catid, ParameterType::INTEGER)
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now);

        switch ($orderBy) {
            case 'date':
            case 'rdate':
                $query->where($db->quoteName('a.created') . ' < (SELECT created FROM #__k2_items WHERE id = :subId)')
                    ->bind(':subId', $id, ParameterType::INTEGER)
                    ->order($db->quoteName('a.created') . ' DESC');
                break;
            default:
                $query->where($db->quoteName('a.ordering') . ' < :ordering')
                    ->bind(':ordering', $ordering, ParameterType::INTEGER)
                    ->order($db->quoteName('a.ordering') . ' DESC');
                break;
        }

        $db->setQuery($query, 0, 1);

        return $db->loadObject();
    }

    /**
     * Get next item
     *
     * @param   integer  $id        Current item id
     * @param   integer  $catid     Category id
     * @param   integer  $ordering  Current ordering
     * @param   string   $orderBy   Order by field
     *
     * @return  object|null  Next item or null
     *
     * @since   3.0.0
     */
    public function getNextItem($id, $catid, $ordering, $orderBy = 'ordering')
    {
        $db = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now = Factory::getDate()->toSql();
        $nullDate = $db->getNullDate();

        $query = $db->getQuery(true);

        $query->select(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.modified'])
            ->from($db->quoteName('#__k2_items', 'a'))
            ->where([
                $db->quoteName('a.published') . ' = 1',
                $db->quoteName('a.trash') . ' = 0',
                $db->quoteName('a.catid') . ' = :catid',
                $db->quoteName('a.id') . ' != :id',
                $db->quoteName('a.access') . ' IN (' . implode(',', $groups) . ')',
                '(' . $db->quoteName('a.publish_up') . ' = :nullDate1 OR ' . $db->quoteName('a.publish_up') . ' <= :now1)',
                '(' . $db->quoteName('a.publish_down') . ' = :nullDate2 OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
            ])
            ->bind(':catid', $catid, ParameterType::INTEGER)
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':nullDate1', $nullDate)
            ->bind(':now1', $now)
            ->bind(':nullDate2', $nullDate)
            ->bind(':now2', $now);

        switch ($orderBy) {
            case 'date':
            case 'rdate':
                $query->where($db->quoteName('a.created') . ' > (SELECT created FROM #__k2_items WHERE id = :subId)')
                    ->bind(':subId', $id, ParameterType::INTEGER)
                    ->order($db->quoteName('a.created') . ' ASC');
                break;
            default:
                $query->where($db->quoteName('a.ordering') . ' > :ordering')
                    ->bind(':ordering', $ordering, ParameterType::INTEGER)
                    ->order($db->quoteName('a.ordering') . ' ASC');
                break;
        }

        $db->setQuery($query, 0, 1);

        return $db->loadObject();
    }

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load state from the request.
        $pk = $app->input->getInt('id');
        $this->setState('item.id', $pk);

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);
    }
}
