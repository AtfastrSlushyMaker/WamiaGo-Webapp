<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use App\Entity\Location;
use App\Enum\GENDER;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your first name']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'First name must be at least {{ limit }} characters long',
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s\'-]+$/',
                        'message' => 'First name can only contain letters, spaces, hyphens and apostrophes'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your last name']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Last name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s\'-]+$/',
                        'message' => 'Last name can only contain letters, spaces, hyphens and apostrophes'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email']),
                    new Email([
                        'message' => 'Please enter a valid email address',
                        'mode' => 'strict'
                    ]),
                    new Length([
                        'max' => 180,
                        'maxMessage' => 'Email cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('phone_number', TelType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your phone number']),
                    new Regex([
                        'pattern' => '/^\+?[1-9][0-9]{7,14}$/',
                        'message' => 'Please enter a valid international phone number'
                    ])
                ]
            ])
            ->add('dateOfBirth', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control datepicker',
                    'placeholder' => 'Select date of birth',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new NotNull(['message' => 'Please enter your date of birth']),
                    new LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'Date of birth cannot be in the future'
                    ]),
                    new GreaterThan([
                        'value' => '-120 years',
                        'message' => 'Please enter a valid date of birth'
                    ])
                ]
            ])            ->add('gender', ChoiceType::class, [
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Male' => 'MALE',
                    'Female' => 'FEMALE'
                ],
                'constraints' => [
                    new NotNull(['message' => 'Please select your gender'])
                ]
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'required' => true,
                'choice_label' => 'address',
                'placeholder' => 'Select your location',
                'constraints' => [
                    new NotNull(['message' => 'Please select your location'])
                ]
            ]);        // Add the date transformer
        $builder->get('dateOfBirth')
            ->addModelTransformer(new CallbackTransformer(
                function ($date) {
                    if ($date instanceof \DateTimeInterface) {
                        return $date->format('Y-m-d');
                    }
                    return $date;
                },
                function ($dateString) {
                    if (empty($dateString)) {
                        return null;
                    }
                    try {
                        return new \DateTime($dateString);
                    } catch (\Exception $e) {
                        return null;
                    }
                }
            ));
            
        // Add gender transformer
        $builder->get('gender')
            ->addModelTransformer(new CallbackTransformer(
                function ($genderEnum) {
                    // Transform from GENDER enum to string
                    if ($genderEnum instanceof GENDER) {
                        return $genderEnum->value;
                    }
                    return null;
                },
                function ($genderString) {
                    // Transform from string to GENDER enum
                    if (!empty($genderString)) {
                        try {
                            return GENDER::from($genderString);
                        } catch (\ValueError $e) {
                            return null;
                        }
                    }
                    return null;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default'],
        ]);
    }
}