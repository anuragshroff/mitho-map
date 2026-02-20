<?php

namespace App\Enums;

enum OrderConversationType: string
{
    case UserDriver = 'user_driver';
    case UserAdmin = 'user_admin';
    case AdminDriver = 'admin_driver';
}
