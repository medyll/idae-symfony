<?php

namespace App\Controller;

use App\Service\MongoAccessGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/{base}/{collection}')]
class DynamicMongoController extends AbstractController
{
    public function __construct(private MongoAccessGuard $mongoAccessGuard) {}

    #[Route('', name: 'dynamic_mongo_list', methods: ['GET'])]
    public function list(string $base, string $collection): JsonResponse
    {
        try {
            $db = $this->mongoAccessGuard->getDatabase($base);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
        $coll = $db->selectCollection($collection);
        $docs = $coll->find()->toArray();
        $data = array_map(function ($doc) {
            $doc['_id'] = (string) $doc['_id'];
            return $doc;
        }, $docs);
        return $this->json($data);
    }

    #[Route('', name: 'dynamic_mongo_create', methods: ['POST'])]
    public function create(string $base, string $collection, Request $request): JsonResponse
    {
        try {
            $db = $this->mongoAccessGuard->getDatabase($base);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
        $coll = $db->selectCollection($collection);
        $data = json_decode($request->getContent(), true);
        $result = $coll->insertOne($data);
        return $this->json(['insertedId' => (string)$result->getInsertedId()], 201);
    }
}
