<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Task;
use App\Models\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Transformers\StatusTransformer;
use App\Transformers\MessageTransformer;
use App\Transformers\LogTransformer;

class MessageController extends Controller {

    /**
     * @var Manager
     */
    private $fractal;

    /**
     * @var StatusTransformer
     */
    private $statusTransformer;

    /**
     * @var MessageTransformer
     */
    private $messageTransformer;

    /**
     * @var LogTransformer
     */
    private $logTransformer;

    /**
     * Create a new MessagesController instance
     * 
     * @param Manager $fractal
     * @param StatusTransformer $statusTransformer
     * @param MessageTransformer $messageTransformer
     * @param LogTransformer $logTransformer
     * 
     * @return void
     */
    public function __construct(Manager $fractal, StatusTransformer $statusTransformer, MessageTransformer $messageTransformer, LogTransformer $logTransformer) {
        $this->middleware('auth:api');
        $this->fractal = $fractal;
        $this->statusTransformer = $statusTransformer;
        $this->messageTransformer = $messageTransformer;
        $this->logTransformer = $logTransformer;
    }
    

    /**
     * Creates a new message
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        $data = $request->validate([
            'subject' => ['required', 'max:255'],
            'message' => ['required', 'max:4096'],
            'task' => ['required', 'integer'] 
        ]);

        $user = auth()->user()->id;

        $task = Task::leftJoin('associations', 'tasks.id', '=', 'associations.task')->
            where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->find($data['task']);

        if (!$task) {
            return response()->json($this->statusTransformer->transform(false), 404);
        }
        $data = $data + ['owner' => $user];
        Message::create($data);
        return response()->json($this->statusTransformer->transform(true), 201);
    }

    /**
     * Updates a specified message
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request) {
        $data = $request->validate([
            'subject' => ['max:255'],
            'message' => ['max:4096'],
            'task' => ['integer'] 
        ]);
        $owner = auth()->user()->id;
        $status = Message::where('owner', $owner)->update($data);
        if ($status) {
            return response()->json($this->statusTransformer->transform(true));
        } else {
            return response()->json($this->statusTransformer->transform(false), 404);
        }
    }

    /**
     * Deletes a specified message
     * 
     * @param int $id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, int $id) {
        validator($request->route()->parameters(), [
            'id' => ['required', 'integer']
        ])->validate();
        $owner = auth()->user()->id;
        $query = Message::where('owner', $owner)->where('id', $id);
        $status = $query->update(['task' => NULL, 'owner' => NULL]);
        if ($status) {
            Message::where('owner', NULL)->delete();
            return response()->json($this->statusTransformer->transform(true));
        }
        return response()->json($this->statusTransformer->transform(false), 404);
    }

    /**
     * Returns a paginated list of all messages for a specific task
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id) {
        validator($request->route()->parameters(), [
            'id' => ['required', 'integer']
        ])->validate();
        $user = auth()->user()->id;
        $messagesPaginator = Message::leftJoin('associations', 'messages.task', '=', 'associations.task')
            ->where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->where('messages.task', $id)->paginate(10);
        $messages = new Collection($messagesPaginator->items(), $this->messageTransformer);
        $messages->setPaginator(new IlluminatePaginatorAdapter($messagesPaginator));
        $messages = $this->fractal->createData($messages);

        return response()->json($messages->toArray());
    }

    /**
     * Shows information aboutu the specified message
     * 
     * @param int $id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function info(Request $request, int $id) {
        validator($request->route()->parameters(), [
            'id' => ['required', 'integer']
        ])->validate();
        $user = auth()->user()->id;

        $message = Message::leftJoin('associations', 'messages.task', '=', 'associations.task')
            ->where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->find($id);
        if (!$message) {
            return response()->json($this->statusTransformer->transform(false), 404);
        }
        Log::create(['message_id' => $id, 'task_id' => $message->task, 'user_id' => $user]);
        return response()->json($this->messageTransformer->transform($message));
    }

    /**
     * Shows paginated list of access logs for specified task
     * 
     * @param int $id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLog(Request $request, int $id) {
        validator($request->route()->parameters(), [
            'id' => ['required', 'integer']
        ])->validate();
        $user = auth()->user()->id;
        $logsPaginator = Log::leftJoin('associations', 'logs.task_id', '=', 'associations.task')
            ->leftJoin('tasks', 'logs.task_id', '=', 'tasks.id')
            ->where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->where('task_id', $id)->paginate(10);

        $logs = new Collection($logsPaginator->items(), $this->logTransformer);
        $logs->setPaginator(new IlluminatePaginatorAdapter($logsPaginator));
        $logs = $this->fractal->createData($logs);

        return response()->json($logs->toArray());

    }


}
