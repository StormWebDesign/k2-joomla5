<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

namespace Joomla\Component\K2\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

/**
 * K2 Utilities Helper
 *
 * @since  3.0.0
 */
class UtilitiesHelper
{
    /**
     * Get user avatar
     *
     * @param   mixed   $userID  User ID or 'alias'
     * @param   string  $email   User email
     * @param   int     $width   Avatar width
     *
     * @return  string  Avatar URL
     *
     * @since   3.0.0
     */
    public static function getAvatar($userID, $email = null, $width = 50)
    {
        $app = Factory::getApplication();
        $params = self::getParams('com_k2');

        // Check for placeholder overrides
        $template = $app->getTemplate();

        if (file_exists(JPATH_SITE . '/templates/' . $template . '/images/placeholder/user.png')) {
            $avatarPath = 'templates/' . $template . '/images/placeholder/user.png';
        } else {
            $avatarPath = 'components/com_k2/images/placeholder/user.png';
        }

        if ($userID === 'alias') {
            return Uri::root(true) . '/' . $avatarPath;
        }

        if ($userID == 0) {
            if ($params->get('gravatar') && !is_null($email)) {
                return 'https://secure.gravatar.com/avatar/' . md5($email) . '?s=' . $width . '&default=' . urlencode(Uri::root() . $avatarPath);
            }

            return Uri::root(true) . '/' . $avatarPath;
        }

        if (is_numeric($userID) && $userID > 0) {
            // Get K2 user profile
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select($db->quoteName('image'))
                ->from($db->quoteName('#__k2_users'))
                ->where($db->quoteName('userID') . ' = ' . (int) $userID);

            $db->setQuery($query);
            $avatar = $db->loadResult();

            if (empty($avatar)) {
                if ($params->get('gravatar') && !is_null($email)) {
                    return 'https://secure.gravatar.com/avatar/' . md5($email) . '?s=' . $width . '&default=' . urlencode(Uri::root() . $avatarPath);
                }

                return Uri::root(true) . '/' . $avatarPath;
            }

            $avatarTimestamp = '';
            $avatarFile = JPATH_SITE . '/media/k2/users/' . $avatar;

            if (is_file($avatarFile) && filemtime($avatarFile)) {
                $avatarTimestamp = '?t=' . date('Ymd_Hi', filemtime($avatarFile));
            }

            return Uri::root(true) . '/media/k2/users/' . $avatar . $avatarTimestamp;
        }

        if (!$params->get('userImageDefault')) {
            return '';
        }

        return Uri::root(true) . '/' . $avatarPath;
    }

    /**
     * Get category image URL
     *
     * @param   string  $image   Image filename
     * @param   object  $params  Component params
     *
     * @return  string|null  Image URL or null
     *
     * @since   3.0.0
     */
    public static function getCategoryImage($image, $params)
    {
        $app = Factory::getApplication();

        if (!empty($image)) {
            $catImageTimestamp = '';
            $catImageFile = JPATH_SITE . '/media/k2/categories/' . $image;

            if (is_file($catImageFile) && filemtime($catImageFile)) {
                $catImageTimestamp = '?t=' . date('Ymd_Hi', filemtime($catImageFile));
            }

            return Uri::root(true) . '/media/k2/categories/' . $image . $catImageTimestamp;
        }

        if ($params->get('catImageDefault')) {
            $template = $app->getTemplate();

            if (is_file(JPATH_SITE . '/templates/' . $template . '/images/placeholder/category.png')) {
                return Uri::root(true) . '/templates/' . $template . '/images/placeholder/category.png';
            }

            return Uri::root(true) . '/components/com_k2/images/placeholder/category.png';
        }

        return null;
    }

    /**
     * Limit text by word count
     *
     * @param   string  $str       Text to limit
     * @param   int     $limit     Word limit
     * @param   string  $end_char  End character
     *
     * @return  string  Limited text
     *
     * @since   3.0.0
     */
    public static function wordLimit($str, $limit = 100, $end_char = '&#8230;')
    {
        if (StringHelper::trim($str) === '') {
            return $str;
        }

        // Always strip tags for text
        $str = strip_tags($str);

        // Clean up whitespace
        $str = preg_replace(["/\r|\n/u", "/\t/u", "/\s\s+/u"], [' ', ' ', ' '], $str);

        preg_match('/\s*(?:\S*\s*){' . (int) $limit . '}/u', $str, $matches);

        if (StringHelper::strlen($matches[0]) === StringHelper::strlen($str)) {
            $end_char = '';
        }

        return StringHelper::rtrim($matches[0]) . $end_char;
    }

    /**
     * Limit text by character count
     *
     * @param   string  $str       Text to limit
     * @param   int     $limit     Character limit
     * @param   string  $end_char  End character
     *
     * @return  string  Limited text
     *
     * @since   3.0.0
     */
    public static function characterLimit($str, $limit = 150, $end_char = '...')
    {
        if (StringHelper::trim($str) === '') {
            return $str;
        }

        // Always strip tags for text
        $str = strip_tags(StringHelper::trim($str));

        // Clean up whitespace
        $str = preg_replace(["/\r|\n/u", "/\t/u", "/\s\s+/u"], [' ', ' ', ' '], $str);

        if (StringHelper::strlen($str) > $limit) {
            $str = StringHelper::substr($str, 0, $limit);

            return StringHelper::rtrim($str) . $end_char;
        }

        return $str;
    }

    /**
     * Clean HTML text
     *
     * @param   string  $text  Text to clean
     *
     * @return  string  Cleaned text
     *
     * @since   3.0.0
     */
    public static function cleanHtml($text)
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get "written by" text based on gender
     *
     * @param   string  $gender  Gender (m/f/n)
     *
     * @return  string  Localized text
     *
     * @since   3.0.0
     */
    public static function writtenBy($gender)
    {
        if (empty($gender) || $gender === 'n') {
            return Text::_('K2_WRITTEN_BY');
        }

        if ($gender === 'm') {
            return Text::_('K2_WRITTEN_BY_MALE');
        }

        if ($gender === 'f') {
            return Text::_('K2_WRITTEN_BY_FEMALE');
        }

        return Text::_('K2_WRITTEN_BY');
    }

    /**
     * Set default image for item based on view context
     *
     * @param   object       $item    Item object
     * @param   string       $view    View name
     * @param   object|null  $params  Component params
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public static function setDefaultImage(&$item, $view, $params = null)
    {
        $imageSize = '';
        $imageWidth = 0;

        if ($view === 'item' && isset($item->params)) {
            $imageSize = $item->params->get('itemImgSize', 'Medium');
        } elseif ($view === 'itemlist' && $params) {
            $itemGroup = $item->itemGroup ?? 'leading';
            $imageSize = $params->get($itemGroup . 'ImgSize', 'Medium');
        } elseif ($view === 'latest' && $params) {
            $imageSize = $params->get('latestItemImageSize', 'Medium');
        } elseif ($view === 'relatedByTag' && $params && $params->get('itemRelatedImageSize')) {
            $imageSize = $params->get('itemRelatedImageSize', 'Medium');
        }

        $imageProperty = 'image' . $imageSize;
        $item->image = $item->$imageProperty ?? '';

        // Set image width based on size
        $widthMap = [
            'XSmall' => 'itemImageXS',
            'Small' => 'itemImageS',
            'Medium' => 'itemImageM',
            'Large' => 'itemImageL',
            'XLarge' => 'itemImageXL',
        ];

        if (isset($widthMap[$imageSize]) && isset($item->params)) {
            $item->imageWidth = $item->params->get($widthMap[$imageSize], 0);
        }
    }

    /**
     * Get component parameters
     *
     * @param   string  $option  Component option
     *
     * @return  object  Component parameters
     *
     * @since   3.0.0
     */
    public static function getParams($option)
    {
        $app = Factory::getApplication();

        if ($app->isClient('site')) {
            return $app->getParams($option);
        }

        return ComponentHelper::getParams($option);
    }

    /**
     * Strip tags except allowed ones
     *
     * @param   string  $string        Text to clean
     * @param   array   $allowed_tags  Array of allowed tag names
     *
     * @return  string  Cleaned text
     *
     * @since   3.0.0
     */
    public static function cleanTags($string, $allowed_tags)
    {
        $allowed_htmltags = [];

        foreach ($allowed_tags as $tag) {
            $allowed_htmltags[] = '<' . $tag . '>';
        }

        return strip_tags($string, implode('', $allowed_htmltags));
    }

    /**
     * Clean HTML tag attributes
     *
     * @param   string  $string      HTML string
     * @param   array   $tag_array   Array of tag names to process
     * @param   array   $attr_array  Array of attributes to remove
     *
     * @return  string  Cleaned HTML
     *
     * @since   3.0.0
     */
    public static function cleanAttributes($string, $tag_array, $attr_array)
    {
        $attr = implode('|', $attr_array);

        foreach ($tag_array as $tag) {
            preg_match_all('#<(' . $tag . ') .+?>#', $string, $matches, PREG_PATTERN_ORDER);

            foreach ($matches[0] as $match) {
                preg_match_all('/(' . $attr . ')=([\\"\\\']).+?([\\"\\\'])/', $match, $matchesAttr, PREG_PATTERN_ORDER);

                foreach ($matchesAttr[0] as $attrToClean) {
                    $string = str_replace($attrToClean, '', $string);
                    $string = preg_replace('|  +|', ' ', $string);
                    $string = str_replace(' >', '>', $string);
                }
            }
        }

        return $string;
    }

    /**
     * Verify reCAPTCHA response
     *
     * @return  boolean  True if verified
     *
     * @since   3.0.0
     */
    public static function verifyRecaptcha()
    {
        $params = ComponentHelper::getParams('com_k2');
        $secret = $params->get('recaptcha_private_key');
        $response = $_POST['g-recaptcha-response'] ?? '';

        if (empty($response)) {
            return false;
        }

        $data = [
            'secret' => $secret,
            'response' => $response,
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        if ($result === false) {
            return false;
        }

        $responseData = json_decode($result);

        return is_object($responseData) && isset($responseData->success) && $responseData->success === true;
    }
}
