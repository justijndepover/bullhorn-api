<?php

namespace Justijndepover\Bullhorn;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use Justijndepover\Teamleader\Exceptions\ApiException;
use Justijndepover\Teamleader\Exceptions\CouldNotAquireAccessTokenException;

class Bullhorn
{
    /**
     * @var string
     */
    private $baseUrl = 'https://auth.bullhornstaffing.com';

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
        return $this->baseUrl . '/oauth/authorize'
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

    public function shouldAuthorize(): bool
    {
        return empty($this->authorizationCode) && empty($this->refreshToken);
    }

    public function shouldRefreshToken(): bool
    {
        return empty($this->accessToken) || $this->tokenHasExpired();
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

            $response = $this->client->post($this->baseUrl . '/oauth/token', $data);

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

            throw CouldNotAquireAccessTokenException::make($response->errors[0]->status, $response->errors[0]->title);
        } catch (Exception $e) {
            throw ApiException::make($e->getCode(), $e->getMessage());
        }
    }
}
