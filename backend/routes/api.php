<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Http\Controllers\EmailController;

return function (RoutingConfigurator $routes) {
    $routes->add('send_invitation', '/v1/emails/send-invitation')
        ->controller([EmailController::class, 'sendInvitation'])
        ->methods(['POST']);

    $routes->add('send_approval', '/v1/emails/send-approval')
        ->controller([EmailController::class, 'sendApproval'])
        ->methods(['POST']);

    $routes->add('send_rejection', '/v1/emails/send-rejection')
        ->controller([EmailController::class, 'sendRejection'])
        ->methods(['POST']);
};
