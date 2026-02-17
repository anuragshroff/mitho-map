<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Restaurant = 'restaurant';
    case Driver = 'driver';
    case Admin = 'admin';
}
