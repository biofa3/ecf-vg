<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\Regime;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class MenuFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du menu',
                'constraints' => [new NotBlank(message: 'Le titre est obligatoire.')],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4],
                'constraints' => [new NotBlank(message: 'La description est obligatoire.')],
            ])
            ->add('conditions', TextareaType::class, [
                'label' => 'Conditions (délais, précautions, restrictions…)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => "Ex : Commande à passer au minimum 48h à l'avance. Annulation possible jusqu'à 24h avant la prestation.",
                ],
            ])
            ->add('prix_par_personne', MoneyType::class, [
                'label' => 'Prix par personne (€)',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(),
                    new Positive(message: 'Le prix doit être positif.'),
                ],
            ])
            ->add('nombre_personne_minimum', IntegerType::class, [
                'label' => 'Nombre de personnes minimum',
                'constraints' => [
                    new NotBlank(),
                    new Positive(message: 'Le minimum doit être positif.'),
                ],
            ])
            ->add('quantite_restante', IntegerType::class, [
                'label' => 'Quantité restante (stock)',
                'constraints' => [new NotBlank()],
            ])
            ->add('regime', EntityType::class, [
                'label' => 'Régime alimentaire',
                'class' => Regime::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => '— Aucun régime —',
            ])
            ->add('theme', EntityType::class, [
                'label' => 'Thème',
                'class' => Theme::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => '— Aucun thème —',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
