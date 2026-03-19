<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\View\Itemlist;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\K2\Site\Helper\RouteHelper;

/**
 * K2 Itemlist View
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The items array
     *
     * @var    array
     * @since  3.0.0
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var    Pagination
     * @since  3.0.0
     */
    protected $pagination;

    /**
     * The category object
     *
     * @var    object
     * @since  3.0.0
     */
    protected $category;

    /**
     * The component params
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.0.0
     */
    protected $params;

    /**
     * User object
     *
     * @var    object
     * @since  3.0.0
     */
    protected $user;

    /**
     * The current task
     *
     * @var    string
     * @since  3.0.0
     */
    protected $task;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $document = $app->getDocument();
        $params = $app->getParams();
        $limitstart = $app->input->getInt('limitstart', 0);

        // Get task
        $task = $app->input->get('task', '');

        // Get the model
        $model = $this->getModel();

        // Get items
        $items = $model->getItems();

        // Get pagination
        $pagination = $model->getPagination();

        // Get category (if applicable)
        $category = null;

        if ($task === 'category' || $task === '') {
            $category = $model->getCategory();

            if ($category) {
                // Check category access and state
                if (!$category->published || $category->trash) {
                    throw new \Exception(Text::_('K2_CATEGORY_NOT_FOUND'), 404);
                }

                $groups = $user->getAuthorisedViewLevels();

                if (!in_array($category->access, $groups)) {
                    if ($user->guest) {
                        $return = base64_encode(Uri::getInstance()->toString());
                        $loginUrl = Route::_('index.php?option=com_users&view=login&return=' . $return, false);
                        $app->enqueueMessage(Text::_('K2_YOU_NEED_TO_LOGIN_FIRST'), 'notice');
                        $app->redirect($loginUrl);
                    }

                    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
                }
            }
        }

        // Trigger plugins on items
        $this->triggerPlugins($items, $params, $limitstart);

        // Set layout based on task
        switch ($task) {
            case 'category':
                $this->setLayout('category');
                break;
            case 'user':
                $this->setLayout('user');
                break;
            case 'tag':
                $this->setLayout('tag');
                break;
            case 'date':
                $this->setLayout('date');
                break;
            case 'search':
                $this->setLayout('search');
                break;
            default:
                $this->setLayout('category');
                break;
        }

        // Set document metadata
        $this->setDocumentMetadata($category, $task, $params);

        // Assign data
        $this->items = $items;
        $this->pagination = $pagination;
        $this->category = $category;
        $this->params = $params;
        $this->user = $user;
        $this->task = $task;

        // Additional data for specific tasks
        if ($task === 'tag') {
            $this->tag = $app->input->getString('tag', '');
        }

        if ($task === 'user') {
            $this->author = $this->getAuthor($app->input->getInt('id', 0));
        }

        if ($task === 'date') {
            $this->year = $app->input->getInt('year', 0);
            $this->month = $app->input->getInt('month', 0);
            $this->day = $app->input->getInt('day', 0);
        }

        if ($task === 'search') {
            $this->searchword = $app->input->getString('searchword', '');
        }

        // Get leading and primary/secondary/links breakdown if category view
        if ($task === 'category' || $task === '') {
            $this->leading = [];
            $this->primary = [];
            $this->secondary = [];
            $this->links = [];

            $numLeading = $params->get('num_leading_items', 1);
            $numPrimary = $params->get('num_primary_items', 4);
            $numSecondary = $params->get('num_secondary_items', 4);
            $numLinks = $params->get('num_links', 4);

            $counter = 0;

            foreach ($items as $item) {
                if ($counter < $numLeading) {
                    $this->leading[] = $item;
                } elseif ($counter < ($numLeading + $numPrimary)) {
                    $this->primary[] = $item;
                } elseif ($counter < ($numLeading + $numPrimary + $numSecondary)) {
                    $this->secondary[] = $item;
                } elseif ($counter < ($numLeading + $numPrimary + $numSecondary + $numLinks)) {
                    $this->links[] = $item;
                }

                $counter++;
            }
        }

        // Get subcategories if enabled
        if ($category && $params->get('subCategories', 1)) {
            $this->subCategories = $this->getSubCategories($category->id);
        }

        parent::display($tpl);
    }

    /**
     * Get subcategories
     *
     * @param   integer  $parentId  The parent category id
     *
     * @return  array  Array of category objects
     *
     * @since   3.0.0
     */
    protected function getSubCategories($parentId)
    {
        $db = Factory::getDbo();
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();

        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__k2_categories'))
            ->where([
                $db->quoteName('parent') . ' = :parentId',
                $db->quoteName('published') . ' = 1',
                $db->quoteName('trash') . ' = 0',
                $db->quoteName('access') . ' IN (' . implode(',', $groups) . ')',
            ])
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':parentId', $parentId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $categories = $db->loadObjectList();

        foreach ($categories as &$category) {
            $category->link = Route::_(RouteHelper::getCategoryRoute($category->id));

            // Get item count
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                ->from($db->quoteName('#__k2_items'))
                ->where([
                    $db->quoteName('catid') . ' = :catid',
                    $db->quoteName('published') . ' = 1',
                    $db->quoteName('trash') . ' = 0',
                ])
                ->bind(':catid', $category->id, \Joomla\Database\ParameterType::INTEGER);

            $db->setQuery($query);
            $category->numOfItems = (int) $db->loadResult();
        }

        return $categories;
    }

    /**
     * Get author details
     *
     * @param   integer  $userId  The user id
     *
     * @return  object  The author object
     *
     * @since   3.0.0
     */
    protected function getAuthor($userId)
    {
        if (!$userId) {
            return null;
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
            'u.id',
            'u.name',
            'u.username',
            'u.email',
            'k.description AS profile_description',
            'k.image AS profile_image',
            'k.url AS profile_url',
        ])
            ->from($db->quoteName('#__users', 'u'))
            ->join('LEFT', $db->quoteName('#__k2_users', 'k'), $db->quoteName('k.userID') . ' = ' . $db->quoteName('u.id'))
            ->where($db->quoteName('u.id') . ' = :userId')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Trigger K2 and content plugins on items
     *
     * @param   array   $items      The items array
     * @param   object  $params     The component params
     * @param   int     $limitstart The limitstart value
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function triggerPlugins(&$items, $params, $limitstart)
    {
        // Import plugins
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('k2');

        foreach ($items as &$item) {
            // Prepare content text
            $item->text = $item->introtext;

            // Trigger content plugins
            Factory::getApplication()->triggerEvent('onContentPrepare', ['com_k2.itemlist', &$item, &$params, $limitstart]);

            $item->introtext = $item->text;

            // Trigger events
            $results = Factory::getApplication()->triggerEvent('onContentAfterTitle', ['com_k2.itemlist', &$item, &$params, $limitstart]);
            $item->event->AfterDisplayTitle = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', ['com_k2.itemlist', &$item, &$params, $limitstart]);
            $item->event->BeforeDisplayContent = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', ['com_k2.itemlist', &$item, &$params, $limitstart]);
            $item->event->AfterDisplayContent = trim(implode("\n", $results));

            // K2 events
            $results = Factory::getApplication()->triggerEvent('onK2BeforeDisplay', [&$item, &$params, $limitstart]);
            $item->event->K2BeforeDisplay = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onK2AfterDisplay', [&$item, &$params, $limitstart]);
            $item->event->K2AfterDisplay = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onK2AfterDisplayTitle', [&$item, &$params, $limitstart]);
            $item->event->K2AfterDisplayTitle = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onK2BeforeDisplayContent', [&$item, &$params, $limitstart]);
            $item->event->K2BeforeDisplayContent = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onK2AfterDisplayContent', [&$item, &$params, $limitstart]);
            $item->event->K2AfterDisplayContent = trim(implode("\n", $results));
        }
    }

    /**
     * Set document metadata
     *
     * @param   object  $category  The category object
     * @param   string  $task      The current task
     * @param   object  $params    The component params
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function setDocumentMetadata($category, $task, $params)
    {
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $menu = $app->getMenu()->getActive();

        // Set page title
        $title = '';

        if ($menu && $menu->getParams()->get('page_title')) {
            $title = $menu->getParams()->get('page_title');
        } elseif ($category) {
            $title = $category->name;
        } elseif ($task === 'tag') {
            $title = Text::sprintf('K2_TAG_PAGE_TITLE', $app->input->getString('tag', ''));
        } elseif ($task === 'user') {
            $author = $this->getAuthor($app->input->getInt('id', 0));
            $title = $author ? $author->name : '';
        } elseif ($task === 'search') {
            $title = Text::sprintf('K2_SEARCH_RESULTS_FOR', $app->input->getString('searchword', ''));
        } elseif ($task === 'date') {
            $year = $app->input->getInt('year', 0);
            $month = $app->input->getInt('month', 0);
            $title = $year . ($month ? '/' . $month : '');
        }

        if ($title) {
            if ($app->get('sitename_pagetitles', 0) == 1) {
                $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
            } elseif ($app->get('sitename_pagetitles', 0) == 2) {
                $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
            }

            $document->setTitle($title);
        }

        // Set meta description
        if ($menu && $menu->getParams()->get('menu-meta_description')) {
            $document->setDescription($menu->getParams()->get('menu-meta_description'));
        } elseif ($category && !empty($category->description)) {
            $metaDesc = htmlspecialchars(strip_tags($category->description), ENT_QUOTES, 'UTF-8');
            $document->setDescription(\Joomla\String\StringHelper::substr($metaDesc, 0, 160));
        }

        // Set meta keywords
        if ($menu && $menu->getParams()->get('menu-meta_keywords')) {
            $document->setMetaData('keywords', $menu->getParams()->get('menu-meta_keywords'));
        }

        // Set canonical URL
        $canonicalURL = $params->get('canonicalURL', 'relative');

        if ($canonicalURL && $category) {
            $url = RouteHelper::getCategoryRoute($category->id);

            if ($canonicalURL === 'absolute') {
                $url = rtrim(Uri::root(), '/') . Route::_($url);
            } else {
                $url = Route::_($url);
            }

            $document->addHeadLink($url, 'canonical', 'rel');
        }
    }
}
