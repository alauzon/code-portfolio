<?php

namespace Drupal\sample_simpleview_feeds\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;


/**
 * Defines a Simpleview event feed parser for Sample.
 *
 * @FeedsParser(
 *   id = "sample_event_parser",
 *   title = "Sample Simpleview Event Parser",
 *   description = @Translation("Parses events generated in the feed."),
 * )
 */
class SampleEventParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Get sources.

    $sources = [];
    foreach($feed->getType()->getMappingSources() as $key => $info){
      if (!empty($info['value'])) {
        $sources[$info['value']] = $key;
      }
    }


    //$feed_config = $feed->getConfigurationFor($this);
    $result = new ParserResult();

    if (!filesize($fetcher_result->getFilePath())) {
      throw new EmptyFeedException();
    }

    // Load and decode results from fetcher
    $loadedJson = json_decode(file_get_contents($fetcher_result->getFilePath()),true);

    // Loop over categories
    foreach ($loadedJson as $row) {

      // Current structure, custom field 131 is index number 6. This is region.
      if ($row['customfields']['customfield'][6]['value'] == 'Adirondacks') {

        // Create feed item
        $item = new DynamicItem();

        // Loop over item properties
        foreach ($row as $key => $value) {
          //\Drupal::messenger()->addMessage(print_r($key, TRUE));

            if (in_array($key, ['phone', 'altphone', 'tollfree'])) {
              // First remove any leading "1-" instances.
              $value = preg_replace("/^1-/", "", $value);
              $value = preg_replace("/[^0-9a-zA-Z]/", "", $value);
            }

            $item->set($key, $value);

          }

        // Add item to results
        $result->addItem($item);
      } // End region test.
    }


    // Report progress.
    $state->total = filesize($fetcher_result->getFilePath());

    return $result;

  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {

    // Get Listing Fields From Simpleview
    // @todo find a better way, or at least store in config
    // @todo right now making a request for one listing and using it's keys, but some have different keys

    $sources = [
      'eventid',
      'title',
      'startdate',
      'enddate',
      'address',
      'city',
      'zip',
      'phone',
      'admission',
      'email',
      'website',
      'description',
      'location',
      'latitude',
      'longitude',
      'eventcategories',
      'imagefile',
      'dates',
      'recurrence',
      'times',
    ];
    $formattedSources = [];
    foreach($sources as $source){
      $formattedSources[$source] = array('label'=>$source);
    }

    return $formattedSources;
  }

}
