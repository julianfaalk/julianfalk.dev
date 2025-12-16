<?php
// Deploy webhook - GitHub calls this, it pulls the code
chdir(__DIR__);
echo shell_exec('git pull 2>&1');
