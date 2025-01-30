<?php

namespace Drupal\news_feed_date_formatter\Plugin\Filter\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 */
#[FieldFormatter(
  id: 'news_feed_date',
  label: new TranslatableMarkup('News feed date'),
  field_types: [
    'timestamp',
    'created',
    'changed',
  ],
)]
class NewsFeedDateFormatter extends FormatterBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a NewsFeedDateFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $updated = $this->formatTimestamp($item->value);
      }
      else {
        $updated = ['#markup' => ''];
      }

      $elements[$delta] = $updated;
    }

    return $elements;
  }

  /**
   * Formats a timestamp.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   *
   * @return array
   *   The formatted timestamp string.
   */
  protected function formatTimestamp($timestamp) {
    if (date('Y-m-d', $timestamp) === date('Y-m-d')) {
      $format = 'H\hi';
    }
    else {
      $format = 'd M.';
    }

    // @see \Drupal\Core\Datetime\DateFormatterInterface::format().
    $short_date = $this->dateFormatter->format($timestamp, 'custom', $format);

    $build = [
      '#markup' => new FormattableMarkup(
          '@short_date',
          ['@short_date' => $short_date->getString()]
      ),
    ];

    CacheableMetadata::createFromObject($short_date)->applyTo($build);
    return $build;
  }

}
