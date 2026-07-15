<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Commande;

class ModifierCommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $minimum = $options['minimum'];

        $builder
            ->add('date_prestation', DateType::class, [
                'label'  => 'Date de prestation',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'min' => (new \DateTime('+1 day'))->format('Y-m-d')],
                'constraints' => [new NotBlank()],
            ])
            ->add('heure_livraison', TextType::class, [
                'label'    => 'Heure de livraison',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => '12:00'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville de livraison',
                'attr'  => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('nombre_personne', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr'  => ['class' => 'form-control', 'min' => $minimum],
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(['value' => $minimum, 'message' => 'Minimum ' . $minimum . ' personnes.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Commande::class, 'minimum' => 1]);
    }
}
