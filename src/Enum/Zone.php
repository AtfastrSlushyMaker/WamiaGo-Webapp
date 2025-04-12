<?php
namespace App\Enum;

enum Zone: string
{
    case NOT_SPECIFIED = '';
    case ARIANA = 'Ariana';
    case BEJA = 'Béja';                        
    case BEN_AROUS = 'Ben_Arous';
    case BIZERTE = 'Bizerte';
    case GABES = 'Gabès';
    case GAFSA = 'Gafsa';
    case JENDOUBA = 'Jendouba';
    case KAIROUAN = 'Kairouan';
    case KASSERINE = 'Kasserine';
    case KEBILI = 'Kebili';
    case KEF = 'Kef';
    case MAHDIA = 'Mahdia';
    case MANOUBA = 'Manouba';
    case MEDENINE = 'Medenine';
    case MONASTIR = 'Monastir';
    case NABEUL = 'Nabeul';
    case SFAX = 'Sfax';
    case SIDI_BOUZID = 'Sidi_Bouzid';
    case SILIANA = 'Siliana';
    case SOUSSE = 'Sousse';
    case TATAOUINE = 'Tataouine';
    case TOZEUR = 'Tozeur';
    case TUNIS = 'Tunis';
    case ZAGHOUAN = 'Zaghouan';

    public function getDisplayName(): string
    {
        return match($this) {
            self::NOT_SPECIFIED => 'Non spécifié',
            default => $this->value
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::NOT_SPECIFIED => '#6C757D',  // Gris
            self::ARIANA => '#4285F4',         // Bleu
            self::BEJA => '#34A853',           // Vert
            self::BEN_AROUS => '#FBBC05',      // Jaune
            self::BIZERTE => '#EA4335',        // Rouge
            self::GABES => '#AB47BC',          // Violet
            self::GAFSA => '#00ACC1',          // Cyan
            self::JENDOUBA => '#FF7043',       // Orange
            self::KAIROUAN => '#9E9E9E',       // Gris foncé
            self::KASSERINE => '#3949AB',      // Bleu indigo
            self::KEBILI => '#8D6E63',         // Marron
            self::KEF => '#43A047',            // Vert foncé
            self::MAHDIA => '#5E35B1',         // Violet foncé
            self::MANOUBA => '#1E88E5',        // Bleu clair
            self::MEDENINE => '#F4511E',       // Rouge orangé
            self::MONASTIR => '#6D4C41',       // Brun
            self::NABEUL => '#039BE5',         // Bleu ciel
            self::SFAX => '#00897B',           // Vert-bleu
            self::SIDI_BOUZID => '#D81B60',    // Rose
            self::SILIANA => '#8E24AA',        // Violet moyen
            self::SOUSSE => '#7CB342',         // Vert olive
            self::TATAOUINE => '#C0CA33',      // Jaune-vert
            self::TOZEUR => '#E53935',         // Rouge vermillon
            self::TUNIS => '#0097A7',          // Turquoise
            self::ZAGHOUAN => '#757575',       // Gris anthracite
        };
    }
}