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

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'annonce',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre ne peut pas être vide.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le prix doit être positif.']),
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new NotBlank(['message' => 'La quantité est obligatoire.']),
                    new PositiveOrZero(['message' => 'La quantité doit être positive.']),
                ],
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos de votre article (fichiers PNG, JPG)',
                'multiple' => true,
                'mapped' => false, // Ne pas mapper ce champ directement à une propriété de l'entité
                'required' => false,
                'constraints' => [
                    new All([ // Appliquer les contraintes à chaque fichier
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/png',
                                'image/jpeg',
                            ],
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