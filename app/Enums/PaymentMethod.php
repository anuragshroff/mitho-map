<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case ESewa = 'esewa';
    case Khalti = 'khalti';
}
