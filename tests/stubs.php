<?php

declare(strict_types=1);

/**
 * Namespace-level stubs for the MyAdmin core helpers that Plugin.php calls.
 *
 * Why these have to live in the Detain\MyAdminZoneMTAMail namespace rather than
 * the global one, alongside the other stubs in tests/bootstrap.php:
 *
 * detain/myadmin-plugin-installer is a hard dependency of this package and
 * composer autoloads its src/modules.php and src/function_requirements.php as
 * `files`, i.e. before any test code runs. Those files define the *real* global
 * get_service_define(), get_module_settings(), get_module_db() and
 * function_requirements(). Every global stub in bootstrap.php is guarded with
 * `if (!function_exists(...))`, so for these four the guard is always false and
 * the stub silently never takes effect. The real get_service_define() is a
 * one-line delegate to \MyAdmin\App::getServiceDefine(), and there is no MyAdmin
 * core in this package's vendor tree, so it died with
 * `Class "MyAdmin\App" not found`.
 *
 * src/Plugin.php calls all of these unqualified, so PHP resolves them in
 * Detain\MyAdminZoneMTAMail first and only then falls back to the global scope.
 * Defining them here shadows the plugin-installer versions for the plugin's own
 * call sites without touching anything global.
 */

namespace Detain\MyAdminZoneMTAMail {
    if (!function_exists('Detain\\MyAdminZoneMTAMail\\get_service_define')) {
        /**
         * @param string $name
         * @return int
         */
        function get_service_define(string $name): int
        {
            $defines = [
                'MAIL_ZONEMTA' => 100,
            ];

            return $defines[$name] ?? 0;
        }
    }

    if (!function_exists('Detain\\MyAdminZoneMTAMail\\get_module_settings')) {
        /**
         * @param string $module
         * @return array<string, string>
         */
        function get_module_settings(string $module): array
        {
            return ['PREFIX' => 'mail', 'TABLE' => 'mail', 'TBLNAME' => 'Mail'];
        }
    }

    if (!function_exists('Detain\\MyAdminZoneMTAMail\\function_requirements')) {
        /**
         * @param string $function
         * @return void
         */
        function function_requirements(string $function): void
        {
        }
    }

    if (!function_exists('Detain\\MyAdminZoneMTAMail\\get_module_db')) {
        /**
         * @param string $module
         * @return object
         */
        function get_module_db(string $module): object
        {
            return new class {
                /** @var array<int, string> */
                public array $queries = [];

                public function query($sql = '', $line = '', $file = '')
                {
                    $this->queries[] = $sql;
                }

                public function next_record($type = null)
                {
                    return false;
                }

                public function num_rows()
                {
                    return 0;
                }

                public function f($n)
                {
                    return 0;
                }

                public function getLastInsertId($table = '', $field = '')
                {
                    return 1;
                }

                public function real_escape($str)
                {
                    return addslashes((string) $str);
                }
            };
        }
    }
}
