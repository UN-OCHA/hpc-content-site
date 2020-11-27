<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'gho_number' formatter.
 *
 * Language aware number formatter with 3 formats:
 * - decimal with grouped thousands (ex: 1,200,000)
 * - compact - long (ex: 1.2 million)
 * - compact - short (ex: 1.2M)
 *
 * @FieldFormatter(
 *   id = "gho_number",
 *   label = @Translation("GHO number"),
 *   field_types = {
 *     "int",
 *     "float",
 *     "bigint"
 *   }
 * )
 */
class GhoNumberFormatter extends FormatterBase {

  // Trait used to filter the prefix/suffix.
  use AllowedTagsXssTrait;

  /**
   * Patterns for the long and short compact number formatting.
   *
   * Note: we are only interested in powers of 1000 and only support Arabic,
   * English, French and Spanish so the values here reflect that.
   *
   * @var array
   *
   * @see https://github.com/twitter/twitter-cldr-js/blob/master/lib/assets/javascripts/twitter_cldr/ar.js
   * @see https://github.com/twitter/twitter-cldr-js/blob/master/lib/assets/javascripts/twitter_cldr/en.js
   * @see https://github.com/twitter/twitter-cldr-js/blob/master/lib/assets/javascripts/twitter_cldr/es.js
   * @see https://github.com/twitter/twitter-cldr-js/blob/master/lib/assets/javascripts/twitter_cldr/fr.js
   *
   * @see https://unicode-org.github.io/cldr-staging/charts/38/supplemental/language_plural_rules.html#ar
   * @see https://unicode-org.github.io/cldr-staging/charts/38/supplemental/language_plural_rules.html#en
   * @see https://unicode-org.github.io/cldr-staging/charts/38/supplemental/language_plural_rules.html#es
   * @see https://unicode-org.github.io/cldr-staging/charts/38/supplemental/language_plural_rules.html#fr
   */
  protected $patterns = [
    // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/ar.html
    "ar" => [
      "long" => [
        "1000" => [
          "few" => "0 آلاف",
          "many" => "0 ألف",
          "one" => "0 ألف",
          "other" => "0 ألف",
          "two" => "0 ألف",
          "zero" => "0 ألف",
        ],
        "1000000" => [
          "few" => "0 ملايين",
          "many" => "0 مليون",
          "one" => "0 مليون",
          "other" => "0 مليون",
          "two" => "0 مليون",
          "zero" => "0 مليون",
        ],
        "1000000000" => [
          "few" => "0 بلايين",
          "many" => "0 بليون",
          "one" => "0 بليون",
          "other" => "0 بليون",
          "two" => "0 بليون",
          "zero" => "0 بليون",
        ],
        "1000000000000" => [
          "few" => "0 تريليونات",
          "many" => "0 تريليون",
          "one" => "0 تريليون",
          "other" => "0 تريليون",
          "two" => "0 تريليون",
          "zero" => "0 تريليون",
        ],
      ],
      "short" => [
        "1000" => [
          "few" => "0 آلاف",
          "many" => "0 ألف",
          "one" => "0 ألف",
          "other" => "0 ألف",
          "two" => "0 ألف",
          "zero" => "0 ألف",
        ],
        "1000000" => [
          "few" => "0 مليو",
          "many" => "0 مليو",
          "one" => "0 مليو",
          "other" => "0 مليو",
          "two" => "0 مليو",
          "zero" => "0 مليو",
        ],
        "1000000000" => [
          "few" => "0 بليو",
          "many" => "0 بليو",
          "one" => "0 بليو",
          "other" => "0 بليو",
          "two" => "0 بليو",
          "zero" => "0 بليو",
        ],
        "1000000000000" => [
          "few" => "0 ترليو",
          "many" => "0 ترليو",
          "one" => "0 ترليو",
          "other" => "0 ترليو",
          "two" => "0 ترليو",
          "zero" => "0 ترليو",
        ],
      ],
    ],
    // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/en.html
    "en" => [
      "long" => [
        "1000" => [
          "one" => "0 thousand",
          "other" => "0 thousand",
        ],
        "1000000" => [
          "one" => "0 million",
          "other" => "0 million",
        ],
        "1000000000" => [
          "one" => "0 billion",
          "other" => "0 billion",
        ],
        "1000000000000" => [
          "one" => "0 trillion",
          "other" => "0 trillion",
        ],
      ],
      "short" => [
        "1000" => [
          "one" => "0K",
          "other" => "0K",
        ],
        "1000000" => [
          "one" => "0M",
          "other" => "0M",
        ],
        "1000000000" => [
          "one" => "0B",
          "other" => "0B",
        ],
        "1000000000000" => [
          "one" => "0T",
          "other" => "0T",
        ],
      ],
    ],
    // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/es.html
    "es" => [
      "long" => [
        "1000" => [
          "one" => "0 mil",
          "other" => "0 mil",
        ],
        "1000000" => [
          "one" => "0 millón",
          "other" => "0 millones",
        ],
        "1000000000" => [
          "one" => "0 mil millones",
          "other" => "0 mil millones",
        ],
        "1000000000000" => [
          "one" => "0 billón",
          "other" => "0 billones",
        ],
      ],
      "short" => [
        "1000" => [
          "one" => "0K",
          "other" => "0K",
        ],
        "1000000" => [
          "one" => "0M",
          "other" => "0M",
        ],
        "1000000000" => [
          "one" => "0000M",
          "other" => "0000M",
        ],
        "1000000000000" => [
          "one" => "0B",
          "other" => "0B",
        ],
      ],
    ],
    // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/fr.html
    "fr" => [
      "long" => [
        "1000" => [
          "one" => "0 millier",
          "other" => "0 mille",
        ],
        "1000000" => [
          "one" => "0 million",
          "other" => "0 millions",
        ],
        "1000000000" => [
          "one" => "0 milliard",
          "other" => "0 milliards",
        ],
        "1000000000000" => [
          "one" => "0 billion",
          "other" => "0 billions",
        ],
      ],
      "short" => [
        "1000" => [
          "one" => "0 k",
          "other" => "0 k",
        ],
        "1000000" => [
          "one" => "0 M",
          "other" => "0 M",
        ],
        "1000000000" => [
          "one" => "0 Md",
          "other" => "0 Md",
        ],
        "1000000000000" => [
          "one" => "0 Bn",
          "other" => "0 Bn",
        ],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'decimal',
      'precision' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [
      'decimal' => $this->t('Thousand marker (ex: 1,200,000)'),
      'long' => $this->t('Compact - Long (ex: 1.2 million)'),
      'short' => $this->t('Compact - Short (ex: 1.2M)'),
    ];
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $options,
      '#default_value' => $this->getSetting('format') ?? 'decimal',
    ];
    $elements['precision'] = [
      '#type' => 'select',
      '#title' => $this->t('Precision'),
      '#descripion' => $this->t('Number of decimal digits in compact form: 1.2 million with a precision of 1, 1.23 million with a precision of 2.'),
      '#options' => [
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
      ],
      '#default_value' => $this->getSetting('precision') ?? '1',
      '#states' => [
        'visible' => [
          'select[name$="[settings][format]"]' => [
            ['value' => 'long'],
            ['value' => 'short'],
          ],
        ],
      ],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $format = $this->getSetting('format') ?? 'decimal';
    $summary[] = $this->t('Format: @format', ['@format' => $format]);
    if ($format === 'short' || $format === 'long') {
      $summary[] = $this->t('Precision: @precision', [
        '@precision' => $this->getSetting('precision') ?? 1,
      ]);
    }
    $summary[] = $this->t('Example: @example', [
      '@example' => $this->formatNumber(12300000, 'en'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $this->formatNumber($item->value, $langcode),
      ];
      // Store the raw value to be displayed as an attribute on the item
      // wrapper.
      $items->_attributes += ['data-raw' => $item->value];
    }

    return $element;
  }

  /**
   * Format a number according to the settings.
   *
   * @param float|int $number
   *   Number to format.
   * @param string $langcode
   *   Language code.
   *
   * @return string
   *   Formatted number.
   */
  public function formatNumber($number, $langcode) {
    if (is_string($number) && !is_numeric($number)) {
      return $this->t('NaN');
    }
    $format = $this->getSetting('format') ?? 'decimal';
    if ($format === 'short' || $format === 'long') {
      $precision = (int) $this->getSetting('precision') ?? 1;
      return $this->formatNumberCompact($number, $langcode, $format, $precision);
    }
    return $this->formatNumberDecimal($number, $langcode);
  }

  /**
   * Format a number with language aware compact decimal formatting.
   *
   * If the language is not supported or no pattern was found, the returned
   * number will be formatted with the grouped thousands formatting.
   *
   * @param float|int $number
   *   Number to format.
   * @param string $langcode
   *   Language code.
   * @param string $type
   *   Either 'short' or 'long' (default).
   * @param int $precision
   *   Precision for the rounding of the number once compacted.
   *
   * @return string
   *   Formatted number.
   *
   * @see http://st.unicode.org/cldr-apps/v#/fr/Compact_Decimal_Formatting
   */
  public function formatNumberCompact($number, $langcode, $type = 'long', $precision = 2) {
    if ($number <= 0) {
      return '-';
    }
    elseif ($number < 1000) {
      return $number;
    }
    elseif ($number < 10000) {
      return $this->formatNumberDecimal($number, $langcode);
    }
    elseif ($number >= 1e15) {
      return $this->formatNumberDecimal($number, $langcode);
    }

    // Skip if the language is not handled.
    if (empty($this->patterns[$langcode])) {
      return $this->formatNumberDecimal($number, $langcode);
    }

    // Get the pattern for the language and type. Skip if undefined.
    if (!isset($this->patterns[$langcode][$type])) {
      return $this->formatNumberDecimal($number, $langcode);
    }
    $patterns = $this->patterns[$langcode][$type];

    // We want something like 1.2 million not 1,200,000, so we "truncate" the
    // number to a value between 0 and 1000 (not included).
    $n = abs($number);
    $p = floor(($n ? log($n) : 0) / log(1000));
    $n = $n / pow(1000, $p);

    // GHO-152: Use the "million" for number below 1 million.
    if ($number < 1e6) {
      $n = $n / 1000;
      $p = $p + 1;
      $precision = 2;
    }
    else {
      $precision = 1;
    }

    // Retrieve the pattern key for the number (ex: 1000000). Skip if Undefined.
    $key = (string) pow(1000, $p);
    if (empty($patterns[$key])) {
      return $this->formatNumberDecimal($number, $langcode);
    }
    $patterns = $patterns[$key];

    // Get the language plural for the number and lookup for the corresponding
    // pattern. Skip if undefined.
    $plural = $this->getPluralFor($n, $langcode);
    if (empty($patterns[$plural])) {
      return $this->formatNumberDecimal($number, $langcode);
    }

    // The GHO print version doesn't seem to translate the truncated part of the
    // number (ex: 1.2) for Arabic and use the English notation instead so we
    // do the same here.
    //
    // @todo Confirm?
    if ($langcode === 'ar') {
      $number = $this->formatNumberDecimal(round($n, $precision), 'en');
    }
    else {
      $number = $this->formatNumberDecimal(round($n, $precision), $langcode);
    }

    // Return the compact number.
    return preg_replace('/0+/u', $number, $patterns[$plural]);
  }

  /**
   * Format a number with grouped thousands.
   *
   * @param float|int $number
   *   Number to format.
   * @param string $langcode
   *   Language code.
   *
   * @return string
   *   Formatted number.
   */
  public function formatNumberDecimal($number, $langcode) {
    if (class_exists('\NumberFormatter')) {
      $formatter = new \NumberFormatter($langcode, \NumberFormatter::DECIMAL);
      $formatted = $formatter->format($number);
      if (intl_is_failure($formatter->getErrorCode())) {
        return $number;
      }
      return $formatted;
    }
    return number_format($number);
  }

  /**
   * Get the language plural corresponding to the given number.
   *
   * Note: this only implements the rules for Arabic, English, Spanish and
   * French.
   *
   * @param float|int $number
   *   Number to format, truncated to be within the 0-1000 range.
   * @param string $langcode
   *   Language code.
   *
   * @return string
   *   Plural for the number. It can be "one", "two", "few", "many" or "other".
   *
   * @see http://cldr.unicode.org/index/cldr-spec/plural-rules
   * @see http://unicode.org/reports/tr35/tr35-numbers.html#Language_Plural_Rules
   * @see https://unicode-org.github.io/cldr-staging/charts/38/supplemental/language_plural_rules.html
   */
  public function getPluralFor($number, $langcode) {
    /*
     * n: absolute value of the source number.
     * i: integer digits of n.
     * v: number of visible fraction digits in n, with trailing zeros.
     * w: number of visible fraction digits in n, without trailing zeros.
     * f: visible fraction digits in n, with trailing zeros.
     * t: visible fraction digits in n, without trailing zeros.
     * c: compact decimal exponent value: exponent of the power of 10 used in
     *    compact decimal formatting.
     * e: currently, synonym for ‘c’. however, may be redefined in the future.
     *
     * Rules from:
     * http://unicode.org/reports/tr35/tr35-numbers.html#Plural_Operand_Meanings
     */

    $n = abs($number);
    $i = floor($n);

    $c = floor(log10($n));

    // To handle both scientific notation (1.23456e-3) and plain notation
    // (0.00123456), we use sprintf() to print in a consistent manner the float
    // value to extract the decimals.
    //
    // The number of decimals to print takes into account the precision of 14
    // decimal digits for floats.
    //
    // @see https://www.php.net/manual/en/language.types.float.php
    $p = $c < 0 ? 1 - $c : max(14 - $c, 0);
    $f = rtrim(substr(strstr(sprintf('%.' . $p . 'f', $n), '.'), 1), '0');

    // We already removed trailing zeros to avoid artifacts from the sprintf.
    // This doesn't have any impact on the language plural rules we support.
    $v = strlen($f);

    /*
     * Those variables are not used for the languages we support:
     * - $e = $c;
     * - $t = rtrim($f, '0');
     * - $w = strlen($t);
     */

    // The rules here are based on the rules and compact decimal values for
    // the languages, adapted to our use case where we provide a number between
    // 0 and 1000.
    switch ($langcode) {
      // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/ar.html
      case 'ar':
        if ($n === 0) {
          return 'zero';
        }
        elseif ($n === 1) {
          return 'one';
        }
        elseif ($n === 2) {
          return 'two';
        }
        elseif (($n % 100 >= 3) && ($n % 100 <= 10)) {
          return 'few';
        }
        elseif (($n % 100 >= 11) && ($n % 100 <= 99)) {
          return 'many';
        }
        return 'other';

      // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/en.html
      case 'en':
        if ($i === 1 && $v === 0) {
          return 'one';
        }
        return 'other';

      // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/es.html
      case 'es':
        if ($n === 1) {
          return 'one';
        }
        return 'other';

      // @see https://unicode-org.github.io/cldr-staging/charts/38/verify/numbers/fr.html
      //
      // Note: the examples are not correct in the linked page above:
      // "1.1 millions" should be "1.1 million" for example, because the rule is
      // that any quantity below 2 are singular even for high numbers
      // (mille, million etc.).
      // Example from GHO 2019: "1,6 million de femmes enceintes".
      case 'fr':
        if ($i === 0 || $i === 1) {
          return 'one';
        }
        return 'other';

    }
    return 'other';
  }

}
