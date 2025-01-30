<?php

namespace Drupal\news_feed_date_formatter\Plugin\Field\FieldFormatter;

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
   * The date format for deciding if two dates are the same day.
   *
   * @var string
   */
  protected $dateCompareToday;

  /**
   * The day today in Y-m-d format.
   *
   * @var string
   */
  protected $today;

  /**
   * The date format for the datetime attribute.
   *
   * @var string
   */
  protected $datetimeFormat;

  /**
   * The date format for the title attribute.
   *
   * @var string
   */
  protected $titleFormat;

  /**
   * The date format for the short date in today mode.
   *
   * @var string
   */
  protected $shortFormatToday;

  /**
   * The date format for the short date in yesterday mode.
   *
   * @var string
   */
  protected $shortFormatYesterday;

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
    $this->dateCompareToday = 'Y-m-d';
    $this->today = date($this->dateCompareToday);
    $this->datetimeFormat = 'c';
    $this->titleFormat = 'l j F à G\hi';
    $this->shortFormatToday = 'H\hi';
    $this->shortFormatYesterday = 'd M.';
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
      $timestamp = $item->value;

      if ($timestamp) {
        // Determine the short date format.
        if (date($this->dateCompareToday, $timestamp) === $this->today) {
          $short_format = $this->shortFormatToday;
          $class = 'today';
        }
        else {
          $short_format = $this->shortFormatYesterday;
          $class = 'yesterday';
        }

        // Compute the short date.
        $short_date = $this->dateFormatter->format(
          $timestamp,
          'custom',
          $short_format
        );

        // Compute the title date.
        $title_date = $this->dateFormatter->format(
          $timestamp,
          'custom',
          $this->titleFormat
        );

        // Compute the datetime date.
        $datetime_date = $this->dateFormatter->format(
          $timestamp,
          'custom',
          $this->datetimeFormat
        );

        $news_feed_date = [
          '#theme' => 'time',
          '#text' => $short_date,
          '#attributes' => [
            'class' => [$class],
            'datetime' => $datetime_date,
            'title' => $title_date,
          ],
          '#cache' => [
            'contexts' => ['timezone'],
          ],
        ];
      }
      else {
        $news_feed_date = ['#markup' => ''];
      }

      $elements[$delta] = $news_feed_date;
    }

    return $elements;
  }

}
