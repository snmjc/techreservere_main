<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CorsMiddleware implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $this->addCorsHeaders($response, $request);
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $this->addCorsHeaders($response, $event->getRequest());
    }

    private function addCorsHeaders(Response $response, Request $request): void
    {
        $origin = $request->headers->get('Origin', '');
        $allowedOrigins = array_filter(array_unique([
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'https://techreserve.farahkenawy.codes',
            'https://employers-mall-switches-bookstore.trycloudflare.com',
            rtrim((string)($_ENV['FRONTEND_URL'] ?? ''), '/'),
            rtrim((string)($_ENV['DEV_FRONTEND_URL'] ?? ''), '/'),
        ]));
        $allowedOrigin = in_array($origin, $allowedOrigins, true) ? $origin : $allowedOrigins[0];

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }
}
