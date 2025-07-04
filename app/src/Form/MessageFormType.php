<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use App\Entity\Messages;

/**
 * Définit le formulaire pour l'envoi d'un message.
 * Il est utilisé dans le contexte d'une conversation.
 */
class MessageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ principal pour le contenu du message.
            ->add('content', TextareaType::class, [
                'label' => false, // On cache le label pour un design plus épuré.
                'attr' => ['placeholder' => 'Écrivez votre message...'],
                'constraints' => [
                    new NotBlank(['message' => 'Le message ne peut pas être vide.']),
                    new Length([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Votre message doit contenir au moins {{ limit }} caractère.',
                        'maxMessage' => 'Votre message ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            // Le bouton de soumission.
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Messages::class,
            'csrf_protection' => true,
            'csrf_token_id'   => 'message_item',
        ]);
    }
}
