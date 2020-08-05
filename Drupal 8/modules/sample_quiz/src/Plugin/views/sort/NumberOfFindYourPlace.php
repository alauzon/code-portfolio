<?php

namespace Drupal\sample_quiz\Plugin\views\sort;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort plugin used to allow Number of Find Your Place matches Sorting.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("number_of_find_your_place")
 */
class NumberOfFindYourPlace extends SortPluginBase {

  /**
   * Flag defining this particular sort as NumberOfFindYourPlace or not.
   *
   * @var bool
   */
  protected $isNumberOfFindYourPlaceSort;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->setNumberOfFindYourPlaceSort(substr($this->options['order'], 0, 1) == 'N');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If this field isn't being used as a NumberOfFindYourPlace Sort Field, move along
    // nothing to see here.
    if (!$this->isNumberOfFindYourPlaceSort()) {
      parent::query();
      return;
    }
    // Get the keys from the cookie sample_find_your_place_tags.
    // On Pantheon cookies not starting with STYXKEY are erased by Varnish.
    if (isset($_COOKIE['STYXKEY_sampleFindYourPlaceTags'])) {
      $sample_find_your_place_tags = str_replace(['[', ']'], '', (string)$_COOKIE['STYXKEY_sampleFindYourPlaceTags']);
      $fyp_keys = explode(',', $sample_find_your_place_tags);
      if ($fyp_keys && count($fyp_keys) > 0) {
        $plus = '';
        $tableFormula = '';
        foreach( $fyp_keys as $key => $fyp_key ) {
          // SQL injection protection.
          $fyp_key = (int)$fyp_key;

          $tableFormula .= $plus . "EXISTS(SELECT fyp$key.entity_id FROM node__field_listing_category AS fyp$key WHERE fyp$key.field_listing_category_target_id = '$fyp_key' AND fyp$key.entity_id = node_field_data.nid)";
          $plus = ' + ';
        }
        $this->query->addOrderBy(NULL, $tableFormula, 'DESC', 'number_of_find_your_place_sort');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function sortOptions() {
    $options = parent::sortOptions();
    $options['NDESC'] = $this->t('Sort descending numberOfFindYourPlacely');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('Exposed');
    }
    $label = parent::adminSummary();
    switch ($this->options['order']) {
      case 'NDESC':
        return $this->t('numberOfFindYourPlace desc');
      default:
        return $label;
    }
  }

  /**
   * Determines if this query is numberOfFindYourPlace sort.
   *
   * @return bool
   *   True if numberOfFindYourPlace sort, False otherwise.
   */
  public function isNumberOfFindYourPlaceSort() {
    return $this->isNumberOfFindYourPlaceSort;
  }

  /**
   * Sets the numberOfFindYourPlace sort flag.
   *
   * @param bool $value
   *   The value.
   */
  protected function setNumberOfFindYourPlaceSort($value) {
    $this->isNumberOfFindYourPlaceSort = $value;
  }

}
