<?php

namespace App\Form\Product;

use App\Entity\PokemonCard;
use App\Entity\Products;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['data'] ?? null;
        $initialNumber = null;
        
        if ($product instanceof Products && $product->getPokemonCard()) {
            $initialNumber = $product->getPokemonCard()->getNumber();
        }

        $builder
            ->add('pokemon_card_number', TextType::class, [
                'label' => 'Numéro de la carte Pokémon',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'data-pokemon-card-number-target' => 'numberInput',
                    'data-property-path' => 'pokemonCard.number'
                ],
                'data' => $initialNumber
            ])
            ->add('pokemonCard', EntityType::class, [
                'class' => PokemonCard::class,
                'choice_label' => 'name',
                'label' => 'Carte Pokémon',
                'placeholder' => 'Sélectionnez une extension d\'abord',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une carte.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                    'data-pokemon-card-target' => 'pokemonCardSelect', // Attribut pour JS
                ],
                'choices' => [], // Initialement vide, sera peuplé par JS
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre du produit'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un titre'
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
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
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Cartes à collectionner' => 'cards',
                    'Figurines' => 'figures',
                    'Jeux de société' => 'boardgames',
                    'Livres' => 'books',
                    'Vêtements' => 'clothing',
                    'Accessoires' => 'accessories',
                    'Autres' => 'others'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une catégorie'
                    ])
                ]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une quantité'
                    ]),
                    new PositiveOrZero([
                        'message' => 'La quantité doit être positive ou nulle'
                    ])
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un prix'
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être positif'
                    ])
                ]
            ])
            
            ->add('media', CollectionType::class, [
                'entry_type' => MediaType::class,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => ['class' => 'media-collection'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'product_form',
            'extensions' => [], // Option pour passer les extensions
        ]);
    }
} 