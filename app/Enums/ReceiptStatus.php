<?php

namespace App\Enums;

enum ReceiptStatus: string
{
    case WAIT = 'wait';
    case FAIL = 'fail';
    case DONE = 'done';
}
