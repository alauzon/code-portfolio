<?php

namespace Drupal\sample_simpleview_feeds\Feeds\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\file\Entity\File;

/**
 * Defines an Simpleview Listing fetcher.
 *
 * @FeedsFetcher(
 *   id = "simpleview_listing_fetcher",
 *   title = @Translation("Simpleview Listing Fetcher"),
 *   description = @Translation("Downloads listing data from simpleview."),
 * )
 */
class SimpleviewListingFetcher extends PluginBase implements ClearableInterface, FetcherInterface {

  /**
   * Drupal file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * Constructs a SimpleviewFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  static private function format_size($size) {
    $mod = 1024;
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
      $size /= $mod;
    }
    return round($size, 2) . $units[$i];
  }

  /**
   * @return Generator
   * @throws \ErrorException
   */
  static private function read_simpleview_feed($page_size) {
    // Keep asking for the next $page_size listings till they stop coming
    // @todo handle when they fail to respond

    $feed_count_total = 1; // the total count is always included in paging->total.
    $total_received = 0; // keep track of how many we have received for.
    for ($page = 1; ($total_received < $feed_count_total); $page++) {
      //$api_request_string = "http://appserver_nginx/sample_simpleview_feeds_mock_api/listings-region1.xml";
      //$api_request_string = "http://appserver_nginx/sample_simpleview_feeds_mock_api/listings-5000-linted.xml";
      $api_request_string = "http://cs.simpleviewinc.com/feeds/listings.cfm?apikey=7C28C7CD-5056-A36A-1C50DAD14441AF20&pagestart={$page}&pagesize={$page_size}";

      // Catch any failures here so we can keep going.
      try {
        \Drupal::logger('Feeds')->info("pagestart={$page}&pagesize={$page_size}");

        set_error_handler(
          function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
          }
        );
        $contents = file_get_contents($api_request_string);
        restore_error_handler();

        $xml_result = simplexml_load_string($contents, null, LIBXML_NOCDATA);

        if (!$xml_result) {
          \Drupal::logger('Feeds')->error('Failed importing %request_string!', ['%request_string' => $api_request_string]);
          return;
        }

        if ($feed_count_total === 1) {
          $feed_count_total = strval($xml_result->paging->total);
        }
        $total_received += ($xml_result->paging->end - $xml_result->paging->start + 1);

        yield $xml_result;
      }
      catch (Exception $e) {
        // Log this for analysis.
        \Drupal::logger('Feeds')->error('Caught exception: ' . $e->getMessage());
        \Drupal::logger('Feeds')->error('API Request was ' . $api_request_string);
      }
    }
    \Drupal::logger('Feeds')->info('%requested/%feed_count_total using %memory_get_peak_usage', ['%requested' => $total_received, '%feed_count_total' => $feed_count_total, '%memory_get_peak_usage' => self::format_size(memory_get_peak_usage())]);

  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $initTime = time();
    $page_size = 1000; // Exactly 1000 is the fastest by far.  Any reasons why? Also it seems that this number must be an exact factor of 1000.
    $readGenerator = self::read_simpleview_feed($page_size);
    $print_threshold = 1000;
    $feed_count_total = 1; // the total count is always included in paging->total.
    $last_printed = 0;
    $total_received = 0; // keep track of how many we have asked for.

    // Open the file in write mode, if file does not exist then it will be created.
    $filename = 'public://feeds_simpleview_listings_fetcher_'.hash('sha256', $feed->getSource()).'.json';
    $recordNumber = 0;
    file_put_contents($filename, '{');

    // write file
    // @todo these won't get deleted ever, but tmp is too unstable on pantheon for this to work with cron
    // @see https://www.drupal.org/project/feeds/issues/2912130
    if ($readGenerator) {
      $comma = '';
      foreach ($readGenerator as $xml_result) {
        $readInPage = 0;

        $listings = $xml_result->listings->listing;

        if ($feed_count_total === 1) {
          $feed_count_total = strval($xml_result->paging->total);
        }
        foreach ($listings as $listing) {
          $readInPage++;
          $total_received++;

          // Test for region, ADK is 1. Of course we are!
          if ((string) $listing->regionid === '1') {
            // We also need to blacklist Herkimer county listings.
            $herkimer_cities = [
              'Cold Brook',
              'Dolgeville',
              'Eagle Bay',
              'Frankfort',
              'Herkimer',
              'Ilion',
              'Jordanville',
              'Little Falls',
              'Middleville',
              'Mohawk',
              'Newport',
              'Old Forge',
              'Poland',
              'Salisbury Center',
              'Thendara',
              'Van Hornesville',
              'West Winfield'
            ];
            if (!in_array($listing->city, $herkimer_cities)) {

              // Put the SimpleXMLElement into an array.
              $record = json_decode(json_encode($listing, TRUE));
              // Prune empty elements
              $record = self::prune_array((array) $record);
              $record = json_encode($record, JSON_FORCE_OBJECT);
              $stringToAppend = $comma . '"' . $recordNumber++ . '":' . $record;
              $comma = ',';
              file_put_contents($filename, $stringToAppend, FILE_APPEND | LOCK_EX);
            }
          }
        }
        if ($total_received - $last_printed >= $print_threshold ||
          ($feed_count_total < $page_size && $total_received >= $page_size)) {
          \Drupal::logger('Feeds')->info('%requested/%feed_count_total using %memory_get_peak_usage', ['%requested' => $total_received, '%feed_count_total' => $feed_count_total, '%memory_get_peak_usage' => self::format_size(memory_get_peak_usage())]);
          $last_printed = $total_received;
        }
      }
      file_put_contents($filename, '}', FILE_APPEND | LOCK_EX);

      $file = File::create([
        'uri' => $filename,
        'uid' => 1,
        'status' => FILE_STATUS_PERMANENT,
      ]);

      $file->save();

      $this->file = $file;
    }
    else {
        $state->setMessage('No listings returned from simpleview.', 'error');
        throw new EmptyFeedException();
    }

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($filename);
    $file_path = $stream_wrapper_manager->realpath();

    if ($file_path != '') {
      \Drupal::logger('Feeds')->info('Fetch time: %fetchTime sec', ['%fetchTime' => time() - $initTime]);
      return new FetcherResult($file_path);
    }
    else {
      throw new EmptyFeedException();
    }
  }

  protected static function prune_array($array) {
    foreach ($array as $key => $element) {
      $array_element = (array) $element;
      if (($key === 'value' &&
          (count($array_element) === 0 ||
           $element !== 'Yes'))) {
        return null;
      }
      elseif (in_array((string)$key, [
          'accountid',
          'additionalcategories',
          'categoryid',
          'companysort',
          'created',
          'fax',
          'lastupdated',
          'meetingfacility',
          'rankid',
          'rankname',
          'ranksort',
          'region',
          'state',
          'subcategoryid',
          'typeid',
          'subcategoryid',
          'typename',
        ])) {
        unset($array[$key]);
      }
      elseif (count($array_element) === 0) {
        unset($array[$key]);
      }
      elseif (is_string($element)) {
        continue;
      }
      elseif (in_array($key, [
          'amenitytabs',
          'amenitytab',
          'amenitygroups',
          'amenitygroup',
          'amenitys',
          'amenity',
          'customfields',
          'customfield',
          ]) ||
          count($array_element) > 1) {
        $element = self::prune_array((array)$element);
        if (!is_null($element)) {
          $array[$key] = $element;
        }
        else {
          unset($array[$key]);
        }
      }
    }
    if (count($array) > 0) {
      if (count($array) === 1 &&
        in_array(array_keys($array)[0], [
          'groupname',
          'tabname',
          ])) {
        return null;
      }
      return count($array) > 0 ? $array : null;
    }
    else {
      return null;
    }
  }

  /**
   * Returns the download cache key for a given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to find the cache key for.
   *
   * @return string
   *   The cache key for the feed.
   */
  protected function getCacheKey(FeedInterface $feed) {
    return $feed->id() . ':' . hash('sha256', $feed->getSource());
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    $this->onFeedDeleteMultiple([$feed]);
  }

  /**
   * necessary for settings from form to be saved
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'listing_category_listing_type' => '',
    ];
  }

  public function defaultFeedConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      //$this->cache->delete($this->getCacheKey($feed));
    }
  }

}
