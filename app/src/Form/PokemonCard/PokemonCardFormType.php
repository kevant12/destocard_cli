<?php

namespace App\Form\PokemonCard;

use App\Entity\Extension;
use App\Entity\PokemonCard;
use App\Entity\Serie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Doctrine\ORM\EntityManagerInterface;

class PokemonCardFormType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la carte',
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro',
            ])
            ->add('rarityText', TextType::class, [
                'label' => 'Rareté',
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
            ])
            ->add('specialType', TextType::class, [
                'label' => 'Type spécial',
                'required' => false,
            ])
            ->add('subSerie', TextType::class, [
                'label' => 'Sous-série',
                'required' => false,
            ])
            ->add('isReversePossible', CheckboxType::class, [
                'label' => 'Version "Reverse" possible ?',
                'required' => false,
            ])
            ->add('apiId', TextType::class, [
                'label' => 'ID API (Optionnel)',
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de la carte (PNG, JPG)',
                'mapped' => false,
                'required' => false, // Rendre l'image facultative à l'édition
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/png', 'image/jpeg'],
                        'mimeTypesMessage' => 'Veuillez téléverser une image PNG ou JPG valide.',
                    ])
                ],
            ])
            ->add('serie', EntityType::class, [
                'class' => Serie::class,
                'choice_label' => 'name',
                'label' => 'Série',
                'placeholder' => 'Sélectionnez une série',
                'mapped' => false, // Ne pas mapper directement, géré via event listener
                'required' => true,
                'attr' => [
                    'data-action' => 'change->pokemon-card-form#onSerieChange'
                ]
            ])
            ->add('extension', EntityType::class, [
                'class' => Extension::class,
                'choice_label' => 'name',
                'label' => 'Extension',
                'placeholder' => 'Sélectionnez une extension',
                'choices' => [], // Sera peuplé dynamiquement
                'required' => true,
            ]);

        $builder->get('serie')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $serie = $form->getData();
                if ($serie) {
                    $this->addExtensionField($form->getParent(), $serie);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var PokemonCard|null $data */
                $data = $event->getData();
                $form = $event->getForm();

                if ($data && $data->getExtension()) {
                    $extension = $data->getExtension();
                    $serie = $extension->getSerie();
                    
                    $form->get('serie')->setData($serie);
                    $this->addExtensionField($form, $serie);
                    $form->get('extension')->setData($extension);
                } else {
                    // Pour un nouveau formulaire, on peut pré-remplir avec les extensions de la première série
                    $allSeries = $this->em->getRepository(Serie::class)->findBy([], ['name' => 'ASC']);
                    if (!empty($allSeries)) {
                        $this->addExtensionField($form, $allSeries[0]);
                    }
                }
            }
        );
    }
    
    /**
     * Ajoute le champ 'extension' au formulaire, peuplé en fonction de la série sélectionnée.
     *
     * @param \Symfony\Component\Form\FormInterface $form
     * @param Serie $serie
     */
    private function addExtensionField(\Symfony\Component\Form\FormInterface $form, Serie $serie): void
    {
        $extensions = $this->em->getRepository(Extension::class)->findBy(
            ['serie' => $serie],
            ['name' => 'ASC']
        );
        
        $form->add('extension', EntityType::class, [
            'class' => Extension::class,
            'choice_label' => 'name',
            'label' => 'Extension',
            'placeholder' => 'Sélectionnez une extension',
            'choices' => $extensions,
            'required' => true,
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PokemonCard::class,
        ]);
    }
} 