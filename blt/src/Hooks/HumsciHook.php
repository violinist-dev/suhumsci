<?php

namespace Acquia\Blt\Custom\Hooks;

use Acquia\Blt\Robo\BltTasks;
use Consolidation\AnnotatedCommand\CommandData;

/**
 * This class defines example hooks.
 */
class HumsciHook extends BltTasks {

  /**
   * Log in when drupal:sync finishes.
   *
   * @hook post-command drupal:sync
   */
  public function postDrupalSync($result, CommandData $commandData) {
    $this->taskDrush()->drush('uli')->run();
  }

  /**
   * Toggle modules first
   *
   * @hook pre-command drupal:config:import
   */
  public function preConfigImport() {
    $this->invokeCommand('drupal:toggle:modules');
  }

  /**
   * Import any missing entity form/display configs since they are ignored.
   *
   * @hook post-command drupal:config:import
   */
  public function postConfigImport() {
    $this->yell('Importing new form and display configuration items that don\'t exist in the database because they are ignored in config.ignore');
    $result = $this->taskDrush()->drush('config-missing-report')->args([
      'type',
      'system.all',
    ])->option('format', 'string')->run();
    $configs = array_filter(explode("\n", $result->getMessage()));

    // Since we ignore all the entity form and entity display configs, drush cim
    // does not import any new ones. So here we are importing any of those
    // missing configs if they are new.
    foreach ($configs as $item) {
      $name = $item['item'];
      if (strpos($name, 'core.entity_') !== FALSE) {
        $this->taskDrush()->drush('config:import-missing')->arg($name)->run();
      }
    }
  }

  /**
   * Generate a new CSR after multisite.
   *
   * @hook post-command recipes:multisite:init
   */
  public function postMultisite() {
    $this->invokeCommand('humsci:csr');
  }
}