<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\GENDER;
use App\Enum\ROLE;
use App\Enum\ACCOUNT_STATUS;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Location;
use Symfony\Component\Form\CallbackTransformer;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter a name',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter an email',
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[2459][0-9]{7}$/',
                        'message' => 'Please enter a valid Tunisian phone number',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'required' => $options['require_password'],
                'mapped' => false,
                'constraints' => $options['require_password'] ? [
                    new Assert\NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Your password must be at least {{ limit }} characters long',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
                        'message' => 'Password must contain at least one letter and one number',
                    ]),
                ] : [],
            ])
            ->add('role', EnumType::class, [
                'class' => ROLE::class,
                'label' => 'Role',
                'required' => true,
                'choice_label' => function($choice, $key, $value) {
                    return ucfirst(strtolower($value));
                },
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select a role',
                    ]),
                ],
            ])
            ->add('account_status', EnumType::class, [
                'class' => ACCOUNT_STATUS::class,
                'label' => 'Account Status',
                'required' => true,
                'choice_label' => function($choice, $key, $value) {
                    return ucfirst(strtolower($value));
                },
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select an account status',
                    ]),
                ],
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'address',
                'placeholder' => 'Select location',
                'required' => false,
            ])
            ->add('date_of_birth', TextType::class, [
                'label' => 'Date of Birth',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker',
                    'autocomplete' => 'off',
                    'placeholder' => 'Select date of birth',
                ],
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'Date of birth cannot be in the future',
                    ]),
                    new Assert\GreaterThan([
                        'value' => '-120 years',
                        'message' => 'Please enter a valid date of birth',
                    ]),
                ],
            ])
            ->add('gender', EnumType::class, [
                'class' => GENDER::class,
                'label' => 'Gender',
                'required' => false,
                'placeholder' => 'Select gender',
                'choice_label' => function($choice, $key, $value) {
                    return ucfirst(strtolower($value));
                },
            ])
            ->add('is_verified', null, [
                'label' => 'Email Verified',
                'required' => false,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
            'csrf_protection' => true,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
    }
} 