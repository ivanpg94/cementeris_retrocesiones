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
    $form['excel_file_structure_cementeris'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Estructura cementiris'),
      '#upload_location' => 'public://retrocesiones/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [25600000], // 25MB
      ],
      '#description' => $this->t('Allowed file types: .csv. Max size: 25MB'),
      '#required' => FALSE,
      '#default_value' => $config->get('excel_file_structure_cementeris') ?? NULL,
    ];

    $form['excel_file_type_of_grave'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Tipos de sepultura'),
      '#upload_location' => 'public://retrocesiones/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [25600000], // 25MB
      ],
      '#description' => $this->t('Allowed file types: .csv. Max size: 25MB'),
      '#required' => FALSE,
      '#default_value' => $config->get('excel_file_type_of_grave') ?? NULL,
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

    // Save date
    $this->config('cementeris_retrocesiones.settings')
      ->set('start_date', $form_state->getValue('start_date'))
      ->set('end_date', $form_state->getValue('end_date'))
      ->save();

    // structure_cementeris csv
    $new_fid_structure = $form_state->getValue('excel_file_structure_cementeris');
    $old_fid_structure = $this->config('cementeris_retrocesiones.settings')->get('excel_file_structure_cementeris');

    if (empty($new_fid_structure) && !empty($old_fid_structure)) {
      $file = \Drupal\file\Entity\File::load($old_fid_structure[0]);
      if ($file) {
        $file->delete();
      }
      $this->config('cementeris_retrocesiones.settings')
        ->clear('excel_file_structure_cementeris')
        ->save();

      $this->messenger()->addStatus($this->t('The previous file for "estructura cementiris" has been deleted.'));
    }

    if (!empty($new_fid_structure[0])) {
      if (empty($old_fid_structure) || $old_fid_structure[0] !== $new_fid_structure[0]) {
        if (!empty($old_fid_structure)) {
          $old_file = \Drupal\file\Entity\File::load($old_fid_structure[0]);
          if ($old_file) {
            $old_file->delete();
          }
        }

        $file = \Drupal\file\Entity\File::load($new_fid_structure[0]);
        if ($file) {
          $file->setPermanent();
          $file->save();

          $this->config('cementeris_retrocesiones.settings')
            ->set('excel_file_structure_cementeris', $new_fid_structure)
            ->save();

          $this->messenger()->addMessage($this->t('Excel file for "estructura cementiris" uploaded successfully.'));
        }
      }
    }

    // type_of_grave csv
    $new_fid_grave = $form_state->getValue('excel_file_type_of_grave');
    $old_fid_grave = $this->config('cementeris_retrocesiones.settings')->get('excel_file_type_of_grave');

    if (empty($new_fid_grave) && !empty($old_fid_grave)) {
      $file = \Drupal\file\Entity\File::load($old_fid_grave[0]);
      if ($file) {
        $file->delete();
      }
      $this->config('cementeris_retrocesiones.settings')
        ->clear('excel_file_type_of_grave')
        ->save();

      $this->messenger()->addStatus($this->t('The previous file for "tipos de sepultura" has been deleted.'));
    }

    if (!empty($new_fid_grave[0])) {
      if (empty($old_fid_grave) || $old_fid_grave[0] !== $new_fid_grave[0]) {
        if (!empty($old_fid_grave)) {
          $old_file = \Drupal\file\Entity\File::load($old_fid_grave[0]);
          if ($old_file) {
            $old_file->delete();
          }
        }

        $file = \Drupal\file\Entity\File::load($new_fid_grave[0]);
        if ($file) {
          $file->setPermanent();
          $file->save();

          $this->config('cementeris_retrocesiones.settings')
            ->set('excel_file_type_of_grave', $new_fid_grave)
            ->save();

          $this->messenger()->addMessage($this->t('Excel file for "tipos de sepultura" uploaded successfully.'));
        }
      }
    }

    $this->messenger()->addMessage($this->t('The date range has been saved.'));
  }
}
