<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Client;

use GuzzleHttp\Client as BaseCLient;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Stadline\Resamania2Bundle\Lib\GreenPass\Exception\TacvCallException;
use Stadline\Resamania2Bundle\Lib\GreenPass\Exception\TacvTypeNotSupportedException;
use Symfony\Component\HttpFoundation\Response;

class TacvClient
{
    public const TYPE_2D_DOC = '2ddoc';
    public const TYPE_QR_CODE = 'qrcode';

    private const RESULT_VALID = 'Valide';

    private BaseClient $client;
    private string $authorizationToken;
    private LoggerInterface $logger;

    public function __construct(BaseClient $client, string $authorizationToken, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->authorizationToken = $authorizationToken;
        $this->logger = $logger;
    }

    public function isValid(string $code, string $type): bool
    {
        switch ($type) {
            case self::TYPE_2D_DOC:
                $uri = 'api/document/2D-DOC';
                break;
            case self::TYPE_QR_CODE:
                $uri = 'api/document/DCC';
                break;
            default:
                throw new TacvTypeNotSupportedException($type);
        }

        try {
            $response = $this->call($uri, $code);
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('Call to TAC V failed: %s', $exception->getMessage()));
            throw new TacvCallException();
        }

        $body = json_decode($response->getBody()->getContents(), true);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->critical(sprintf('Status Code %s From TAC V: %s',
                $response->getStatusCode(),
                json_encode($body['errors'])
            ));

            throw new TacvCallException();
        }

        return !$this->isInvalid($body) && !$this->isBlacklisted($body);
    }

    private function call(string $uri, string $code): ResponseInterface
    {
        try {
            return $this->client->post($uri, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->authorizationToken,
                    'Content-Type' => 'text/plain',
                ],
                'body' => $code,
            ]);
        } catch (RequestException $exception) {
            throw new TacvCallException($exception->getMessage());
        }
    }

    private function isInvalid(array $body): bool
    {
        return self::RESULT_VALID !== $body['data']['dynamic'][0]['liteValidityStatus'] ?? false;
    }

    private function isBlacklisted(array $body): bool
    {
        return $body['data']['static']['isBlacklisted'] ?? false;
    }
}
