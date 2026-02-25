<?php

namespace App\Form;

use App\Entity\ChatMessage;
use App\Entity\ChatRoom;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChatMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Content')
            ->add('creationDate')
            ->add('chatRoom', EntityType::class, [
                'class' => ChatRoom::class,
                'choice_label' => 'id',
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChatMessage::class,
        ]);
    }
}
