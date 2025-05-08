<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'Description is required']),
                    new Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'Description must be at least {{ limit }} characters',
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Describe your goods...'
                ]
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Transport Date',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Date is required'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'validation_groups' => ['Default']
        ]);
    }
}