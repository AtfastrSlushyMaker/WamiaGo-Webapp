<?php

namespace App\Form;

use App\Entity\BicycleStation;
use App\Entity\Enum\StationStatus;
use App\Entity\Location;
use App\Enum\BICYCLE_STATION_STATUS;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class BicycleStationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Station Name',
                'attr' => [
                    'placeholder' => 'Enter station name',
                    'class' => 'form-control'
                ]
            ])
            ->add('totalDocks', IntegerType::class, [
                'label' => 'Total Docking Spaces',
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => BICYCLE_STATION_STATUS::ACTIVE,
                    'Maintenance' => BICYCLE_STATION_STATUS::MAINTENANCE,
                    'Inactive' => BICYCLE_STATION_STATUS::INACTIVE,
                    'Disabled' => BICYCLE_STATION_STATUS::DISABLED
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'address',
                'required' => false,  // Make this field not required since we're handling it manually
                'placeholder' => 'Select a location or click on the map',
                'attr' => [
                    'class' => 'form-select'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BicycleStation::class,
        ]);
    }
}
