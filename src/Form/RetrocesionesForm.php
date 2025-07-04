<?php

namespace Drupal\cementeris_retrocesiones\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Configuration form to store start and end dates for retrocessions.
 */
class RetrocesionesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cementeris_retrocessions_date_range_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cementeris_retrocessiones.settings'];
  }

  /**
   * Returns all unique cemetery names from the CSV.
   *
   * @return array
   *   ['COLLSEROLA' => 'COLLSEROLA', ...]
   */
  private function getCemeteryList(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $cemeteries = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 2000, ','); // Real separator is ','

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        if (isset($data[1])) {
          $name = strtoupper(trim($data[1]));
          if (!empty($name)) {
            $cemeteries[$name] = $name;
          }
        }
      }

      fclose($handle);
    }

    return $cemeteries;
  }

  /**
   * Reads tipos_sepultura.csv and returns an array of options.
   *
   * @return array
   *   ['1' => 'OSARIO MISMO NIVEL', '4' => 'OSARIO GRUPO 2B', ...]
   */
  private function getGraveTypes(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/tipos_sepultura.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $types = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle, 2000, ','); // Skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $code = trim($data[0] ?? '');
        $description = trim($data[1] ?? '');
        if ($code && $description) {
          $types[$code] = $description;
        }
      }

      fclose($handle);
    }

    return $types;
  }

  /**
   * Reads the CSV and returns an array of enclosures by cemetery.
   *
   * @return array
   *   Associative array [ 'COLLSEROLA' => ['Recinte A', 'Recinte B'], ... ]
   */
  private function getEnclosuresByCemetery(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $enclosures = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 2000, ','); // Read header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $cemetery_name = trim($data[1] ?? ''); // Nom Cementiri
        $enclosure_code = trim($data[4] ?? ''); // Nom Recinte (Codi)

        if ($cemetery_name && $enclosure_code) {
          $enclosures[$cemetery_name][$enclosure_code] = $enclosure_code;
        }
      }

      fclose($handle);
    }

    return $enclosures;
  }

  /**
   * AJAX callback to update the enclosure field based on selected cemetery.
   */
  public function updateEnclosureOptions(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));

    $structure = $this->getEnclosuresByCemetery();
    $enclosures = $structure[$cemetery] ?? [];
    $enclosures = ['' => $this->t('- Select an option -')] + $enclosures;

    // Update Enclosure
    $form['grave_code']['enclosure_wrapper']['enclosure']['#options'] = $enclosures;
    $form['grave_code']['enclosure_wrapper']['enclosure']['#value'] = '';
    $form['grave_code']['enclosure_wrapper']['enclosure']['#default_value'] = '';

    // Clear Department
    $form['grave_code']['department_wrapper']['department']['#options'] = ['' => $this->t('- Select an option -')];
    $form['grave_code']['department_wrapper']['department']['#value'] = '';
    $form['grave_code']['department_wrapper']['department']['#default_value'] = '';

    // Clear Way
    $form['grave_code']['way_wrapper']['way']['#options'] = ['' => $this->t('- Select an option -')];
    $form['grave_code']['way_wrapper']['way']['#value'] = '';
    $form['grave_code']['way_wrapper']['way']['#default_value'] = '';

    // Clear Grouping
    $form['grave_code']['grouping_wrapper']['grouping']['#options'] = ['' => $this->t('- Select an option -')];
    $form['grave_code']['grouping_wrapper']['grouping']['#value'] = '';
    $form['grave_code']['grouping_wrapper']['grouping']['#default_value'] = '';

    // Clear values in form_state
    $form_state->unsetValue(['grave_code', 'enclosure']);
    $form_state->unsetValue(['grave_code', 'department']);
    $form_state->unsetValue(['grave_code', 'way']);
    $form_state->unsetValue(['grave_code', 'grouping']);

    $input = $form_state->getUserInput();
    $input['grave_code']['enclosure'] = '';
    $input['grave_code']['department'] = '';
    $input['grave_code']['way'] = '';
    $input['grave_code']['grouping'] = '';
    $form_state->setUserInput($input);

    // Return all affected wrappers
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enclosure-wrapper', $form['grave_code']['enclosure_wrapper']));
    $response->addCommand(new ReplaceCommand('#department-wrapper', $form['grave_code']['department_wrapper']));
    $response->addCommand(new ReplaceCommand('#way-wrapper', $form['grave_code']['way_wrapper']));
    $response->addCommand(new ReplaceCommand('#grouping-wrapper', $form['grave_code']['grouping_wrapper']));
    return $response;
  }

  /**
   * Returns departments by cemetery and enclosure.
   *
   * @return array
   *   [ 'COLLSEROLA' => [ 'A' => [ '1' => 'DEPARTAMENT PRIMER', ... ] ] ]
   */
  private function getDepartmentsByCemeteryAndEnclosure(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $departments = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 2000, ',');

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $cemetery = strtoupper(trim($data[1] ?? ''));
        $enclosure = strtoupper(trim($data[4] ?? '')) ?: '_NONE_';
        $department = strtoupper(trim($data[7] ?? ''));

        if ($cemetery && $department) {
          $departments[$cemetery][$enclosure][$department] = $department;
        }
      }

      fclose($handle);
    }

    return $departments;
  }

  /**
   * AJAX callback to update departments based on cemetery and enclosure.
   */
  public function updateDepartmentOptions(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form['grave_code']['cemetery']['#value'] ?? ''));
    $enclosure = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));

    $departments = [];
    $ways = [];
    $groupings = [];

    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle, 2000, ','); // Skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if ($row_cemetery === $cemetery && $row_enclosure === $enclosure) {
          if (!empty($row_department)) {
            $departments[$row_department] = $row_department;
          }
          if (!empty($row_way)) {
            $ways[$row_way] = $row_way;
          }
          if (!empty($row_grouping)) {
            $groupings[$row_grouping] = $row_grouping;
          }
        }
      }

      fclose($handle);
    }

    ksort($departments);
    ksort($ways);
    ksort($groupings);

    // Update options only, DO NOT clear selected values
    $form['grave_code']['department_wrapper']['department']['#options'] = ['' => $this->t('- Select an option -')] + $departments;
    $form['grave_code']['way_wrapper']['way']['#options'] = ['' => $this->t('- Select an option -')] + $ways;
    $form['grave_code']['grouping_wrapper']['grouping']['#options'] = ['' => $this->t('- Select an option -')] + $groupings;

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#department-wrapper', $form['grave_code']['department_wrapper']));
    $response->addCommand(new ReplaceCommand('#way-wrapper', $form['grave_code']['way_wrapper']));
    $response->addCommand(new ReplaceCommand('#grouping-wrapper', $form['grave_code']['grouping_wrapper']));

    return $response;
  }

  /**
   * Returns ways by cemetery, enclosure and department.
   */
  private function getWaysByCemeteryEnclosureDepartment(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $ways = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle, 2000, ',');

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $cemetery = strtoupper(trim($data[1] ?? ''));
        $enclosure = strtoupper(trim($data[4] ?? '')) ?: '_NONE_';
        $department = strtoupper(trim($data[7] ?? '')) ?: '_NONE_';
        $way = strtoupper(trim($data[10] ?? ''));

        if ($cemetery && $way) {
          $ways[$cemetery][$enclosure][$department][$way] = $way;
        }
      }

      fclose($handle);
    }

    return $ways;
  }

  /**
   * AJAX callback to update ways based on cemetery and department.
   */
  public function updateWayOptions(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form['grave_code']['cemetery']['#value'] ?? ''));
    $department_input = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));
    $department = preg_replace('/\s+/', ' ', $department_input);

    $enclosures = [];
    $ways = [];
    $groupings = [];

    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle); // Skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = preg_replace('/\s+/', ' ', strtoupper(trim($data[7] ?? '')));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if ($row_cemetery === $cemetery && $row_department === $department) {
          if (!empty($row_enclosure)) {
            $enclosures[$row_enclosure] = $row_enclosure;
          }
          if (!empty($row_way)) {
            $ways[$row_way] = $row_way;
          }
          if (!empty($row_grouping)) {
            $groupings[$row_grouping] = $row_grouping;
          }
        }
      }

      fclose($handle);
    }

    ksort($enclosures);
    ksort($ways);
    ksort($groupings);

    // Update options only (keep selected values)
    $form['grave_code']['enclosure_wrapper']['enclosure']['#options'] = ['' => $this->t('- Select an option -')] + $enclosures;
    $form['grave_code']['way_wrapper']['way']['#options'] = ['' => $this->t('- Select an option -')] + $ways;
    $form['grave_code']['grouping_wrapper']['grouping']['#options'] = ['' => $this->t('- Select an option -')] + $groupings;

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enclosure-wrapper', $form['grave_code']['enclosure_wrapper']));
    $response->addCommand(new ReplaceCommand('#way-wrapper', $form['grave_code']['way_wrapper']));
    $response->addCommand(new ReplaceCommand('#grouping-wrapper', $form['grave_code']['grouping_wrapper']));

    return $response;
  }

  /**
   * Returns groupings by cemetery, enclosure, department and way.
   */
  private function getGroupingsByCemeteryEnclosureDepartmentWay(): array {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (!file_exists($file_path)) {
      return [];
    }

    $groupings = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle, 2000, ','); // Skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $cemetery = strtoupper(trim($data[1] ?? ''));
        $enclosure = strtoupper(trim($data[4] ?? ''));
        $department = strtoupper(trim($data[7] ?? ''));
        $way = strtoupper(trim($data[10] ?? ''));
        $grouping = strtoupper(trim($data[13] ?? ''));

        if (!$cemetery || !$grouping) {
          continue;
        }

        // Use optional keys depending on whether they are empty
        $groupings[$cemetery]
        [$enclosure ?: '_NONE_']
        [$department ?: '_NONE_']
        [$way ?: '_NONE_']
        [$grouping] = $grouping;
      }

      fclose($handle);
    }

    return $groupings;
  }

  /**
   * AJAX callback to update groupings based on cemetery and way.
   */
  public function updateGroupingOptions(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form['grave_code']['cemetery']['#value'] ?? ''));
    $way_input = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));
    $way = preg_replace('/\s+/', ' ', $way_input);

    $enclosures = [];
    $departments = [];
    $groupings = [];

    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle); // Skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if ($row_cemetery === $cemetery && $row_way === $way) {
          if (!empty($row_enclosure)) {
            $enclosures[$row_enclosure] = $row_enclosure;
          }
          if (!empty($row_department)) {
            $departments[$row_department] = $row_department;
          }
          if (!empty($row_grouping)) {
            $groupings[$row_grouping] = $row_grouping;
          }
        }
      }

      fclose($handle);
    }

    ksort($enclosures);
    ksort($departments);
    ksort($groupings);

    // Update options only, no value reset
    $form['grave_code']['enclosure_wrapper']['enclosure']['#options'] = ['' => $this->t('- Select an option -')] + $enclosures;
    $form['grave_code']['department_wrapper']['department']['#options'] = ['' => $this->t('- Select an option -')] + $departments;
    $form['grave_code']['grouping_wrapper']['grouping']['#options'] = ['' => $this->t('- Select an option -')] + $groupings;

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enclosure-wrapper', $form['grave_code']['enclosure_wrapper']));
    $response->addCommand(new ReplaceCommand('#department-wrapper', $form['grave_code']['department_wrapper']));
    $response->addCommand(new ReplaceCommand('#grouping-wrapper', $form['grave_code']['grouping_wrapper']));
    return $response;
  }

  /**
   * AJAX callback to update dependent selects when cemetery changes.
   */
  public function updateCemeteryDependents(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));

    $enclosures = [];
    $departments = [];
    $ways = [];
    $groupings = [];

    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle); // skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));

        if ($row_cemetery !== $cemetery) {
          continue;
        }

        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = strtoupper(trim($data[10] ?? ''));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if (!empty($row_enclosure)) {
          $enclosures[$row_enclosure] = $row_enclosure;
        }
        if (!empty($row_department)) {
          $departments[$row_department] = $row_department;
        }
        if (!empty($row_way)) {
          $ways[$row_way] = $row_way;
        }
        if (!empty($row_grouping)) {
          $groupings[$row_grouping] = $row_grouping;
        }
      }

      fclose($handle);
    }

    ksort($enclosures);
    ksort($departments);
    ksort($ways);
    ksort($groupings);

    $form['grave_code']['enclosure_wrapper']['enclosure']['#options'] = ['' => $this->t('- Select an option -')] + $enclosures;
    $form['grave_code']['department_wrapper']['department']['#options'] = ['' => $this->t('- Select an option -')] + $departments;
    $form['grave_code']['way_wrapper']['way']['#options'] = ['' => $this->t('- Select an option -')] + $ways;
    $form['grave_code']['grouping_wrapper']['grouping']['#options'] = ['' => $this->t('- Select an option -')] + $groupings;

    // Do not clear values, only update options
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enclosure-wrapper', $form['grave_code']['enclosure_wrapper']));
    $response->addCommand(new ReplaceCommand('#department-wrapper', $form['grave_code']['department_wrapper']));
    $response->addCommand(new ReplaceCommand('#way-wrapper', $form['grave_code']['way_wrapper']));
    $response->addCommand(new ReplaceCommand('#grouping-wrapper', $form['grave_code']['grouping_wrapper']));
    return $response;
  }

  /**
   * AJAX callback to update dependents when grouping changes.
   */
  public function updateGroupingDependents(array &$form, FormStateInterface $form_state): AjaxResponse {
    $cemetery = strtoupper(trim($form['grave_code']['cemetery']['#value'] ?? ''));
    $grouping_input = strtoupper(trim($form_state->getTriggeringElement()['#value'] ?? ''));
    $grouping = preg_replace('/\s+/', ' ', $grouping_input);

    $enclosures = [];
    $departments = [];
    $ways = [];

    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      fgetcsv($handle); // skip header

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if ($row_cemetery === $cemetery && $row_grouping === $grouping) {
          if (!empty($row_enclosure)) {
            $enclosures[$row_enclosure] = $row_enclosure;
          }
          if (!empty($row_department)) {
            $departments[$row_department] = $row_department;
          }
          if (!empty($row_way)) {
            $ways[$row_way] = $row_way;
          }
        }
      }

      fclose($handle);
    }

    ksort($enclosures);
    ksort($departments);
    ksort($ways);

    // Update options only, keep selected values
    $form['grave_code']['enclosure_wrapper']['enclosure']['#options'] = ['' => $this->t('- Select an option -')] + $enclosures;
    $form['grave_code']['department_wrapper']['department']['#options'] = ['' => $this->t('- Select an option -')] + $departments;
    $form['grave_code']['way_wrapper']['way']['#options'] = ['' => $this->t('- Select an option -')] + $ways;

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enclosure-wrapper', $form['grave_code']['enclosure_wrapper']));
    $response->addCommand(new ReplaceCommand('#department-wrapper', $form['grave_code']['department_wrapper']));
    $response->addCommand(new ReplaceCommand('#way-wrapper', $form['grave_code']['way_wrapper']));

    return $response;
  }
  /**
   * Validate NIF
   *
   * @param string $nif
   *
   * @return bool
   *   TRUE if NIF is correct else FALSE.
   */
  protected function isValidNif(string $nif): bool {
    $nif = strtoupper(preg_replace('/[^0-9A-Z]/', '', $nif));

    // Formato DNI/NIF: 8 dígitos seguidos de una letra.
    if (!preg_match('/^([0-9]{8})([A-Z])$/', $nif, $matches)) {
      return FALSE;
    }

    $numero   = intval($matches[1]);
    $letra_in = $matches[2];

    $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letra_ok = $letras[$numero % 23];

    return $letra_ok === $letra_in;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['#prefix'] = '<div id="retrocesiones-form-wrapper">';
    $form['#suffix'] = '</div>';
    // Group: Grave code
    $form['grave_code'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Grave code'),
    ];

    // Cemetery
    $form['grave_code']['cemetery'] = [
      '#type' => 'select',
      '#title' => $this->t('Cemetery'),
      '#options' => ['' => $this->t('- Select an option -')] + $this->getCemeteryList(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateCemeteryDependents',
        'event' => 'change',
        'wrapper' => 'enclosure-wrapper',
      ],
      '#default_value' => '',
      '#validated' => TRUE,
    ];

    // Enclosure
    $form['grave_code']['enclosure_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'enclosure-wrapper'],
    ];
    $form['grave_code']['enclosure_wrapper']['enclosure'] = [
      '#type' => 'select',
      '#title' => $this->t('Enclosure'),
      '#options' => ['' => $this->t('- Select an option -')],
      '#ajax' => [
        'callback' => '::updateDepartmentOptions',
        'event' => 'change',
        'wrapper' => 'department-wrapper',
      ],
      '#default_value' => NULL,
      '#validated' => TRUE,
    ];

    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger) && isset($trigger['#name']) && $trigger['#name'] === 'grave_code[cemetery]') {
      $form['grave_code']['enclosure_wrapper']['enclosure']['#value'] = '';
      $form['grave_code']['enclosure_wrapper']['enclosure']['#default_value'] = '';

      $form['grave_code']['department_wrapper']['department']['#value'] = '';
      $form['grave_code']['department_wrapper']['department']['#default_value'] = '';

      $form['grave_code']['way_wrapper']['way']['#value'] = '';
      $form['grave_code']['way_wrapper']['way']['#default_value'] = '';

      $form['grave_code']['grouping_wrapper']['grouping']['#value'] = '';
      $form['grave_code']['grouping_wrapper']['grouping']['#default_value'] = '';
    }

    // Department
    $form['grave_code']['department_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'department-wrapper'],
    ];
    $form['grave_code']['department_wrapper']['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department'),
      '#options' => ['' => $this->t('- Select an option -')],
      '#ajax' => [
        'callback' => '::updateWayOptions',
        'event' => 'change',
        'wrapper' => 'way-wrapper',
      ],
      '#default_value' => '',
      '#validated' => TRUE,
    ];

    if (!empty($trigger) && in_array($trigger['#name'], ['grave_code[cemetery]', 'grave_code[enclosure]'])) {
      $form['grave_code']['department_wrapper']['department']['#value'] = '';
      $form['grave_code']['department_wrapper']['department']['#default_value'] = '';
    }

    // Way / Block / Zone
    $form['grave_code']['way_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'way-wrapper'],
    ];
    $form['grave_code']['way_wrapper']['way'] = [
      '#type' => 'select',
      '#title' => $this->t('Way / Block / Zone'),
      '#options' => ['' => $this->t('- Select an option -')],
      '#ajax' => [
        'callback' => '::updateGroupingOptions',
        'event' => 'change',
        'wrapper' => 'grouping-wrapper',
      ],
      '#default_value' => '',
      '#validated' => TRUE,
    ];

    if (!empty($trigger) && in_array($trigger['#name'], ['grave_code[cemetery]', 'grave_code[enclosure]', 'grave_code[way]'])) {
      $form['grave_code']['way_wrapper']['way']['#value'] = '';
      $form['grave_code']['way_wrapper']['way']['#default_value'] = '';
    }

    // Grouping
    $form['grave_code']['grouping_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'grouping-wrapper'],
    ];

    $form['grave_code']['grouping_wrapper']['grouping'] = [
      '#type' => 'select',
      '#title' => $this->t('Grouping'),
      '#options' => ['' => $this->t('- Select an option -')],
      '#default_value' => '',
      '#ajax' => [
        'callback' => '::updateGroupingDependents',
        'event' => 'change',
        'wrapper' => 'enclosure-wrapper', // Or more if you want to refresh more fields
      ],
      '#validated' => TRUE,
    ];

    if (!empty($trigger) && in_array($trigger['#name'], ['grave_code[cemetery]', 'grave_code[enclosure]', 'grave_code[way]', 'grave_code[grouping]', 'grave_code[department]'])) {
      $form['grave_code']['grouping_wrapper']['grouping']['#value'] = '';
      $form['grave_code']['grouping_wrapper']['grouping']['#default_value'] = '';
    }

    // Grave type
    $form['grave_code']['grave_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of Grave'),
      '#options' => ['' => $this->t('- Select an option -')] + $this->getGraveTypes(),
      '#required' => TRUE,
      '#default_value' => '',
    ];

    // Class
    $form['grave_code']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#default_value' => '',
    ];

    // Number
    $form['grave_code']['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number'),
      '#default_value' => '',
    ];

    // Bis
    $form['grave_code']['bis'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bis'),
      '#default_value' => '',
    ];

    // Floor
    $form['grave_code']['floor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Floor'),
      '#default_value' => '',
    ];

    // NIF/NIE
    $form['nif'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NIF / NIE'),
      '#required' => TRUE,
    ];

    // First name
    $form['nombre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // First surname
    $form['apellido1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First surname of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // Second surname
    $form['apellido2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second surname of the owner or co-owner'),
      '#required' => TRUE,
    ];

    // Phone
    $form['telefono'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact phone number'),
      '#required' => TRUE,
    ];

    // Email
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    // Accept data protection policy
    $form['acepta_politica'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and accept the CBSA data protection policy CBSA.'),
      '#required' => TRUE,
      '#description' => $this->t(
        '<a href=":url_ca" target="_blank">@catalan</a> | <a href=":url_es" target="_blank">@spanish</a>',
        [
          ':url_ca' => 'https://cementiris.ajuntament.barcelona.cat/ca/avis-legal-i-privacitat',
          ':url_es' => 'https://cementiris.ajuntament.barcelona.cat/es/avis-legal-i-privacitat',
          '@catalan' => $this->t('Catalan'),
          '@spanish' => $this->t('Spanish'),
        ]
      ),
    ];

    // Consent to notifications
    $form['acepta_notificaciones'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I consent to receive notifications via the provided email or phone.'),
      '#required' => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'wrapper'  => 'retrocesiones-form-wrapper',
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // --- NIF / DNI ----------------------------------------------------------
    $nif = $form_state->getValue('nif');
    if (!$this->isValidNif($nif)) {
      $form_state->setErrorByName('nif', $this->t('The NIF/DNI entered is not valid.'));
    }

    // --- e-mail -------------------------------------------------------------
    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');
    $email = $form_state->getValue('email');
    if (!$email_validator->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('The email address is not valid.'));
    }

    // --- floor (número entero) ---------------------------------------------
    $floor = $form_state->getValue('floor');
    if ($floor !== '' && !ctype_digit((string) $floor)) {
      $form_state->setErrorByName('floor', $this->t('Floor must be an integer.'));
    }

    // --- number (número, entero o decimal) ---------------------------------
    $number = $form_state->getValue('number');
    if ($number !== '' && !is_numeric($number)) {
      $form_state->setErrorByName('number', $this->t('Number must be a numeric value.'));
    }

    // --- class (una sola letra) --------------------------------------------
    $class = $form_state->getValue('class');
    if ($class !== '' && !preg_match('/^[A-Za-z]$/', $class)) {
      $form_state->setErrorByName('class', $this->t('Class must contain exactly one letter.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $cemetery = $values['cemetery'] ?? '';
    $enclosure = $values['enclosure'] ?? '';
    $department = $values['department'] ?? '';
    $way = $values['way'] ?? '';
    $grouping = $values['grouping'] ?? '';
    $grave_type = $values['grave_type'] ?? '';
    $class = $values['class'] ?? '';
    $number = $values['number'] ?? '';
    $bis = $values['bis'] ?? '';
    $floor = $values['floor'] ?? '';
    $nif = $values['nif'] ?? '';
    $nombre = $values['nombre'] ?? '';
    $apellido1 = $values['apellido1'] ?? '';
    $apellido2 = $values['apellido2'] ?? '';
    $telefono = $values['telefono'] ?? '';
    $email = $values['email'] ?? '';
    $acepta_politica = $values['acepta_politica'] ?? FALSE;
    $acepta_notificaciones = $values['acepta_notificaciones'] ?? FALSE;

    // CSV path
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    $match = null;

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 2000, ',');

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if (
          $row_cemetery === strtoupper($cemetery) &&
          $row_enclosure === strtoupper($enclosure) &&
          $row_department === strtoupper($department) &&
          $row_way === strtoupper($way) &&
          $row_grouping === strtoupper($grouping)
        ) {
          $match = $data;
          break;
        }
      }

      fclose($handle);
    }

    if ($match) {
      $cemetery = $match[0];
      $enclosure = $match[2];
      $department = $match[5];
      $way = $match[8];
      $grouping = $match[11];
    }
//
//
//    dump([
//      'cemetery' => $cemetery,
//      'enclosure' => $enclosure,
//      'department' => $department,
//      'way' => $way,
//      'grouping' => $grouping,
//      'grave_type' => $grave_type,
//      'class' => $class,
//      'number' => $number,
//      'bis' => $bis,
//      'floor' => $floor,
//      'nif' => $nif,
//      'nombre' => $nombre,
//      'apellido1' => $apellido1,
//      'apellido2' => $apellido2,
//      'telefono' => $telefono,
//      'email' => $email,
//      'acepta_politica' => $acepta_politica,
//      'acepta_notificaciones' => $acepta_notificaciones,
//    ]);
//    die();
  }
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // 1) Si hay errores de validación, devolvemos el formulario actualizado.
    if ($form_state->getErrors()) {
      $response->addCommand(new ReplaceCommand(
        '#retrocesiones-form-wrapper',   // ← el wrapper de tu formulario
        $form
      ));
      return $response;
    }

    $values = $form_state->getValues();

    $cemetery = $values['cemetery'] ?? '';
    $enclosure = $values['enclosure'] ?? '';
    $department = $values['department'] ?? '';
    $way = $values['way'] ?? '';
    $grouping = $values['grouping'] ?? '';
    $grave_type = $values['grave_type'] ?? '';
    $class = $values['class'] ?? '';
    $number = $values['number'] ?? '';
    $bis = $values['bis'] ?? '';
    $floor = $values['floor'] ?? '';
    $nif = $values['nif'] ?? '';
    $nombre = $values['nombre'] ?? '';
    $apellido1 = $values['apellido1'] ?? '';
    $apellido2 = $values['apellido2'] ?? '';
    $telefono = $values['telefono'] ?? '';
    $email = $values['email'] ?? '';
    $acepta_politica = $values['acepta_politica'] ?? FALSE;
    $acepta_notificaciones = $values['acepta_notificaciones'] ?? FALSE;

    // CSV path
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cementeris_retrocesiones') . '/data/estructura_cementiris.csv';

    $match = null;

    if (file_exists($file_path) && ($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 2000, ',');

      while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
        $row_cemetery = strtoupper(trim($data[1] ?? ''));
        $row_enclosure = strtoupper(trim($data[4] ?? ''));
        $row_department = strtoupper(trim($data[7] ?? ''));
        $row_way = preg_replace('/\s+/', ' ', strtoupper(trim($data[10] ?? '')));
        $row_grouping = strtoupper(trim($data[13] ?? ''));

        if (
          $row_cemetery === strtoupper($cemetery) &&
          $row_enclosure === strtoupper($enclosure) &&
          $row_department === strtoupper($department) &&
          $row_way === strtoupper($way) &&
          $row_grouping === strtoupper($grouping)
        ) {
          $match = $data;
          break;
        }
      }

      fclose($handle);
    }

    if ($match) {
      $cemetery = $match[0];
      $enclosure = $match[2];
      $department = $match[5];
      $way = $match[8];
      $grouping = $match[11];
    }
    // TODO HERE WE CALL SERVICES

    $content = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Cementerio: @v',   ['@v' => $cemetery       ?: '-']),
        $this->t('Recinto: @v',      ['@v' => $enclosure      ?: '-']),
        $this->t('Departamento: @v', ['@v' => $department     ?: '-']),
        $this->t('Vía: @v',          ['@v' => $way            ?: '-']),
        $this->t('Agrupación: @v',   ['@v' => $grouping       ?: '-']),
        $this->t('Tipo sepultura: @v',['@v' => $grave_type    ?: '-']),
        $this->t('Clase: @v',        ['@v' => $class          ?: '-']),
        $this->t('Número: @v',       ['@v' => $number         ?: '-']),
        $this->t('Bis: @v',          ['@v' => $bis ? $si : $no]),
        $this->t('Piso: @v',         ['@v' => $floor          ?: '-']),
        $this->t('NIF: @v',          ['@v' => $nif            ?: '-']),
        $this->t('Titular: @v',      ['@v' => $nombre . ' ' . $apellido1 . ' ' . $apellido2]),
        $this->t('Teléfono: @v',     ['@v' => $telefono       ?: '-']),
        $this->t('Email: @v',        ['@v' => $email          ?: '-']),
        $this->t('Acepta política: @v',       ['@v' => (int) $acepta_politica]),
        $this->t('Acepta notificaciones: @v', ['@v' => (int) $acepta_notificaciones]),
      ],
    ];
    // TODO $CONTENT MUST CONTAIN SERVICE RESPONSE MESSAGE

    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Request sent'),
      $content,
      ['width' => '500']
    ));

    return $response;
  }
}

