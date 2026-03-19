<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Plugin\System\K2\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;

/**
 * K2 System Plugin
 *
 * @since  3.0.0
 */
class K2Plugin extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * K2 version
     *
     * @var    string
     * @since  3.0.0
     */
    public const K2_VERSION = '3.0.0';

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   3.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'onAfterInitialise',
            'onAfterRoute'      => 'onAfterRoute',
            'onAfterRender'     => 'onAfterRender',
        ];
    }

    /**
     * After initialise event handler
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function onAfterInitialise(): void
    {
        // Define K2 version constants for compatibility
        if (!defined('K2_CURRENT_VERSION')) {
            define('K2_CURRENT_VERSION', self::K2_VERSION);
        }

        if (!defined('K2_JVERSION')) {
            define('K2_JVERSION', '50');
        }

        // Define DS constant for backwards compatibility with old template overrides
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
    }

    /**
     * After route event handler
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function onAfterRoute(): void
    {
        $app = $this->getApplication();

        // Load K2 language file
        $basepath = $app->isClient('site') ? JPATH_SITE : JPATH_ADMINISTRATOR;
        $app->getLanguage()->load('com_k2', $basepath);
        $app->getLanguage()->load('com_k2.dates', JPATH_ADMINISTRATOR, null, true);

        // Skip for admin and edit tasks
        if ($app->isClient('administrator')) {
            return;
        }

        $input = $app->input;
        $option = $input->get('option');
        $task = $input->get('task');

        if ($option === 'com_k2' && ($task === 'add' || $task === 'edit')) {
            return;
        }

        // Load K2 CSS and JS for frontend
        $this->loadFrontendAssets();
    }

    /**
     * After render event handler
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function onAfterRender(): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $params = ComponentHelper::getParams('com_k2');
        $response = $app->getBody();

        // Fix OpenGraph meta tags (use property instead of name attribute)
        if ($params->get('facebookMetatags', 1)) {
            $searches = [
                '<meta name="og:url"',
                '<meta name="og:title"',
                '<meta name="og:type"',
                '<meta name="og:image"',
                '<meta name="og:description"',
            ];

            $replacements = [
                '<meta property="og:url"',
                '<meta property="og:title"',
                '<meta property="og:type"',
                '<meta property="og:image"',
                '<meta property="og:description"',
            ];

            // Add OpenGraph namespace to html tag if not present
            if (strpos($response, 'http://ogp.me/ns#') === false) {
                $searches[] = '<html ';
                $searches[] = '<html>';
                $replacements[] = '<html prefix="og: http://ogp.me/ns#" ';
                $replacements[] = '<html prefix="og: http://ogp.me/ns#">';
            }

            $response = str_ireplace($searches, $replacements, $response);
            $app->setBody($response);
        }

        // Add K2 powered-by header
        $app->setHeader('X-Content-Powered-By', 'K2 v' . self::K2_VERSION . ' (by Storm Web Design)', true);

        // Handle caching headers for guests
        $user = $app->getIdentity();

        if ($user->guest) {
            $config = $app->get('caching', 0);

            if ($config) {
                $cacheTime = $app->get('cachetime', 15) * 60;
                $app->setHeader('Cache-Control', 'public, max-age=' . $cacheTime, true);
                $app->setHeader('Pragma', 'public', true);
            }

            $app->setHeader('X-Logged-In', 'False', true);
        } else {
            $app->setHeader('X-Logged-In', 'True', true);
        }
    }

    /**
     * Load frontend CSS and JavaScript assets
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function loadFrontendAssets(): void
    {
        $app = $this->getApplication();
        $document = $app->getDocument();

        if (!($document instanceof HtmlDocument)) {
            return;
        }

        $params = ComponentHelper::getParams('com_k2');

        // Get WebAssetManager
        $wa = $document->getWebAssetManager();

        // Register K2 assets
        $wa->getRegistry()->addRegistryFile('media/com_k2/joomla.asset.json');

        // Load K2 CSS if not disabled
        if (!$params->get('disableK2CSS', 0)) {
            if ($wa->assetExists('style', 'com_k2.frontend')) {
                $wa->useStyle('com_k2.frontend');
            } else {
                $document->addStyleSheet(Uri::root(true) . '/media/k2/assets/css/k2.css?v=' . self::K2_VERSION);
            }
        }

        // Load K2 JavaScript
        if ($wa->assetExists('script', 'com_k2.frontend')) {
            $wa->useScript('com_k2.frontend');
        } else {
            $document->addScript(Uri::root(true) . '/media/k2/assets/js/k2.js?v=' . self::K2_VERSION);
        }
    }
}
