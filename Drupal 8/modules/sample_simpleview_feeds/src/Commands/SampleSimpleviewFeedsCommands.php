<?php

/*
 * Definition of Drush commands that will lock, unlock and start feed processes.
 */

namespace Drupal\sample_simpleview_feeds\Commands;

use Drush\Commands\DrushCommands;
use Drupal\feeds\Entity\Feed;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ValidationException;
use Exception;
use Drupal\feeds\Plugin\QueueWorker\FeedRefresh;

/**
 * Class SampleSimpleviewFeedsCommands.
 *
 * @package Drupal\sample_simpleview_feeds\Commands
 */
class SampleSimpleviewFeedsCommands extends DrushCommands {

  /**
   * Constructs an SampleSimpleviewFeedsCommands object.
   */
  public function __construct() {
  }

  /**
   * Schedule feed import.
   *
   * @param string $name
   *   Feed to unlock.
   *
   * @command sample-sf:unlock
   *
   * @usage drush sample-sf-schedule-import feed_name
   *   Schedule Import for the feed_name feed
   * @aliases assi sample-sf-schedule-import
   * @throws Exception
   */
  public function schedule_import($name) {
    if (!$name) {
      throw new Exception(dt('No feed_name specified?'));
    }

    try {
      $feeds = \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->loadByProperties([
          'title' => $name,
        ]);
      if (count($feeds) > 0) {
        /** @var Feed $feed */
        foreach ($feeds as $fid => $feed) {
          $feed->startCronImport();
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (ValidationException $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getFormattedMessage(),
      ]), 'warning');
    }
    catch (Exception $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getMessage(),
      ]), 'warning');
    }
  }

  /**
   * Unlock feed.
   *
   * @param string $name
   *   Feed to unlock.
   *
   * @command sample-sf:unlock
   *
   * @usage drush sample-sf-unlock feed_name
   *   Unlock the feed_name feed
   * @aliases asu sample-sf-unlock
   * @throws Exception
   */
  public function unlock($name) {
    if (!$name) {
      throw new Exception(dt('No feed_name specified?'));
    }

    try {
      $feeds = \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->loadByProperties([
          'title' => $name,
        ]);
      if (count($feeds) > 0) {
        /** @var Feed $feed */
        foreach ($feeds as $fid => $feed) {
          $feed->unlock();
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (ValidationException $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getFormattedMessage(),
      ]), 'warning');
    }
    catch (Exception $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getMessage(),
      ]), 'warning');
    }
  }

  /**
   * Lock feed.
   *
   * @param string $name
   *   Feed to lock.
   *
   * @command sample-sf:lock
   *
   * @usage drush sample-sf-lock feed_name
   *   Locks the feed_name feed
   * @aliases asl sample-sf-lock
   * @throws Exception
   */
  public function lock($name) {
    if (!$name) {
      throw new Exception(dt('No feed_name specified?'));
    }

    try {
      $feeds = \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->loadByProperties([
          'title' => $name,
        ]);
      if (count($feeds) > 0) {
        /** @var Feed $feed */
        foreach ($feeds as $fid => $feed) {
          $feed->lock();
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (ValidationException $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getFormattedMessage(),
      ]), 'warning');
    }
    catch (Exception $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getMessage(),
      ]), 'warning');
    }
  }

  // $this->plugin->processItem(NULL);
  //    $this->plugin->processItem([$this->feed, FeedRefresh::BEGIN, []]);

  /**
   * Import feed.
   *
   * @param string $name
   *   Feed to import.
   *
   * @command sample-sf:import-feed
   *
   * @usage drush sample-sf-import-feed feed_name
   *   Import the feed feed_name
   * @aliases asif sample-sf-import-feed
   * @throws Exception
   */
  public function import_feed($name) {
    if (!$name) {
      throw new Exception(dt('Feed with name %feed_name does not exist.  Import failed.', ['%feed_name' => $name]));
    }

    try {
      $feeds = \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->loadByProperties([
          'title' => $name,
        ]);
      if (count($feeds) > 0) {
        $feedRefresh = new FeedRefresh();
        /** @var Feed $feed */
        foreach ($feeds as $fid => $feed) {

          $feed->startCronImport();
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (ValidationException $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getFormattedMessage(),
      ]), 'warning');
    }
    catch (Exception $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed names @name \'@exception_message\'.', [
        '@name' => $name,
        '@exception_message' => $e->getMessage(),
      ]), 'warning');
    }
  }

  /**
   * Unlock feed.
   *
   * @command sample-sf:list-feeds
   *
   * @usage drush sample-sf-list-feeds
   *   Unlock the feed_id feed
   * @aliases aslf sample-sf-list-feeds
   */
  public function list_feeds() {

    try {
      $feeds = \Drupal::entityTypeManager()
        ->getStorage('feeds_feed')
        ->loadByProperties([]);
      if (count($feeds) > 0) {
        /** @var Feed $feed */
        foreach ($feeds as $fid => $feed) {
          echo "title: " . $feed->get('title')->getValue()[0]['value'] . ", " .
            "id: " . $feed->id() . ", " .
            "type: " . $feed->get('type')->getValue()[0]['target_id'] . ", " .
            "status: " . $feed->get('status')->getValue()[0]['value'] . ", " .
            "last imported: " . date('Y-m-d H:i:s', $feed->get('imported')->getValue()[0]['value']) . ", " .
            "queued: " . date('Y-m-d H:i:s', $feed->get('queued')->getValue()[0]['value']) . ", " .
            "next: " . date('Y-m-d H:i:s', $feed->get('next')->getValue()[0]['value']) . ", " .
            "item count: " . $feed->get('item_count')->getValue()[0]['value'] .
               "\n";
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (ValidationException $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed id @fid \'@exception_message\'.', [
        '@fid' => $fid,
        '@exception_message' => $e->getFormattedMessage(),
      ]), 'warning');
    }
    catch (Exception $e) {
      \Drupal::messenger()->addWarning(t('The following exception occurred while looking for a feed id @fid \'@exception_message\'.', [
        '@fid' => $fid,
        '@exception_message' => $e->getMessage(),
      ]), 'warning');
    }
  }

}
