<?php

namespace App\Form\Type;

use App\Enum\Zone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

class ZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            // Transform from Zone to string/null
            function ($zoneEnum) {
                // For null values
                if (null === $zoneEnum) {
                    return '';
                }
                
                // For Zone enum values
                return $zoneEnum->value;
            },
            // Transform from string/null to Zone
            function ($zoneString) {
                // Don't transform empty values
                if (empty($zoneString)) {
                    return null;
                }
                
                // Match string to enum case
                foreach (Zone::cases() as $zone) {
                    if ($zone->value === $zoneString) {
                        return $zone;
                    }
                }
                
                return null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_combine(
                array_map(fn(Zone $zone) => $zone->value, Zone::cases()),
                array_map(fn(Zone $zone) => $zone->value, Zone::cases())
            ),
            'placeholder' => 'Sélectionnez une zone',
            'invalid_message' => 'Veuillez sélectionner une zone valide',
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}