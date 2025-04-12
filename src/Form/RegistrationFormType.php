<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Location;
use App\Enum\GENDER;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'First Name',
                'attr' => [
                    'placeholder' => 'Enter your first name',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your first name']),
                    new Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('lastName', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Last Name',
                'attr' => [
                    'placeholder' => 'Enter your last name',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your last name']),
                    new Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('email', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email']),
                    new Email(['message' => 'Please enter a valid email address'])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ])
                ]
            ])            
            ->add('phone_number', TextType::class, [
                'required' => true,
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => 'Enter your phone number',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your phone number']),
                ]
            ])
            ->add('profile_picture', TextType::class, [
                'required' => false,
                'label' => 'Profile Picture',
                'attr' => [
                    'placeholder' => 'Enter profile picture URL',
                    'class' => 'form-control',
                ],
            ])
            ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Date of Birth',
                'attr' => [
                    'placeholder' => 'Enter your date of birth',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your date of birth']),
                    new LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'The date of birth cannot be in the future'
                    ])
                ]
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'address',
                'placeholder' => 'Select a location',
                'required' => false,
                'label' => 'Location',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => 'MALE',
                    'Female' => 'FEMALE',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Gender',
                'required' => true,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}