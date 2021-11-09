<?php

namespace Justijndepover\Bullhorn;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Justijndepover\Bullhorn\Exceptions\ApiException;
use Justijndepover\Bullhorn\Exceptions\CouldNotAquireAccessTokenException;

class Bullhorn
{
    /**
     * @var string
     */
    private $authUrl = 'https://auth.bullhornstaffing.com';

    /**
     * @var string
     */
    private $loginUrl = 'https://rest.bullhornstaffing.com';

    /**
     * @var string
     */
    private $restUrl;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var Client|null
     */
    private $client;

    /**
     * @var callable(Connection)
     */
    private $tokenUpdateCallback;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $authorizationCode;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var int
     */
    private $tokenExpiresAt;

    /**
     * @var string
     */
    private $BHRestToken;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri, string $state)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->state = $state;

        $this->client = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => true,
        ]);
    }

    public function redirectForAuthorizationUrl(): string
    {
        return $this->authUrl . '/oauth/authorize'
            . '?client_id=' . $this->clientId
            . '&response_type=code'
            . '&redirect_uri=' . $this->redirectUri
            . '&state=' . $this->state;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(?string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getAuthorizationCode(): ?string
    {
        return $this->authorizationCode;
    }

    public function setAuthorizationCode(?string $authorizationCode): void
    {
        $this->authorizationCode = $authorizationCode;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getTokenExpiresAt(): ?int
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?int $tokenExpiresAt): void
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
    }

    public function getBHRestToken(): ?string
    {
        return $this->BHRestToken;
    }

    public function setBHRestToken(?string $BHRestToken): void
    {
        $this->BHRestToken = $BHRestToken;
    }

    public function getRestUrl(): ?string
    {
        return $this->restUrl;
    }

    public function setRestUrl(?string $restUrl): void
    {
        $this->restUrl = $restUrl;
    }

    public function shouldAuthorize(): bool
    {
        if (! $this->shouldObtainBHRestToken()) {
            return false;
        }

        return empty($this->authorizationCode) && empty($this->refreshToken);
    }

    public function shouldRefreshToken(): bool
    {
        return empty($this->accessToken) || $this->tokenHasExpired();
    }

    public function shouldObtainBHRestToken(): bool
    {
        return empty($this->BHRestToken) || empty($this->restUrl);
    }

    public function connect(): void
    {
        if ($this->shouldAuthorize()) {
            header("Location: {$this->redirectForAuthorizationUrl()}");
            exit;
        }

        if ($this->shouldRefreshToken()) {
            $this->acquireAccessToken();
        }

        if ($this->shouldObtainBHRestToken()) {
            $this->acquireBHRestToken();
        }
    }

    public function setTokenUpdateCallback(callable $callback): void
    {
        $this->tokenUpdateCallback = $callback;
    }

    private function tokenHasExpired(): bool
    {
        if (empty($this->tokenExpiresAt)) {
            return true;
        }

        return ($this->tokenExpiresAt - 60) < time();
    }

    private function acquireAccessToken(): void
    {
        try {
            // If refresh token not yet acquired, do token request
            if (empty($this->refreshToken)) {
                $data = [
                    'form_params' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'code' => $this->authorizationCode,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $this->redirectUri,
                    ],
                ];
            } else { // else do refresh token request
                $data = [
                    'form_params' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'refresh_token' => $this->refreshToken,
                        'grant_type' => 'refresh_token',
                    ],
                ];
            }

            $response = $this->client->post($this->authUrl . '/oauth/token', $data);

            Message::rewindBody($response);
            $body = json_decode($response->getBody()->getContents(), true);

            $this->accessToken = $body['access_token'];
            $this->refreshToken = $body['refresh_token'];
            $this->tokenExpiresAt = time() + $body['expires_in'];

            if (is_callable($this->tokenUpdateCallback)) {
                call_user_func($this->tokenUpdateCallback, $this);
            }
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());

            throw CouldNotAquireAccessTokenException::make($e->getCode(), $response->error . ' - ' . $response->error_description);
        } catch (Exception $e) {
            throw ApiException::make($e->getCode(), $e->getMessage());
        }
    }

    public function acquireBHRestToken(): void
    {
        try {
            $data = [
                'form_params' => [
                    'version' => '*',
                    'access_token' => $this->accessToken,
                ],
            ];

            $response = $this->client->post($this->loginUrl . '/rest-services/login', $data);

            Message::rewindBody($response);
            $body = json_decode($response->getBody()->getContents(), true);

            $this->BHRestToken = $body['BhRestToken'];
            $this->restUrl = $body['restUrl'];

            if (is_callable($this->tokenUpdateCallback)) {
                call_user_func($this->tokenUpdateCallback, $this);
            }
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());

            throw CouldNotAquireAccessTokenException::make($e->getCode(), $response->error . ' - ' . $response->error_description);
        } catch (Exception $e) {
            throw ApiException::make($e->getCode(), $e->getMessage());
        }
    }

    public function get(string $endpoint, array $parameters = [])
    {
        try {
            $request = $this->createRequest('GET', $endpoint, null, $parameters);
            $response = $this->client->send($request);

            return $this->parseResponse($response);
        } catch (ClientException $e) {
            $this->parseExceptionForErrorMessages($e);
        } catch (Exception $e) {
            throw ApiException::make($e->getCode(), $e->getMessage());
        }
    }

    public function post(string $endpoint, array $body, array $parameters = [])
    {
        $body = json_encode($body);

        try {
            $request = $this->createRequest('POST', $endpoint, $body, $parameters);
            $response = $this->client->send($request);

            return $this->parseResponse($response);
        } catch (ClientException $e) {
            $this->parseExceptionForErrorMessages($e);
        } catch (Exception $e) {
            throw ApiException::make($e->getCode(), $e->getMessage());
        }
    }

    private function createRequest($method, $endpoint, $body = null, array $parameters = [], array $headers = [])
    {
        $endpoint = $this->buildUrl($endpoint);

        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'BHRestToken' => $this->BHRestToken,
        ]);

        // Create param string
        if (! empty($parameters)) {
            $endpoint .= '?' . http_build_query($parameters);
        }

        // Create the request
        $request = new Request($method, $endpoint, $headers, $body);

        return $request;
    }

    private function buildUrl(string $endpoint): string
    {
        return $this->getRestUrl() . ltrim($endpoint, '/');
    }

    private function parseResponse(Response $response)
    {
        try {
            if ($response->getStatusCode() === 204) {
                return [];
            }

            Message::rewindBody($response);
            $json = json_decode($response->getBody()->getContents(), true);

            return $json;
        } catch (\RuntimeException $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function parseExceptionForErrorMessages(ClientException $e): void
    {
        $response = json_decode($e->getResponse()->getBody()->getContents());

        throw ApiException::make($e->getCode(), $response->errorMessage);
    }
}
