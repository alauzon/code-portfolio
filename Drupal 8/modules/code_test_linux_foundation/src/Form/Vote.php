<?php

/**
 * @file
 * Contains \Drupal\code_test_linux_foundation\Form\Vote.
 */

namespace Drupal\code_test_linux_foundation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\code_test_linux_foundation\Storage\CodeTestLinuxFoundationStorage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Vote.
 *
 * @package Drupal\code_test_linux_foundation\Form
 */
class Vote extends \Drupal\Core\Form\FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'voteForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If we have a value for the first dropdown from $form_state['values']
    // we use this both as the default value for the first dropdown and also as
    // a parameter to pass to the function that retrieves the options for the
    // second dropdown.
    $form_state_values = $form_state->getUserInput();
    if (!empty($form_state->get('thank_you'))) {
      $form['#title'] = $this->t('Thank you for voting, %name%!',
                                 array('%name%' => $form_state_values['name']));
      $votes = CodeTestLinuxFoundationStorage::loadEventCities($form_state_values['select_an_event'], 5);
      $form['paragraph_1'] = array(
        '#markup' => $this->t(
          "You voted for next year's %event% to take place in %city%",
           array(
             '%event%' => $form_state_values['select_an_event'],
             '%city%' => $form_state_values['select_a_city'],
           )),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $form['paragraph_2'] = array(
        '#markup' =>
        $this->t("Here are the current rankings for %event%",
                 array(
                   '%event%' => $form_state_values['select_an_event'],
                 )),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $form['cities'] = array(
        '#prefix' => '<div id="cities_rankings">',
        '#suffix' => '</div>',
      );
      $city_number = 1;
      foreach ($votes as $city) {
        $form['cities']['paragraph_' . $city_number] = array(
          '#markup' => $city_number++ . '. ' . $city->city,
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        );
      }
    }
    else {
      $selected = isset($form_state_values['select_an_event']) ? $form_state_values['select_an_event'] : '0';

      $cid = 'code_test_linux_foundation events';
      if ($cache = \Drupal::cache()->get($cid)) {
        $events = $cache->data;
      }
      else {
        $client = new Client();
        try {
          $res = $client->get('http://devtest.linuxfound.info/citylist/rest/', array('http_errors' => FALSE));
          $content = '' . $res->getBody()->getContents();
          $events = json_decode($content, TRUE);
        }
        catch (RequestException $e) {
          drupal_set_message($this->t('Error'));
          exit;
        }
        \Drupal::cache()->set($cid, $events);
      }
      $options = array('0' => $this->t('Choose an event'));
      foreach ($events as $event) {
        $options[$event['name']] = $event['name'];
      }

      $form['select_an_event'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select an event'),
        '#title_display' => 'before',
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => $selected,
        // Bind an ajax callback to the change event (which is the default for
        // the select form type) of the event dropdown. It will replace the
        // second dropdown when rebuilt.
        '#ajax' => array(
          // When 'event' occurs, Drupal will perform an ajax request in the
          // background. Usually the default value is sufficient (eg. change for
          // select elements), but valid values include any jQuery event,
          // most notably 'mousedown', 'blur', and 'submit'.
          // 'event' => 'change'.
          'callback' => 'Drupal\code_test_linux_foundation\Form\Vote::restOfTheFormCallback',
          'wrapper' => 'fields-revealed-only-after-event-selected',
          'effect' => 'fade',
        ),
        '#attached' => array(
          'library' => array(
            'code_test_linux_foundation/vote',
          ),
        ),
      );

      $hidden_fields_class = empty($form_state_values['op'])
                             ? 'hidden-container' : '';
      $form['hidden_fields_on_start'] = array(
        '#prefix' => '<div id="fields-revealed-only-after-event-selected"
                      class="' . $hidden_fields_class . '">',
        '#suffix' => '</div>',
      );

      $form['hidden_fields_on_start']['select_a_city'] = array(
        '#type' => 'select',
        '#title' => t('Vote for a city'),
        '#title_display' => 'before',
        '#required' => TRUE,
        // When the form is rebuilt during ajax processing, the $selected
        // variable will now have the new value and so the options will change.
        '#default_value' => !empty($user_input['select_a_city']) ? self::selectCities($user_input['select_an_event']) : 0,
        '#options' => self::selectCities('0'),
      );
      $form['hidden_fields_on_start']['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Your name'),
        '#title_display' => 'before',
        '#required' => TRUE,
      );
      $form['hidden_fields_on_start']['email'] = array(
        '#type' => 'email',
        '#title' => t('Your email'),
        '#title_display' => 'before',
        '#required' => TRUE,
      );

      $form['hidden_fields_on_start']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Vote'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('select_an_event'))) {
      $form_state->setErrorByName('select_an_event',
                                  $this->t('The Event must be selected.'));
    }
    if (empty($form_state->getValue('select_a_city'))) {
      $form_state->setErrorByName('select_a_city',
                                  $this->t('The City must be selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    CodeTestLinuxFoundationStorage::insertVote(
      array(
        'event' => $user_input['select_an_event'],
        'city' => $user_input['select_a_city'],
        'name' => $user_input['name'],
        'email' => $user_input['email'],
      )
    );

    $form_state->set('thank_you', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Function that will return the rest of the form.
   *
   * @param array $form
   *   The initial form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return mixed
   *   The new form array to be displayed.
   */
  public static function restOfTheFormCallback(array $form,
    FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['select_an_event'])) {
      $form['hidden_fields_on_start']['select_a_city']['#options'] = self::selectCities($user_input['select_an_event']);

      return $form['hidden_fields_on_start'];
    }

    return NULL;
  }

  /**
   * Select cities from the event.
   *
   * @param string $selected_event
   *   The selected event.
   *
   * @return array
   *   The cities.
   */
  private static function selectCities($selected_event) {
    $cid = 'code_test_linux_foundation events';
    if ($cache = \Drupal::cache()->get($cid)) {
      $events = $cache->data;
    }
    else {
      $client = new Client();
      try {
        $res = $client->get('http://devtest.linuxfound.info/citylist/rest/', ['http_errors' => FALSE]);
        $content = '' . $res->getBody()->getContents();
        $events = json_decode($content, TRUE);
      }
      catch (RequestException $e) {
        drupal_set_message(t('Error'));
        exit;
      }
      \Drupal::cache()->set($cid, $events);
    }
    $options = array('0' => t('Choose a city'));
    if ($selected_event != '0') {
      foreach ($events as $event) {
        if ($event['name'] == $selected_event) {
          foreach ($event['cities'] as $city) {
            $options[$city] = $city;
          }
        }
      }
    }
    else {
      foreach ($events as $event) {
        foreach ($event['cities'] as $city) {
          $options[$city] = $city;
        }
      }
    }
    return $options;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    // TODO: Implement getEditableConfigNames() method.
    return NULL;
  }

}
