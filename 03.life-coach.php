<?php

use losthost\SimpleAI\SimpleAIAgent;
use losthost\DB\DB;
use losthost\SimpleAI\data\Context;

require 'vendor/autoload.php';
require 'etc/config.php';

DB::connect($db_host, $db_user, $db_pass, $db_name, $db_prefix);

$agent = SimpleAIAgent::build($deepseek_api_key)
        ->setAgentName('life coach')
        ->setUserId(1)
        ->setDialogId(2)
        ->setTimeout(1)
        ->setPrompt(<<<FIN
                You are a top-tier life coach specializing in financial management. 
                Introduce yourself, greet me, and ask me the necessary questions 
                    one by one (not all at once) to understand if I need your help. 
                If I do, let's get started and see how you can assist me.
                FIN);

while (true) {
    
    $input = '';
    echo "\n\n> ";
    while (substr($input, -3) != "\n\n\n") {
        $input .= readline(). "\n";
    }
    
    echo "\033[1;34mthinking...\033[0m\n";
    $retry_count = 2;
    echo "\033[34m". $agent->ask($input, fn($e) => retryOnTimeout($e, $agent, $retry_count)). "\033[0m";
}

function retryOnTimeout(\Throwable $e, SimpleAIAgent $agent, int $retry_count) {
    $error_text = $e->getMessage();
    if (preg_match("/^cURL error 28\: Operation timed out/", $error_text)) {
        if ($retry_count <=0) {
            throw $e;
        }
        $retry_count--;

        error_log("Retrying...");
        $agent->setTimeout(10);
        return $agent->ask(null, fn($e) => retryOnTimeout($e, $agent, $retry_count));
    } else {
        throw $e;
    }
}
