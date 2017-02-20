<?php

/**
 * @file
 * Batch functions for my_module_species module.
 */

/**
 * Implements callback_batch_finished().
 *
 * Reports the status of batch operation for node_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param int $results
 *   The number of nodes updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 */
function my_module_species_csv_import_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The CSV import has completed.'));
  }
  else {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    $message = format_plural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $message .= theme('item_list', array('items' => $results));
    drupal_set_message($message);
  }
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for my_module_species_csv_node_import_form_submit().
 *
 * @param string $filename
 *   The filename to remember.
 * @param array $context
 *   An array of contextual key/values.
 */
function my_module_species_csv_import_remember_filename($filename, &$context) {
  $context['results']['uploaded_filename'] = $filename;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for my_module_species_csv_node_import_form_submit().
 *
 * @param string $line
 *   The line to import.
 * @param array $context
 *   An array of contextual key/values.
 */
function my_module_species_csv_import_import_line($line, &$context) {
  if (!isset($context['results']['rows_imported'])) {
    $context['results']['rows_imported'] = 0;
  }

  $context['results']['rows_imported']++;

  $line = $cleaned_line = array_map('base64_decode', $line);

  // Show the row count by default.
  $context['message'] = t('Importing row !c', array('!c' => $context['results']['rows_imported']));

  // Or, Provide some feedback about the row currently being processed.
  $context['message'] = t('Importing %title', array('%title' => $line[0]));

  $result = my_module_species_csv_import_create_node($line, $context['results']['rows_imported']);
}

/**
 * Create the node from CSV line.
 *
 * @param array $line
 *   The line.
 * @param int $line_number
 *   The line number.
 *
 * @return array
 *   Array containing 'success' bool status and 'message' string.
 */
function my_module_species_csv_import_create_node($line, $line_number = 0) {
  $node = new stdClass();

  $node->type = 'species_detection';
  $node->language = LANGUAGE_NONE;

  node_object_prepare($node);

  list($organization, $collector, $date, $location, $latitude, $longitude, $ecosystem, $group, $specie, $presence, $reference, $size) = $line;

  // Node title.
  if ($title === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: The first column (title) in the row can't be empty.", array('@line_number' => $line_number + 1)));
  }

  $node->title = $title;

  // Organization Name.
  if ($organization === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Organization name value can't be empty.", array('@line_number' => $line_number + 1)));
  }

  // Data collector.
  if ($collector === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Organization name value can't be empty.", array('@line_number' => $line_number + 1)));
  }
  else {
    $dataCollectors = node_load_multiple(array(), array('title' => $collector, 'type' => 'data_collector'));
    $vocabulary = taxonomy_vocabulary_machine_name_load('organization');
    $organizations = taxonomy_term_load_multiple(array(), array('vid' => $vocabulary->vid, 'name' => $organization));
    if (count($dataCollectors) === 0) {
      return my_module_species_csv_import_line_response(t('Line #@line_number: Data collector value was not found (@collector).', array('@collector' => $collector, '@line_number' => $line_number + 1)));
    }
    elseif (count($organizations) === 0) {
      return my_module_species_csv_import_line_response(t('Line #@line_number: A matching Organization name was not found (@organization).', array('@organization' => $organization, '@line_number' => $line_number + 1)));
    }
    $dc_orgs = array();
    foreach ($dataCollectors as $dataCollector) {
      if (!empty($dataCollector->field_organization_term[LANGUAGE_NONE][0]['tid']) &&
          !empty($organizations[$dataCollector->field_organization_term[LANGUAGE_NONE][0]['tid']])) {
        $dc_orgs[] = $dataCollector;
      }
    }
    if (count($dc_orgs) == 0) {
      return my_module_species_csv_import_line_response(t('Line #@line_number: There is no Data Collector and Organization Name couple that fits the description (@collector, @organization).', array(
        '@collector' => $collector,
        '@organization' => $organization,
        '@line_number' => $line_number + 1,
      )));
    }
    elseif (count($dc_orgs) > 1) {
      return my_module_species_csv_import_line_response(t('Line #@line_number: There are more than one Data Collector and Organization Name couples that fits the description (@collector, @organization).', array(
        '@collector' => $collector,
        '@organization' => $organization,
        '@line_number' => $line_number + 1,
      )));
    }
    $dataCollector = array_pop($dc_orgs);
    $node->field_data_collector[LANGUAGE_NONE][0]['target_id'] = $dataCollector->nid;
    $node->field_organization_term[LANGUAGE_NONE][0]['tid'] = $dataCollector->field_organization_term[LANGUAGE_NONE][0]['tid'];
  }

  // Date.
  if ($date === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Date value can't be empty.", array(
      '@line_number' => $line_number + 1,
    )));
  }

  $detectionDate = DateTime::createFromFormat('n/j/y', $date);
  if (!$detectionDate || $detectionDate->format('n/j/y') !== $date) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Date value is incorrect (@date). Date format is n/j/y.', array('@date' => $date, '@line_number' => $line_number + 1)));
  }

  $node->field_date[LANGUAGE_NONE][0]['value'] = $detectionDate->format('Y-m-d H:i:s');
  $node->field_date_of_data[LANGUAGE_NONE][0] = array(
    'value' => $detectionDate->format('Y-m-d H:i:s'),
    'timezone' => $detectionDate->getTimeZone()->getName(),
    'timezone_db' => $detectionDate->getTimeZone()->getName(),
    'date_type' => 'datetime',
  );

  // Location name.
  if ($location === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Location value can't be empty.", array(
      '@line_number' => $line_number + 1,
    )));
  }

  $locations = taxonomy_get_term_by_name($location, 'location');

  if (count($locations) !== 1) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Location value is incorrect (@location).', array(
      '@location' => $location,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_location_name[LANGUAGE_NONE][0]['tid'] = array_pop(array_keys($locations));

  // Ecosystem / habitat of interest.
  if ($ecosystem === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Ecosystem value can't be empty.", array(
      '@line_number' => $line_number + 1,
    )));
  }

  $ecosystems = taxonomy_get_term_by_name($ecosystem, 'ecosystem');

  if (count($ecosystems) !== 1) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Ecosystem value is incorrect (@ecosystem).', array(
      '@ecosystem' => $ecosystem,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_ecosystem[LANGUAGE_NONE][0]['tid'] = array_pop(array_keys($ecosystems));

  // Taxonomic group.
  if ($group === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Taxonomic group value can't be empty.", array(
      '@line_number' => $line_number + 1,
    )));
  }

  $groups = taxonomy_get_term_by_name($group, 'taxonomic_group');

  if (count($groups) !== 1) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Taxonomic group value is incorrect (@group).', array(
      '@group' => $group,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_taxonomic_group[LANGUAGE_NONE][0]['tid'] = array_pop(array_keys($groups));

  // Species.
  if ($specie === '') {
    return my_module_species_csv_import_line_response(t("Line #@line_number: Species value can't be empty.", array(
      '@line_number' => $line_number + 1,
    )));
  }

  $species = taxonomy_get_term_by_name($specie, 'species');

  if (count($species) !== 1) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Species value is incorrect (@specie).', array(
      '@specie' => $specie,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_species[LANGUAGE_NONE][0]['tid'] = array_pop(array_keys($species));

  if (!in_array($reference, array('Reference', 'City', ''))) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Reference or city value is incorrect (@reference).', array(
      '@reference' => $reference,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_reference_city[LANGUAGE_NONE][0]['value'] = $reference;

  // Coordinates (optional).
  if ($latitude !== '' && $longitude !== '') {
    $coordinates = $latitude . ',' . $longitude;
    if (0 === preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $coordinates)) {
      return my_module_species_csv_import_line_response(t('Line #@line_number: The coordinates are incorrect (@latitude,@longitude).', array(
        '@latitude' => $latitude,
        '@longitude' => $longitude,
        '@line_number' => $line_number + 1,
      )));
    }
    $node->field_location[LANGUAGE_NONE][0] = array(
      'geom' => "POINT ($longitude $latitude)",
      'geo_type' => 'point',
      'lat' => $latitude,
      'lon' => $longitude,
      'left' => $longitude,
      'top' => $latitude,
      'right' => $longitude,
      'bottom' => $latitude,
    );
  }

  // Presence / Absence
  if (!in_array($presence, array('Presence', 'Absence', ''))) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Presence/Absence value is incorrect (@presence).', array(
      '@presence' => $presence,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_presence_absence[LANGUAGE_NONE][0]['value'] = $presence;

  // Population size
  if ($size !== '' && !is_numeric($size)) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Population size value must be a number or empty (@size given).', array(
      '@size' => $size,
      '@line_number' => $line_number + 1,
    )));
  }

  if ($size !== '' && $size < 0) {
    return my_module_species_csv_import_line_response(t('Line #@line_number: Population size value must be a positive number (@size given).', array(
      '@size' => $size,
      '@line_number' => $line_number + 1,
    )));
  }

  $node->field_population_size[LANGUAGE_NONE][0]['value'] = $size;

  global $user;
  $node->uid = $user->uid;

  node_save($node);

  return my_module_species_csv_import_line_response(t('The node was imported successfully from row #@line_number.', array(
    '@line_number' => $line_number + 1,
  )), TRUE);
}

/**
 * Helper to return a parsing error.
 *
 * @param string $message
 *   The message to display.
 * @param bool $success
 *   The status of the operation.
 *
 * @return array
 *   Array containing 'success' bool status and 'message' string.
 */
function my_module_species_csv_import_line_response($message, $success = FALSE) {
  if ($success !== TRUE) {
    drupal_set_message($message);
  }
  return array(
    'success' => $success,
    'message' => $message,
  );
}
