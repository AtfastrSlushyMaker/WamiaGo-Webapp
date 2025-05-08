<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\GENDER;
use App\Enum\ROLE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter full name'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a name']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Name should be at least {{ limit }} characters',
                        'maxMessage' => 'Name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter email address'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email address']),
                ],
            ])
            ->add('phone_number', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter phone number'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ]);
            
        if (!$isEdit) {
            // For new users, password is required
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Enter password'
                    ],
                    'label_attr' => [
                        'class' => 'form-label'
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a password']),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Password should be at least {{ limit }} characters',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Confirm password'
                    ],
                    'label_attr' => [
                        'class' => 'form-label'
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
                'mapped' => true,
            ]);
        } else {
            // For existing users, password is optional
            $builder->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Leave blank to keep current password'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Password should be at least {{ limit }} characters',
                    ]),
                ],
            ]);
        }
            
        $builder
            ->add('role', ChoiceType::class, [
                'label' => 'Role',
                'choices' => $this->getRoleChoices(),
                'attr' => [
                    'class' => 'form-control'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ])
            ->add('account_status', ChoiceType::class, [
                'label' => 'Account Status',
                'choices' => $this->getAccountStatusChoices(),
                'attr' => [
                    'class' => 'form-control'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'required' => false,
                'choices' => $this->getGenderChoices(),
                'attr' => [
                    'class' => 'form-control'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Verified Account',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'user_form',
        ]);
    }
    
    private function getRoleChoices(): array
    {
        $roles = [];
        foreach (ROLE::cases() as $role) {
            $roles[$role->value] = $role->value;
        }
        return $roles;
    }
    
    private function getAccountStatusChoices(): array
    {
        $statuses = [];
        foreach (ACCOUNT_STATUS::cases() as $status) {
            $statuses[$status->value] = $status->value;
        }
        return $statuses;
    }
    
    private function getGenderChoices(): array
    {
        $genders = [];
        foreach (GENDER::cases() as $gender) {
            $genders[$gender->value] = $gender->value;
        }
        return $genders;
    }
}