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
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Définit le formulaire d'inscription pour les nouveaux utilisateurs.
 * Ce formulaire est lié à l'entité `Users`.
 * Chaque champ est configuré avec des contraintes de validation pour assurer l'intégrité des données.
 */
class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour la civilité (M. ou Mme)
            ->add('civility', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => 'Mr',
                    'Madame' => 'Mme'
                ],
                'expanded' => true, // Affiche comme des boutons radio pour une meilleure UX
                'multiple' => false, // Un seul choix possible
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner votre civilité'])
                ]
            ])
            // Champ pour le prénom
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre prénom']),
                    new Length([
                        'min' => 2, 'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            // Champ pour le nom de famille
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre nom']),
                    new Length(['min' => 2, 'max' => 100])
                ]
            ])
            // Champ pour l'email avec validation de format
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre email']),
                    new Email(['message' => 'Veuillez saisir un email valide'])
                ]
            ])
            // Champ pour le numéro de téléphone avec validation par expression régulière
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '06 xx xx xx xx'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre numéro de téléphone']),
                    new Regex([
                        'pattern' => '/^[0-9\s\-\+\(\)]{10,20}$/',
                        'message' => 'Veuillez saisir un numéro de téléphone valide'
                    ])
                ]
            ])
            // Champ pour le mot de passe
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre mot de passe'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Ce mot de passe est trop faible. Il doit contenir des majuscules, minuscules, chiffres et symboles.'
                    ])
                ]
            ])
            // Case à cocher pour les conditions générales
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales d\'utilisation',
                'mapped' => false, // Ce champ n'est pas lié à l'entité Users, il sert uniquement à la validation.
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter nos conditions pour continuer.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class, // Lie ce formulaire à l'entité Users.
            'csrf_protection' => true,      // Active la protection CSRF.
            'csrf_field_name' => '_token',  // Nom du champ CSRF qui sera ajouté au formulaire.
            'csrf_token_id'   => 'register_form', // ID unique pour le token CSRF, pour s'assurer que ce token n'est valide que pour ce formulaire.
        ]);
    }
} 