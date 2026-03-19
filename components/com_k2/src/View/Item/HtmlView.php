<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\View\Item;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\K2\Site\Helper\RouteHelper;

/**
 * K2 Item View
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The item object
     *
     * @var    object
     * @since  3.0.0
     */
    protected $item;

    /**
     * The component params
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.0.0
     */
    protected $params;

    /**
     * Pagination object
     *
     * @var    object
     * @since  3.0.0
     */
    protected $pagination;

    /**
     * User object
     *
     * @var    object
     * @since  3.0.0
     */
    protected $user;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @throws  \Exception
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

        $this->setLayout('item');

        // Get the model
        $model = $this->getModel();

        // Get the item
        $item = $model->getItem();

        if (!$item) {
            throw new \Exception(Text::_('K2_ITEM_NOT_FOUND'), 404);
        }

        // Check published and trash state
        $now = Factory::getDate()->toSql();
        $nullDate = Factory::getDbo()->getNullDate();

        if (
            !$item->published ||
            $item->trash ||
            ($item->publish_up !== $nullDate && $item->publish_up > $now) ||
            ($item->publish_down !== $nullDate && $item->publish_down < $now) ||
            (!$item->category->published || $item->category->trash)
        ) {
            throw new \Exception(Text::_('K2_ITEM_NOT_FOUND'), 404);
        }

        // Increase hit counter
        if ($params->get('siteItemHits', 1)) {
            $model->hit($item->id);
        }

        // Trigger K2 plugins
        $this->triggerPlugins($item, $params, $limitstart);

        // Get comments
        if ($item->params->get('itemComments')) {
            $limit = $params->get('commentsLimit', 10);
            $item->comments = $model->getItemComments($item->id, $limitstart, $limit);

            // Format comments
            foreach ($item->comments as &$comment) {
                $comment->commentText = nl2br(htmlspecialchars($comment->commentText, ENT_QUOTES, 'UTF-8'));
            }

            // Create pagination for comments
            $total = $item->numOfComments;
            $item->commentsPagination = new \Joomla\CMS\Pagination\Pagination($total, $limitstart, $limit);
        }

        // Get author's latest items
        if ($item->params->get('itemAuthorLatest') && empty($item->created_by_alias)) {
            $itemlistModel = $this->getModel('Itemlist');
            $authorLatestItems = $itemlistModel->getAuthorLatest(
                $item->id,
                $item->params->get('itemAuthorLatestLimit', 5),
                $item->created_by
            );

            foreach ($authorLatestItems as &$latestItem) {
                $latestItem->link = Route::_(RouteHelper::getItemRoute(
                    $latestItem->id,
                    $latestItem->catid
                ));
            }

            $this->authorLatestItems = $authorLatestItems;
        }

        // Get related items
        if ($item->params->get('itemRelated') && !empty($item->tags)) {
            $itemlistModel = $this->getModel('Itemlist');
            $relatedItems = $itemlistModel->getRelatedItems($item->id, $item->tags, $item->params);

            foreach ($relatedItems as &$relatedItem) {
                $relatedItem->link = Route::_(RouteHelper::getItemRoute(
                    $relatedItem->id,
                    $relatedItem->catid
                ));
            }

            $this->relatedItems = $relatedItems;
        }

        // Get previous/next items
        if ($item->params->get('itemNavigation')) {
            $previousItem = $model->getPreviousItem(
                $item->id,
                $item->catid,
                $item->ordering,
                $item->params->get('catOrdering')
            );

            if ($previousItem) {
                $item->previous = new \stdClass();
                $item->previous->title = $previousItem->title;
                $item->previous->link = Route::_(RouteHelper::getItemRoute($previousItem->id, $previousItem->catid));
                $item->previousLink = $item->previous->link;
                $item->previousTitle = $previousItem->title;
            }

            $nextItem = $model->getNextItem(
                $item->id,
                $item->catid,
                $item->ordering,
                $item->params->get('catOrdering')
            );

            if ($nextItem) {
                $item->next = new \stdClass();
                $item->next->title = $nextItem->title;
                $item->next->link = Route::_(RouteHelper::getItemRoute($nextItem->id, $nextItem->catid));
                $item->nextLink = $item->next->link;
                $item->nextTitle = $nextItem->title;
            }
        }

        // Set absolute URL for sharing
        $item->absoluteURL = Uri::getInstance()->toString();
        $item->sharinglink = $item->absoluteURL;

        // Social sharing links
        $item->socialLink = urlencode($item->absoluteURL);

        if ($params->get('twitterUsername')) {
            $item->twitterURL = 'https://twitter.com/intent/tweet?text=' . urlencode($item->title) . '&url=' . urlencode($item->absoluteURL) . '&via=' . $params->get('twitterUsername');
        } else {
            $item->twitterURL = 'https://twitter.com/intent/tweet?text=' . urlencode($item->title) . '&url=' . urlencode($item->absoluteURL);
        }

        // Email link
        if (file_exists(JPATH_SITE . '/components/com_mailto/helpers/mailto.php')) {
            require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';
            $template = $app->getTemplate();
            $item->emailLink = Route::_('index.php?option=com_mailto&tmpl=component&template=' . $template . '&link=' . \MailToHelper::addLink($item->absoluteURL));
        }

        // Set metadata
        $this->setDocumentMetadata($item, $params);

        // Assign data
        $this->item = $item;
        $this->params = $item->params;
        $this->user = $user;

        parent::display($tpl);
    }

    /**
     * Trigger K2 and content plugins
     *
     * @param   object  $item       The item object
     * @param   object  $params     The component params
     * @param   int     $limitstart The limitstart value
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function triggerPlugins(&$item, $params, $limitstart)
    {
        // Import plugins
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('k2');

        // Prepare content text
        $item->text = '';

        if (!empty($item->introtext)) {
            $item->text .= $item->introtext;
        }

        if (!empty($item->fulltext)) {
            $item->text .= '{K2Splitter}' . $item->fulltext;
        }

        // Trigger content plugins on the text
        $results = Factory::getApplication()->triggerEvent('onContentPrepare', ['com_k2.item', &$item, &$params, $limitstart]);

        // Split back
        $textParts = explode('{K2Splitter}', $item->text);
        $item->introtext = $textParts[0] ?? '';
        $item->fulltext = $textParts[1] ?? '';

        // Trigger other content events
        $results = Factory::getApplication()->triggerEvent('onContentAfterTitle', ['com_k2.item', &$item, &$params, $limitstart]);
        $item->event->AfterDisplayTitle = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', ['com_k2.item', &$item, &$params, $limitstart]);
        $item->event->BeforeDisplayContent = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', ['com_k2.item', &$item, &$params, $limitstart]);
        $item->event->AfterDisplayContent = trim(implode("\n", $results));

        // Trigger K2 specific events
        $results = Factory::getApplication()->triggerEvent('onK2AfterDisplayTitle', [&$item, &$params, $limitstart]);
        $item->event->K2AfterDisplayTitle = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onK2BeforeDisplayContent', [&$item, &$params, $limitstart]);
        $item->event->K2BeforeDisplayContent = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onK2AfterDisplayContent', [&$item, &$params, $limitstart]);
        $item->event->K2AfterDisplayContent = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onK2BeforeDisplay', [&$item, &$params, $limitstart]);
        $item->event->K2BeforeDisplay = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onK2AfterDisplay', [&$item, &$params, $limitstart]);
        $item->event->K2AfterDisplay = trim(implode("\n", $results));

        // K2 User display
        if (isset($item->author) && is_object($item->author) && $item->author->id) {
            $results = Factory::getApplication()->triggerEvent('onK2UserDisplay', [&$item->author, &$params, $limitstart]);
            $item->event->K2UserDisplay = trim(implode("\n", $results));
        }

        // Comments events
        $results = Factory::getApplication()->triggerEvent('onK2CommentsCounter', [&$item, &$params, $limitstart]);
        $item->event->K2CommentsCounter = trim(implode("\n", $results));

        $results = Factory::getApplication()->triggerEvent('onK2CommentsBlock', [&$item, &$params, $limitstart]);
        $item->event->K2CommentsBlock = trim(implode("\n", $results));
    }

    /**
     * Set document metadata
     *
     * @param   object  $item    The item object
     * @param   object  $params  The component params
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function setDocumentMetadata($item, $params)
    {
        $app = Factory::getApplication();
        $document = $app->getDocument();

        // Set page title
        $title = $item->rawTitle;

        if ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $document->setTitle($title);

        // Set meta description
        if (!empty($item->metadesc)) {
            $document->setDescription(htmlspecialchars(strip_tags($item->metadesc), ENT_QUOTES, 'UTF-8'));
        } elseif (!empty($item->introtext)) {
            $metaDesc = htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8');
            $document->setDescription(\Joomla\String\StringHelper::substr($metaDesc, 0, 160));
        }

        // Set meta keywords
        if (!empty($item->metakey)) {
            $document->setMetaData('keywords', $item->metakey);
        } elseif (!empty($item->tags)) {
            $keywords = [];
            foreach ($item->tags as $tag) {
                $keywords[] = $tag->name;
            }
            $document->setMetaData('keywords', implode(', ', $keywords));
        }

        // Set author
        if (!empty($item->author->name) && $app->get('MetaAuthor') == '1') {
            $document->setMetaData('author', $item->author->name);
        }

        // Set canonical URL
        $canonicalURL = $params->get('canonicalURL', 'relative');

        if ($canonicalURL) {
            $url = $item->link;

            if ($canonicalURL === 'absolute') {
                $url = str_replace(Uri::root(true), '', Uri::root(false));
                $url = rtrim($url, '/') . $item->link;
            }

            $document->addHeadLink($url, 'canonical', 'rel');
        }

        // Set OpenGraph metadata
        if ($params->get('facebookMetatags', 1)) {
            $document->setMetaData('og:url', $item->absoluteURL);
            $document->setMetaData('og:type', 'article');
            $document->setMetaData('og:title', htmlspecialchars(strip_tags($item->rawTitle), ENT_QUOTES, 'UTF-8'));

            if (!empty($item->metadesc)) {
                $document->setMetaData('og:description', htmlspecialchars(strip_tags($item->metadesc), ENT_QUOTES, 'UTF-8'));
            }

            if (!empty($item->imageLarge)) {
                $document->setMetaData('og:image', Uri::root() . ltrim($item->imageLarge, '/'));
            }
        }

        // Set Twitter metadata
        if ($params->get('twitterMetatags', 1)) {
            $document->setMetaData('twitter:card', $params->get('twitterCardType', 'summary'));

            if ($params->get('twitterUsername')) {
                $document->setMetaData('twitter:site', '@' . $params->get('twitterUsername'));
            }

            $document->setMetaData('twitter:title', htmlspecialchars(strip_tags($item->rawTitle), ENT_QUOTES, 'UTF-8'));

            if (!empty($item->metadesc)) {
                $document->setMetaData('twitter:description', htmlspecialchars(strip_tags($item->metadesc), ENT_QUOTES, 'UTF-8'));
            }

            if (!empty($item->imageLarge)) {
                $document->setMetaData('twitter:image', Uri::root() . ltrim($item->imageLarge, '/'));
            }
        }
    }
}
