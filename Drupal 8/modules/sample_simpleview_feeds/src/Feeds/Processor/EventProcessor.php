<?php

namespace Drupal\sample_simpleview_feeds\Feeds\Processor;

use DateTime;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Feeds\Processor\NodeProcessor;
use Drupal\taxonomy\Entity\Term;
use Drupal\sample_simpleview_feeds\SampleSimpleviewFeedsHelper;

/**
 * Defines a feeds processor.
 *
 * Creates nodes and paragraphs from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:node:event",
 *   title = @Translation("Events"),
 *   description = @Translation("Creates Event nodes."),
 *   entity_type = "node",
 *   arguments = {"@entity_type.manager", "@entity.query", "@entity_type.bundle.info"},
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class EventProcessor extends NodeProcessor {

  public function process(FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    // Initialize clean list if needed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    if (!$clean_state->initiated()) {
      $this->initCleanList($feed, $clean_state);
    }

    $existing_entity_id = $this->existingEntityId($feed, $item);
    $skip_existing = $this->configuration['update_existing'] == static::SKIP_EXISTING;

    // If the entity is an existing entity it must be removed from the clean
    // list.
    if ($existing_entity_id) {
      $clean_state->removeItem($existing_entity_id);
    }

    // Bulk load existing entities to save on db queries.
    if ($skip_existing && $existing_entity_id) {
      $state->skipped++;
      return;
    }

    // Delay building a new entity until necessary.
    if ($existing_entity_id) {
      $entity = $this->storageController->load($existing_entity_id);
    }

    $hash = $this->hash($item);
    $changed = $existing_entity_id && ($hash !== $entity->get('feeds_item')->hash);

    // Do not proceed if the item exists, has not changed, and we're not
    // forcing the update.
    if ($existing_entity_id && !$changed && !$this->configuration['skip_hash_check']) {
      $state->skipped++;
      return;
    }

    // Build a new entity.
    if (!$existing_entity_id) {
      $entity = $this->newEntity($feed);
    }

    try {
      // Set feeds_item values.
      $feeds_item = $entity->get('feeds_item');
      $feeds_item->target_id = $feed->id();
      $feeds_item->hash = $hash;

      // Set field values.
      $this->map($feed, $entity, $item);

      // Validate the entity.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PREVALIDATE, $entity, $item);
      $this->entityValidate($entity);

      // Dispatch presave event.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PRESAVE, $entity, $item);

      // This will throw an exception on failure.
      $this->entitySaveAccess($entity);
      // Set imported time.
      $entity->get('feeds_item')->imported = \Drupal::service('datetime.time')->getRequestTime();

      // Dates
      $startdate = $item->get('startdate');
      $enddate = $item->get('enddate');
      $starttime = is_string($item->get('starttime')) ? $item->get('starttime') : '00:00:00';
      $endtime = is_string($item->get('endtime')) ? $item->get('endtime') : '23:59:59';
      $rrule = '';
      // First check if dates are recurring
      if (!empty($item->get('recurrence')) && !is_array($item->get('recurrence'))) {
        $recurrence = $item->get('recurrence');
        $reg_weekly = '/Recurring weekly on (((Sunday)|(Monday)|(Tuesday)|(Wednesday)|(Thursday)|(Friday)|(Saturday))(, )?)*/';
        preg_match($reg_weekly, $recurrence, $weekdays);
        if (count($weekdays) > 0) {
          // We have a weekly recurrence
          $day_of_the_week_keys = [
            'SU' => 0,
            'MO' => 0,
            'TU' => 0,
            'WE' => 0,
            'TH' => 0,
            'FR' => 0,
            'SA' => 0,
          ];
          foreach ($weekdays as $weekday) {
            if (isset($day_of_the_week_keys[strtoupper(substr($weekday, 0, 2))])) {
              $day_of_the_week_keys[strtoupper(substr($weekday, 0, 2))] = TRUE;
            }
          }
          $rrule = 'RRULE:FREQ=WEEKLY;INTERVAL=1;BYDAY=';
          $comma = '';
          foreach ($day_of_the_week_keys as $day_of_the_week_key => $presence) {
            if ($presence) {
              $rrule .= $comma . $day_of_the_week_key;
              $comma = ',';
            }
          }
          $enddatetime = gmdate('Ymd\THis\Z', strtotime($enddate . ' ' . $endtime. ' America/New_York'));
          $rrule .= ';UNTIL=' . $enddatetime;
          $entity->field_recurrent_date = [[
            'value'     => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $starttime. ' America/New_York')),
            'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $endtime. ' America/New_York')),
            'rrule'     => $rrule,
            'timezone'  => 'America/New_York',
            'infinite'  => FALSE,
          ]];
        }
        else {
          if ($recurrence === 'Recurring daily') {
            // Daily recurrent dates
            $enddatetime = gmdate('Ymd\THis\Z', strtotime($enddate . ' ' . $endtime. ' America/New_York'));
            $rrule = 'RRULE:FREQ=DAILY;INTERVAL=1;UNTIL=' . $enddatetime;
            $entity->field_recurrent_date = [[
              'value'     => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $starttime. ' America/New_York')),
              'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $endtime. ' America/New_York')),
              'rrule'     => $rrule,
              'timezone'  => 'America/New_York',
              'infinite'  => FALSE,
            ]];
          }
        }
      }
      elseif (isset($item->get('eventdates')['eventdate'])) {
        if (!is_array($item->get('eventdates')['eventdate'])) {
          // One day only
          $entity->field_recurrent_date = [[
            'value'     => gmdate('Y-m-d\TH:i:s', strtotime($item->get('eventdates')['eventdate'] . ' ' . $starttime. ' America/New_York')),
            'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($item->get('eventdates')['eventdate'] . ' ' . $endtime. ' America/New_York')),
            'rrule'     => '',
            'timezone'  => 'America/New_York',
            'infinite'  => FALSE,
          ]];
        }
        else {
          // Is it recurrent?
          $date_diffs = [];
          $smallest_date_diff = PHP_INT_MAX;
          $previous_date = 0;
          foreach ($item->get('eventdates')['eventdate'] as $eventdate) {
            if ($previous_date == 0) {
              $previous_date = $eventdate;
            }
            else {
              $date_diff = (int)round((strtotime($eventdate) - strtotime($previous_date)) / (60*60*24));
              $date_diffs[$date_diff] = $date_diff;
              $smallest_date_diff = min($smallest_date_diff, $date_diff);
              $previous_date = $eventdate;
            }
          }
          $exdate = '';
          if (count($date_diffs) > 1) { // This is a recurrent date with exceptions
            // Find and set the exceptional dates
            $comma = '';
            $start_timestamp = strtotime($startdate . ' ' . $starttime . ' America/New_York');
            $end_timestamp = strtotime($enddate . ' ' . $starttime . ' America/New_York');
            $timestamp_step = $smallest_date_diff * 60 * 60 * 24;
            for ($timestamp = $start_timestamp; $timestamp <= $end_timestamp; $timestamp += $timestamp_step) {
              if (!in_array(date('m/d/Y', $timestamp), $item->get('eventdates')['eventdate'])) {
                $exdate .= $comma . gmdate('Ymd\THis\Z', $timestamp);
                $comma = ',';
              }
            }
          }
          // We have detected a recurrent date
          $enddatetime = gmdate('Ymd\THis\Z', strtotime($enddate . ' ' . $endtime. ' America/New_York'));
          if ($smallest_date_diff === 1 ) {
            // And it is daily
            $rrule = "RRULE:FREQ=DAILY;INTERVAL=1";
            $rrule .= ';UNTIL=' . $enddatetime;
            $entity->field_recurrent_date = [[
              'value'     => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $starttime . ' America/New_York')),
              'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $endtime . ' America/New_York')),
              'rrule'     => $rrule,
              'exdate'    => $exdate,
              'timezone'  => 'America/New_York',
              'infinite'  => FALSE,
            ]];
          }
          elseif ($smallest_date_diff % 7 === 0 ) {
            // And it is weekly
            $interval = $smallest_date_diff/7;
            $rrule = "RRULE:FREQ=WEEKLY;INTERVAL=$interval;BYDAY=";
            $rrule .= strtoupper(substr(date("D", strtotime($startdate)),0, 2));
            $rrule .= ';UNTIL=' . $enddatetime;
            $rrule .= "\r\nEXDATE:$exdate";
            $entity->field_recurrent_date = [[
              'value'     => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $starttime. ' America/New_York')),
              'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($startdate . ' ' . $endtime. ' America/New_York')),
              'rrule'     => $rrule,
              'timezone'  => 'America/New_York',
              'infinite'  => FALSE,
            ]];
          }
          if ($rrule === '') {
            // No pattern found, it is just a bunch of dates
            $entity->field_recurrent_date = [];
            foreach ($item->get('eventdates')['eventdate'] as $eventdate) {
              $entity->field_recurrent_date[] = [
                'value' => gmdate('Y-m-d\TH:i:s', strtotime($eventdate . ' ' . $starttime . ' America/New_York')),
                'end_value' => gmdate('Y-m-d\TH:i:s', strtotime($eventdate . ' ' . $endtime . ' America/New_York')),
                'rrule' => '',
                'timezone' => 'America/New_York',
                'infinite' => FALSE,
              ];
            }
          }
        }
      }

      // Handle cats. First clear all.
      $entity->field_tags = [];
      $event_cat_vocab_machine_vid = 'event_categories';
      foreach ($item->get('eventcategories')['eventcategory'] as $cat => $cat_array) {
        if (isset($cat_array['categoryname'])) {
          $term_array = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $cat_array['categoryname'], 'vid' => $event_cat_vocab_machine_vid]);
          if (!isset($term_array) || !count($term_array)) {
            // Create it.
            $term = Term::create([
              'name' => $cat_array['categoryname'],
              'vid' => $event_cat_vocab_machine_vid,
            ]);
            $term->save();
            $entity->field_tags[] = ['target_id' => $term->id()];
          }
          else {
            $entity->field_tags[] = ['target_id' => key($term_array)];
          }
        }
      }

      // Create a media image for the field_simpleview_main_image.
      $main_image = $item->get('imagefile');
      $images = $item->get('images');
      $entity->field_simpleview_main_image = SampleSimpleviewFeedsHelper::setImage($item, $main_image, $images, $state);

      // And... Save! We made it.
      $this->storageController->save($entity);

      // Dispatch postsave event.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_POSTSAVE, $entity, $item);

      // Track progress.
      $existing_entity_id ? $state->updated++ : $state->created++;
    }
    catch (EmptyFeedException $e) {
      // Not an error.
      $state->skipped++;
    }
      // Something bad happened, log it.
    catch (ValidationException $e) {
      $state->failed++;
      $state->setMessage($e->getFormattedMessage(), 'warning');
    }
    catch (\Exception $e) {
      $state->failed++;
      $state->setMessage($e->getMessage(), 'warning');
    }
  }



  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Event');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Events');
  }

}
