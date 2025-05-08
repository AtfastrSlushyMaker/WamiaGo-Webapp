<?php

namespace App\Form\DataTransformer;

use App\Enum\Zone;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ZoneTransformer implements DataTransformerInterface
{
    public function transform($value): ?string
    {
        if ($value instanceof Zone) {
            return $value->value;
        }
        return null;
    }

    public function reverseTransform($value): ?Zone
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Zone::from($value);
        } catch (\ValueError $e) {
            throw new TransformationFailedException('Veuillez sélectionner une zone valide');
        }
    }
}