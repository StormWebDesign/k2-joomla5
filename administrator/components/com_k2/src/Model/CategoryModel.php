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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Registry\Registry;

/**
 * K2 Category Model
 *
 * @since  3.0.0
 */
class CategoryModel extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     * @since  3.0.0
     */
    public $typeAlias = 'com_k2.category';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_CATEGORY';

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     *
     * @since   3.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_k2.category', 'category', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   3.0.0
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_k2.edit.category.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  \Joomla\CMS\Object\CMSObject|boolean  Object on success, false on failure.
     *
     * @since   3.0.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // Parse params
            if (!empty($item->params)) {
                $item->params = new Registry($item->params);
            }

            // Parse plugins
            if (!empty($item->plugins)) {
                $item->plugins = new Registry($item->plugins);
            }
        }

        return $item;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  The Table object
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function prepareTable($table)
    {
        // Generate alias
        if (empty($table->alias)) {
            $table->alias = $table->name;
        }
        $table->alias = OutputFilter::stringURLSafe($table->alias);

        // Check for unique alias
        $table->alias = $this->generateUniqueAlias($table);

        // Set ordering
        if (empty($table->id)) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('ordering') . ')')
                ->from($db->quoteName('#__k2_categories'))
                ->where($db->quoteName('parent') . ' = ' . (int) $table->parent);
            $db->setQuery($query);
            $max = $db->loadResult();
            $table->ordering = $max + 1;
        }
    }

    /**
     * Generate a unique alias for a category.
     *
     * @param   Table  $table  The table object.
     *
     * @return  string  The unique alias.
     *
     * @since   3.0.0
     */
    protected function generateUniqueAlias($table)
    {
        $db = $this->getDatabase();
        $alias = $table->alias;
        $i = 2;

        while (true) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__k2_categories'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));

            if ($table->id) {
                $query->where($db->quoteName('id') . ' != ' . (int) $table->id);
            }

            $db->setQuery($query);
            $count = $db->loadResult();

            if ($count == 0) {
                break;
            }

            $alias = $table->alias . '-' . $i;
            $i++;
        }

        return $alias;
    }

    /**
     * Method to get the table.
     *
     * @param   string  $name    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   3.0.0
     */
    public function getTable($name = 'Category', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($name, $prefix, $config);
    }
}
