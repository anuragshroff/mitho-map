<?php

namespace App\Enums;

enum KitchenOrderTicketStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case InPreparation = 'in_preparation';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
