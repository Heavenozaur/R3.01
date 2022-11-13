<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use HltvApi\Client;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};


