<?php

namespace App\MessageHandler;

use App\Message\NotificationMessage;
use App\Service\WebSocketService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __construct(
        private WebSocketService $webSocketService,
        private SerializerInterface $serializer
    ) {
    }

    public function __invoke(NotificationMessage $message)
    {

        $this->webSocketService->send($this->objectsToArray($message->data), $message->senderId);
    }

    private function objectsToArray(mixed $data): array
    {
        $result = [];
        try {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    $result[$key] = $this->objectToArray($value);
                    continue;
                }

                $result[$key] = $value;
            }

        } catch (\Throwable $th) {
            dd($data, $th);
        }

        return $result;
    }

    private function objectToArray(object $object): array
    {
        $result = array_map(function ($item) {
            return $item;
        }, (array)$object);

       return $result;
    }
} 
