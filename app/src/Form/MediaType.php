<?php

namespace App\Form;

use App\Entity\Media;
use App\Form\DataTransformer\DataUriToUploadedFileTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Image (JPG, PNG, GIF)',
                'mapped' => true, // Indique que ce champ est directement mappé à une propriété de l'entité Media
                'required' => false,
                'property_path' => 'file',
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF)',
                    ])
                ],
            ]);

        $builder->get('webcamImage')
            ->addModelTransformer(new DataUriToUploadedFileTransformer());

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
