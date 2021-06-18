<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Todo()
 * @method static static Closed()
 * @method static static Hold()
 */
final class TaskStatus extends Enum
{
    const Todo = 'todo';
    const Closed = 'closed';
    const Hold = 'hold';
}
