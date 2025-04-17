<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Entity\Response;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Response Content',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Enter your response here',
                    'data-max-length' => 1000,
                ],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-3'],
                'help' => 'Between 10 and 1000 characters',
                'help_attr' => ['class' => 'form-text'],
                'constraints' => [
                    new NotBlank(['message' => 'The response content cannot be empty']),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'The response content should be at least {{ limit }} characters',
                        'maxMessage' => 'The response content cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Response Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-3'],
                'constraints' => [
                    new NotNull(['message' => 'Please provide a date']),
                ],
            ])
            ->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
                'choice_label' => function (Reclamation $reclamation) {
                    return $reclamation->getId_reclamation() . ' - ' . $reclamation->getTitle();
                },
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'Reclamation',
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-3'],
                'placeholder' => 'Select a reclamation',
                'constraints' => [
                    new NotNull(['message' => 'Please select a reclamation']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Response',
                'attr' => [
                    'class' => 'btn btn-primary mt-3',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Response::class,
            'attr' => [
                // Remove novalidate attribute
                'class' => 'needs-validation',
            ],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'response_form',
        ]);
    }
}
