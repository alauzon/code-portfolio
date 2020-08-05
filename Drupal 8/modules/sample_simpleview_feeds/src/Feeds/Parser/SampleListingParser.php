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
 * Defines a Simpleview listing feed parser for Sample.
 *
 * @FeedsParser(
 *   id = "sample_listing_parser",
 *   title = "Sample Simpleview Listing Parser",
 *   description = @Translation("Deletes listing generated paragraphs not in feed."),
 * )
 */
class SampleListingParser extends PluginBase implements ParserInterface {

  static private function format_size($size) {
    $mod = 1024;
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
      $size /= $mod;
    }
    return round($size, 2) . $units[$i];
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    \Drupal::logger('Feeds')->info('Peak memory usage SampleListingParser start: %memory_get_peak_usage.', ['%memory_get_peak_usage' => self::format_size(memory_get_peak_usage())]);
    // Get sources.
    $sources = [];
    foreach($feed->getType()->getMappingSources() as $key => $info){
      if (!empty($info['value'])) {
        $sources[$info['value']] = $key;
      }
    }

    //$feed_config = $feed->getConfigurationFor($this);
    $result = new ParserResult();

    $filename = $fetcher_result->getFilePath();
    if (!filesize($filename)) {
      throw new EmptyFeedException();
    }

    // Load and decode results from fetcher
    $loadedJson = json_decode(file_get_contents($filename),true);

    // Keep list of existing simpleview ids so we can delete ones that aren't in it
    $simpleviewIdsInThisImport = array();

    $recordsProcessedTotal = 0;
    $recordProcessedThreshold = 100;

    // Loop over categories
    foreach ($loadedJson as $row) {

      if ($row['regionid'] == 1) {

        // Create feed item
        $item = new DynamicItem();

        // Add id to id list
        $simpleviewIdsInThisImport[] = $row['listingid'];

        // Loop over item properties
        foreach ($row as $key => $value) {
          if ($key === 'socialdata') {
            if (count($value)) {
              // 1 == twitter (format is inconsistent)
              // 4 == facebook (format is inconsistent)
              foreach ($value as $service => $serviceArray) {
                switch ($serviceArray['serviceid']) {
                  case 1:
                    $item->set('twitter_url', $serviceArray['value']);
                    break;
                  case 4:
                    $item->set('facebook_url', $serviceArray['value']);
                    break;
                }
              }
            }
          }
          // Image
          if (in_array($key, ['listingmedia'])) {
            // $value is array with index media[0][mediafile].
            // No ALT text from Simpleview
            if (count($value)) {
              if (count($value['media']) > 1) {
                $item->set('main_image', $value['media'][0]['mediafile']);
              }
              else {
                $item->set('main_image', $value['media']['mediafile']);
              }
            }
          }
          else {
            if (in_array($key, ['phone', 'altphone', 'tollfree'])) {
              // First remove any leading "1-" instances.
              $value = preg_replace("/^1-/", "", $value);
              $value = preg_replace("/[^0-9a-zA-Z]/", "", $value);
            }

            if ($key === 'company') {
              $item->set('title', $value);
            }

            $item->set($key, $value);
          }
        }

        // Add item to results
        $result->addItem($item);

        $recordsProcessedTotal++;
      } // End region test.
    }

    // Report progress.
    $state->total = filesize($filename);
    \Drupal::logger('Feeds')->info('SampleListingParser %recordsProcessedTotal records parsed.', ['%recordsProcessedTotal' => $recordsProcessedTotal]);
    \Drupal::logger('Feeds')->info('Peak memory usage SampleListingParser end: %memory_get_peak_usage.', ['%memory_get_peak_usage' => self::format_size(memory_get_peak_usage())]);

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
      'listingid',
      'categoryname',
      'subcategoryname',
      'company',
      'address1',
      'city',
      'zip',
      'phone',
      'tollfree',
      'email',
      'website',
      'description',
      'main_image',
      'latitude',
      'longitude',
      'amenitytabs',
      'facebook_url',
      'twitter_url',
    ];
    $formattedSources = [];
    foreach($sources as $source){
      $formattedSources[$source] = array('label'=>$source);
    }

    return $formattedSources;
  }

}
