<?php

namespace losthost\SimpleAI\Test;

use PHPUnit\Framework\TestCase;
use losthost\SimpleAI\SimpleAIAgent;
use losthost\SimpleAI\data\DBContext;
use losthost\DB\DB;

class SimpleAIAgentTest extends TestCase
{
    protected static $api_key;

    public static function setUpBeforeClass(): void
    {
        // Настройка API ключа (загрузи из config.php)
        self::$api_key = DEEPSEEK_API_KEY ?: 'test_key';
        
        // Подключение к БД
        DB::connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PREF);
        
    }

    public function testBuildMethod()
    {
        $agent = SimpleAIAgent::build(self::$api_key);
        $this->assertInstanceOf(SimpleAIAgent::class, $agent);
    }

    public function testSimpleQuery()
    {
        $agent = SimpleAIAgent::build(self::$api_key)
            ->setTimeout(60);
        
        $response = $agent->ask('Скажи коротко "Тест пройден"')->asString();
        $this->assertStringContainsString('Тест', $response);
    }

    public function testMaxTokensLimit()
    {
        $agent = SimpleAIAgent::build(self::$api_key)
            ->setTimeout(60)
            ->setUserId(100)
            ->setDialogId(2)
            ->setMaxTokens(100); // Устанавливаем маленький лимит

        // Запрашиваем что-то длинное
        //$response = $agent->ask('Напиши подробное эссе о философии искусственного интеллекта, сравнивая подходы Тьюринга, Шеннона и современных исследователей машинного обучения. Обязательно включи анализ этических аспектов и будущих перспектив развития ИИ.')->asString();
        $response = $agent->ask('Сколько существует часовых поясов?')->asString();
        
        error_log("ANSWER 1: $response");
        // Проверяем, что ответ не слишком длинный
//        $this->assertLessThan(500, strlen($response), 'Ответ должен быть ограничен 100 токенами');

        $response2 = $agent->ask('...')->asString();
        error_log("ANSWER 2: $response2");
        // Или проверяем finish_reason если он доступен
        // $this->assertStringContainsString('length', $response->finish_reason ?? '');
    }
    
    public function testPrompt()
    {
        $agent = SimpleAIAgent::build(self::$api_key)
            ->setPrompt('Локальный')
            ->setTimeout(60);
        
        $this->assertEquals('Локальный', $agent->getPrompt());
    }

    public function testTemperatureConfiguration()
    {
        $agent = SimpleAIAgent::build(self::$api_key);

        $this->assertEquals(1, $agent->getTemperature());
        $agent->setTemperature(0.5);
        $this->assertEquals(0.5, $agent->getTemperature());
    }

    public function testErrorHandlingWithString()
    {
        $agent = SimpleAIAgent::build('invalid_key')
            ->setTimeout(5);
        
        $result = $agent->ask('test', 'Ошибка перехвачена')->asString();
        $this->assertEquals('Ошибка перехвачена', $result);
    }

    public function testErrorHandlingWithCallback()
    {
        $agent = SimpleAIAgent::build('invalid_key')
            ->setTimeout(5);
        
        $result = $agent->ask('test', function($ex) {
            return "Callback: " . get_class($ex);
        })->asString();
        
        $this->assertStringContainsString('Callback:', $result);
    }

    public function testFluentInterface()
    {
        $agent = SimpleAIAgent::build(self::$api_key)
            ->setUserId('user123')
            ->setDialogId('dialog1')
            ->setTemperature(0.7)
            ->setTimeout(30);
        
        $this->assertInstanceOf(SimpleAIAgent::class, $agent);
    }

    public function testDefaultValues()
    {
        $agent = SimpleAIAgent::build(self::$api_key);
        
        $this->assertEquals(SimpleAIAgent::DEFAULT_PROMPT, $agent->getPrompt());
        $this->assertEquals(SimpleAIAgent::DEFAULT_TEMPERATURE, $agent->getTemperature());
    }

    public function testContextPersistence()
    {
        
        $user_id = 'test_user_' . uniqid();
        $dialog_id = 'test_dialog_' . uniqid();
        
        $agent = SimpleAIAgent::build(self::$api_key)
            ->setUserId($user_id)
            ->setDialogId($dialog_id)
            ->setPrompt('Отвечай на вопросы коротко без дополнительных комментариев')
            ->setTimeout(60);
        
        // Первый запрос
        $response1 = $agent->ask('Столица Англии')->asString();
        
        // Второй запрос - должен помнить контекст
        $response2 = $agent->ask('Повтори ответ')->asString();
        
        $this->assertIsString($response1);
        $this->assertIsString($response2);
        $this->assertEquals($response2, $response1);
        
    }
    
    protected function tearDown(): void {
        try {
            DB::query('TRUNCATE TABLE [sai_context]');
        } catch (\Throwable $e) {
            ///
        }
    }
}
