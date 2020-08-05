<?php
/*
 * Helper class for Feeds
 */

namespace Drupal\sample_simpleview_feeds;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;

class SampleSimpleviewFeedsHelper {

  static public function setImage($item, $main_image, $other_images, $state)
  {
    $field_simpleview_main_image = [];
    if (isset($main_image) && !is_array($main_image)) {
      $field_simpleview_main_image = self::setAndReturnMainImageField($item, $main_image, $state);
    } elseif ($other_images && is_array($other_images)) {
      foreach ($other_images as $other_image) {
        if (isset($other_image['mediafile'])) {
          $field_simpleview_main_image = self::setAndReturnMainImageField($item, $other_image['mediafile'], $state);
        }
      }
    }

    return $field_simpleview_main_image;
  }

  static public function setAndReturnMainImageField($item, $url, $state) {
    $field_simpleview_main_image = [];

    $filesystem = \Drupal::service('file_system');
    $file_data = file_get_contents($url);
    $file_name = $filesystem->basename($url);
    $file_uri = 'public://' . $file_name;
    $file = file_save_data($file_data, $file_uri, FileSystemInterface::EXISTS_REPLACE);
    if (isset($file)) {
      try {
        $image_media_array = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties([
            'field_media_image' => [
              'target_id' => $file->id(),],
          ]);
        if ($image_media_array === NULL || !count($image_media_array)) {
          $image_media = Media::create([
            'bundle' => 'image',
            'uid' => \Drupal::currentUser()->id(),
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'field_media_image' => [
              'target_id' => $file->id(),
              'alt' => $item->get('company'),
              'title' => $item->get('title'),
            ],
          ]);
          $image_media->save();
        } else {
          $image_media = array_pop($image_media_array);
        }
        $field_simpleview_main_image[] = [
          'target_id' => $image_media->id(),
        ];
      } catch (InvalidPluginDefinitionException $e) {
        $state->failed++;
        $state->setMessage($e->getMessage(), 'warning');
      } catch (PluginNotFoundException $e) {
        $state->failed++;
        $state->setMessage($e->getMessage(), 'warning');
      }
    }

    return $field_simpleview_main_image;
  }

}
