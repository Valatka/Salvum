<?php

namespace App\Transformers;

use App\Models\Task;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract {
    public function transform(Task $task) {
        return [
            'name' => $task->name,
            'description' => $task->description,
            'type' => $task->type,
            'status' => $task->status
        ];
    }
}

?>