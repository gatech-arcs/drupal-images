<?php

declare(strict_types=1);

namespace Drupal\gt_components\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * GT Grid Container — CSS Grid layout plugin for Layout Builder.
 *
 * Per-instance config (columns, gaps, max-width, padding, full-bleed) is
 * emitted as inline CSS custom properties on the wrapper element and consumed
 * by gt-grid-container.css via var(--gt-grid-*).
 */
#[Layout(
  id: 'gt_grid_container',
  label: new TranslatableMarkup('GT Grid Container'),
  category: new TranslatableMarkup('Georgia Tech'),
  description: new TranslatableMarkup('Responsive CSS Grid container. Configurable columns, gaps, max-width, and optional full-bleed. Collapses to single column on narrow viewports.'),
  template: 'layout/gt-grid-container',
  library: 'gt_components/gt_grid_container',
  regions: [
    'content' => ['label' => 'Content'],
  ],
)]
final class GtGridContainerLayout extends LayoutDefault {

  /** Accepts: digit(s) + CSS unit, bare 0, or auto. */
  private const CSS_VALUE_PATTERN = '/^\d+(\.\d+)?(px|rem|em|%|vw|vh|dvw|dvh|svw|svh|fr|ch|ex)$|^0$|^auto$/';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'columns'        => 3,
      'column_gap'     => '1.5rem',
      'row_gap'        => '1.5rem',
      'max_width'      => '1200px',
      'padding'        => '1rem',
      'item_min_width' => '280px',
      'full_bleed'     => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $c = $this->getConfiguration();

    $form['columns'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Columns'),
      '#description'   => $this->t('Number of columns at full width. Collapses fluidly on narrow viewports.'),
      '#default_value' => $c['columns'],
      '#min'           => 1,
      '#max'           => 12,
      '#required'      => TRUE,
    ];

    $form['item_min_width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Item minimum width'),
      '#description'   => $this->t('Minimum width per grid item before collapsing (e.g. <code>280px</code>, <code>18rem</code>).'),
      '#default_value' => $c['item_min_width'],
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['column_gap'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Column gap'),
      '#default_value' => $c['column_gap'],
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['row_gap'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Row gap'),
      '#default_value' => $c['row_gap'],
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['max_width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Maximum width'),
      '#description'   => $this->t('Inner content max-width (e.g. <code>1200px</code>). The static @media breakpoint in gt-grid-container.css defaults to 1200px; add a theme override if you change this.'),
      '#default_value' => $c['max_width'],
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['padding'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Edge padding'),
      '#description'   => $this->t('Inline padding below max-width (e.g. <code>1rem</code>).'),
      '#default_value' => $c['padding'],
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['full_bleed'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Full bleed (100vw)'),
      '#description'   => $this->t('Extends the container background to the full viewport width. Requires a non-<code>overflow: hidden</code> parent context.'),
      '#default_value' => $c['full_bleed'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    $css_fields = [
      'column_gap'     => $this->t('Column gap'),
      'row_gap'        => $this->t('Row gap'),
      'max_width'      => $this->t('Maximum width'),
      'padding'        => $this->t('Edge padding'),
      'item_min_width' => $this->t('Item minimum width'),
    ];

    foreach ($css_fields as $field => $label) {
      $value = trim($form_state->getValue($field, ''));
      if (!preg_match(self::CSS_VALUE_PATTERN, $value)) {
        $form_state->setErrorByName($field, $this->t('@label must be a valid CSS measurement (e.g. 1rem, 24px). Given: @v', [
          '@label' => $label,
          '@v'     => $value,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['columns']        = (int) $form_state->getValue('columns');
    $this->configuration['column_gap']     = trim($form_state->getValue('column_gap'));
    $this->configuration['row_gap']        = trim($form_state->getValue('row_gap'));
    $this->configuration['max_width']      = trim($form_state->getValue('max_width'));
    $this->configuration['padding']        = trim($form_state->getValue('padding'));
    $this->configuration['item_min_width'] = trim($form_state->getValue('item_min_width'));
    $this->configuration['full_bleed']     = (bool) $form_state->getValue('full_bleed');
  }

}
