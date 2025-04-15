<?php

namespace App\Form;

use App\Entity\Announcement;
use App\Enum\Zone;
use App\Form\DataTransformer\ZoneTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

class TransporterAnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Service Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter announcement title'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'The announcement title is required'
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Title must contain at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Service Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Enter detailed description'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'The announcement content is required'
                    ]),
                    new Length([
                        'min' => 20,
                        'max' => 2000,
                        'minMessage' => 'Content must contain at least {{ limit }} characters',
                        'maxMessage' => 'Content cannot exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('zone', ChoiceType::class, [
                'label' => 'Service Area',
                'choices' => array_combine(
                    array_map(fn(Zone $zone) => $zone->getDisplayName(), Zone::cases()),
                    array_map(fn(Zone $zone) => $zone->value, Zone::cases())
                ),
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Select a zone',
                'constraints' => [
                    new NotNull([
                        'message' => 'Please select a service zone'
                    ])
                ]
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Activate this service',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'constraints' => [
                    new NotNull([
                        'message' => 'Please indicate whether the announcement is active or not'
                    ])
                ]
            ]);

        $builder->get('zone')->addModelTransformer(new ZoneTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}