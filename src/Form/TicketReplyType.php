<?php

namespace App\Form;

use App\Entity\TicketReply;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TicketReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => 'Votre réponse',
            'constraints' => [
                new NotBlank(message: 'La réponse ne peut pas être vide.'),
                new Length(min: 2, max: 3000),
            ],
            'attr' => ['rows' => 4, 'placeholder' => 'Votre réponse...'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TicketReply::class]);
    }
}