<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RessourceDTO
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    public ?string $content = null;

    #[Assert\NotNull(message: 'Veuillez sélectionner une catégorie.')]
    public ?\App\Entity\Category $category = null;

    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un type de relation.')]
    public $relationTypes = [];

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'video/mp4'],
        mimeTypesMessage: 'Veuillez uploader un document valide (PDF, Word, PNG, JPEG, MP4)'
    )]
    public ?UploadedFile $multimedia = null;
}
