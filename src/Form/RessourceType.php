<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\RelationType;
use App\Entity\Ressource;
use App\DTO\RessourceDTO;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('content')
            ->add('multimedia', FileType::class, [
                'label' => 'Fichier multimédia (Max 10Mo, PDF/DOC/IMAGES/MP4)',
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'video/mp4',
                        ],
                        mimeTypesMessage: 'Veuillez uploader un document valide (PDF, Word, PNG, JPEG, MP4)'
                    )
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('relationTypes', EntityType::class, [
                'class' => RelationType::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RessourceDTO::class,
        ]);
    }
}
