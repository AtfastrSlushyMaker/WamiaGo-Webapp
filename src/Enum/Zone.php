<?php
namespace App\Enum;

enum Zone: string
{case NOT_SPECIFIED = '';
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
}