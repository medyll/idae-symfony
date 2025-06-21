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

    public function isAllowed(string $base): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $host = $request ? $request->getHost() : null;
        $prefix = $host && isset($this->allowedHosts[$host]['prefix']) ? $this->allowedHosts[$host]['prefix'] : null;
        return $prefix && strpos($base, $prefix.'_') === 0;
    }

    public function getPrefixForHost(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $host = $request ? $request->getHost() : null;
        return $host && isset($this->allowedHosts[$host]['prefix']) ? $this->allowedHosts[$host]['prefix'] : null;
    }

    public function getDatabase(string $base)
    {
        if (!$this->isAllowed($base)) {
            throw new \RuntimeException('Unauthorized database for this host');
        }
        return $this->mongoClient->selectDatabase($base);
    }
}
