<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\GENDER;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Location;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\CallbackTransformer;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Your name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Your name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'Phone Number',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[2459][0-9]{7}$/',
                        'message' => 'Please enter a valid Tunisian phone number',
                    ]),
                ],
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'address',
                'placeholder' => 'Select your location',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select your location'
                    ])
                ]
            ])
            ->add('date_of_birth', TextType::class, [
                'label' => 'Date of Birth',
                'attr' => [
                    'class' => 'datepicker',
                    'autocomplete' => 'off',
                    'placeholder' => 'Select date of birth',
                ],
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\LessThanOrEqual('today', message: 'Date of birth cannot be in the future'),
                    new Assert\GreaterThan('-120 years', message: 'Please enter a valid date of birth'),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Male' => 'MALE',
                    'Female' => 'FEMALE'
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select your gender'])
                ]
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF)',
                        'maxSizeMessage' => 'The file is too large. Maximum size allowed is {{ limit }} {{ suffix }}'
                    ])
                ]
            ]);

        // Add the date transformer
        $builder->get('date_of_birth')
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
        ]);
    }
} 