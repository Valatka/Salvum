<?php

namespace App\Transformers;

use App\Models\Message;
use League\Fractal\TransformerAbstract;

class MessageTransformer extends TransformerAbstract {
    public function transform(Message $message) {
        return [
            'subject' => $message->subject,
            'message' => $message->message,
        ];
    }
}

?>