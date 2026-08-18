<?php

declare(strict_types=1);

namespace Drupal\gt_components\Hook;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Runtime theme and preprocess hooks for GT Components.
 *
 * Auto-discovered by HookCollectorPass via #[Hook] attributes.
 * No services.yml registration required.
 */
final class GtComponentsHooks {

  /**
   * Registers the GT Card block template.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'block__block_content__gt_card' => [
        'base hook' => 'block',
        'template'  => 'block--block-content--gt-card',
        'path'      => $path . '/templates/block',
      ],
    ];
  }

  /**
   * Ensures the GT Card template suggestion fires in all placement contexts.
   *
   * Layout Builder inline blocks use a different plugin ID path and may not
   * produce the bundle-specific suggestion automatically.
   */
  #[Hook('theme_suggestions_block_alter')]
  public function themeSuggestionsBlockAlter(array &$suggestions, array $variables): void {
    $block_content = $variables['elements']['content']['#block_content']
      ?? $variables['elements']['#block_content']
      ?? NULL;

    if ($block_content instanceof BlockContentInterface && $block_content->bundle() === 'gt_card') {
      $suggestions[] = 'block__block_content__gt_card';
    }
  }

  /**
   * Prepares template variables for GT Card blocks.
   *
   * Sets: gt_card_classes, gt_card_has_media, gt_card_has_link, gt_card_link.
   * Attaches the gt_card library. Clears Drupal's default block classes.
   * Adds a stable element ID for aria-labelledby.
   */
  #[Hook('preprocess_block__block_content__gt_card')]
  public function preprocessBlockBlockContentGtCard(array &$variables): void {
    $block_content = $variables['elements']['content']['#block_content']
      ?? $variables['elements']['#block_content']
      ?? NULL;

    if (!$block_content instanceof BlockContentInterface) {
      return;
    }

    $variables['#attached']['library'][] = 'gt_components/gt_card';

    // Stable ID for aria-labelledby; falls back to 'preview' for unsaved LB blocks.
    $entity_id = (string) ($block_content->id() ?? 'preview');
    if (empty($variables['attributes']['id'])) {
      $variables['attributes']['id'] = 'gt-card-' . $entity_id;
    }

    // Clear Drupal's default block classes; template controls all BEM output.
    $variables['attributes']['class'] = [];

    // Build BEM class list from selected variant values.
    $card_classes = ['gt-card'];

    if ($block_content->hasField('field_gt_card_variant') && !$block_content->get('field_gt_card_variant')->isEmpty()) {
      foreach ($block_content->get('field_gt_card_variant') as $variant_item) {
        $raw = trim((string) ($variant_item->value ?? ''));
        if ($raw !== '') {
          // Strip everything except lowercase letters, digits, hyphens.
          $safe = preg_replace('/[^a-z0-9\-]/', '', strtolower($raw));
          if ($safe !== '') {
            $card_classes[] = 'gt-card--' . $safe;
          }
        }
      }
    }

    $variables['gt_card_classes']   = $card_classes;
    $variables['gt_card_has_media'] = FALSE;
    $variables['gt_card_has_link']  = FALSE;
    $variables['gt_card_link']      = NULL;

    if ($block_content->hasField('field_gt_card_media') && !$block_content->get('field_gt_card_media')->isEmpty()) {
      $variables['gt_card_has_media'] = TRUE;
    }

    if ($block_content->hasField('field_gt_card_link') && !$block_content->get('field_gt_card_link')->isEmpty()) {
      $link_item  = $block_content->get('field_gt_card_link')->first();
      $url        = $link_item->getUrl();
      $link_title = trim((string) ($link_item->title ?? ''));

      // Accessible label priority: link title → card title → translatable fallback.
      $header_text = '';
      if ($block_content->hasField('field_gt_card_header') && !$block_content->get('field_gt_card_header')->isEmpty()) {
        $header_text = trim((string) $block_content->get('field_gt_card_header')->value);
      }

      $variables['gt_card_has_link'] = TRUE;
      $variables['gt_card_link']     = [
        'url'         => $url->toString(),
        'title'       => $link_title,
        'is_external' => $url->isExternal(),
        'aria_label'  => $link_title ?: $header_text ?: (string) new TranslatableMarkup('Read more'),
      ];
    }
  }

}
