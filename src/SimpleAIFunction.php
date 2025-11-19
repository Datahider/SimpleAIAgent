<?php

namespace losthost\SimpleAI;

class SimpleAIFunction {
    
    public static function getName(): string 
    {
        if (static::class !== self::class) {
            throw new \BadMethodCallException(
                static::class. ' must override '. __FUNCTION__
            );
        }
        
        return 'base_function';
    }
    
    public static function getDescription(): string 
    {
        if (static::class !== self::class) {
            throw new \BadMethodCallException(
                static::class. ' must override '. __FUNCTION__
            );
        }
        
        return 'Базовая функция для тестирования и примера';
    }
    
    public static function getSchema(): array {

        if (static::class !== self::class) {
            throw new \BadMethodCallException(
                static::class. ' must override '. __FUNCTION__
            );
        }
        
        return [
            'type' => 'object',
            'properties' => [
                'test_param' => [
                    'type' => 'string',
                    'description' => 'Тестовый параметр'
                ]
            ]
        ];
    }
    
    public static function execute(array $params): string {
        
        if (static::class !== self::class) {
            throw new \BadMethodCallException(
                static::class. ' must override '. __FUNCTION__
            );
        }
        
        return 'Вызывана тестовая функция с параметром: ' . ($params['test_param'] ?? '');
    }
}
