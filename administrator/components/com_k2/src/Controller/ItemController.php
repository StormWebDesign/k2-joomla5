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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Item controller class.
 *
 * @since  3.0.0
 */
class ItemController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  3.0.0
     */
    protected $text_prefix = 'COM_K2_ITEM';

    /**
     * Method override to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowAdd($data = [])
    {
        $categoryId = isset($data['catid']) ? (int) $data['catid'] : 0;

        if ($categoryId) {
            return Factory::getApplication()->getIdentity()->authorise('core.create', 'com_k2.category.' . $categoryId);
        }

        return parent::allowAdd($data);
    }

    /**
     * Method override to check if you can edit an existing record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key; default is id.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
        $user = Factory::getApplication()->getIdentity();

        // Zero record (id), return component edit permission by action
        if (!$recordId) {
            return $user->authorise('core.edit', 'com_k2');
        }

        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_k2.item.' . $recordId)) {
            return true;
        }

        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_k2.item.' . $recordId)) {
            // Need to do a lookup from the model to get the owner
            $record = $this->getModel()->getItem($recordId);

            if (empty($record)) {
                return false;
            }

            $ownerId = $record->created_by;

            // If the owner matches 'me' then allow editing
            if ($ownerId == $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method to run batch operations.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean   True if successful, false otherwise and internal error is set.
     *
     * @since   3.0.0
     */
    public function batch($model = null)
    {
        $this->checkToken();

        // Set the model
        $model = $this->getModel('Item', 'Administrator', []);

        // Preset the redirect
        $this->setRedirect(Route::_('index.php?option=com_k2&view=items' . $this->getRedirectToListAppend(), false));

        return parent::batch($model);
    }

    /**
     * Gets the URL arguments to append to a list redirect.
     *
     * @return  string  The arguments to append to the redirect URL.
     *
     * @since   3.0.0
     */
    protected function getRedirectToListAppend()
    {
        $tmpl = $this->input->get('tmpl');
        $append = '';

        if ($tmpl) {
            $append .= '&tmpl=' . $tmpl;
        }

        return $append;
    }

    /**
     * Method to save an item.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.0.0
     */
    public function save($key = null, $urlVar = null)
    {
        $result = parent::save($key, $urlVar);

        // Handle extra fields, image upload, attachments, etc.
        if ($result) {
            $this->processItemMedia();
        }

        return $result;
    }

    /**
     * Process item media (images, attachments, gallery, video).
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processItemMedia()
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_k2');
        $data = $this->input->post->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);

        if (!$id) {
            return;
        }

        // Process image upload
        $this->processItemImage($id, $params);

        // Process attachments
        $this->processAttachments($id, $params);

        // Process gallery
        $this->processGallery($id, $params);

        // Process video
        $this->processVideo($id, $params);

        // Process tags
        $this->processTags($id);

        // Process extra fields
        $this->processExtraFields($id);
    }

    /**
     * Process item image upload.
     *
     * @param   int     $id      The item ID.
     * @param   object  $params  The component params.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processItemImage($id, $params)
    {
        $files = $this->input->files->get('jform', [], 'array');

        if (!isset($files['image']) || empty($files['image']['name'])) {
            return;
        }

        $file = $files['image'];

        // Validate file
        if ($file['error'] !== 0) {
            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
        $ext = strtolower(File::getExt($file['name']));

        if (!in_array($ext, $allowedExtensions)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_K2_INVALID_IMAGE_TYPE'), 'error');
            return;
        }

        // Generate filename based on item ID
        $filename = md5('Image' . $id) . '.jpg';
        $savePath = JPATH_ROOT . '/media/k2/items/src/';

        if (!Folder::exists($savePath)) {
            Folder::create($savePath);
        }

        // Move uploaded file
        if (!File::upload($file['tmp_name'], $savePath . $filename)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_K2_ERROR_UPLOADING_IMAGE'), 'error');
            return;
        }

        // Generate image sizes
        $this->generateImageSizes($id, $savePath . $filename, $params);
    }

    /**
     * Generate different image sizes.
     *
     * @param   int     $id        The item ID.
     * @param   string  $source    The source image path.
     * @param   object  $params    The component params.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function generateImageSizes($id, $source, $params)
    {
        $sizes = [
            'XS' => (int) $params->get('itemImageXS', 100),
            'S' => (int) $params->get('itemImageS', 200),
            'M' => (int) $params->get('itemImageM', 400),
            'L' => (int) $params->get('itemImageL', 600),
            'XL' => (int) $params->get('itemImageXL', 900),
            'Generic' => (int) $params->get('itemImageGeneric', 300),
        ];

        $cachePath = JPATH_ROOT . '/media/k2/items/cache/';

        if (!Folder::exists($cachePath)) {
            Folder::create($cachePath);
        }

        $filename = md5('Image' . $id);

        foreach ($sizes as $sizeKey => $width) {
            $this->resizeImage($source, $cachePath . $filename . '_' . $sizeKey . '.jpg', $width, $params);
        }
    }

    /**
     * Resize an image.
     *
     * @param   string  $source       The source image path.
     * @param   string  $destination  The destination path.
     * @param   int     $width        The target width.
     * @param   object  $params       The component params.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function resizeImage($source, $destination, $width, $params)
    {
        if (!File::exists($source)) {
            return false;
        }

        $quality = (int) $params->get('imagesQuality', 90);

        // Get image info
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calculate new dimensions
        $ratio = $sourceWidth / $sourceHeight;
        $newWidth = $width;
        $newHeight = (int) ($width / $ratio);

        // Create source image resource based on mime type
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Create destination image
        $destImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
        }

        // Resize
        imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        // Save
        $result = imagejpeg($destImage, $destination, $quality);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($destImage);

        return $result;
    }

    /**
     * Process attachments.
     *
     * @param   int     $id      The item ID.
     * @param   object  $params  The component params.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processAttachments($id, $params)
    {
        // Attachment processing would go here
        // This is a simplified version - full implementation would handle file uploads
    }

    /**
     * Process gallery.
     *
     * @param   int     $id      The item ID.
     * @param   object  $params  The component params.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processGallery($id, $params)
    {
        // Gallery processing would go here
    }

    /**
     * Process video.
     *
     * @param   int     $id      The item ID.
     * @param   object  $params  The component params.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processVideo($id, $params)
    {
        // Video processing would go here
    }

    /**
     * Process tags.
     *
     * @param   int  $id  The item ID.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processTags($id)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $tags = $this->input->post->get('tags', [], 'array');

        // Delete existing tag references
        $query = $db->getQuery(true)
            ->delete('#__k2_tags_xref')
            ->where('itemID = ' . (int) $id);
        $db->setQuery($query);
        $db->execute();

        // Insert new tag references
        foreach ($tags as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId > 0) {
                $query = $db->getQuery(true)
                    ->insert('#__k2_tags_xref')
                    ->columns(['tagID', 'itemID'])
                    ->values($tagId . ', ' . (int) $id);
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
     * Process extra fields.
     *
     * @param   int  $id  The item ID.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    protected function processExtraFields($id)
    {
        $extraFields = $this->input->post->get('K2ExtraField', [], 'array');

        if (empty($extraFields)) {
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $objects = [];

        foreach ($extraFields as $fieldId => $value) {
            $object = new \stdClass();
            $object->id = (int) str_replace('_', '', $fieldId);
            $object->value = is_array($value) ? $value : [$value];
            $objects[] = $object;
        }

        $extraFieldsData = json_encode($objects);

        // Build search index
        $extraFieldsSearch = '';
        foreach ($objects as $object) {
            if (is_array($object->value)) {
                $extraFieldsSearch .= implode(' ', $object->value) . ' ';
            } else {
                $extraFieldsSearch .= $object->value . ' ';
            }
        }

        // Update item
        $query = $db->getQuery(true)
            ->update('#__k2_items')
            ->set($db->quoteName('extra_fields') . ' = ' . $db->quote($extraFieldsData))
            ->set($db->quoteName('extra_fields_search') . ' = ' . $db->quote(trim($extraFieldsSearch)))
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Delete an attachment.
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function deleteAttachment()
    {
        $this->checkToken('get');

        $id = $this->input->getInt('id');
        $itemId = $this->input->getInt('itemID');

        if ($id && $itemId) {
            $model = $this->getModel();
            $model->deleteAttachment($id);
        }

        $this->setRedirect(Route::_('index.php?option=com_k2&view=item&layout=edit&id=' . $itemId, false));
    }
}
