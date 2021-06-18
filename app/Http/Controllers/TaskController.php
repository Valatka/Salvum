<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Association;
use App\Transformers\TaskTransformer;
use App\Transformers\StatusTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use BenSampo\Enum\Rules\EnumValue;

class TaskController extends Controller {


    /**
     * @var Manager
     */
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $taskTransformer;

    /**
     * @var StatusTransformer
     */
    private $statusTransformer;

    /**
     * Create a new TaskController instance.
     * 
     * @param Manager $fractal
     * @param TaskTransformer $taskTransformer
     * @param StatusTransformer $statusTransformer
     * 
     * @return void
     */
    public function __construct(Manager $fractal, TaskTransformer $taskTransformer, StatusTransformer $statusTransformer) {
        $this->middleware('auth:api');
        $this->fractal = $fractal;
        $this->taskTransformer = $taskTransformer;
        $this->statusTransformer = $statusTransformer;
    }

    /**
     * Attaches users to a task
     * 
     * @param array $users
     * @param int $owner
     * @param int $id
     * 
     * @return [type]
     */
    private function attach(array $users, int $owner, int $id) {
        $toAttach = [];
        foreach ($users as $user) {
            if ($user === $owner) {
                continue;
            }
            $toAttach += ['user' => $user, 'task' => $id];
        }
        if (count($toAttach) > 0) {
            Association::create($toAttach);
        }
    }

    /**
     * Creates a new task
     * 
     * @param Request $request
     * 
     * @return string JSON
     */
    public function create(Request $request) {
        $data = $request->validate([
            'name' => ['required', 'max:255'], 
            'description' => ['required', 'max:4096'], 
            'type' => ['required', new EnumValue(TaskType::class)], 
            'status' => ['required', new EnumValue(TaskStatus::class)],
            'attach' => ['array'],
            'attach.*' => ['integer', 'exists:users,id']
        ]);
        $owner = auth()->user()->id;
        $data += ['owner' => $owner];

        $task = Task::create($data);

        if (array_key_exists('attach', $data)) {
            $this->attach($data['attach'], $owner, $task->id);
        }

        return response()->json($this->statusTransformer->transform(true), 201);
    }

    /**
     * Updates a given task
     * 
     * @param Request $request
     * 
     * @return string JSON
     */
    public function update(Request $request) {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'name' => ['max:255'],
            'description' => ['max:4096'],
            'type' => [new EnumValue(TaskType::class)],
            'status' => [new EnumValue(TaskStatus::class)],
            'attach' => ['array'],
            'attach.*' => ['integer', 'exists:users,id']
        ]);

        $owner = auth()->user()->id;
        $status = Task::where('id', $data['id'])->where('owner', $owner)->update(array_diff_key($data, array_flip(['attach'])));
        if ($status) {
            if (array_key_exists('attach', $data)) {
                $this->attach($data['attach'], $owner, $data['id']);
            }
            return response()->json($this->statusTransformer->transform(true));
        }
        return response()->json($this->statusTransformer->transform(false), 404);
    }

    /**
     * Deletes a given task
     * 
     * @param int $id
     * 
     * @return string JSON
     */
    public function delete(int $id) {
        $owner = auth()->user()->id;
        $status = Task::where('owner', $owner)->where('id', $id)->delete();
        if ($status) {
            return response()->json($this->statusTransformer->transform(true));
        }
        return response()->json($this->statusTransformer->transform(false), 404);
        
    }

    /**
     * Shows a paginated list of tasks owned by / attached to a user
     * 
     * @return string JSON
     */
    public function show() {
        $user = auth()->user()->id;
        $tasksPaginator = Task::leftJoin('associations', 'tasks.id', '=', 'associations.task')->
            where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->paginate(10);
        $tasks = new Collection($tasksPaginator->items(), $this->taskTransformer);
        $tasks->setPaginator(new IlluminatePaginatorAdapter($tasksPaginator));
        $tasks = $this->fractal->createData($tasks);

        return response()->json($tasks->toArray());
    }

    /**
     * Changes the status of a given task to TaskStatus::Closed
     * 
     * @param Request $request
     * 
     * @return string JSON
     */
    public function close(Request $request) {
        $data = $request->validate([
            'task' => ['required']
        ]);
        $user = auth()->user()->id;
        $status = Task::leftJoin('associations', 'tasks.id', '=', 'associations.task')->
            where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->where('tasks.id', $data['task'])->update(['status' => TaskStatus::Closed]);
        if ($status) {
            return response()->json($this->statusTransformer->transform(true));
        }
        return response()->json($this->statusTransformer->transform(false), 404);
    }

    /**
     * Returns information about a specific task
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return string JSON
     */
    public function info(Request $request, int $id) {
        validator($request->route()->parameters(), [
            'id' => ['required', 'integer']
        ])->validate();

        $user = auth()->user()->id;
        $task = Task::leftJoin('associations', 'tasks.id', '=', 'associations.task')
            ->where(function ($query) use ($user) {
                $query->where('owner', $user)->orWhere('user', $user);
            })->find($id);
        if (!$task) {
            return response()->json($this->statusTransformer->transform(false), 404);
        }
        return response()->json($this->taskTransformer->transform($task));

    }
}

?>