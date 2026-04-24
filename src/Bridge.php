<?php

namespace classicframework\migration;

use classicframework\core\App;
use classicframework\core\Config;
use classicframework\core\BridgeInterface;

class Bridge implements BridgeInterface
{
  public static function register(App $app)
  {
    $config = Config::extract('migration');

    $database = $app->get_service('db');

    if ($database === null) {
      $database = $app->get_service('database');
    }

    $migration = new Migration($config, $database);

    $app->set_service('migration', $migration);
  }
}