<?php

namespace App\Form\Product;

use App\Entity\Products;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Définit le formulaire pour la création et la modification d'un produit.
 */
class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Nom / Titre de l\'annonce',
                'constraints' => [new NotBlank(['message' => 'Le titre ne peut pas être vide.'])],
                'attr' => ['placeholder' => 'Ex: Dracaufeu']
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 4/102']
            ])
             ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Carte Pokémon']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            // MoneyType est un champ spécialisé pour les montants monétaires.
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR', // Définit la devise
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le prix doit être positif.']),
                ],
            ])
            // FileType permet l'upload de fichiers.
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos de votre article (fichiers PNG, JPG)',
                'multiple' => true, // Permet de sélectionner plusieurs fichiers.
                'mapped' => false,  // TRÈS IMPORTANT: ce champ n'est pas lié à une propriété de l'entité Products.
                                    // Nous le traiterons manuellement dans le contrôleur pour uploader les fichiers.
                'required' => false, // Le champ n'est pas obligatoire (ex: pour une modification sans changer les images).
                'constraints' => [
                    // 'All' permet d'appliquer une contrainte à chaque fichier uploadé.
                    new All([
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/png', 'image/jpeg'],
                            'mimeTypesMessage' => 'Veuillez téléverser une image valide (PNG ou JPG).',
                        ])
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
        ]);
    }
}