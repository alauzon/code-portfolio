<?php

namespace Drupal\sample_simpleview_feeds\Feeds\Processor;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Feeds\Processor\NodeProcessor;
use Drupal\taxonomy\Entity\Term;
use Drupal\sample_simpleview_feeds\SampleSimpleviewFeedsHelper;

define('AMENITYTABS_VID', 'amenitytabs');
define('LISTING_CATEGORIES_VID', 'listing_categories');
define('SIMPLEVIEW_CATEGORY_VID', 'simpleview_category');

/**
 * Defines a feeds processor.
 *
 * Creates nodes and paragraphs from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:node:listing",
 *   title = @Translation("Listing"),
 *   description = @Translation("Creates Listing nodes."),
 *   entity_type = "node",
 *   arguments = {"@entity_type.manager", "@entity.query", "@entity_type.bundle.info"},
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class ListingProcessor extends NodeProcessor {

  static $printed = FALSE;
  static $recordsProcessedTotal = 0;
  static $recordsProcessed = 0;
  static $recordProcessedThreshold = 100;

  static private function format_size($size) {
    $mod = 1024;
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
      $size /= $mod;
    }
    return round($size, 2) . $units[$i];
  }

  public function process(FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    if (!self::$printed) {
      \Drupal::logger('Feeds')->info('Peak memory usage ListingProcessor: %memory_get_peak_usage.', ['%memory_get_peak_usage' => self::format_size(memory_get_peak_usage())]);
      self::$printed = TRUE;
    }
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

    self::$recordsProcessedTotal++;
    self::$recordsProcessed++;
    if (self::$recordsProcessed >= self::$recordProcessedThreshold) {
      self::$recordsProcessed = 0;
      \Drupal::logger('Feeds')->info('ListingProcessor %recordsProcessedTotal records processed.', ['%recordsProcessedTotal' => self::$recordsProcessedTotal]);
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

      $entity->field_amenities = $entity->field_simpleview_category = array();
      $amenity_tabs = $item->get('amenitytabs');
      if (isset($amenity_tabs)) {
        foreach ($amenity_tabs['amenitytab'] as $tab => $amenity_groups) {
          $grand_parent_term_array = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $amenity_groups['tabname'], 'vid' => AMENITYTABS_VID]);
          if (!count($grand_parent_term_array) || $grand_parent_term_array === NULL) {
            // Create it.
            $grand_parent_term = Term::create([
              'name' => $amenity_groups['tabname'],
              'vid' => AMENITYTABS_VID,
            ]);
            $grand_parent_term->save();
            $grand_parent_term_id = $grand_parent_term->id();
          } else {
            $grand_parent_term_id = key($grand_parent_term_array);
          }
          foreach ($amenity_groups['amenitygroups']['amenitygroup'] as $amenities) {
            // Apparently some of the listings don't have these.
            if (!empty($amenities['amenitys'])) {
              $parent_term_array = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties([
                  'name' => $amenities['groupname'],
                  'vid' => AMENITYTABS_VID,
                  'parent' => $grand_parent_term_id,
                ]);
              if (!count($parent_term_array) || $parent_term_array === NULL) {
                // Create it.
                $parent_term = Term::create([
                  'name' => $amenities['groupname'],
                  'vid' => AMENITYTABS_VID,
                  'parent' => $grand_parent_term_id,
                ]);
                $parent_term->save();
                $parent_term_id = $parent_term->id();
              } else {
                $parent_term_id = key($parent_term_array);
              }
              foreach ($amenities['amenitys']['amenity'] as $amenity) {
                if (isset($amenity['value']) && $amenity['value'] === 'Yes') {
                  $term_array = \Drupal::entityTypeManager()
                    ->getStorage('taxonomy_term')
                    ->loadByProperties([
                      'name' => $amenity['name'],
                      'vid' => AMENITYTABS_VID,
                      'parent' => $parent_term_id,
                    ]);
                  if (!count($term_array) || $term_array === NULL) {
                    // Create it.
                    $term = Term::create([
                      'name' => $amenity['name'],
                      'vid' => AMENITYTABS_VID,
                      'parent' => $parent_term_id,
                    ]);
                    $term->save();
                    $term_id = $term->id();
                  }
                  else {
                    $term_id = key($term_array);
                  }
                  $entity->field_amenities[] = ['target_id' => $term_id];
                }
              }
            }
          }
        }
      }

      /*
       * Add subcategories under the category
       */
      $categoryname = $item->get('categoryname');
      $subcategoryname = $item->get('subcategoryname');
      // Fetch or create the level 0 category term in our Taxonomy
      $category_term_array = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $categoryname,
          'vid' => SIMPLEVIEW_CATEGORY_VID,
        ]);
      if (!count($category_term_array) || $category_term_array === NULL) {
        // Create it.
        $category_term = Term::create([
          'name' => $categoryname,
          'vid' => SIMPLEVIEW_CATEGORY_VID,
        ]);
        $category_term->save();
        $category_term_id = $category_term->id();
      }
      else {
        $category_term_id = key($category_term_array);
      }
      // Fetch or create the level 1 category term in our Taxonomy
      $subcategory_term_array = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $subcategoryname,
          'vid' => SIMPLEVIEW_CATEGORY_VID,
          'parent' => $category_term_id,
        ]);
      if (!count($subcategory_term_array) || $subcategory_term_array === NULL) {
        // Create it.
        $subcategory_term = Term::create([
          'name' => $subcategoryname,
          'vid' => SIMPLEVIEW_CATEGORY_VID,
          'parent' => $category_term_id,
        ]);
        $subcategory_term->save();
        $subcategory_term_id = $subcategory_term->id();
      }
      else {
        $subcategory_term_id = key($subcategory_term_array);
      }
      // Set a relationship from the entity to our category level 1.
      $entity->field_simpleview_category[] = $subcategory_term_id;

      // Create a media image for the field_simpleview_main_image.
      $main_image = $item->get('main_image');
      $listingmedia = $item->get('listingmedia');
      $entity->field_simpleview_main_image = SampleSimpleviewFeedsHelper::setImage($item, $main_image, $listingmedia, $state);

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
   * sorts the images array since SV didn't do it
   * @param $a
   * @param $b
   *
   * @return int
   */
  private function sortbysoval($a, $b) {
    return ($a['SORTORDER'] < $b['SORTORDER']) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Listing');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Listings');
  }

}
