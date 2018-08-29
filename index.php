<?php

define('BASE_DIR', __DIR__);
define('EXECUTION_START_TIME', microtime(true));

$files = glob('config/{{*/}*,*}*config.php', GLOB_BRACE);
if(is_array($files))
foreach(array_reverse($files) as $file) {include $file;}

require_once "core/core.php";

if(DEBUG_MODE == 1) @\Core\Cache::Clean();

$files = glob('bundles/{{*/}*,*}*.bundle.php', GLOB_BRACE);
if(is_array($files))
foreach(array_reverse($files) as $file) {include $file;}

use Core\Core;

$files = glob('routes/{{*/}*,*}*.php', GLOB_BRACE);
if(is_array($files))
foreach($files as $file) {include $file;}

$files = glob('events/{{*/}*,*}*.event.php', GLOB_BRACE);
if(is_array($files))
foreach($files as $file) {@include $file;}

@$core = new Core();
