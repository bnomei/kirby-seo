<?php

declare(strict_types=1);

use Kirby\Cms\App;

const KIRBY_HELPER_DUMP = false;
const KIRBY_HELPER_E = false;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/index.php';

App::$enableWhoops = false;
