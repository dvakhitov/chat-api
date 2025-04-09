<?php

namespace App\Controller\Api\Chat;

use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
#[AsController]
readonly class DeleteChatByIndexController
{
    public function __construct(
        private ChatRepository $chatRepository
    ) {
    }

    #[Route('/chats/{chatIndex}', name: "api_chat_delete_by_index", methods: ['DELETE'])]
    public function __invoke(string $chatIndex, EntityManagerInterface $em): Response
    {
        $chat = $this->chatRepository->findOneBy(['chatIndex' => $chatIndex]);

        if (!$chat) {
            throw new NotFoundHttpException('Chat not found.');
        }
        $chat->setLastMessage(null);

        foreach ($chat->getMessages() as $message) {
            $message->setChat(null);
        }

        $em->flush();
        $em->remove($chat);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
