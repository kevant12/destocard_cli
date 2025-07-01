<?php

namespace App\Form\PokemonCard;

use App\Entity\PokemonCard;
use App\Entity\Extension;
use App\Entity\Serie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PokemonCardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('serie', EntityType::class, [
                'class' => Serie::class,
                'choice_label' => 'name',
                'label' => 'Série',
                'placeholder' => '--- Sélectionner une série ---',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'data-pokemon-card-target' => 'serieSelect',
                    'data-action' => 'change->pokemon-card#onSerieChange',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une série'])
                ]
            ])
            ->add('extension', EntityType::class, [
                'class' => Extension::class,
                'choice_label' => 'name',
                'label' => 'Extension',
                'placeholder' => '--- Sélectionner une extension ---',
                'required' => true,
                'attr' => [
                    'data-pokemon-card-target' => 'extensionSelect',
                    'data-action' => 'change->pokemon-card#onExtensionChange',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une extension'])
                ]
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro de la carte',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 001, 025, 150/151',
                    'data-pokemon-card-target' => 'numberInput',
                    'data-action' => 'blur->pokemon-card#checkCardExists',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de la carte est requis']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le numéro ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la carte',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Pikachu, Charizard',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la carte est requis']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('apiId', TextType::class, [
                'label' => 'ID API (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ID de l\'API externe',
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'ID API ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => true,
                'choices' => [
                    'Pokémon' => 'Pokemon',
                    'Dresseur' => 'Trainer',
                    'Énergie' => 'Energy',
                ],
                'placeholder' => '--- Sélectionner une catégorie ---',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une catégorie'])
                ]
            ])
            ->add('specialType', TextType::class, [
                'label' => 'Type spécial (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: EX, GX, V, VMAX',
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le type spécial ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('raritySymbol', ChoiceType::class, [
                'label' => 'Symbole de rareté',
                'required' => true,
                'choices' => [
                    'Commune (●)' => 'common',
                    'Peu commune (◆)' => 'uncommon',
                    'Rare (★)' => 'rare',
                    'Rare Holo (★)' => 'rare_holo',
                    'Ultra Rare (★★)' => 'ultra_rare',
                    'Secrète (★★★)' => 'secret',
                ],
                'placeholder' => '--- Sélectionner une rareté ---',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une rareté'])
                ]
            ])
            ->add('rarityText', TextType::class, [
                'label' => 'Texte de rareté',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Common, Uncommon, Rare',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le texte de rareté est requis']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le texte de rareté ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('isReversePossible', CheckboxType::class, [
                'label' => 'Version reverse possible',
                'required' => false,
                'help' => 'Cochez si cette carte peut exister en version reverse/holo'
            ])
            ->add('subSerie', TextType::class, [
                'label' => 'Sous-série (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Promo, Special',
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La sous-série ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de la carte',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WebP, GIF)',
                        'maxSizeMessage' => 'L\'image ne peut pas dépasser 5MB'
                    ])
                ]
            ]);

        // Événement pour pré-remplir les champs lors de l'édition
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $pokemonCard = $event->getData();
            $form = $event->getForm();

            if ($pokemonCard && $pokemonCard->getExtension()) {
                $extension = $pokemonCard->getExtension();
                $serie = $extension->getSerie();

                // Pré-remplir la série
                $form->get('serie')->setData($serie);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PokemonCard::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'pokemon_card_form'
        ]);
    }
}

