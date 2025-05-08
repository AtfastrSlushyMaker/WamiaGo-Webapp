<?php

namespace App\Form;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Enum\BICYCLE_STATUS;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BicycleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get all possible BICYCLE_STATUS values
        $statusChoices = [];
        foreach (BICYCLE_STATUS::cases() as $status) {
            $statusChoices[ucfirst(strtolower(str_replace('_', ' ', $status->name)))] = $status->value;
        }

        $builder
            // Add the hidden field for bicycle ID
            ->add('idBike', HiddenType::class, [
                'mapped' => false,
                'data' => $options['bicycleId']  // Set the bicycleId data here
            ])
            ->add('battery_level', NumberType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.1
                ],
                'label' => 'Battery Level (%)',
            ])
            ->add('range_km', NumberType::class, [
                'attr' => [
                    'min' => 0,
                    'step' => 0.1
                ],
                'label' => 'Range (km)',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => BICYCLE_STATUS::cases(),
                'choice_label' => fn(BICYCLE_STATUS $choice) => ucfirst(strtolower(str_replace('_', ' ', $choice->name))),
                'choice_value' => fn(?BICYCLE_STATUS $choice) => $choice?->value,
                'label' => 'Status',
                'required' => true,
                'empty_data' => BICYCLE_STATUS::AVAILABLE,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('bicycleStation', EntityType::class, [
                'class' => BicycleStation::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'No Station',
                'label' => 'Station',
            ])
            ->add('last_updated', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'label' => 'Last Updated',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bicycle::class,
            'csrf_protection' => true,
            'bicycleId' => null,  // No need to bind this to the Bicycle entity
        ]);
    }
}
