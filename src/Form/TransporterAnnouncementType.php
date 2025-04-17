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
            'required' => false,
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Enter announcement title'
            ]
           
        ])
        ->add('content', TextareaType::class, [
            'label' => 'Service Description',
            'required' => false,
            'attr' => [
                'class' => 'form-control',
                'rows' => 6,
                'placeholder' => 'Enter detailed description'
            ]
            
        ])
        ->add('zone', ChoiceType::class, [
            'label' => 'Service Area',
            'required' => false,
            'placeholder' => '— Choisir une zone —',
            'choices' => array_combine(
                array_map(fn(Zone $zone) => $zone->getDisplayName(), Zone::cases()),
                array_map(fn(Zone $zone) => $zone->value, Zone::cases())
            ),
            'attr' => [
                'class' => 'form-select'
            ],
            'placeholder' => 'Select a zone'
            
        ])
        ->add('status', CheckboxType::class, [
            'label' => 'Activate this service',
            'required' => false,
            'attr' => ['class' => 'form-check-input'],
            'label_attr' => ['class' => 'form-check-label']
            
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