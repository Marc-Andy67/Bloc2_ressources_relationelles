<?php

namespace App\Service;

use App\DTO\RessourceDTO;
use App\Entity\Ressource;
use App\Factory\RessourceFactory;

class RessourceManager implements RessourceManagerInterface
{
    private FileUploaderInterface $fileUploader;
    private RessourceFactory $factory;

    public function __construct(FileUploaderInterface $fileUploader, RessourceFactory $factory)
    {
        $this->fileUploader = $fileUploader;
        $this->factory = $factory;
    }

    /**
     * Instantiates an Entity from the DTO
     */
    public function createFromDTO(RessourceDTO $dto): Ressource
    {
        $ressource = $this->factory->createFromDTO($dto);
        $this->handleFileUpload($dto, $ressource);

        return $ressource;
    }

    /**
     * Updates an existing Entity from the DTO
     */
    public function updateFromDTO(RessourceDTO $dto, Ressource $ressource): Ressource
    {
        $ressource = $this->factory->updateFromDTO($dto, $ressource);
        $this->handleFileUpload($dto, $ressource);

        return $ressource;
    }

    /**
     * Handle File Upload if present
     */
    private function handleFileUpload(RessourceDTO $dto, Ressource $ressource): void
    {
        if ($dto->multimedia) {
            $fileName = $this->fileUploader->upload($dto->multimedia);

            $ressource->setType($dto->multimedia->getMimeType());
            $ressource->setSize($dto->multimedia->getSize());

            $currentContent = $ressource->getContent() ?? '';
            $ressource->setContent($currentContent . "\n\n[Fichier multimédia attaché : /uploads/multimedia/" . $fileName . "]");
        }
    }
}
