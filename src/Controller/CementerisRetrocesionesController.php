<?php

namespace Drupal\cementeris_retrocesiones\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Retrocesiones routes.
 */
class CementerisRetrocesionesController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function description(): array {

    $build['title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Retrocessions'),
      '#prefix' => '<div class="title-page"><h3>',
      '#suffix' => '</h3></div>',
    ];
    $build['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Vivamus sollicitudin leo eget dui placerat vehicula. Aenean ultrices euismod arcu sed finibus. Vivamus commodo erat sed mauris aliquam, non pellentesque lorem bibendum. Etiam sit amet dolor at velit consectetur tincidunt. Ut ultrices pretium tellus id pretium. Sed interdum bibendum tortor, vitae dictum tortor fringilla vel. Duis ut ultrices lectus, at viverra tellus. Proin aliquam commodo porta. Ut ac sagittis enim, non gravida augue. Praesent a urna eget enim rutrum luctus. Aenean odio ipsum, tempus sit amet orci nec, tristique elementum dolor. Ut justo quam, placerat in convallis eu, sodales vitae augue. Phasellus ac dolor porta, commodo ex non, pellentesque sem. Donec purus nisi, gravida bibendum ullamcorper nec, gravida ac velit.'),
      '#prefix' => '<div class="page-information"><p>',
      '#suffix' => '</p></div>',
      '#attached' => [
        'library' => [
          'cementeris_retrocesiones/cementeris_retrocesiones',
        ],
      ],
    ];

    return $build;
  }

  public function information(): array {

    $build['content'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Information'),
      '#prefix' => '<div class="title-page"><h3>',
      '#suffix' => '</h3></div>',
    ];
    $build['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Quisque odio nisl, tempus sed imperdiet a, rhoncus commodo purus. Mauris quis dapibus orci. In semper congue sem, eu faucibus purus iaculis eu. Nulla risus urna, ornare a sem rhoncus, iaculis sollicitudin ex. Cras at convallis velit, eget egestas augue. Nulla massa ligula, vehicula eu erat vel, rhoncus bibendum erat. In interdum erat tortor, in mollis tortor ullamcorper ac. Morbi eget semper erat. Suspendisse ultrices aliquam lacus. Duis pharetra nulla nec posuere placerat. Morbi nec nisl sit amet lacus cursus ornare non ut urna. Maecenas quam quam, rhoncus non venenatis in, commodo et sapien. Duis in convallis ligula.'),
      '#prefix' => '<div class="page-information"><p>',
      '#suffix' => '</p></div>',
      '#attached' => [
        'library' => [
          'cementeris_retrocesiones/cementeris_retrocesiones',
        ],
      ],
    ];

    return $build;
  }

}
