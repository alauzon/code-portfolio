<?php

/**
 * @file
 * Helpers for my_module_species module.
 */

/**
 * Gets the percentage of presence/absence given the location name and date.
 *
 * @param string $locationName
 *   The location name.
 * @param string $date
 *   The date.
 *
 * @return bool|int
 *   Either FALSE or the calculated percentage.
 */
function my_module_species_get_percentage($locationName, $date) {
  $year = substr($date, 0, 4);
  $firstDayTime = new DateTime('first day of January' . $year);
  $firstDay = $firstDayTime->format('Y-m-d');
  $lastDayTime = new DateTime('first day of January ' . ($year + 1));
  $lastDay = $lastDayTime->format('Y-m-d');

  $query = db_select('field_data_field_presence_absence', 'fp');
  $query->join('field_data_field_location_name', 'fl', 'fl.entity_id = fp.entity_id');
  $query->join('field_data_field_date', 'fd', 'fd.entity_id = fp.entity_id');
  $query->fields('fp', array('field_presence_absence_value'));
  $query->condition('fd.field_date_value', $firstDay, '>=');
  $query->condition('fd.field_date_value', $lastDay, '<');
  $query->condition('fl.field_location_name_tid', $locationName);

  $result = $query->execute();

  $rows = $result->fetchAll();
  $totalRows = count($rows);

  if ($totalRows === 0) {
    return FALSE;
  }

  if ($totalRows === 1) {
    return 100;
  }

  $presentRows = 0;
  foreach ($rows as $row) {
    if ($row->field_presence_absence_value === 'Presence') {
      $presentRows++;
    }
  }

  return round($presentRows * 100 / $totalRows, 2);
}
