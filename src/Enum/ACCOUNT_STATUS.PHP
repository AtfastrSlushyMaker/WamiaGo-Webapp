<?php
namespace App\Enum;
enum ACCOUNT_STATUS: string
{
    case ACTIVE = 'ACTIVE';
    case BANNED = 'BANNED';
    case DEACTIVATED = 'DEACTIVATED';
}