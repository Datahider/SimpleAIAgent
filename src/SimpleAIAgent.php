<?php

namespace losthost\SimpleAI;

use DeepSeek\DeepSeekClient;
use losthost\SimpleAI\data\Context;
use losthost\DB\DBValue;
use losthost\DB\DBView;

class SimpleAIAgent {

    protected string $deepseek_api_key;
    
    protected string $user_id;
    protected string $agent_name;
    protected string $dialog_id;

    protected bool $logging;
    protected int $timeout;
    
    static protected array $prompt_for_agent = [];

    public function __construct(string $deepseek_api_key) {
        $this->deepseek_api_key = $deepseek_api_key;
        $this->logging = false;
        $this->timeout = 30;
        $this->dialog_id = '';
        $this->agent_name = '';
        
    }
    
    static public function build(string $deepseek_api_key) : static {
        return new static($deepseek_api_key);
    }

    public function setPrompt(string $prompt) : static {
        if (!isset($this->agent_name)) {
            $this->throw('Agent name is not set', __FILE__, __LINE__);
        }
        
        static::$prompt_for_agent[$this->agent_name] = $prompt;
        $this->log("Prompt for $this->agent_name is set");
        return $this;
    }
    
    public function getPrompt() : string {
        if (!isset($this->agent_name)) {
            $this->throw('Agent name is not set', __FILE__, __LINE__);
        }
        
        return static::$prompt_for_agent[$this->agent_name] ?? '';
    }
    
    public function setUserId(string $user_id) : static {
        $this->user_id = $user_id;
        $this->log("User id is set to \"$this->user_id\"");
        return $this;
    }
    
    public function setAgentName(string $agent_name) : static {
        $this->agent_name = $agent_name;
        $this->log("Agent name is set to \"$this->agent_name\"");
        return $this;
    }

    public function setDialogId(string $dialog_id) : static {
        $this->dialog_id = $dialog_id;
        $this->log("Dialog id is set to \"$this->dialog_id\"");
        return $this;
    }

    public function ask(?string $query=null, bool|string|callable $handle_errors=false) {
    
        try {
            return $this->dispatchQuery($query);
        } catch (\Throwable $ex) {
            if ($handle_errors === false) {
                throw $ex;
            } elseif ($handle_errors === true) {
                return $ex->getMessage();
            } elseif (is_callable($handle_errors)) {
                $answer = $handle_errors($ex);
                return $answer;
            } elseif (is_string($handle_errors)) {
                return $handle_errors;
            }
        }
    }

    protected function dispatchQuery(?string $query) : string {
        if (empty($this->user_id)) {
            return $this->simpleQuery($query);
        } else {
            return $this->contextQuery($query);
        }
    }
    
    protected function simpleQuery(string $query) {
        $response = DeepSeekClient::build(apiKey: $this->deepseek_api_key, timeout: $this->timeout)
                ->query($query)
                ->run();
        return $this->getResponseContent($response);
    }
    
    protected function contextQuery(?string $query) {
        
        $agent = DeepSeekClient::build(apiKey: $this->deepseek_api_key, timeout: $this->timeout);
        $context = $this->getContext($query);
        
        foreach ($context as $context_item) {
            $agent->query($context_item['content'], $context_item['role']);
        }
        
        $answer = $this->getResponseContent($agent->run());
        
        $this->storeAnswer($answer);
        return $answer;
    }
    
    protected function storeAnswer(string $answer) : void {
        Context::add($this->user_id, $this->agent_name, $this->dialog_id, 'assistant', $answer);
    }
    
    protected function getContext($query) {
        
        if (!$this->hasContext()) {
            $this->makeContext();
        } 
        
        if ($query) {
            Context::add($this->user_id, $this->agent_name, $this->dialog_id, 'user', $query);
        } // Пустой ввод не добавляем в контекст
        
        $context_view = new DBView(<<<FIN
                SELECT role, content 
                FROM [sai_context] 
                WHERE user_id = ? AND agent_name = ? AND dialog_id = ? 
                ORDER BY id
                FIN, [$this->user_id, $this->agent_name, $this->dialog_id]);
        
        $context = [];
        while ($context_view->next()) {
            $context[] = ['role' => $context_view->role, 'content' => $context_view->content];
        }
        
        return $context;
    }
    
    protected function hasContext() {
        Context::initDataStructure();
        $context = new DBValue(<<<FIN
                SELECT COUNT(*) AS messages 
                FROM [sai_context] 
                WHERE user_id = ? AND agent_name = ? AND dialog_id = ? 
                FIN, [$this->user_id, $this->agent_name, $this->dialog_id]);

        return (bool)$context->messages;
    }
    
    protected function makeContext() {
        $prompt = $this->getPrompt();
        if (!empty($prompt)) {
            Context::add($this->user_id, $this->agent_name, $this->dialog_id, 'system', $prompt);
        }
    }
    
    protected function getResponseContent(string $response_json) : string {
        if (!$response_json) {
            $this->throw('Empty response from DeepSeek', __FILE__, __LINE__);
        } 
        
        $response = json_decode($response_json);
        if (!$response) {
            $this->throw($response_json, __FILE__, __LINE__);
        }
        
        if (empty($response->choices)) {
            $this->log($response);
            $this->throw("No choices in DeepSeek's response", __FILE__, __LINE__);
        }
        
        return $response->choices[0]->message->content;
    }
    
    protected function throw(string $exception_message, string $file_name, int $line_number) {
        throw new \Exception("$exception_message in $file_name ($line_number).");
    }
    
    public function setTimeout(int $timeout) : static {
        $this->timeout = $timeout;
        return $this;
    }
    public function setLogging(bool $enable) : static {
        if (!$enable) {
            $this->log("Logging disabled");
        }
        $this->logging = $enable;
        $this->log("Logging enabled");
        return $this;
    }
    
    protected function log(mixed $what_to_log, ?string $file_name=null, ?int $line_number=null) {
        if (!$this->logging) {
            return;
        }
        
        if (is_string($what_to_log)) {
            $log_message = "$what_to_log";
            if ($file_name) {
                $log_message .= " in $file_name";
            }
            if ($line_number) {
                $log_message .= " ($line_number)";
            }
        } else {
            $log_message = print_r($what_to_log, true);
            if ($file_name) {
                $log_message .= "\n in $file_name";
            }
            if ($line_number) {
                $log_message .= " ($line_number)";
            }
        }
        
        $m = [];
        preg_match("/(\w+)$/", static::class, $m);
        error_log($m[1]. ": ". $log_message);
    }
}
