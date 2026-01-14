<?php

namespace App\Controller;

use App\Application\CommandHandler\OrderCommandHandler;
use App\Application\Projection\OrderProjection;
use App\Domain\Command\CreateOrder;
use App\Domain\Command\DeliverOrder;
use App\Domain\Command\IssueRefund;
use App\Domain\Command\PrepareOrder;
use App\Domain\Command\ReceiveItemBack;
use App\Domain\Command\RequestReturn;
use App\Domain\Command\ShipOrder;
use App\Domain\Command\StartFeedbackWindow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderCommandHandler $commandHandler,
        private readonly OrderProjection $projection
    ) {
    }

    #[Route('/api/orders', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $command = new CreateOrder(
            orderId: $data['orderId'] ?? uniqid('order_', true),
            customerId: $data['customerId'],
            items: $data['items'] ?? []
        );

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true, 'orderId' => $command->getAggregateId()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/prepare', methods: ['POST'])]
    public function prepareOrder(string $orderId): JsonResponse
    {
        $command = new PrepareOrder($orderId);

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/ship', methods: ['POST'])]
    public function shipOrder(string $orderId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $command = new ShipOrder(
            orderId: $orderId,
            trackingNumber: $data['trackingNumber']
        );

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/deliver', methods: ['POST'])]
    public function deliverOrder(string $orderId): JsonResponse
    {
        $command = new DeliverOrder($orderId);

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/start-feedback-window', methods: ['POST'])]
    public function startFeedbackWindow(string $orderId): JsonResponse
    {
        $command = new StartFeedbackWindow($orderId);

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/request-return', methods: ['POST'])]
    public function requestReturn(string $orderId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $command = new RequestReturn(
            orderId: $orderId,
            reason: $data['reason']
        );

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/{orderId}/receive-item-back', methods: ['POST'])]
    public function receiveItemBack(string $orderId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $command = new ReceiveItemBack(
            orderId: $orderId,
            warehouseId: $data['warehouseId']
        );

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders/{orderId}/issue-refund', methods: ['POST'])]
    public function issueRefund(string $orderId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $command = new IssueRefund(
            orderId: $orderId,
            amount: $data['amount'],
            refundTransactionId: $data['refundTransactionId']
        );

        try {
            $this->commandHandler->handle($command);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/orders', methods: ['GET'])]
    public function listOrders(): JsonResponse
    {
        $orders = array_map(
            fn($readModel) => $readModel->toArray(),
            $this->projection->getAll()
        );

        return $this->json($orders);
    }

    #[Route('/api/orders/{orderId}', methods: ['GET'])]
    public function getOrder(string $orderId): JsonResponse
    {
        $readModel = $this->projection->get($orderId);

        if ($readModel === null) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($readModel->toArray());
    }
}
