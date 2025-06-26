<?php

namespace Drupal\cementeris_retrocesiones\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form to store start and end dates for retrocessions.
 */
class DateRangeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cementeris_retrocesiones_date_range_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cementeris_retrocesiones.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('cementeris_retrocesiones.settings');

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#required' => TRUE,
      '#default_value' => $config->get('start_date') ?? '',
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#required' => TRUE,
      '#default_value' => $config->get('end_date') ?? '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');

    if (strtotime($end_date) < strtotime($start_date)) {
      $form_state->setErrorByName('end_date', $this->t('The end date must be after the start date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('cementeris_retrocesiones.settings')
      ->set('start_date', $form_state->getValue('start_date'))
      ->set('end_date', $form_state->getValue('end_date'))
      ->save();

    $this->messenger()->addMessage($this->t('The date range has been saved.'));
  }

}
