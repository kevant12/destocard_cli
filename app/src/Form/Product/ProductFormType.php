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
 * Formulaire de création et modification de produits
 * 
 * Ce formulaire gère :
 * - Les informations de base du produit (titre, prix, description)
 * - Les détails spécifiques (numéro, catégorie)
 * - L'upload multiple d'images avec validation
 * - La validation côté serveur et client
 * 
 * Utilisé dans ProductController pour new() et edit()
 */
class ProductFormType extends AbstractType
{
    /**
     * Construction du formulaire avec tous les champs nécessaires
     * 
     * @param FormBuilderInterface $builder Pour construire le formulaire
     * @param array $options Options de configuration du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ titre - obligatoire et principal
            ->add('title', TextType::class, [
                'label' => 'Nom / Titre de l\'annonce',
                'constraints' => [new NotBlank(['message' => 'Le titre ne peut pas être vide.'])],
                'attr' => ['placeholder' => 'Ex: Dracaufeu']
            ])
            
            // Champ numéro - optionnel, pour les cartes à collectionner
            ->add('number', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 4/102']
            ])
            
            // Champ catégorie - aide à la classification
             ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Carte Pokémon']
            ])
            
            // Champ description - optionnel mais recommandé pour les ventes
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            
            // MoneyType est un champ spécialisé pour les montants monétaires
            // Il gère automatiquement la devise et la validation des prix
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR', // Définit la devise en euros
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le prix doit être positif.']),
                ],
            ])
            
            // FileType permet l'upload de fichiers avec validation avancée
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos de votre article (fichiers PNG, JPG)',
                'multiple' => true, // Permet de sélectionner plusieurs fichiers à la fois
                'mapped' => false,  // TRÈS IMPORTANT: ce champ n'est pas lié à une propriété de l'entité Products
                                    // Nous le traiterons manuellement dans le contrôleur pour uploader les fichiers
                'required' => false, // Le champ n'est pas obligatoire (ex: pour une modification sans changer les images)
                'constraints' => [
                    // 'All' permet d'appliquer une contrainte à chaque fichier uploadé individuellement
                    new All([
                        new File([
                            'maxSize' => '5M', // Taille maximale par fichier
                            'mimeTypes' => ['image/png', 'image/jpeg'], // Types MIME autorisés
                            'mimeTypesMessage' => 'Veuillez téléverser une image valide (PNG ou JPG).',
                        ])
                    ])
                ],
            ]);
    }

    /**
     * Configuration des options du formulaire
     * 
     * @param OptionsResolver $resolver Pour définir les options par défaut
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Lie ce formulaire à l'entité Products pour l'hydratation automatique
            'data_class' => Products::class,
        ]);
    }
}