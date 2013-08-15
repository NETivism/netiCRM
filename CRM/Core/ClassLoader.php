<?php


class CRM_Core_ClassLoader {

    /**
     * Registers this instance as an autoloader.
     *
     * @param Boolean $prepend Whether to prepend the autoloader or not
     *
     * @api
     */
    function register($prepend = false) {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            spl_autoload_register(array($this, 'loadClass'), true, $prepend);
        }
        else {
            // http://www.php.net/manual/de/function.spl-autoload-register.php#107362
            // "when specifying the third parameter (prepend), the function will fail badly in PHP 5.2"
            spl_autoload_register(array($this, 'loadClass'), true);
        }
    }

    function loadClass($class) {
        if (
            // Only load classes that clearly belong to CiviCRM.
            //0 === strncmp($class, 'CRM_', 4) &&
            // Do not load PHP 5.3 namespaced classes.
            // (in a future version, maybe)
            FALSE === strpos($class, '\\')
        ) {
            $file = strtr($class, '_', '/') . '.php';
            $include_paths = explode(PATH_SEPARATOR, get_include_path());
            foreach ($include_paths as $base_dir)
            {
                $absolute_path = implode(DIRECTORY_SEPARATOR, array($base_dir, $file));
                if (file_exists($absolute_path))
                {
                    require $file;
                    return;
                }
            }
        }
    }
}
