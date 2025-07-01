<?php

namespace App\Form\Product;

use App\Entity\Extension;
use App\Entity\PokemonCard;
use App\Entity\Products;
use App\Entity\Serie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;






class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('extension', TextType::class, [
                'label' => 'Extension',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom de l\'extension',
                ],
            ])
            ->add('serie', TextType::class, [
                'label' => 'Série',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom de la série',
                ],
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Numéro de la carte/produit',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description du produit'
                ],
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('media', CollectionType::class, [
                'entry_type' => MediaType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'required' => false,
            ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $product = $event->getData();
                $pokemonCard = $options['pokemonCard'] ?? null;

                if ($pokemonCard instanceof PokemonCard) {
                    $form->get('extension')->setData($pokemonCard->getExtension()->getId());
                    $form->get('pokemonCardNumber')->setData($pokemonCard->getNumber());

                    // Set the single choice for pokemonCard field
                    $form->add('pokemonCard', EntityType::class, [
                        'class' => PokemonCard::class,
                        'choice_label' => 'name',
                        'label' => 'Nom de la carte',
                        'placeholder' => '--- Sélectionner une carte ---',
                        'choices' => [$pokemonCard], // Only the pre-filled card
                        'attr' => [
                            'data-card-selector-target' => 'cardSelect',
                            'data-action' => 'change->card-selector#onCardChange',
                        ],
                        'data' => $pokemonCard, // Set the selected value
                    ]);
                } else if ($product instanceof Products && $product->getPokemonCard()) {
                    // If product already has a pokemonCard (e.g., on edit), pre-fill
                    $form->get('extension')->setData($product->getPokemonCard()->getExtension()->getId());
                    $form->get('pokemonCardNumber')->setData($product->getPokemonCard()->getNumber());

                    $form->add('pokemonCard', EntityType::class, [
                        'class' => PokemonCard::class,
                        'choice_label' => 'name',
                        'label' => 'Nom de la carte',
                        'placeholder' => '--- Sélectionner une carte ---',
                        'choices' => [$product->getPokemonCard()],
                        'attr' => [
                            'data-card-selector-target' => 'cardSelect',
                            'data-action' => 'change->card-selector#onCardChange',
                        ],
                        'data' => $product->getPokemonCard(),
                    ]);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
            'series' => [],
            'extensions' => [],
            'pokemonCards' => [],
        ]);
    }
}