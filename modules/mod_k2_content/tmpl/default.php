<?php
/**
 * @version    3.0.0
 * @package    K2
 * @author     Russell English https://stormwebdesign.co.uk
 * @copyright  Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (empty($items)) {
    return;
}

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
?>
<div class="mod-k2-content<?php echo $moduleclass_sfx; ?>">
    <?php foreach ($items as $item) : ?>
        <div class="mod-k2-content__item">
            <?php if ($params->get('itemImage', 1) && !empty($item->image)) : ?>
                <div class="mod-k2-content__image">
                    <a href="<?php echo $item->link; ?>">
                        <img src="<?php echo $item->image; ?>" alt="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($params->get('itemTitle', 1)) : ?>
                <h3 class="mod-k2-content__title">
                    <a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                </h3>
            <?php endif; ?>

            <?php if ($params->get('itemDate', 1)) : ?>
                <span class="mod-k2-content__date">
                    <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC3')); ?>
                </span>
            <?php endif; ?>

            <?php if ($params->get('itemAuthor', 0)) : ?>
                <span class="mod-k2-content__author">
                    <?php echo Text::sprintf('MOD_K2_CONTENT_WRITTEN_BY', $item->author->name); ?>
                </span>
            <?php endif; ?>

            <?php if ($params->get('itemCategory', 0)) : ?>
                <span class="mod-k2-content__category">
                    <a href="<?php echo $item->category->link; ?>"><?php echo htmlspecialchars($item->category->name, ENT_QUOTES, 'UTF-8'); ?></a>
                </span>
            <?php endif; ?>

            <?php if ($params->get('itemIntroText', 1) && !empty($item->introtext)) : ?>
                <div class="mod-k2-content__introtext">
                    <?php echo $item->introtext; ?>
                </div>
            <?php endif; ?>

            <?php if ($params->get('itemHits', 0)) : ?>
                <span class="mod-k2-content__hits">
                    <?php echo Text::sprintf('MOD_K2_CONTENT_HITS', $item->hits); ?>
                </span>
            <?php endif; ?>

            <?php if ($params->get('itemReadMore', 1)) : ?>
                <a class="mod-k2-content__readmore" href="<?php echo $item->link; ?>">
                    <?php echo Text::_('MOD_K2_CONTENT_READ_MORE'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
