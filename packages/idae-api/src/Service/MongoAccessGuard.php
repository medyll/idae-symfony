<?php

namespace App\Service;

use MongoDB\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

class MongoAccessGuard
{
    private array $allowedHosts;
    private Client $mongoClient;

    public function __construct(private RequestStack $requestStack, Client $mongoClient)
    {
        $configPath = dirname(__DIR__, 2) . '/config/idae/config.yaml';
        $config = Yaml::parseFile($configPath);
        $this->allowedHosts = $config['env']['parameters']['allowed_hosts'] ?? [];
        $this->mongoClient = $mongoClient;
    }

    private function getHostFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getHost() : null;
    }

    public function isAllowed(string $base): bool
    {
        $host = $this->getHostFromRequest();
        printf("Checking access for host: %s, base: %s\n", $host, $base);
        if (empty($base)) {
            throw new \RuntimeException('Database base is not set in the request');
        }
        $this->prefix = $prefix =  $host && isset($this->allowedHosts[$host]['prefix']) ? $this->allowedHosts[$host]['prefix'] : null;
        return $prefix && strpos($base, $prefix . '_') === 0;
    }

    public function getPrefixForHost(): ?string
    {
        $host = $this->getHostFromRequest();
        $base = $this->requestStack->getCurrentRequest()->attributes->get('base', '');
        $prefix = $this->allowedHosts[$host]['prefix'] ?? null;
        if (empty($prefix)) {
            throw new \RuntimeException('Prefix for host is not defined in the configuration file');
        }
        return $host && isset($this->allowedHosts[$host]['prefix']) ?  $prefix . '_' . $base : null;
    }

    public function getDb(): \MongoDB\Database
    {
        $host = $this->getHostFromRequest();
        $base = $this->requestStack->getCurrentRequest()->attributes->get('base', '');
        var_dump($base);
        if (empty($base)) {
            throw new \RuntimeException('Database base is not set in the request');
        }
        if (!$this->isAllowed($base)) {
            throw new \RuntimeException("Unauthorized database $base for this host $host");
        }
        return $this->mongoClient->selectDatabase($this->getPrefixForHost());
    }

    public function getCollection(): \MongoDB\Collection
    {
        try {
            $this->isAllowed($this->requestStack->getCurrentRequest()->attributes->get('base', ''));
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Unauthorized collection access: ' . $e->getMessage());
        }
        $db = $this->getDb();
        $collection = $this->requestStack->getCurrentRequest()->attributes->get('collection', '');
        try {
            $coll = $db->selectCollection($collection);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('error selecting collection: ' . $e->getMessage());
        }

        return $coll;
    }
}
