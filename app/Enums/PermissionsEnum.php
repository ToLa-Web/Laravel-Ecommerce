<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    case ApproveVendors = 'approveVendors';
    case SellProducts = 'sellProducts';
    case BuyProducts = 'buyProducts';
}
