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
use App\Form\DataTransformer\DataUriToUploadedFileTransformer;


class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Image ou vidéo',
                'required' => false,
                'attr' => [
                    'accept' => 'image/*,video/*',
                    'capture' => 'environment',
                ],
            ]);

        // Ajout du DataTransformer pour accepter les data URI (webcam/photo/vidéo)
        $builder->get('file')->addModelTransformer(new DataUriToUploadedFileTransformer());
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