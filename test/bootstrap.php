<?php

function defineFromEnv($name) {
    if (false !== getenv($name)) {
        define($name, getenv($name));
    }
}

// disable xdebug backtrace
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

defineFromEnv('WP_PLUGIN_DIR');
defineFromEnv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');

if (false !== getenv('WP_DEVELOP_DIR')) {
    require getenv('WP_DEVELOP_DIR') . '/tests/phpunit/includes/bootstrap.php';
}
