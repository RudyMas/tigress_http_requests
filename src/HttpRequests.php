<?php

namespace Tigress;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class HttpRequests (PHP version 8.4)
 *
 * @author Rudy Mas <rudy.mas@rudymas.be>
 * @copyright 2025, rudymas.be. (http://www.rudymas.be/)
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version 2025.09.15.0
 * @package Tigress\HttpRequests
 */
class HttpRequests
{
    private Client $httpClient;
    private ?LoggerInterface $logger = null;

    /**
     * HttpRequest constructor.
     *
     * @param string $baseUri
     * @param LoggerInterface|null $logger
     */
    public function __construct(private string $baseUri = '', ?LoggerInterface $logger = null)
    {
        $this->httpClient = new Client();
        $this->logger = $logger;
    }

    /**
     * Get the version of the HttpRequests class
     *
     * @return string
     */
    public static function version(): string
    {
        return '2025.09.15';
    }

    /**
     * Perform a get HttpRequest
     *
     * @param string $url
     * @param string|array|null $body
     * @param array $queryParams
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @param string $contentType
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function get(
        string            $url,
        string|array|null $body = null,
        array             $queryParams = [],
        ?array            $headers = null,
        ?string           $username = null,
        ?string           $password = null,
        string            $contentType = 'application/json'
    ): ResponseInterface
    {
        return $this->sendRequest('GET', $url, $body, $headers, $username, $password, $contentType, $queryParams);
    }

    /**
     * Perform a post HttpRequest
     *
     * @param string $url
     * @param string|array $body
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @param string $contentType
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post(
        string       $url,
        string|array $body,
        ?array       $headers = null,
        ?string      $username = null,
        ?string      $password = null,
        string       $contentType = 'application/json'
    ): ResponseInterface
    {
        return $this->sendRequest('POST', $url, $body, $headers, $username, $password, $contentType);
    }

    /**
     * Perform a put HttpRequest
     *
     * @param string $url
     * @param string|array $body
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @param string $contentType
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function put(
        string       $url,
        string|array $body,
        ?array       $headers = null,
        ?string      $username = null,
        ?string      $password = null,
        string       $contentType = 'application/json'
    ): ResponseInterface
    {
        return $this->sendRequest('PUT', $url, $body, $headers, $username, $password, $contentType);
    }

    /**
     * Perform a patch HttpRequest
     *
     * @param string $url
     * @param string|array $body
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @param string $contentType
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function patch(
        string       $url,
        string|array $body,
        ?array       $headers = null,
        ?string      $username = null,
        ?string      $password = null,
        string       $contentType = 'application/json'
    ): ResponseInterface
    {
        return $this->sendRequest('PATCH', $url, $body, $headers, $username, $password, $contentType);
    }

    /**
     * Perform a delete HttpRequest
     *
     * @param string $url
     * @param string|array|null $body
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function delete(
        string            $url,
        string|array|null $body = null,
        ?array            $headers = null,
        ?string           $username = null,
        ?string           $password = null
    ): ResponseInterface
    {
        return $this->sendRequest('DELETE', $url, $body, $headers, $username, $password);
    }

    /**
     * Perform an upload HttpRequest
     *
     * @param string $url
     * @param array $files
     * @param array $fields
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function upload(
        string  $url,
        array   $files,
        array   $fields = [],
        ?array  $headers = null,
        ?string $username = null,
        ?string $password = null
    ): ResponseInterface
    {
        $multipart = [];

        foreach ($fields as $name => $value) {
            $multipart[] = [
                'name' => $name,
                'contents' => $value,
            ];
        }

        foreach ($files as $name => $filePath) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ];
        }

        $options = [
            'multipart' => $multipart,
            'headers' => $headers ?? [],
        ];

        if ($username !== null && $password !== null) {
            $options['auth'] = [$username, $password];
        }

        return $this->sendRawRequest('POST', $url, $options);
    }

    /**
     * Perform a HttpRequest
     *
     * @param string $method
     * @param string $url
     * @param string|array|null $body
     * @param array|null $headers
     * @param string|null $username
     * @param string|null $password
     * @param string $contentType
     * @param array $queryParams
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function sendRequest(
        string            $method,
        string            $url,
        string|array|null $body = null,
        ?array            $headers = null,
        ?string           $username = null,
        ?string           $password = null,
        string            $contentType = 'application/json',
        array             $queryParams = []
    ): ResponseInterface
    {
        $headers = $this->buildHeaders($headers, $contentType);

        $options = [
            'headers' => $headers,
            'query' => $queryParams,
        ];

        if ($body !== null) {
            if (is_array($body) && str_contains($contentType, 'json')) {
                $body = json_encode($body);
            }
            $options['body'] = $body;
        }

        if ($username !== null && $password !== null) {
            $options['auth'] = [$username, $password];
        }

        return $this->sendRawRequest($method, $url, $options);
    }

    /**
     * Send a raw HttpRequest
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function sendRawRequest(string $method, string $url, array $options): ResponseInterface
    {
        $fullUrl = $this->baseUri . $url;

        if ($this->logger) {
            $this->logger->info("HTTP {$method} request to {$fullUrl}", $options);
        }

        try {
            $response = $this->httpClient->request($method, $fullUrl, $options);

            if ($this->logger) {
                $this->logger->info("Received response with status {$response->getStatusCode()}");
            }

            return $response;
        } catch (GuzzleException $e) {
            if ($this->logger) {
                $this->logger->error("HTTP request failed: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Build the headers for the HttpRequest
     *
     * @param array|null $headers
     * @param string $contentType
     * @return array
     */
    private function buildHeaders(?array $headers, string $contentType): array
    {
        $headers = $headers ?? [];

        // Alleen voor non-multipart headers
        if (!str_contains($contentType, 'multipart')) {
            $headers['Content-Type'] ??= $contentType;
            $headers['accept'] ??= $contentType;
        }

        return $headers;
    }

    /**
     * Get the JSON body from the response
     *
     * @param ResponseInterface $response
     * @return mixed
     */
    public function getJsonBody(ResponseInterface $response): mixed
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get the baseUri
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Set the baseUri
     *
     * @param string $baseUri
     * @return void
     */
    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }
}
