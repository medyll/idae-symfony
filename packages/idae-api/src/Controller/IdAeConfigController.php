<?php

namespace App\Controller;

use App\Document\IdAeConfig;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class IdAeConfigController extends AbstractController
{
    #[Route('/api/idae-config', name: 'idae_config_list', methods: ['GET'])]
    public function list(DocumentManager $dm): JsonResponse
    {
        $configs = $dm->getRepository(IdAeConfig::class)->findAll();
        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'value' => $c->getValue(),
        ], $configs);
        return $this->json($data);
    }

    #[Route('/api/idae-config', name: 'idae_config_create', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $config = new IdAeConfig();
        $config->setName($data['name'] ?? '');
        $config->setValue($data['value'] ?? null);
        $dm->persist($config);
        $dm->flush();
        return $this->json(['id' => $config->getId()], 201);
    }

    #[Route('/api/idae-config/{id}', name: 'idae_config_read', methods: ['GET'])]
    public function read(string $id, DocumentManager $dm): JsonResponse
    {
        $config = $dm->getRepository(IdAeConfig::class)->find($id);
        if (!$config) {
            return $this->json(['error' => 'Not found'], 404);
        }
        return $this->json([
            'id' => $config->getId(),
            'name' => $config->getName(),
            'value' => $config->getValue(),
        ]);
    }

    #[Route('/api/idae-config/{id}', name: 'idae_config_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $config = $dm->getRepository(IdAeConfig::class)->find($id);
        if (!$config) {
            return $this->json(['error' => 'Not found'], 404);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) {
            $config->setName($data['name']);
        }
        if (array_key_exists('value', $data)) {
            $config->setValue($data['value']);
        }
        $dm->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/idae-config/{id}', name: 'idae_config_delete', methods: ['DELETE'])]
    public function delete(string $id, DocumentManager $dm): JsonResponse
    {
        $config = $dm->getRepository(IdAeConfig::class)->find($id);
        if (!$config) {
            return $this->json(['error' => 'Not found'], 404);
        }
        $dm->remove($config);
        $dm->flush();
        return $this->json(['success' => true]);
    }
}
