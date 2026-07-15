<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AvisFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [
                    '⭐ 1 — Très mauvais'    => 1,
                    '⭐⭐ 2 — Mauvais'        => 2,
                    '⭐⭐⭐ 3 — Correct'       => 3,
                    '⭐⭐⭐⭐ 4 — Bien'         => 4,
                    '⭐⭐⭐⭐⭐ 5 — Excellent'   => 5,
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => ['rows' => 4, 'placeholder' => 'Décrivez votre expérience...'],
                'constraints' => [new NotBlank(message: 'Le commentaire ne peut pas être vide.')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}