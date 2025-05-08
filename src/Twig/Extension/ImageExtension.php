<?php

namespace App\Twig\Extension;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('image_to_base64', [$this, 'imageToBase64'])
        ];
    }

    public function imageToBase64(string $path): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $fullPath = $projectDir . '/public/' . $path;

        if (!file_exists($fullPath)) {
            return '';
        }

        $imageData = file_get_contents($fullPath);
        $mimeType = mime_content_type($fullPath);
        
        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
}