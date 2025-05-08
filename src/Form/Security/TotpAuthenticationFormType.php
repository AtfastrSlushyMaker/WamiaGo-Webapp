<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class TotpAuthenticationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Authentication Code',
                'attr' => [
                    'autocomplete' => 'one-time-code',
                    'autofocus' => true,
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'placeholder' => 'Enter your 6-digit code',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the authentication code',
                    ]),
                    new Length([
                        'min' => 6,
                        'max' => 6,
                        'exactMessage' => 'The authentication code must be exactly {{ limit }} digits',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Verify',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'security',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'two_factor_totp';
    }
} 