<?php

namespace classicframework\migration;

class Migration
{
  protected $config = array();
  protected $database = null;

  public function __construct($config = array(), $database = null)
  {
    $this->config = is_array($config) ? $config : array();
    $this->database = $database;
  }

  public function set_database($database)
  {
    $this->database = $database;
    return $this;
  }

  public function up($database = null)
  {
    if ($database !== null) {
      $this->database = $database;
    }

    $this->ensure_database();
    $this->ensure_migration_table();

    $files = $this->migration_files();
    $done = $this->executed_migrations();

    $result = array(
      'executed' => array(),
      'skipped' => array(),
    );

    foreach ($files as $file) {
      $name = basename($file);

      if (isset($done[$name])) {
        $result['skipped'][] = $name;
        continue;
      }

      $db = $this->database;

      require $file;

      $this->mark_executed($name);

      $result['executed'][] = $name;
    }

    return $result;
  }

  protected function ensure_database()
  {
    if (!is_object($this->database)) {
      throw new \Exception('Migration database service is missing.');
    }

    if (!method_exists($this->database, 'execute')) {
      throw new \Exception('Migration database service must have an execute() method.');
    }
  }

  protected function ensure_migration_table()
  {
    $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->database->table('migrations') . ' (
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
      name varchar(255) NOT NULL,
      executed_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    $this->database->execute($sql);
  }

  protected function migration_files()
  {
    $path = isset($this->config['path']) ? (string) $this->config['path'] : '';

    if ($path === '') {
      throw new \Exception('Migration path is missing.');
    }

    $files = glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php');

    if (!is_array($files)) {
      return array();
    }

    sort($files);

    return $files;
  }

  protected function executed_migrations()
  {
    $rows = $this->database->rows(
      'SELECT name FROM ' . $this->database->table('migrations')
    );

    $done = array();

    foreach ($rows as $row) {
      if (isset($row['name'])) {
        $done[(string) $row['name']] = true;
      }
    }

    return $done;
  }

  protected function mark_executed($name)
  {
    $this->database->insert('migrations', array(
      'name' => (string) $name,
      'executed_at' => date('Y-m-d H:i:s'),
    ));
  }
}