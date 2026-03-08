<?php

namespace App\Service;

use App\DTO\RessourceDTO;
use App\Entity\Ressource;

interface RessourceManagerInterface
{
    /**
     * Instantiates an Entity from the DTO
     */
    public function createFromDTO(RessourceDTO $dto): Ressource;

    /**
     * Updates an existing Entity from the DTO
     */
    public function updateFromDTO(RessourceDTO $dto, Ressource $ressource): Ressource;
}
