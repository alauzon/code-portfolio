<?php

/**
 * @file
 * Contains \Drupal\code_test_linux_foundation\Storage\CodeTestLinuxFoundationStorage.
 *
 * Mixed storage controller for events, cities and votes.
 */

namespace Drupal\code_test_linux_foundation\Storage;

/**
 * Class CodeTestLinuxFoundationStorage.
 */
class CodeTestLinuxFoundationStorage {

  /**
   * Save a vote in the database.
   *
   * If need be it will also insert an event and a city in their
   * corresponding tables.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function insertVote($entry) {
    $return_value = NULL;

    if (empty($entry['event']) || empty($entry['city'])) {
      return NULL;
    }

    $event = self::loadEvent($entry);
    if (count($event) == 0) {
      self::insertEvent($entry);
      $event = self::loadEvent($entry);
    }

    $city = self::loadCity($entry);
    if (count($city) == 0) {
      self::insertCity($entry);
      $city = self::loadCity($entry);
    }
    $entry = array(
      'eid' => $event[0]->eid,
      'cid' => $city[0]->cid,
      'name' => $entry['name'],
      'email' => $entry['email'],
      'timestamp' => time(),
    );

    try {
      $return_value = db_insert('ctlf_vote')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query',
                           array(
                             '%message' => $e->getMessage(),
                             '%query' => $e->query_string,
                           )
                         ), 'error');
    }
    return $return_value;
  }

  /**
   * Save an event in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function insertEvent($entry) {
    $return_value = NULL;

    if (empty($entry['event'])) {
      return NULL;
    }

    try {
      $return_value = db_insert('ctlf_event')
        ->fields(array('event' => $entry['event']))
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query',
                           array(
                             '%message' => $e->getMessage(),
                             '%query' => $e->query_string,
                           )
                         ),
                         'error');
    }
    return $return_value;
  }

  /**
   * Save a city in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function insertCity($entry) {
    $return_value = NULL;

    if (empty($entry['city'])) {
      return NULL;
    }

    try {
      $return_value = db_insert('ctlf_city')
        ->fields(array('city' => $entry['city']))
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query',
                           array(
                             '%message' => $e->getMessage(),
                             '%query' => $e->query_string,
                           )
                         ), 'error');
    }
    return $return_value;
  }

  /**
   * Read from the ctlf_event table using a filter array.
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_select()
   * @see db_query()
   * @see http://drupal.org/node/310072
   * @see http://drupal.org/node/310075
   */
  public static function loadEvent($entry = array()) {
    if (empty($entry['event'])) {
      return NULL;
    }
    // Read all fields from the ctlf_event table.
    $select = db_select('ctlf_event', 'event');
    $select->fields('event');
    $select->condition('event', $entry['event']);

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Read from the ctlf_city table using a filter array.
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_select()
   * @see db_query()
   * @see http://drupal.org/node/310072
   * @see http://drupal.org/node/310075
   */
  public static function loadCity($entry = array()) {
    if (empty($entry['city'])) {
      return NULL;
    }
    // Read all fields from the ctlf_city table.
    $select = db_select('ctlf_city', 'city');
    $select->fields('city');
    $select->condition('city', $entry['city']);

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Read from the ctlf_city table using a filter array.
   *
   * @param array $event
   *   The associated event.
   * @param array $limit
   *   The maximum number of cities to retrieve.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_select()
   * @see db_query()
   * @see http://drupal.org/node/310072
   * @see http://drupal.org/node/310075
   */
  public static function loadEventCities($event, $limit) {
    // Return the $limit most voted cities associated to $event.
    $select = db_select('ctlf_vote', 'v');
    $select->rightJoin('ctlf_city', 'c', 'v.cid = c.cid');
    $select->rightJoin('ctlf_event', 'e', 'v.eid = e.eid');
    $select->addExpression('COUNT(v.vid)', 'votes');
    $select->fields('c', array('city'));
    $select->condition('e.event', $event);
    $select->groupBy('c.city');
    $select->orderBy('votes', 'desc');
    $select->range(0, $limit);

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

}
