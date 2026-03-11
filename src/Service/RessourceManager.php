<?php

namespace App\Service;

use App\DTO\RessourceDTO;
use App\Entity\Ressource;
use App\Factory\RessourceFactory;

class RessourceManager implements RessourceManagerInterface
{
    private FileUploaderInterface $fileUploader;
    private RessourceFactory $factory;
    private \Symfony\Bundle\SecurityBundle\Security $security;

    public function __construct(FileUploaderInterface $fileUploader, RessourceFactory $factory, \Symfony\Bundle\SecurityBundle\Security $security)
    {
        $this->fileUploader = $fileUploader;
        $this->factory = $factory;
        $this->security = $security;
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

        if (!$this->security->isGranted('ROLE_MODERATOR') && !$this->security->isGranted('ROLE_ADMIN')) {
            $ressource->setStatus('pending');
        }

        return $ressource;
    }

    /**
     * Handle File Upload if present
     */
    private function handleFileUpload(RessourceDTO $dto, Ressource $ressource): void
    {
        if ($dto->multimedia) {
            $mimeType = $dto->multimedia->getMimeType();
            $size = $dto->multimedia->getSize();

            $fileName = $this->fileUploader->upload($dto->multimedia);

            $ressource->setType($mimeType);
            $ressource->setSize($size);

            $currentContent = $ressource->getContent() ?? '';
            $ressource->setContent($currentContent . "\n\n[Fichier multimédia attaché : /uploads/multimedia/" . $fileName . "]");
        }
    }
}
