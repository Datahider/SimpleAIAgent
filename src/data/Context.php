<?php

namespace losthost\SimpleAI\data;

use losthost\DB\DBObject;
use losthost\DB\DB;

class Context extends DBObject {
    const METADATA = [
        'id' => 'BIGINT NOT NULL AUTO_INCREMENT',
        'user_id' => 'VARCHAR(50) NOT NULL',
        'dialog_id' => 'VARCHAR(50) NOT NULL',
        'role' => 'ENUM("system", "user", "assistant")',
        'content' => 'TEXT',
        'date_time' => 'DATETIME NOT NULL',
        'PRIMARY KEY' => 'id',
        'INDEX USER_DIALOG' => ['user_id', 'dialog_id']
    ];
    
    public static function tableName() {
        return DB::$prefix. 'sai_context';
    }
    
    static public function add(string $user_id, string $dialog_id, string $role, string $content) : static {
        
        $me = new Context();
        $me->user_id = $user_id;
        $me->dialog_id = $dialog_id;
        $me->role = $role;
        $me->content = $content;
        $me->date_time = date_create();
        $me->write();
        
        return $me;
    }
}
