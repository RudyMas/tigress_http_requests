# Tigress HTTP Requests — Programmer's Manual

**Version:** 2025.12.09  
**Author:** Rudy Mas  
**License:** GPL-3.0-or-later

---

## Overview

`tigress/http_requests` is a lightweight PHP library that wraps GuzzleHttp 7 to provide a clean, fluent API for HTTP requests. It handles JSON, form-urlencoded, and XML body serialisation automatically, supports file uploads via multipart/form-data, basic authentication, and optional PSR-3 logging.

---

## Installation

```bash
composer require tigress/http_requests
```

**Requirements:**
- PHP >= 8.5
- `ext-simplexml`
- `guzzlehttp/guzzle` ^7
- `monolog/monolog` ^3.9 (optional, only if you inject a logger)

---

## Quick Start

```php
use Tigress\HttpRequests;

require_once 'vendor/autoload.php';

$http = new HttpRequests('https://api.example.com');

try {
    $response = $http->get('/users');
    $data = $http->getJsonBody($response);
    print_r($data);
} catch (Throwable $e) {
    echo $e->getMessage();
}
```

---

## API Reference

### Constructor

```php
public function __construct(
    private string $baseUri = '',
    ?LoggerInterface $logger = null
)
```

| Parameter  | Type                  | Description                                   |
|------------|-----------------------|-----------------------------------------------|
| `$baseUri` | `string`              | Base URL prepended to every relative request  |
| `$logger`  | `LoggerInterface|null`| Optional PSR-3 logger (e.g. Monolog)          |

If `$baseUri` is empty, all request URLs must be absolute (`https://...`).  
If `$baseUri` is set, request URLs can be relative (`/api/users`) and the base URI is prepended automatically.

---

### HTTP Verbs

Each method returns `Psr\Http\Message\ResponseInterface` and throws `GuzzleHttp\Exception\GuzzleException` on failure.

#### `get()`

```php
public function get(
    string            $url,
    string|array|null $body = null,
    array             $queryParams = [],
    ?array            $headers = null,
    ?string           $username = null,
    ?string           $password = null,
    string            $contentType = 'application/json'
): ResponseInterface
```

#### `post()`

```php
public function post(
    string       $url,
    string|array $body,
    array        $queryParams = [],
    ?array       $headers = null,
    ?string      $username = null,
    ?string      $password = null,
    string       $contentType = 'application/json'
): ResponseInterface
```

#### `put()`

```php
public function put(
    string       $url,
    string|array $body,
    array        $queryParams = [],
    ?array       $headers = null,
    ?string      $username = null,
    ?string      $password = null,
    string       $contentType = 'application/json'
): ResponseInterface
```

#### `patch()`

```php
public function patch(
    string       $url,
    string|array $body,
    array        $queryParams = [],
    ?array       $headers = null,
    ?string      $username = null,
    ?string      $password = null,
    string       $contentType = 'application/json'
): ResponseInterface
```

#### `delete()`

```php
public function delete(
    string            $url,
    string|array|null $body = null,
    array             $queryParams = [],
    ?array            $headers = null,
    ?string           $username = null,
    ?string           $password = null,
    string            $contentType = 'application/json'
): ResponseInterface
```

#### Common parameters

| Parameter      | Type                  | Description                                                   |
|----------------|-----------------------|---------------------------------------------------------------|
| `$url`         | `string`              | Absolute or relative URL (relative uses `$baseUri`)           |
| `$body`        | `string\|array\|null` | Request body (see "Body serialisation" below)                 |
| `$queryParams` | `array`               | Associative array appended as query string `?key=value`       |
| `$headers`     | `array\|null`         | Custom headers. If omitted, `Content-Type`/`Accept` are set   |
| `$username`    | `string\|null`        | HTTP Basic auth username                                      |
| `$password`    | `string\|null`        | HTTP Basic auth password                                      |
| `$contentType` | `string`              | MIME type for Content-Type and Accept headers (default JSON)  |

> **Note:** `get()` and `delete()` accept `null` body; all other verbs require a body.

---

### Body Serialisation

When `$body` is an **array**, it is serialised according to `$contentType`:

| Content-Type                             | Serialisation                                    |
|------------------------------------------|--------------------------------------------------|
| `application/json` (default)             | `json_encode($body)`                             |
| `application/x-www-form-urlencoded`      | Sent as Guzzle `form_params`                     |
| `text/xml` or `application/xml`          | Wrapped in `<root><data>JSON_ENCODED</data></root>` via SimpleXMLElement |
| Any other value                          | `http_build_query($body)`                        |

When `$body` is a **string**, it is sent verbatim (raw).

---

### File Uploads

```php
public function upload(
    string  $url,
    array   $files,
    array   $fields = [],
    ?array  $headers = null,
    ?string $username = null,
    ?string $password = null
): ResponseInterface
```

Sends a **POST** request with `multipart/form-data`.

| Parameter  | Type     | Description                                              |
|------------|----------|----------------------------------------------------------|
| `$files`   | `array`  | Associative: `fieldName => /path/to/file`                |
| `$fields`  | `array`  | Additional form fields: `fieldName => value`             |

```php
$response = $http->upload('/upload', 
    files: ['document' => '/var/data/report.pdf'],
    fields: ['description' => 'Monthly report']
);
```

---

### Response Helpers

```php
public function getJsonBody(ResponseInterface $response): mixed
```

Decodes the response body from JSON into a PHP array/object (`json_decode($body, true)`).

```php
$response = $http->get('/users');
$users = $http->getJsonBody($response); // array
```

---

### Base URI Management

```php
public function getBaseUri(): string
public function setBaseUri(string $baseUri): void
```

```php
$http->setBaseUri('https://another-api.com');
echo $http->getBaseUri(); // https://another-api.com
```

---

### Version

```php
public static function version(): string
```

```php
echo HttpRequests::version(); // 2025.12.09
```

---

## Logging

Pass any PSR-3 logger to the constructor:

```php
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('tigress');
$logger->pushHandler(new StreamHandler(__DIR__ . '/http.log', Level::Debug));

$http = new HttpRequests('https://api.example.com', $logger);
```

Every request and response status is logged at `INFO` level; errors (`GuzzleException`) at `ERROR` level.

---

## Error Handling

All public methods throw `GuzzleHttp\Exception\GuzzleException` for HTTP-level errors (connection failures, timeouts, 4xx/5xx). Catch `Throwable` to handle all exceptions:

```php
try {
    $response = $http->get('/users');
} catch (GuzzleHttp\Exception\GuzzleException $e) {
    echo "HTTP error: " . $e->getMessage();
} catch (Throwable $e) {
    echo "Unexpected error: " . $e->getMessage();
}
```

---

## Complete Examples

### GET with query parameters and auth

```php
$response = $http->get(
    url: '/protected/users',
    queryParams: ['page' => 2, 'limit' => 50],
    username: 'admin',
    password: 'secret123'
);
$data = $http->getJsonBody($response);
```

### POST with JSON body

```php
$response = $http->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

### POST with form-urlencoded body

```php
$response = $http->post('/login', [
    'username' => 'admin',
    'password' => 's3cr3t',
], contentType: 'application/x-www-form-urlencoded');
```

### PUT with raw XML string

```php
$response = $http->put('/resource/42', '<item><id>42</id></item>',
    contentType: 'application/xml');
```

### PATCH with partial update

```php
$response = $http->patch('/users/42', ['name' => 'Jane Doe']);
```

### DELETE a resource

```php
$response = $http->delete('/users/42');
```

### Using a full absolute URL (no base URI)

```php
$http = new HttpRequests(); // no base URI
$response = $http->get('https://jsonplaceholder.typicode.com/posts/1');
```

### Custom headers

```php
$response = $http->get('/users', headers: [
    'X-API-Key' => 'abc123',
    'Authorization' => 'Bearer token123',
]);
```
