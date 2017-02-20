<?php

/**
 * @file
 * Menu callbacks for my_module_species module.
 */

/**
 * Implements hook_form().
 */
function my_module_species_csv_node_import_form($form, &$form_state) {
  $form['intro'] = array(
    '#type' => 'markup',
    '#markup' => '<h3>Guidelines to successfully upload data (csv)</h3>
<ol>
    <li>Columns should be listed in the following order on the .csv to be uploaded:
Organization name, Contact name, Date of data, Location Name, Latitude, Longitude, Ecosystem/habitat of interest, Taxonomic Group, Species, Presence/Absence, 
Reference or city site, Relative Abundance</li>
    <li>Organization name and contact name should already exist in the database. If the Organization and contact name has not been previously entered it should be added at the Add Data Collector sidebar menu before the .csv file is uploaded. </li>
    <li>Be sure that dates in the .csv file are entered in the “n/j/y” format. For example data collected on August 3rd 2009 should be entered as 8/3/09. Note: .csv files will sometimes default back to the mm/dd/yyyy format when the file is closed and reopened. Highlight the date of data column, right click and select "format cells". Under the "Category" section click on "Date". In the "Type" section click the example with the appropriate format "3/14/01" then click the "Okay" button at the bottom of the window. Save the file and attempt to upload again. </li>
    <li>In order to limit the possibility of duplicate entries and typos, new data categories will need to be pre-entered into the database. This can be done most efficiently by a City Site Manager through the "Taxonomy" link. Additionally a General User can add new category names by entering new single records through the Add options in the User Menu. </li>
</ol>',
    '#prefix' => '<div class="readmore-toggle">',
    '#suffix' => '</div>',
  );

  $form['#attributes'] = array(
    'enctype' => 'multipart/form-data',
  );

  $form['csv'] = array(
    '#type'              => 'file',
    '#title'             => t('CSV file'),
    '#description'       => t('The file that holds species detection node data in CSV format'),
  );

  $form['submit_button'] = array(
    '#type'  => 'submit',
    '#value' => t('Upload and import'),
  );

  $form['#validate'] = array(
    'my_module_species_csv_node_import_form_validate',
    'my_module_species_csv_node_import_form_validate_format',
  );

  return $form;
}

/**
 * Form validation handler for my_module_species_csv_node_import_form().
 *
 * This one handles file upload validation.
 */
function my_module_species_csv_node_import_form_validate($form, &$form_state) {
  $validators = array(
    // Validate the extension of the file.
    'file_validate_extensions' => array('csv'),
    // Validate maximum size of the file.
    'file_validate_size' => array(4 * 1024 * 1024),
  );

  // Try to save the uploaded files in the context of defined validators.
  $file = file_save_upload('csv', $validators, 'public://', FILE_EXISTS_REPLACE);

  // The validation failed.
  if (!$file) {
    form_set_error('csv', t('Validation failed for the uploaded file'));

    return;
  }

  // The validation didn't fail, save the path to the uploaded file.
  $form_state['values']['csv'] = $file->destination;
}

/**
 * Form validation handler for my_module_species_csv_node_import_form().
 *
 * This one handles the file content validation.
 */
function my_module_species_csv_node_import_form_validate_format($form, &$form_state) {
  if ($form_state['values']['csv'] === '') {
    return;
  }

  if (FALSE === ($handle = fopen($form_state['values']['csv'], 'r'))) {
    form_set_error('csv', t('Unable to read uploaded file !filepath', array('!filepath' => $form_state['values']['csv'])));

    return;
  }

  $columns = fgetcsv($handle, 4096);

  fclose($handle);
}

/**
 * Form submission handler for my_module_species_csv_node_import_form().
 */
function my_module_species_csv_node_import_form_submit($form, &$form_state) {
  // Accepts line endings from any kind of computer.
  ini_set('auto_detect_line_endings', TRUE);

  // Starts a bacth import process
  $batch = array(
    'title'            => t('Importing CSV ...'),
    'operations'       => array(),
    'init_message'     => t('Starting import'),
    'progress_message' => t('Processed @current out of @total.'),
    'error_message'    => t('An error occurred during processing'),
    'finished'         => 'my_module_species_csv_import_finished',
    'file'             => drupal_get_path('module', 'my_module_species') . '/includes/batch.inc',
  );

  // This shouldn't happen, log it.
  if (!isset($form_state['values']['csv'])) {
    drupal_set_message('my_module_species', 'An unknown error has occured. Please check', 'error');
    return;
  }

  if (FALSE === ($handle = fopen($form_state['values']['csv'], 'r'))) {
    drupal_set_message('my_module_species', 'An unknown error has occured on file open. Please check', 'error');
    return;
  }

  $batch['operations'][] = array(
    'my_module_species_csv_import_remember_filename',
    array($form_state['values']['csv']),
  );

  $first = TRUE;
  while ($line = fgetcsv($handle, 4096)) {
    // If it's the header line, skip to the next line.
    if ($first) {
      $first = FALSE;
      continue;
    }

    $batch['operations'][] = array(
      'my_module_species_csv_import_import_line',
      array(array_map('base64_encode', $line)),
    );
  }

  fclose($handle);

  batch_set($batch);
}
