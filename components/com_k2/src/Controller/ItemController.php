<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\K2\Site\Helper\RouteHelper;

/**
 * K2 Site Item Controller
 *
 * @since  3.0.0
 */
class ItemController extends FormController
{
    /**
     * The URL view item variable.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $view_item = 'item';

    /**
     * The URL view list variable.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $view_list = 'itemlist';

    /**
     * Method to add a new item.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function add()
    {
        $app = Factory::getApplication();

        // Get the current user
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_k2')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->redirect(Route::_('index.php?option=com_k2&view=itemlist', false));
            return;
        }

        // Clear the item edit information from the session
        $app->setUserState('com_k2.edit.item.id', null);
        $app->setUserState('com_k2.edit.item.data', null);

        // Redirect to the edit screen
        $this->setRedirect(Route::_('index.php?option=com_k2&view=item&layout=edit', false));
    }

    /**
     * Method to edit an existing item.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key.
     *
     * @return  boolean  True if access level check and target ID pass, false otherwise.
     *
     * @since   3.0.0
     */
    public function edit($key = null, $urlVar = 'id')
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $id = $this->input->getInt('id', 0);

        // Get the item
        $model = $this->getModel();
        $item = $model->getItem($id);

        if (!$item) {
            $app->enqueueMessage(Text::_('K2_ITEM_NOT_FOUND'), 'error');
            $app->redirect(Route::_('index.php?option=com_k2&view=itemlist', false));
            return false;
        }

        // Check if the user can edit this item
        $canEdit = $user->authorise('core.edit', 'com_k2.item.' . $id);
        $canEditOwn = $user->authorise('core.edit.own', 'com_k2') && $item->created_by == $user->id;

        if (!$canEdit && !$canEditOwn) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->redirect(Route::_('index.php?option=com_k2&view=itemlist', false));
            return false;
        }

        // Set the item id in session for editing
        $app->setUserState('com_k2.edit.item.id', $id);

        // Redirect to the edit screen
        $this->setRedirect(Route::_('index.php?option=com_k2&view=item&layout=edit&id=' . $id, false));

        return true;
    }

    /**
     * Method to save an item.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.0.0
     */
    public function save($key = null, $urlVar = 'id')
    {
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $model = $this->getModel();
        $data = $this->input->post->get('jform', [], 'array');
        $id = isset($data['id']) ? (int) $data['id'] : 0;

        // Check permissions
        if ($id) {
            $item = $model->getItem($id);
            $canEdit = $user->authorise('core.edit', 'com_k2.item.' . $id);
            $canEditOwn = $user->authorise('core.edit.own', 'com_k2') && $item && $item->created_by == $user->id;

            if (!$canEdit && !$canEditOwn) {
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
                return false;
            }
        } else {
            if (!$user->authorise('core.create', 'com_k2')) {
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
                return false;
            }
        }

        // Validate the posted data
        $form = $model->getForm($data, false);

        if (!$form) {
            $app->enqueueMessage($model->getError(), 'error');
            return false;
        }

        // Validate the data
        $validData = $model->validate($form, $data);

        if ($validData === false) {
            $errors = $model->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($error, 'warning');
                }
            }

            // Save the data in the session
            $app->setUserState('com_k2.edit.item.data', $data);

            // Redirect back to the edit screen
            $this->setRedirect(
                Route::_('index.php?option=com_k2&view=item&layout=edit&id=' . $id, false)
            );

            return false;
        }

        // Attempt to save the data
        if (!$model->save($validData)) {
            // Save the data in the session
            $app->setUserState('com_k2.edit.item.data', $data);

            // Redirect back to the edit screen
            $this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
            $this->setRedirect(
                Route::_('index.php?option=com_k2&view=item&layout=edit&id=' . $id, false)
            );

            return false;
        }

        // Clear the item data in the session
        $app->setUserState('com_k2.edit.item.id', null);
        $app->setUserState('com_k2.edit.item.data', null);

        // Get the saved item id
        $id = $model->getState('item.id');

        // Redirect to the item
        $app->enqueueMessage(Text::_('K2_ITEM_SAVED'));
        $this->setRedirect(RouteHelper::getItemRoute($id));

        return true;
    }

    /**
     * Method to cancel an edit.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since   3.0.0
     */
    public function cancel($key = 'id')
    {
        $app = Factory::getApplication();

        // Clear the edit item state
        $app->setUserState('com_k2.edit.item.id', null);
        $app->setUserState('com_k2.edit.item.data', null);

        // Redirect to the itemlist view
        $this->setRedirect(Route::_('index.php?option=com_k2&view=itemlist', false));

        return true;
    }
}
