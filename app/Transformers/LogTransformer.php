<?php

namespace App\Transformers;

use App\Models\Log;
use League\Fractal\TransformerAbstract;

class LogTransformer extends TransformerAbstract {
    public function transform(Log $log) {
        return [
            'message' => $log->message_id,
            'accessed_at' => $log->created_at,
            'user' => $log->user_id
        ];
    }
}

?>