<?php

namespace App\Transformers;

use App\Models\Task;
use League\Fractal\TransformerAbstract;

class StatusTransformer extends TransformerAbstract {
    public function transform($status) {
        return [
            'success' => $status,
        ];
    }
}

?>