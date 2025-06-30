<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('civility', ChoiceType::class, [
            'label' => 'Civilité',
            'choices' => [
                'Monsieur' => 'Mr',
                'Madame' => 'Mme'
            ],
            'expanded' => true,
            'multiple' => false,
            'attr' => [
                'class' => 'form-check-input'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez sélectionner votre civilité'
                ])
            ]
        ])
        ->add('firstname', TextType::class, [
            'label' => 'Prénom',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Votre prénom'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez saisir votre prénom'
                ]),
                new Length([
                    'min' => 2,
                    'max' => 100,
                    'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                    'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                ])
            ]
        ])
        ->add('lastname', TextType::class, [
            'label' => 'Nom',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Votre nom'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez saisir votre nom'
                ]),
                new Length([
                    'min' => 2,
                    'max' => 100,
                    'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                    'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                ])
            ]
        ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre email'
                    ]),
                    new Email([
                        'message' => 'Veuillez saisir un email valide'
                    ])
                ]
            ])
           
            
           
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '06 xx xx xx xx'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre numéro de téléphone'
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9\s\-\+\(\)]{10,20}$/',
                        'message' => 'Veuillez saisir un numéro de téléphone valide'
                    ])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre mot de passe'
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'register_form'
        ]);
    }
} 