<?php

/**
 * Implements hook_views_data_alter().
 */
function sample_quiz_views_data_alter(array &$data) {
  $data['node__field_number_of_find_your_place_']['field_number_of_find_your_place__value']['sort']['id'] = 'number_of_find_your_place';
}
