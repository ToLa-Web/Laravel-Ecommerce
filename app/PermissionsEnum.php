<?php

namespace App;

enum PermissionsEnum: string
{
    case ApproveVendors = 'approveVendors';
    case SellProducts = 'sellProducts';
    case BuyProducts = 'buyProducts';
}
