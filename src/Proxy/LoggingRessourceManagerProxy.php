<?php

namespace App\Proxy;

use App\DTO\RessourceDTO;
use App\Entity\Ressource;
use App\Service\RessourceManagerInterface;
use Psr\Log\LoggerInterface;

class LoggingRessourceManagerProxy implements RessourceManagerInterface
{
    private RessourceManagerInterface $realManager;
    private LoggerInterface $logger;

    public function __construct(RessourceManagerInterface $realManager, LoggerInterface $logger)
    {
        // By relying on the interface, we inject the actual concrete implementation
        // that does the heavy lifting, yet we intercept it via this Proxy wrapper.
        $this->realManager = $realManager;
        $this->logger = $logger;
    }

    public function createFromDTO(RessourceDTO $dto): Ressource
    {
        $this->logger->info(sprintf('User requested creation of a new ressource: %s', $dto->title));

        $startTime = microtime(true);
        $result = $this->realManager->createFromDTO($dto);
        $endTime = microtime(true);

        $this->logger->info(sprintf('Created new Ressource entity in proxy wrapper. Took %.4f ms', ($endTime - $startTime) * 1000));

        return $result;
    }

    public function updateFromDTO(RessourceDTO $dto, Ressource $ressource): Ressource
    {
        $this->logger->info(sprintf('User requested update of ressource ID: %d', $ressource->getId()));

        $result = $this->realManager->updateFromDTO($dto, $ressource);

        $this->logger->info('Ressource updated successfully via proxy.');

        return $result;
    }
}
