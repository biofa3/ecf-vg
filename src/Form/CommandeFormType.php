<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class CommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $menu = $options['menu'];

        $builder
            ->add('date_prestation', DateType::class, [
                'label' => 'Date de la prestation',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une date'),
                ],
            ])
           ->add('heure_livraison', TextType::class, [
                 'label' => 'Heure de livraison souhaitée',
                 'attr' => ['placeholder' => 'Ex: 12:30'],
                 'required' => false,
])
            ->add('ville', TextType::class, [
                'label' => 'Ville de livraison',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer une ville'),
                ],
            ])
            ->add('nombre_personne', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'mapped' => false,
                'data' => $menu->getNombrePersonneMinimum(),
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un nombre de personnes'),
                    new GreaterThanOrEqual(
                        value: $menu->getNombrePersonneMinimum(),
                        message: 'Le nombre minimum de personnes est {{ compared_value }}'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'menu' => null,
        ]);
    }
}