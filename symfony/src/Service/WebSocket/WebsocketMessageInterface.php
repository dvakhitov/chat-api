<?php

namespace App\Service\WebSocket;

interface WebsocketMessageInterface
{
    public function getWebsocketUserId(): int;

    public function getWebsocketMessage():string;
}