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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

/**
 * Extra Field controller class.
 *
 * @since  3.0.0
 */
class ExtrafieldController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_EXTRAFIELD';

    /**
     * Method to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowAdd($data = [])
    {
        return Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_k2');
    }

    /**
     * Method to check if you can edit a record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        return Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_k2');
    }

    /**
     * Override save to handle extra field value structure.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.0.0
     */
    public function save($key = null, $urlVar = null)
    {
        // Process extra field values
        $this->processExtraFieldValues();

        return parent::save($key, $urlVar);
    }

    /**
     * Process extra field values and convert to JSON.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processExtraFieldValues()
    {
        $data = $this->input->post->get('jform', [], 'array');
        $type = $data['type'] ?? 'textfield';

        $optionNames = $this->input->post->get('option_name', [], 'array');
        $optionValues = $this->input->post->get('option_value', [], 'array');
        $optionTargets = $this->input->post->get('option_target', [], 'array');

        $objects = [];

        if (!empty($optionNames)) {
            foreach ($optionNames as $key => $name) {
                $object = new \stdClass();
                $object->name = $name;

                if (in_array($type, ['select', 'multipleSelect', 'radio'])) {
                    $object->value = $key + 1;
                } elseif ($type === 'link') {
                    $value = $optionValues[$key] ?? '';
                    if ($value && !preg_match('/^https?:\/\//', $value)) {
                        $value = 'http://' . $value;
                    }
                    $object->value = $value;
                } else {
                    $object->value = $optionValues[$key] ?? '';
                }

                $object->target = $optionTargets[$key] ?? '_self';
                $objects[] = $object;
            }
        }

        // Set the JSON value back
        $data['value'] = json_encode($objects);
        $this->input->post->set('jform', $data);
    }
}
