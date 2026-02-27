<?php

namespace App\Tests\Feature\Middleware;

use App\Infrastructure\Auth\ClerkTokenVerifier;
use App\Infrastructure\Auth\ClerkVerificationFailedException;
use App\Middleware\AuthenticationMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

// ===== AI GENERATED: AuthenticationMiddlewareTest =====
// Purpose: Feature tests for AuthenticationMiddleware
// Tests: public route bypass, missing token 401, invalid token 401, valid token passes

class AuthenticationMiddlewareTest extends TestCase
{
    private ClerkTokenVerifier|MockObject $clerkTokenVerifier;
    private AuthenticationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->clerkTokenVerifier = $this->createMock(ClerkTokenVerifier::class);
        $this->middleware = new AuthenticationMiddleware($this->clerkTokenVerifier);
    }

    public function testPublicRouteBypassesAuthentication(): void
    {
        $request = Request::create('/api/v1/health', 'GET');
        $event = $this->createRequestEvent($request);

        $this->clerkTokenVerifier
            ->expects($this->never())
            ->method('verifyTokenAndGetIdentity');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $request = Request::create('/api/v1/accounts/me', 'GET');
        $event = $this->createRequestEvent($request);

        $this->middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('AuthenticationRequired', $body['errorCode']);
    }

    public function testInvalidBearerTokenReturns401(): void
    {
        $request = Request::create('/api/v1/accounts/me', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid_token_here');
        $event = $this->createRequestEvent($request);

        $this->clerkTokenVerifier
            ->expects($this->once())
            ->method('verifyTokenAndGetIdentity')
            ->with('invalid_token_here')
            ->willThrowException(new ClerkVerificationFailedException('Token verification failed'));

        $this->middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('AuthenticationFailed', $body['errorCode']);
    }

    public function testValidBearerTokenSetsIdentityOnRequest(): void
    {
        $request = Request::create('/api/v1/accounts/me', 'GET');
        $request->headers->set('Authorization', 'Bearer valid_token_here');
        $event = $this->createRequestEvent($request);

        $normalizedIdentity = [
            'clerkUserId' => 'user_abc123',
            'emailAddress' => 'admin@techreserve.edu.ph',
        ];

        $this->clerkTokenVerifier
            ->expects($this->once())
            ->method('verifyTokenAndGetIdentity')
            ->with('valid_token_here')
            ->willReturn($normalizedIdentity);

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertSame($normalizedIdentity, $request->attributes->get('authenticatedIdentity'));
    }

    public function testMalformedAuthorizationHeaderWithoutBearerPrefixReturns401(): void
    {
        $request = Request::create('/api/v1/accounts/me', 'GET');
        $request->headers->set('Authorization', 'Basic some_credentials');
        $event = $this->createRequestEvent($request);

        $this->middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
