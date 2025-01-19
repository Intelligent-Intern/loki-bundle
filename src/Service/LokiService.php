<?php

namespace IntelligentIntern\LokiBundle\Service;

use App\Interface\LogServiceInterface;
use App\Service\VaultService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LokiService extends AbstractProcessingHandler implements LogServiceInterface
{
    private Client $client;
    private string $lokiUrl;
    private ?string $token;

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function __construct(VaultService $vaultService)
    {
        parent::__construct($this->getLogLevel($vaultService), true);
        $this->client = new Client();
        $this->initializeConfig($vaultService);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function initializeConfig(VaultService $vaultService): void
    {
        $lokiConfig = $vaultService->fetchSecret('secret/data/data/loki');

        $this->lokiUrl = $lokiConfig['url'] ?? throw new \RuntimeException('Loki URL not found in Vault.');
        if (isset($lokiConfig['username'], $lokiConfig['password'])) {
            $this->token = base64_encode("{$lokiConfig['username']}:{$lokiConfig['password']}");
        } else {
            $this->token = $lokiConfig['token'] ?? null;
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getLogLevel(VaultService $vaultService): int
    {
        $lokiConfig = $vaultService->fetchSecret('secret/data/data/loki');
        $logLevel = $lokiConfig['log_level'] ?? 'debug';

        return match (strtolower($logLevel)) {
            'info' => \Monolog\Level::Info->value,
            'notice' => \Monolog\Level::Notice->value,
            'warning' => \Monolog\Level::Warning->value,
            'error' => \Monolog\Level::Error->value,
            'critical' => \Monolog\Level::Critical->value,
            'alert' => \Monolog\Level::Alert->value,
            'emergency' => \Monolog\Level::Emergency->value,
            default => \Monolog\Level::Debug->value,
        };
    }

    /**
     * @throws GuzzleException
     */
    protected function write(array|\Monolog\LogRecord $record): void
    {
        $headers = [];
        if ($this->token) {
            $headers['Authorization'] = "Basic {$this->token}";
        }

        $exception = $record['context']['exception'] ?? null;
        $stacktrace = $exception instanceof \Throwable ? $exception->getTraceAsString() : '';

        $log = [
            'streams' => [
                [
                    'stream' => [
                        'level' => $record['level_name'],
                        'application' => 'symfony',
                    ],
                    'values' => [
                        [sprintf("%.0f", microtime(true) * 1e9), json_encode([
                            'message' => $record['message'],
                            'context' => $record['context'],
                            'stacktrace' => $stacktrace,
                        ])],
                    ],
                ],
            ],
        ];

        $this->client->post($this->lokiUrl, [
            'json' => $log,
            'headers' => $headers,
        ]);
    }

    public function supports(string $provider): bool
    {
        return strtolower($provider) === 'loki';
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->write([
            'level_name' => strtoupper($level),
            'message' => $message,
            'context' => $context,
        ]);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}
