<?php

namespace App\Form\Product;

use App\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image_url', TextType::class, [
                'label' => 'URL de l\'image',
                'required' => true,
                'attr' => [
                    'placeholder' => 'https://...'
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'URL ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Url([
                        'message' => 'Veuillez saisir une URL valide.'
                    ])
                ]
            ])
            ->add('video_url', TextType::class, [
                'label' => 'URL de la vidéo (YouTube, etc.)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://youtube.com/...'
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'URL ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Url([
                        'message' => 'Veuillez saisir une URL valide.'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'media_form'
        ]);
    }
} 