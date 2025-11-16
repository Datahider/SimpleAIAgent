<?php

use losthost\SimpleAI\SimpleAIAgent;

require 'vendor/autoload.php';
require 'etc/config.php'; // set $deepseek_api_key

echo SimpleAIAgent::build($deepseek_api_key)
        -> ask('Проверка работы DeepSeek API. Если работает, скажи "Работает". Больше никаких комментариев.');
        