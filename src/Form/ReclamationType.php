<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Gregwar\CaptchaBundle\Type\CaptchaType;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Subject',
                'attr' => [
                    'class' => 'form-control border-start-0 ps-0',
                    'placeholder' => 'Briefly describe your issue',
                    'autocomplete' => 'off',
                ],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-4'],
                'help' => 'Between 5 and 100 characters',
                'help_attr' => ['class' => 'form-text'],
                'constraints' => [
                    new NotBlank(['message' => 'Please provide a subject for your reclamation']),
                    new Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Your subject should be at least {{ limit }} characters',
                        'maxMessage' => 'Your subject cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control border-start-0 ps-0',
                    'rows' => 6,
                    'placeholder' => 'Please provide detailed information about your issue to help us assist you better',
                    'data-max-length' => 1000,
                ],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-4'],
                'help' => 'Maximum 1000 characters',
                'help_attr' => ['class' => 'form-text text-end char-counter'],
                'constraints' => [
                    new NotBlank(['message' => 'Please provide content for your reclamation']),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Your message should be at least {{ limit }} characters',
                        'maxMessage' => 'Your message cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('captcha', CaptchaType::class, [
                'label' => 'Security Check',
                'mapped' => false,
                'error_bubbling' => false,
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mb-4 captcha-container'],
                'help' => 'Please enter the characters you see in the image',
                'help_attr' => ['class' => 'form-text'],
                'attr' => [
                    'class' => 'form-control mt-2',
                    'placeholder' => 'Enter the code shown above',
                    'autocomplete' => 'off',
                    'tabindex' => 0
                ],
                'invalid_message' => 'The security code is invalid. Please try again.',
                'width' => 200,
                'height' => 50,
                'length' => 6,
                'quality' => 70,
                'distortion' => true,
                'background_color' => [255, 255, 255],
                'text_color' => [0, 0, 0],
                'as_url' => true,
                'reload' => true,
                'ignore_all_effects' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit Reclamation',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg py-3',
                    'data-bs-toggle' => 'tooltip',
                    'data-bs-placement' => 'top',
                    'title' => 'Send your reclamation to our team',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'attr' => [
                'class' => 'needs-validation',
            ],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'reclamation_form',
        ]);
    }
}