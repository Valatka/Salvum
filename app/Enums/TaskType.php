<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Basic()
 * @method static static Advanced()
 * @method static static Expert()
 */
final class TaskType extends Enum
{
    const BASIC = 'basic';
    const ADVANCED = 'advanced';
    const EXPERT = 'expert';
}
