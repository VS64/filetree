<?php

/**
 * @file
 * Contains \Drupal\filetree\Plugin\Filter\FileTree.
 */

// Namespace for filter.
namespace Drupal\filetree\Plugin\Filter;

// Base class for filters.
use Drupal\filter\Plugin\FilterBase;

// Necessary for forms.
use Drupal\Core\Form\FormStateInterface;

// Necessary for result of process().
use Drupal\filter\FilterProcessResult;

// Necessary for URL.
use Drupal\Core\Url;

use Drupal\Component\Utility\Html;

/**
 * Provides a base filter for FileTree Filter.
 *
 * @Filter(
 *   id = "filter_filetree",
 *   title = @Translation("File Tree"),
 *   description = @Translation("Replaces [filetree arguments] with
 *     an inline list of files."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "folders" = "",
 *   },
 *   weight = 0
 * )
 */
class FilterFileTree extends FilterBase {
  /**
   * Object with configuration for filetree.
   *
   * @var object
   */
  protected $config;

  /**
   * Object with configuration for filetree, where we need editable..
   *
   * @var object
   */
  protected $configEditable;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = \Drupal::config('filetree.settings');
    $this->configEditable = \Drupal::configFactory()
      ->getEditable('filetree.settings');
  }

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cache tags and #post_render_cache callbacks.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
    // Look for our special [filetree] token.
    if (!preg_match_all('/(?:<p>)?\[filetree\s*(.*?)\](?:<\/p>)?/s', $text, $matches)) {
      $result = new FilterProcessResult($text);

      return $result;
    }

    // Setup our default parameters.
    $default_params = [
      'dir' => NULL,
      'multi' => TRUE,
      'controls' => TRUE,
      'extensions' => TRUE,
      'absolute' => TRUE,
      'url' => '',
      'animation' => TRUE,
      'sortorder' => 'asc',
    ];
    $matches2 = [];

    // The token might be present multiple times; loop through each instance.
    foreach ($matches[1] as $key => $passed_params) {
      // Load the defaults.
      $params[$key] = $default_params;

      // Parse the parameters (but only the valid ones).
      preg_match_all('/(\w*)=(?:\"|&quot;)(.*?)(?:\"|&quot;)/', $passed_params, $matches2[$key]);

      foreach ($matches2[$key][1] as $param_key => $param_name) {
        if (in_array($param_name, array_keys($default_params))) {
          // If default param is a boolean, convert the passed param to boolean.
          // Note: "false" (as a string) is considered TRUE by PHP, so there's a
          // special check for it.
          if (is_bool($default_params[$param_name])) {
            $params[$key][$param_name] = $matches2[$key][2][$param_key] == "false" ? FALSE : (bool) $matches2[$key][2][$param_key];
          }
          else {
            $params[$key][$param_name] = $matches2[$key][2][$param_key];
          }
        }
      }

      // Make sure that "dir" was provided,
      if (!$params[$key]['dir']
        // ...it's an allowed path for this input format,
        or !\Drupal::service('path.matcher')
          ->matchPath($params[$key]['dir'], $this->settings['folders'])
        // ...the URI builds okay,
        or !$params[$key]['uri'] = file_build_uri($params[$key]['dir'])
        // ...and it's within the files directory.
        or !file_prepare_directory($params[$key]['uri'])
      ) {
        continue;
      }

      // Render tree.
      $files = filetree_list_files($params[$key]['uri'], $params[$key], $params[$key]['sortorder']);

      $rendered = $this->render($files, $params[0]);

      // Replace token with rendered tree.
      $text = str_replace($matches[0][$key], $rendered, $text);

    }  // end foreach


    // Create the object with result.
    $result = new FilterProcessResult($text);

    // Associate assets to be attached.
    $result->setAttachments([
      'library' => [
        'filetree/filetree',
      ],
    ]);

    return $result;
  }

  /**
   * Get the tips for the filter.
   *
   * @param bool $long
   *   If get the long or short tip.
   *
   * @return string
   *   The tip to show for the user.
   */
  public function tips($long = FALSE) {
    $output = t('You may use [filetree dir="some-directory"] to display a list of files inline.');
    if ($long) {
      $output = '<p>' . $output . '</p>';
      $output .= '<p>' . t('Additional options include "multi", "controls", "extensions", "sortorder", and "absolute"; for example, [filetree dir="some-directory" multi="false" controls="false" extensions="false" sortorder="desc" absolute="false"].') . '</p>';
    }
    return $output;
  }

  /**
   * Render.
   *
   * @param $files
   * @param $params
   * @return string
   */
  protected function render($files, $params) {
    $output = '';

    // Render controls (but only if multiple folders is enabled, and only if
    // there is at least one folder to expand/collapse).
    if ($params['multi'] and $params['controls']) {
      $has_folder = FALSE;
      foreach ($files as $file) {
         if (isset($file['children'])) {
          $has_folder = TRUE;
          break;
        }
      }

      if ($has_folder) {
        $controls = '<ul class="controls"><li><a href="#" class="expand">' . t('expand') . '</a></li>'
          . '<li><a href="#" class="collapse">' . t('collapse') . '</a></li></ul>';
        $output .= $controls;

        $output .= render($render);
      }
    }

    // Render files.
    $render = [
      '#theme' => 'item_list',
      '#items' => $files,
      '#type' => 'ul',
      '#attributes' => ['class' => 'files'],
    ];
    $output .= render($render);

    // Generate classes and unique ID for wrapper div.
    $id = Html::cleanCssIdentifier(uniqid('filetree-'));
    $classes = ['filetree'];
    if ($params['multi']) {
      $classes[] = 'multi';
    }
    // If using animation, add class.
    if ($params['animation']) {
      $classes[] = 'filetree-animation';
    }

    return '<div id="' . $id . '" class="' . implode(' ', $classes) . '">' . $output . '</div>';
  }

  /**
   * Create the settings form for the filter.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of
   *   this filter. The submitted form values should match $this->settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['folders'] = [
      '#type' => 'textarea',
      '#title' => t('Allowed folder paths'),
      '#description' => t('Enter one folder per line as paths which are allowed to be rendered as a list of files (relative to your <a href="@url">file system path</a>). The "*" character is a wildcard. Example paths are "*", "some-folder", and "some-folder/*".', [
        '@url' => URL::fromUri('base:admin/config/media/file-system')->toString(),
      ]),
      '#default_value' => $this->settings['folders'],
    ];

    return $elements;
  }
}
