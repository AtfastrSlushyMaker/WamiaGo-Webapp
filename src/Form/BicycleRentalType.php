<?php

namespace App\Form;

use App\Entity\BicycleRental;
use App\Entity\User;
use App\Entity\BicycleStation;
use App\Entity\Bicycle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class BicycleRentalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addEntityFields($builder);
        $this->addTimeFields($builder);
        $this->addMeasurementFields($builder);
        
        // Add additional fields if editing an existing rental
        if ($options['is_edit'] && $builder->getData()->getStartTime()) {
            $this->addEditModeFields($builder);
        }
    }
    
    private function addEntityFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName();
                },
                'placeholder' => 'Choose a user',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a user',
                    ]),
                ],
            ])
            ->add('bicycle', EntityType::class, [
                'class' => Bicycle::class,
                'choice_label' => function (Bicycle $bicycle) {
                    return 'Bicycle #' . $bicycle->getIdBike();
                },
                'placeholder' => 'Choose a bicycle',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a bicycle',
                    ]),
                ],
            ])
            ->add('startStation', EntityType::class, [
                'class' => BicycleStation::class,
                'choice_label' => function (BicycleStation $station) {
                    return $station->getName() ?? $station->getId();
                },
                'placeholder' => 'Choose a start station',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a start station',
                    ]),
                ],
            ])
            ->add('endStation', EntityType::class, [
                'class' => BicycleStation::class,
                'choice_label' => function (BicycleStation $station) {
                    return $station->getName() ?? $station->getId();
                },
                'placeholder' => 'Choose an end station',
                'required' => false,
            ]);
    }
    
    private function addTimeFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'label' => 'Start Time',
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a start time',
                    ]),
                ],
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'End Time',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
            ]);
    }
    
    private function addMeasurementFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('distanceKm', NumberType::class, [
                'label' => 'Distance (km)',
                'required' => true,
                'scale' => 1,
                'html5' => true,
                'attr' => [
                    'step' => '0.1',
                    'min' => '0',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the distance',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Distance must be greater than or equal to 0',
                    ]),
                ],
            ])
            ->add('batteryUsed', NumberType::class, [
                'label' => 'Battery Used (%)',
                'required' => true,
                'scale' => 1,
                'html5' => true,
                'attr' => [
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '100',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the battery used',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Battery used must be greater than or equal to 0',
                    ]),
                    new LessThanOrEqual([
                        'value' => 100,
                        'message' => 'Battery used cannot exceed 100%',
                    ]),
                ],
            ])
            ->add('cost', NumberType::class, [
                'label' => 'Estimated Cost (TND)',
                'required' => true,
                'scale' => 3,
                'html5' => true,
                'attr' => [
                    'step' => '0.001',
                    'min' => '0',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the estimated cost',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Cost must be greater than or equal to 0',
                    ]),
                ],
            ]);
    }
    
    private function addEditModeFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('distanceKm', NumberType::class, [
                'label' => 'Distance (km)',
                'mapped' => false,
                'required' => false,
                'scale' => 1,
                'html5' => true,
                'attr' => [
                    'step' => '0.1',
                    'min' => '0',
                ],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Distance must be greater than or equal to 0',
                    ]),
                ],
            ])
            ->add('batteryUsed', NumberType::class, [
                'label' => 'Battery Used (%)',
                'mapped' => false,
                'required' => false,
                'scale' => 1,
                'html5' => true,
                'attr' => [
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '100',
                ],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Battery used must be greater than or equal to 0',
                    ]),
                    new LessThanOrEqual([
                        'value' => 100,
                        'message' => 'Battery used cannot exceed 100%',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BicycleRental::class,
            'is_edit' => false,
        ]);
    }
}
