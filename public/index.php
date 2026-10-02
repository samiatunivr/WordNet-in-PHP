<?php
declare(strict_types=1);

// Single entry point. All application code lives in ../codebase (outside the web root).
require dirname(__DIR__) . '/codebase/bootstrap.php';

Asl\App::run();
