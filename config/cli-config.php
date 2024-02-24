<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once '/var/www/buslab_socketfull/app.php';

return ConsoleRunner::createHelperSet($em);
