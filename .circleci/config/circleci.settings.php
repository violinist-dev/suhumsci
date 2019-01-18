<?php

/**
 * @file
 * Database credentials and any additional settings for circle.ci.
 */

$databases['default']['default'] = array(
  'database' => 'drupal8',
  'username' => 'root',
  'password' => '',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
