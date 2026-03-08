<?php

namespace App\Factory;

use App\DTO\RessourceDTO;
use App\Entity\Ressource;

class RessourceFactory
{
    /**
     * Factory Pattern Method: Encapsulates the complex creation logic of a Ressource entity.
     * Prevents the Manager or Controller from needing to know *how* to construct the object.
     */
    public function createFromDTO(RessourceDTO $dto): Ressource
    {
        $ressource = new Ressource();

        $ressource->setTitle($dto->title);
        $ressource->setContent($dto->content);
        $ressource->setCategory($dto->category);

        foreach ($dto->relationTypes as $relType) {
            $ressource->addRelationType($relType);
        }

        // Default empty type if no file is present yet
        // The Manager/Uploader will override this if a file is attached
        $ressource->setType('text/post');
        $content = $ressource->getContent() ?? '';
        $ressource->setSize(strlen($content));

        return $ressource;
    }

    /**
     * Factory Pattern Method for updating an existing entity structure cleanly.
     */
    public function updateFromDTO(RessourceDTO $dto, Ressource $ressource): Ressource
    {
        $ressource->setTitle($dto->title);
        $ressource->setContent($dto->content);
        $ressource->setCategory($dto->category);

        // Sync RelationTypes
        foreach ($ressource->getRelationTypes() as $rel) {
            $ressource->removeRelationType($rel);
        }
        foreach ($dto->relationTypes as $relType) {
            $ressource->addRelationType($relType);
        }

        return $ressource;
    }
}
