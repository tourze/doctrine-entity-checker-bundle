<?php

$autoloadFile = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    // 尝试使用 composer 安装的 autoload
    $autoloadFile = __DIR__ . '/../vendor/autoload.php';
}

require_once $autoloadFile;
