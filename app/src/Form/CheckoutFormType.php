<?php

namespace App\Form;

use App\Entity\Addresses;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class CheckoutFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('deliveryAddress', EntityType::class, [
                'class' => Addresses::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('a')
                        ->where('a.users = :user')
                        ->andWhere('a.type = :type')
                        ->setParameter('user', $user)
                        ->setParameter('type', Addresses::TYPE_SHIPPING);
                },
                'choice_label' => function(Addresses $address) {
                    return sprintf('%s %s, %s %s, %s', 
                        $address->getNumber(), 
                        $address->getStreet(), 
                        $address->getZipCode(), 
                        $address->getCity(), 
                        $address->getCountry()
                    );
                },
                'placeholder' => 'Choisissez une adresse de livraison',
                'label' => 'Adresse de livraison',
                'required' => true,
                'expanded' => true, // Afficher comme des boutons radio
            ])
            ->add('deliveryMethod', ChoiceType::class, [
                'label' => 'Mode de livraison',
                'choices' => [
                    'Standard (5.00 €)' => 'standard',
                    'Express (10.00 €)' => 'express',
                ],
                'expanded' => true, // Afficher comme des boutons radio
                'multiple' => false,
                'required' => true,
            ])
            ->add('shippingCost', HiddenType::class, [
                'mapped' => false, // Ce champ n'est pas mappé à l'entité
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null, // Définir l'option 'user' avec une valeur par défaut null
            // Configure your form options here
        ]);

        $resolver->setRequired(['user']); // Rendre l'option 'user' obligatoire
    }
}
