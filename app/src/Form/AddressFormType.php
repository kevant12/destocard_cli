<?php

namespace App\Form;

use App\Entity\Addresses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Définit le formulaire pour créer et modifier une entité `Addresses`.
 * Chaque champ correspond à une propriété de l'entité et bénéficie
 * des contraintes de validation directement définies dans cette dernière.
 */
class AddressFormType extends AbstractType
{
    /**
     * Construit le formulaire champ par champ.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Le champ `number` est un simple champ texte.
            ->add('number', TextType::class, [
                'label' => 'Numéro',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 12, 12bis, 12ter...',
                    'maxlength' => 10
                ],
                'help' => 'Numéro de rue, appartement, etc.'
            ])
            ->add('street', TextType::class, [
                'label' => 'Rue',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Rue de la Paix',
                    'maxlength' => 255
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Paris',
                    'maxlength' => 100
                ]
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 75001',
                    'maxlength' => 10,
                    'pattern' => '[0-9]{5}'
                ],
                'help' => 'Code postal français (5 chiffres)'
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: France',
                    'maxlength' => 100
                ],
                'data' => 'France' // Valeur par défaut
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'adresse',
                'choices' => [
                    // Le label visible par l'utilisateur est la clé, la valeur stockée est la valeur.
                    'Domicile' => Addresses::TYPE_HOME,
                    'Facturation' => Addresses::TYPE_BILLING,
                    'Livraison' => Addresses::TYPE_SHIPPING,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => 'Choisir le type',
                'help' => 'À quoi servira cette adresse ?',
                'required' => true
            ]);
    }

    /**
     * Configure les options globales du formulaire.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Lie ce formulaire à l'entité Addresses. Symfony s'occupe de l'hydratation.
            'data_class' => Addresses::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'address_form'
        ]);
    }
} 