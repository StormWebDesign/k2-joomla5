<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

/**
 * Media controller class.
 *
 * @since  3.0.0
 */
class MediaController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $default_view = 'media';

    /**
     * Connector for elFinder.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function connector()
    {
        // Check token
        if (!Session::checkToken('get')) {
            $this->app->close();
        }

        // Check authorization
        $user = Factory::getApplication()->getIdentity();
        if ($user->guest) {
            $this->app->close();
        }

        // Get params
        $params = ComponentHelper::getParams('com_media');
        $root = $params->get('file_path', 'images');

        // elFinder configuration
        $opts = [
            'debug' => false,
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => JPATH_ROOT . '/' . $root,
                    'URL' => Uri::root() . $root . '/',
                    'accessControl' => [$this, 'accessControl'],
                    'uploadAllow' => [
                        'image/png',
                        'image/gif',
                        'image/jpeg',
                        'image/webp',
                        'application/pdf',
                        'application/zip',
                        'audio/mpeg',
                        'video/mp4',
                        'video/webm'
                    ],
                    'uploadDeny' => ['all'],
                    'uploadOrder' => ['allow', 'deny'],
                ]
            ]
        ];

        // Load elFinder
        $elFinderPath = JPATH_ROOT . '/media/k2/assets/vendors/studio-42/elfinder/php/';

        if (file_exists($elFinderPath . 'autoload.php')) {
            require_once $elFinderPath . 'autoload.php';

            // Run elFinder
            $connector = new \elFinderConnector(new \elFinder($opts));
            $connector->run();
        }

        $this->app->close();
    }

    /**
     * Access control for elFinder.
     *
     * @param   string  $attr     Attribute name
     * @param   string  $path     File path
     * @param   mixed   $data     Extra data
     * @param   mixed   $volume   Volume driver
     *
     * @return  boolean|null
     *
     * @since   3.0.0
     */
    public function accessControl($attr, $path, $data, $volume)
    {
        // Hide files starting with .
        return strpos(basename($path), '.') === 0
            ? !($attr === 'read' || $attr === 'write')
            : null;
    }
}
