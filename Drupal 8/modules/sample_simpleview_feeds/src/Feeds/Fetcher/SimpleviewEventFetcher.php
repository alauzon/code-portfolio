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
 * Defines an Simpleview Event fetcher.
 *
 * @FeedsFetcher(
 *   id = "simpleview_event_fetcher",
 *   title = @Translation("Simpleview Event Fetcher"),
 *   description = @Translation("Downloads events data from simpleview."),
 * )
 */
class SimpleviewEventFetcher extends PluginBase implements ClearableInterface, FetcherInterface {

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

  /**
   * @return Generator
   */
  static private function read_simpleview_feed() {
    // Keep asking for the next 100 listings till they stop coming
    // @todo handle when they fail to respond
    $page_size = 100;
    $feed_count_total = 1; // the total count is always included in paging->total.
    $total_received = 0; // keep track of how many we have received for.
    for ($page = 1; ($total_received < $feed_count_total); $page++) {

      //$api_request_string = "http://appserver_nginx/sample_simpleview_feeds_mock_api/events4.xml";
      $api_request_string = "http://cs.simpleviewinc.com/feeds/events.cfm?apikey=7C28C7CD-5056-A36A-1C50DAD14441AF20&pagestart={$page}&pagesize={$page_size}";

      $xml_result = simplexml_load_string(file_get_contents ($api_request_string), null, LIBXML_NOCDATA);

      $events_returned = $xml_result->events->event;

      foreach ($events_returned as $event) {
        yield $event;
      }

      if ($feed_count_total === 1) {
        $feed_count_total = strval($xml_result->paging->total);
      }
      $total_received += ($xml_result->paging->end - $xml_result->paging->start + 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $feedReader = self::read_simpleview_feed();
    $filename = 'public://feeds_simpleview_events_fetcher_'.hash('sha256', $feed->getSource()).'.json';
    $recordNumber = 0;
    file_put_contents($filename, '{');

    // write file
    // @todo these won't get deleted ever, but tmp is too unstable on pantheon for this to work with cron
    // @see https://www.drupal.org/project/feeds/issues/2912130
    if ($feedReader){
      foreach ($feedReader as $record) {
        $comma = '';
        foreach ($feedReader as $record) {
          $record = json_encode($record, JSON_FORCE_OBJECT);
          $stringToAppend = $comma . '"' . $recordNumber++ . '":' . $record;
          $comma = ',';
          file_put_contents($filename, $stringToAppend, FILE_APPEND | LOCK_EX);
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
    }
    else{
        $state->setMessage('No events returned from simpleview.', 'error');
        throw new EmptyFeedException();
    }

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri('public://feeds_simpleview_events_fetcher_'.hash('sha256', $feed->getSource()).'.json');
    $file_path = $stream_wrapper_manager->realpath();

    if($file_path != ''){
        return new FetcherResult($file_path);
    }
    else{
        throw new EmptyFeedException();
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
      'event_category_event_type' => '',
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
