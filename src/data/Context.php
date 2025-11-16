<?php

namespace losthost\SimpleAI\data;

use losthost\DB\DBObject;
use losthost\DB\DB;

class Context extends DBObject {
    const METADATA = [
        'id' => 'BIGINT NOT NULL AUTO_INCREMENT',
        'user_id' => 'VARCHAR(50) NOT NULL',
        'agent_name' => 'VARCHAR(50) NOT NULL',
        'dialog_id' => 'VARCHAR(50) NOT NULL',
        'role' => 'ENUM("system", "user", "assistant")',
        'content' => 'TEXT',
        'date_time' => 'DATETIME NOT NULL',
        'PRIMARY KEY' => 'id',
        'INDEX USER_AGENT_DIALOG' => ['user_id', 'agent_name', 'dialog_id']
    ];
    
    public static function tableName() {
        return DB::$prefix. 'sai_context';
    }
    
    static public function add(string $user_id, string $agent_name, string $dialog_id, string $role, string $content) : static {
        
        $me = new Context();
        $me->user_id = $user_id;
        $me->agent_name = $agent_name;
        $me->dialog_id = $dialog_id;
        $me->role = $role;
        $me->content = $content;
        $me->date_time = date_create();
        $me->write();
        
        return $me;
    }
}
