<?php

namespace Drupal\cementeris_retrocesiones\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form to store start and end dates for retrocessions.
 */
class RetrocesionesForm extends ConfigFormBase {

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
    // Grupo: Código de la sepultura
    $form['codigo_sepultura'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Grave code'),
    ];

    // Cementerio
    $form['codigo_sepultura']['cementerio'] = [
      '#type' => 'select',
      '#title' => $this->t('Cemetery'),
      '#options' => [
        '1' => $this->t('Collserola'),
        '2' => $this->t('Montjuïc'),
        '3' => $this->t('Les Corts'),
        '4' => $this->t('Sant Gervasi'),
        '5' => $this->t('Sarrià'),
        '6' => $this->t('Sants'),
        '7' => $this->t('Sant Andreu'),
        '8' => $this->t('Horta'),
        '9' => $this->t('Poblenou'),
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateRecintoOptions',
        'event' => 'change',
        'wrapper' => 'recinto-wrapper',
      ],
      '#default_value' => '',
    ];

    // Recinto
    // TODO este select es el que se carga con las datos del excel/taxonomias Cementerios.xlsx
    $form['codigo_sepultura']['recinto_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'recinto-wrapper'],
    ];
    $form['codigo_sepultura']['recinto_wrapper']['recinto'] = [
      '#type' => 'select',
      '#title' => $this->t('Enclosure'),
      '#options' => ['1' => '1', '2' => '2', '3' => '3'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateDepartamentoOptions',
        'event' => 'change',
        'wrapper' => 'departamento-wrapper',
      ],
      '#default_value' => '',
    ];

    // Departamento
    // TODO este select es el que se carga con las datos del excel/taxonomias Cementerios.xlsx
    $form['codigo_sepultura']['departamento_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'departamento-wrapper'],
    ];
    $form['codigo_sepultura']['departamento_wrapper']['departamento'] = [
      '#type' => 'select',
      '#title' => $this->t('Department'),
      '#options' => ['1' => '1', '2' => '2', '3' => '3'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateViaOptions',
        'event' => 'change',
        'wrapper' => 'via-wrapper',
      ],
      '#default_value' => '',
    ];

    // Vía
    // TODO este select es el que se carga con las datos del excel/taxonomias Cementerios.xlsx
    $form['codigo_sepultura']['via_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'via-wrapper'],
    ];
    $form['codigo_sepultura']['via_wrapper']['via'] = [
      '#type' => 'select',
      '#title' => $this->t('Way / Block / Zone'),
      '#options' => ['1' => '1', '2' => '2', '3' => '3'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateAgrupacionOptions',
        'event' => 'change',
        'wrapper' => 'agrupacion-wrapper',
      ],
      '#default_value' => '',
    ];

    // Agrupación
    // TODO este select es el que se carga con las datos del excel/taxonomias Cementerios.xlsx
    $form['codigo_sepultura']['agrupacion_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'agrupacion-wrapper'],
    ];
    $form['codigo_sepultura']['agrupacion_wrapper']['agrupacion'] = [
      '#type' => 'select',
      '#title' => $this->t('Grouping'),
      '#options' => ['1' => '1', '2' => '2', '3' => '3'],
      '#required' => TRUE,
      '#default_value' => '',
    ];

    // Tipo de sepultura
    // TODO este select es el que se carga con las datos del excel/taxonomias Tipos de sepultura.xlsx
    $form['codigo_sepultura']['tipo_sepultura'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of Grave'),
      '#options' => ['1' => '1', '2' => '2', '3' => '3'],
      '#required' => TRUE,
      '#default_value' => '',
    ];

    // Clase
    $form['codigo_sepultura']['clase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#default_value' => '',
    ];

    // Número
    $form['codigo_sepultura']['numero'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number'),
      '#default_value' => '',
    ];

    // Bis
    $form['codigo_sepultura']['bis'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bis'),
      '#default_value' => '',
    ];

    // Piso
    $form['codigo_sepultura']['piso'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Floor'),
      '#default_value' => '',
    ];

    // NIF/NIE
    $form['nif'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NIF / NIE of the owner'),
      '#required' => TRUE,
    ];

    // Nombre
    $form['nombre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // Primer apellido
    $form['apellido1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First surname of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // Segundo apellido
    $form['apellido2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second surname of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // Teléfono
    $form['telefono'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact phone number'),
      '#required' => TRUE,
    ];

    // Email
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];

    // Acepto política de protección de datos
    $form['acepta_politica'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and accept the CBSA data protection policy.'),
      '#required' => TRUE,
      '#description' => $this->t('<a href=":url_ca" target="_blank">Catalan</a> | <a href=":url_es" target="_blank">Spanish</a>', [
        ':url_ca' => 'https://cementiris.ajuntament.barcelona.cat/ca/avis-legal-i-privacitat',
        ':url_es' => 'https://cementiris.ajuntament.barcelona.cat/es/avis-legal-i-privacitat',
      ]),
    ];

    // Consentimiento
    $form['acepta_notificaciones'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I consent to receive notifications via the provided email or phone.'),
      '#required' => FALSE,
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
