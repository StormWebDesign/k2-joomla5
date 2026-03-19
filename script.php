<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/**
 * K2 Installation Script
 *
 * @since  3.0.0
 */
class Com_K2InstallerScript implements InstallerScriptInterface
{
    /**
     * Minimum PHP version required
     *
     * @var    string
     * @since  3.0.0
     */
    protected string $minimumPhp = '8.1.0';

    /**
     * Minimum Joomla version required
     *
     * @var    string
     * @since  3.0.0
     */
    protected string $minimumJoomla = '5.0.0';

    /**
     * Function called before extension installation/update/removal
     *
     * @param   string            $type    The type of change (install, update, discover_install, or uninstall)
     * @param   InstallerAdapter  $parent  The parent object
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_K2_INSTALL_ERROR_PHP_VERSION', $this->minimumPhp, PHP_VERSION),
                'error'
            );
            return false;
        }

        // Check Joomla version
        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_K2_INSTALL_ERROR_JOOMLA_VERSION', $this->minimumJoomla, JVERSION),
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Method called after installation
     *
     * @param   string            $type    The type of change
     * @param   InstallerAdapter  $parent  The parent object
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($type === 'install') {
            $this->createDefaultUserGroups($db);
            $this->createMediaFolders();
        }

        if ($type === 'update') {
            $this->updateDatabaseSchema($db);
        }

        $this->displayInstallationMessage($type);

        return true;
    }

    /**
     * Method called when installing
     *
     * @param   InstallerAdapter  $parent  The parent object
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function install(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Method called when updating
     *
     * @param   InstallerAdapter  $parent  The parent object
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function update(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Method called when uninstalling
     *
     * @param   InstallerAdapter  $parent  The parent object
     *
     * @return  boolean  True on success
     *
     * @since   3.0.0
     */
    public function uninstall(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Create default K2 user groups
     *
     * @param   DatabaseInterface  $db  The database driver
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function createDefaultUserGroups(DatabaseInterface $db): void
    {
        // Check if table exists and has data
        try {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_user_groups'));
            $db->setQuery($query);

            if ($db->loadResult() > 0) {
                return;
            }
        } catch (\Exception $e) {
            // Table might not exist yet, skip this step
            return;
        }

        // Insert default groups
        $groups = [
            [
                'name' => 'Registered',
                'permissions' => json_encode([
                    'comment' => '1',
                    'frontEdit' => '0',
                    'add' => '0',
                    'editOwn' => '0',
                    'editAll' => '0',
                    'publish' => '0',
                    'editPublished' => '0',
                    'inheritance' => '0',
                    'categories' => 'all',
                ]),
            ],
            [
                'name' => 'Site Owner',
                'permissions' => json_encode([
                    'comment' => '1',
                    'frontEdit' => '1',
                    'add' => '1',
                    'editOwn' => '1',
                    'editAll' => '1',
                    'publish' => '1',
                    'editPublished' => '1',
                    'inheritance' => '1',
                    'categories' => 'all',
                ]),
            ],
        ];

        foreach ($groups as $group) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__k2_user_groups'))
                ->columns([$db->quoteName('name'), $db->quoteName('permissions')])
                ->values($db->quote($group['name']) . ', ' . $db->quote($group['permissions']));
            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\Exception $e) {
                // Ignore if already exists
            }
        }
    }

    /**
     * Create media folders
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function createMediaFolders(): void
    {
        $folders = [
            JPATH_ROOT . '/media/k2/items/src',
            JPATH_ROOT . '/media/k2/items/cache',
            JPATH_ROOT . '/media/k2/categories',
            JPATH_ROOT . '/media/k2/users',
            JPATH_ROOT . '/media/k2/attachments',
            JPATH_ROOT . '/media/k2/galleries',
            JPATH_ROOT . '/media/k2/videos',
        ];

        foreach ($folders as $folder) {
            if (!Folder::exists($folder)) {
                Folder::create($folder);
            }
        }
    }

    /**
     * Update database schema for migrations
     *
     * @param   DatabaseInterface  $db  The database driver
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function updateDatabaseSchema(DatabaseInterface $db): void
    {
        // Add language column to categories if not exists
        $columns = $db->getTableColumns('#__k2_categories');

        if (!array_key_exists('language', $columns)) {
            $query = "ALTER TABLE `#__k2_categories` ADD `language` CHAR(7) NOT NULL DEFAULT '*'";
            $db->setQuery($query);
            $db->execute();
        }

        // Add language column to items if not exists
        $columns = $db->getTableColumns('#__k2_items');

        if (!array_key_exists('language', $columns)) {
            $query = "ALTER TABLE `#__k2_items` ADD `language` CHAR(7) NOT NULL DEFAULT '*'";
            $db->setQuery($query);
            $db->execute();
        }

        // Add featured_ordering column to items if not exists
        if (!array_key_exists('featured_ordering', $columns)) {
            $query = "ALTER TABLE `#__k2_items` ADD `featured_ordering` INT(11) NOT NULL DEFAULT '0' AFTER `featured`";
            $db->setQuery($query);
            $db->execute();
        }

        // Create log table if not exists
        $query = "CREATE TABLE IF NOT EXISTS `#__k2_log` (
            `status` INT(11) NOT NULL,
            `response` TEXT NOT NULL,
            `timestamp` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci";
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Display installation message
     *
     * @param   string  $type  The installation type
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function displayInstallationMessage(string $type): void
    {
        $message = $type === 'install'
            ? Text::_('COM_K2_INSTALLATION_SUCCESS')
            : Text::_('COM_K2_UPDATE_SUCCESS');

        echo '<div class="alert alert-success">';
        echo '<h3>K2 for Joomla 5</h3>';
        echo '<p>' . $message . '</p>';
        echo '<p>Version: 3.0.0</p>';
        echo '</div>';
    }
}
