<?php

namespace App;

enum PaymentProvider: string
{
    case Manual = 'manual';
    case Midtrans = 'midtrans';
    case Xendit = 'xendit';
}
