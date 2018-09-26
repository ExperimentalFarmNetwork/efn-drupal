<?php

namespace Drupal\yamlform_to_webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the YAML Form to Webform migrate manager.
 */
class YamlFormToWebformMigrateManager implements YamlFormToWebformMigrateManagerInterface {

  use StringTranslationTrait;

  /**
   * YAML Form modules that can be migrated.
   *
   * @var array
   */
  protected $yamlformModules = [
    'yamlform',
    'yamlform_devel',
    'yamlform_examples',
    'yamlform_node',
    'yamlform_templates',
    'yamlform_to_webform',
    'yamlform_ui',
    'yamlform_test',
    'yamlform_test_third_party_settings',
    'yamlform_test_translation',
  ];

  /**
   * Table columns that may contain references to YAML Form.
   *
   * @var array
   */
  protected $replaceColumns = [
    'bundle' => 'string',
    'type' => 'string',

    'config.name' => 'string',
    'config.data' => 'serial',

    'file_managed.filename' => 'string',
    'file_managed.uri' => 'string',

    'file_usage.module' => 'string',
    'file_usage.type' => 'string',

    'key_value.collection' => 'string',
    'key_value.name' => 'string',
    'key_value.value' => 'serial',

    'key_value_expire.collection' => 'string',
    'key_value_expire.name' => 'string',
    'key_value_expire.value' => 'serial',

    'menu_tree.menu_name' => 'string',
    'menu_tree.id' => 'string',
    'menu_tree.parent' => 'string',
    'menu_tree.route_name' => 'string',
    'menu_tree.route_parameters' => 'serial',
    'menu_tree.title' => 'serial',
    'menu_tree.description' => 'serial',
    'menu_tree.options' => 'serial',
    'menu_tree.provider' => 'string',

    'menu_link_content_data.title' => 'string',
    'menu_link_content_data.description' => 'string',
    'menu_link_content_data.link_uri' => 'string',
    'menu_link_content_data.link_title' => 'string',
    'menu_link_content_data.link_options' => 'serial',

    'router.name' => 'string',
    'router.path' => 'string',
    'router.pattern_outline' => 'string',
    'router.route' => 'serial',

    'shortcut_field_data.link__uri' => 'string',
    'shortcut_field_data.link__title' => 'string',

    'shortcut_set_users.name' => 'string',

    'url_alias.source' => 'string',

    'user__roles.roles_target_id' => 'string',
  ];

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler to load includes.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new YamlFormEmailProvider.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for loading includes.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->connection = Database::getConnection('default');
  }

  /**
   * {@inheritdoc}
   */
  public function requirements() {
    $requirements = [];

    // Check database.
    $database_type = Database::getConnection('default')->databaseType();
    if ($database_type != 'mysql') {
      $requirements['database'] = $this->t('Database (@type) is not supported. Only sites using MySQL can be migrated.', ['@type' => $database_type]);
    }

    // Check that Webform module exists and is not '8.x-4.x-dev'.
    $files = file_scan_directory('modules/', '/^webform\.info.yml$/');
    $webform_info_file = reset($files);
    if (empty($webform_info_file)) {
      $requirements['webform'] = $this->t('Webform 8.x-5.x module is missing from the/modules directory.');
    }
    else {
      $webform_info = Yaml::decode(file_get_contents($webform_info_file->uri));
      if (isset($webform_info['version']) && $webform_info['version'] === '8.x-4.x-dev') {
        $requirements['webform'] = $this->t('Webform 8.x-5.x is required. Please download Webform 8.x-5.x.');
      }
    }

    // Check that the Webform module is disabled.
    if ($this->moduleHandler->moduleExists('webform')) {
      $requirements['webform'] = $this->t('Webform module must be disabled.');
    }

    // Check that the Yaml Form module is enabled.
    if (!$this->moduleHandler->moduleExists('yamlform')) {
      $requirements['yamlform'] = $this->t('YAML Form module must be enabled.');
    }

    // Check for unsupported YAML Form modules.
    $modules = $this->moduleHandler->getModuleList();
    foreach ($modules as $module_name => $module_info) {
      if (strpos($module_name, 'yamlform') !== FALSE && !in_array($module_name, $this->yamlformModules)) {
        $t_args = [
          '@name' => $module_name,
          '@title' => $module_info->getName(),
        ];
        $requirements["module.$module_name"] = $this->t('The @name module must be uninstalled before performing a migration.', $t_args);
      }
    }

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function migrate() {
    // Store maintenance_mode setting so we can restore it when done.
    $maintenance_mode = \Drupal::state()->get('system.maintenance_mode');

    // Force site into maintenance_mode.
    \Drupal::state()->set('system.maintenance_mode', TRUE);

    // Move uploaded files from 'yamlform' to 'webform' directory.
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    foreach ($stream_wrappers as $uri_schema => $stream_wrapper) {
      if (file_exists("$uri_schema://webform")) {
        file_unmanaged_delete_recursive("$uri_schema://webform");
      }
      if (file_exists("$uri_schema://yamlform")) {
        file_unmanaged_move("$uri_schema://yamlform", "$uri_schema://webform", FILE_EXISTS_REPLACE);
      }
    }

    // Manually fix the YAML Form node module's configuration.
    if (\Drupal::moduleHandler()->moduleExists('yamlform_node')) {
      $this->configFactory->getEditable('node.type.yamlform')
        ->set('name', 'Webform')
        ->set('type', 'webform')
        ->set('description', 'A basic page with a webform attached.')
        ->save();
      $this->configFactory->getEditable('field.field.node.yamlform.yamlform')
        ->set('label', 'Webform')
        ->save();
    }

    // Manually uninstall the yamlform_to_webform.module.
    $config = $this->configFactory->getEditable('core.extension');
    $config->clear('module.yamlform_to_webform');
    $config->save();
    $this->connection->query("DELETE FROM {key_value} WHERE name='yamlform_to_webform'");

    // Set webform module schema to 8006.
    $this->connection->query("UPDATE {key_value} SET value=:value WHERE collection = 'system.schema' AND name='yamlform'", [':value' => 'i:8006;']);

    // Reset webform sub module schemas to 8000.
    $this->connection->query("UPDATE {key_value} SET value=:value WHERE collection = 'system.schema' AND name LIKE 'yamlform_%'", [':value' => 'i:8000;']);

    // Rename database tables, indexes, and columns.
    $messages = [];
    $tables = $this->getTables();
    foreach ($tables as $table_name) {
      $messages += $this->renameColumns($table_name);
      $messages += $this->renameIndexes($table_name);
      $messages += $this->renameTable($table_name);
    }
    ksort($messages);

    // Restore maintenance_mode state.
    \Drupal::state()->set('system.maintenance_mode', $maintenance_mode);

    return $messages;
  }

  /****************************************************************************/
  // Database.
  /****************************************************************************/

  /**
   * Rename a table.
   *
   * @param string $table_name
   *   The table name.
   *
   * @return array
   *   An associative array containing status messages.
   */
  protected function renameTable($table_name) {
    if (strpos($table_name, 'yamlform') === FALSE && strpos($table_name, 'yaml_form') === FALSE) {
      return [];
    }
    $new_table_name = str_replace(
      ['yamlform', 'yaml_form'],
      ['webform', 'webform'],
      $table_name
    );
    $t_args = [
      '@source' => $table_name,
      '@destination' => $new_table_name,
    ];
    $this->connection->query("RENAME TABLE $table_name TO $new_table_name");
    return ["$table_name" => $this->t("Renamed '@source' to '@destination'", $t_args)];
  }

  /**
   * Rename a table's indexes.
   *
   * @param string $table_name
   *   The table name.
   *
   * @return array
   *   An associative array containing status messages.
   */
  protected function renameIndexes($table_name) {
    $messages = [];
    $indexes = $this->getIndexes($table_name);
    foreach ($indexes as $index_name => $index_columns) {
      $new_index_name = str_replace(
        ['yamlform', 'yaml_form'],
        ['webform', 'webform'],
        $index_name
      );
      $t_args = [
        '@source' => "$table_name.$index_name",
        '@destination' => "$table_name.$new_index_name ",
      ];

      $column_list = implode(',', $index_columns);

      // Execute MySQL specific ALTER TABLE commands.
      $this->connection->query("ALTER TABLE $table_name DROP INDEX $index_name");
      $this->connection->query("ALTER TABLE $table_name ADD INDEX $new_index_name ($column_list)");

      $messages["$table_name.$index_name"] = $this->t("Renamed '@source' to '@destination'", $t_args);
    }
    ksort($messages);
    return $messages;
  }

  /**
   * Rename a table's columns.
   *
   * @param string $table_name
   *   The table name.
   *
   * @return array
   *   An associative array containing status messages.
   */
  protected function renameColumns($table_name) {
    $messages = [];
    $columns = $this->getColumns($table_name);
    foreach ($columns as $column_name => $column) {
      $new_column_name = str_replace(
        ['yamlform', 'yaml_form'],
        ['webform', 'webform'],
        $column_name
      );
      $t_args = [
        '@source' => "$table_name.$column_name",
        '@destination' => "$table_name.$new_column_name",
      ];

      // Replace values.
      $replace_type = FALSE;
      if (isset($this->replaceColumns[$column_name])) {
        $replace_type = $this->replaceColumns[$column_name];
      }
      elseif (isset($this->replaceColumns["$table_name.$column_name"])) {
        $replace_type = $this->replaceColumns["$table_name.$column_name"];
      }
      if ($replace_type) {
        switch ($replace_type) {
          case 'serial':
            $result = $this->connection->query("SELECT $column_name FROM $table_name WHERE $column_name LIKE '%yaml%' OR $column_name LIKE '%Yaml%' OR $column_name LIKE '%YAML%'");
            while ($record = $result->fetchAssoc()) {
              $value = $record[$column_name];
              $new_value = $value;
              if (preg_match_all('/s:\d+:"[^"]*?(yamlform|yaml form|yaml_form)[^"]*?";/i', $value, $matches)) {
                foreach ($matches[0] as $match) {
                  $string = unserialize($match);
                  $new_string = $this->replace($string);
                  $new_value = str_replace($match, serialize($new_string), $new_value);
                }
                $params = [
                  ':value' => $value,
                  ':new_value' => $new_value,
                ];
                $this->connection->query("UPDATE $table_name SET $column_name=:new_value WHERE $column_name=:value", $params);
              }
            }
            break;

          default:
            $this->connection->query("UPDATE $table_name SET $column_name = REPLACE($column_name, 'yamlform', 'webform')");
            $this->connection->query("UPDATE $table_name SET $column_name = REPLACE($column_name, 'yaml_form', 'webform')");
            break;
        }
        $messages["$table_name.$column_name.value"] = $this->t("Changed 'yamlform' to 'webform in '@destination' (@type)", ['@type' => $replace_type] + $t_args);
      }

      // Rename column.
      if ($column_name !== $new_column_name) {
        $column_type = $column['type'];
        // Execute MySQL specific ALTER TABLE commands.
        $this->connection->query("ALTER TABLE $table_name CHANGE {$column_name} {$new_column_name} $column_type");
        $messages["$table_name.$column_name"] = $this->t("Renamed '@source' to '@destination'", $t_args);
      }
    }
    return $messages;
  }

  /**
   * Get table names.
   *
   * @return array
   *   An array containing database table name.
   */
  protected function getTables() {
    $tables = $this->connection->schema()->findTables('%%');
    $prefixed_tables = [];
    foreach ($tables as $index => $table_name) {
      if (!preg_match('/^test\d+/', $table_name)) {
        $prefixed_table_name = $this->connection->tablePrefix($table_name) . $table_name;
        $prefixed_tables[$table_name] = $prefixed_table_name;
      }
    }

    // Map replace columns to prefixed table names.
    foreach ($this->replaceColumns as $replace_column => $data_type) {
      if (strpos($replace_column, '.') !== FALSE) {
        list($table_name, $column_name) = explode('.', $replace_column);
        if (isset($prefixed_tables[$table_name])) {
          $prefixed_table_name = $prefixed_tables[$table_name];
          unset($this->replaceColumns[$replace_column]);
          $this->replaceColumns["$prefixed_table_name.$column_name"] = $data_type;
        }
      }
    }

    return array_values($prefixed_tables);
  }

  /**
   * Get a table's columns.
   *
   * @param string $table_name
   *   The table name.
   *
   * @return array
   *   An associative array containing a table's columns.
   */
  protected function getColumns($table_name) {
    $columns = [];
    // Execute MySQL specific SHOW COLUMNS commands.
    $table_columns = $this->connection->query("SHOW FULL COLUMNS FROM $table_name")->fetchAllAssoc('Field');
    foreach ($table_columns as $table_column_name => $table_column) {
      $columns[$table_column_name] = [
        'type' => $table_column->Type,
      ];
    }
    return $columns;
  }

  /**
   * Get a table's indexes.
   *
   * @param string $table_name
   *   The table name.
   *
   * @return array
   *   An associative array containing a table's indexes.
   */
  protected function getIndexes($table_name) {
    $indexes = [];
    // Execute MySQL specific SHOW INDEXES commands.
    $table_indexes = $this->connection->query("SHOW INDEXES FROM $table_name WHERE key_name LIKE '%yamlform%'")->fetchAllAssoc('Key_name');
    foreach ($table_indexes as $table_index_name => $table_index) {
      $indexes[$table_index_name][$table_index->Seq_in_index] = $table_index->Column_name;
    }
    return $indexes;
  }

  /**
   * Search-n-replace YAML form variations in string with the Webform namespace.
   *
   * @param string $string
   *   A string.
   *
   * @return string
   *   The string with all variations of YAML form replace with the Webform
   *   namespace.
   */
  protected function replace($string) {
    $string = str_replace(
      ['yamlform', 'YamlForm', 'yaml form', 'yaml_form', 'YAML Form'],
      ['webform', 'Webform', 'webform', 'webform', 'Webform'],
      $string
    );
    $string = str_ireplace(
      ['yaml form'],
      ['Webform'],
      $string
    );
    return $string;
  }

}
