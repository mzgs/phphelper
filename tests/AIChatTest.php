<?php
declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AIChat.php';

final class AIChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AIChat::reset();
    }

    protected function tearDown(): void
    {
        AIChat::reset();
        parent::tearDown();
    }

    public function testReplyReturnsAssistantContent(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'choices' => [
                    ['message' => ['content' => " Hello! \n"], 'finish_reason' => 'stop'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        AIChat::init([
            'client_options' => [
                'handler' => HandlerStack::create($mock),
                'base_uri' => 'https://example.test/',
            ],
        ]);

        $reply = AIChat::reply('Say hello');

        $this->assertSame('Hello!', $reply);
    }

    public function testReplyAppendsPromptToContext(): void
    {
        $capturedBody = null;

        $history = Middleware::tap(function ($request) use (&$capturedBody): void {
            $capturedBody = (string) $request->getBody();
        });

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'choices' => [
                    ['message' => ['content' => 'ok']],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        AIChat::init([
            'client_options' => [
                'handler' => $stack,
                'base_uri' => 'https://example.com/',
            ],
        ]);

        AIChat::reply('User question', [], [
            ['role' => 'system', 'content' => 'You are helpful'],
        ]);

        $this->assertNotNull($capturedBody);

        $payload = json_decode((string) $capturedBody, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $payload['messages']);
        $this->assertSame('system', $payload['messages'][0]['role']);
        $this->assertSame('You are helpful', $payload['messages'][0]['content']);
        $this->assertSame('user', $payload['messages'][1]['role']);
        $this->assertSame('User question', $payload['messages'][1]['content']);
    }

    public function testChatSendsPayloadWithAuthorizationHeader(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'choices' => [
                    ['message' => ['content' => 'ok']],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        AIChat::init([
            'api_key' => 'test-key',
            'endpoint' => 'chat',
            'model' => 'test-model',
            'client_options' => [
                'handler' => $stack,
                'base_uri' => 'https://example.com/',
            ],
        ]);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helper'],
            ['role' => 'user', 'content' => 'Ping'],
        ];

        $result = AIChat::chat($messages, ['temperature' => 0.2]);

        $this->assertSame('ok', $result['choices'][0]['message']['content']);
        $this->assertCount(1, $container);

        $request = $container[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('chat', ltrim($request->getUri()->getPath(), '/'));
        $this->assertSame('Bearer test-key', $request->getHeaderLine('Authorization'));

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('test-model', $body['model']);
        $this->assertSame($messages, $body['messages']);
        $this->assertSame(0.2, $body['temperature']);
    }

}
