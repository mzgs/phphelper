<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class AIChat
{
    private const DEFAULT_CONFIG = [
        'base_uri' => 'https://api.openai.com/v1/',
        'endpoint' => 'chat/completions',
        'model' => 'gpt-4o-mini',
        'api_key' => null,
        'timeout' => 15.0,
        'headers' => [],
        'response_format' => null,
        'client_options' => [],
    ];

    private static ?Client $client = null;

    /**
     * @var array<string, mixed>
     */
    private static array $config = self::DEFAULT_CONFIG;

    /**
     * Configure the AI chat client (base URI, model, key, etc.).
     *
     * @param array<string, mixed> $config
     */
    public static function init(array $config): void
    {
        if (isset($config['headers']) && !is_array($config['headers'])) {
            $config['headers'] = [];
        }

        if (array_key_exists('headers', $config)) {
            self::$config['headers'] = array_merge(self::DEFAULT_CONFIG['headers'], $config['headers']);
            unset($config['headers']);
        }

        if (isset($config['client_options']) && !is_array($config['client_options'])) {
            $config['client_options'] = [];
        }

        if (array_key_exists('client_options', $config)) {
            self::$config['client_options'] = array_merge(self::DEFAULT_CONFIG['client_options'], $config['client_options']);
            unset($config['client_options']);
        }

        self::$config = array_replace(self::$config, $config);
        self::$client = null;
    }

    public static function setApiKey(?string $apiKey): void
    {
        self::$config['api_key'] = $apiKey;
        self::$client = null;
    }

    public static function setClient(?Client $client): void
    {
        self::$client = $client;
    }

    public static function reset(): void
    {
        self::$client = null;
        self::$config = self::DEFAULT_CONFIG;
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function chat(array $messages, array $payload = []): array
    {
        if ($messages === []) {
            throw new InvalidArgumentException('AIChat::chat requires at least one message.');
        }

        $body = array_merge([
            'model' => self::$config['model'],
            'messages' => $messages,
        ], $payload);

        if (!isset($body['response_format']) && is_array(self::$config['response_format'])) {
            $body['response_format'] = self::$config['response_format'];
        }

        try {
            $response = self::client()->post(self::$config['endpoint'], [
                'json' => $body,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('AIChat request failed: ' . $e->getMessage(), 0, $e);
        }

        return self::decodeResponse($response);
    }

    /**
     * Convenience helper that accepts a text prompt and optional context.
     *
     * @param array<int, array<string, mixed>> $contextMessages Preceding conversation turns (system/assistant/etc.)
     * @param array<string, mixed> $payload Additional API parameters (temperature, response_format, ...)
     */
    public static function reply(string $prompt, array $payload = [], array $contextMessages = []): string
    {
        $messages = array_values($contextMessages);
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $data = self::chat($messages, $payload);

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            throw new RuntimeException('AIChat: Missing assistant content in response.');
        }

        return trim($content);
    }

    private static function client(): Client
    {
        if (self::$client instanceof Client) {
            return self::$client;
        }

        $headers = self::$config['headers'];
        if (!is_array($headers)) {
            $headers = [];
        }

        if (!empty(self::$config['api_key'])) {
            $headers = array_merge([
                'Authorization' => 'Bearer ' . self::$config['api_key'],
            ], $headers);
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }

        $options = self::$config['client_options'];
        if (!is_array($options)) {
            $options = [];
        }

        $existingHeaders = $options['headers'] ?? [];
        if (!is_array($existingHeaders)) {
            $existingHeaders = [];
        }

        $options['headers'] = array_merge($headers, $existingHeaders);

        if (!array_key_exists('base_uri', $options)) {
            $options['base_uri'] = self::$config['base_uri'];
        }

        if (!array_key_exists('timeout', $options) && self::$config['timeout'] !== null) {
            $options['timeout'] = self::$config['timeout'];
        }

        self::$client = new Client($options);

        return self::$client;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            throw new RuntimeException('AIChat: Empty response body.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('AIChat: Unable to decode JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
