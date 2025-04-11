<?php

namespace App\Form;

use App\Entity\Announcement;
use App\Enum\Zone;
use App\Form\DataTransformer\ZoneTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class TransporterAnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de votre annonce...'
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Décrivez votre service de transport...'
                ]
            ])
            ->add('zone', EnumType::class, [
                'class' => Zone::class,
                'choice_label' => fn (Zone $zone) => $zone->getDisplayName(),
                'placeholder' => 'Sélectionnez une zone',
                'required' => false,
                'empty_data' => Zone::NOT_SPECIFIED,
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Activer cette annonce',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label']
            ]);

        // Ajoutez le transformer directement dans le FormType
        $builder->get('zone')->addModelTransformer(new ZoneTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}