<?php

namespace App\Controller;

use App\Application\TriggerHandler\TriggerHandler;
use App\Domain\Trigger\Trigger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TriggerController extends AbstractController
{
    public function __construct(
        private readonly TriggerHandler $triggerHandler
    ) {
    }

    #[Route('/api/triggers', methods: ['POST'])]
    public function createTrigger(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $trigger = new Trigger(
            id: $data['triggerId'] ?? uniqid('trigger_', true),
            name: $data['name'],
            payload: $data['payload'] ?? []
        );

        try {
            $this->triggerHandler->handleTrigger($trigger, $data['aggregateId']);
            
            return $this->json([
                'success' => true,
                'triggerId' => $trigger->getId(),
                'message' => 'Trigger received and processed'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/triggers/recalculate', methods: ['POST'])]
    public function triggerRecalculation(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $now = isset($data['date']) 
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $data['date'])
            : new \DateTimeImmutable();

        if ($now === false) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $processed = $this->triggerHandler->processScheduledRecalculation($now);
            
            return $this->json([
                'success' => true,
                'processed' => $processed,
                'date' => $now->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
