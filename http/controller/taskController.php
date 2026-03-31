namespace TaskManagementAPI\http\controller;

use TaskManagementAPI\model\task;
use Illuminate\Http\Request;
use TaskManagementAPI\http\requests\storeTask;
use TaskManagementAPI\http\requests\updateStatus;
use Carbon\Carbon;

class taskController extends Controller
{
    // Create task
    public function store(storeTask $request)
    {
        // Check duplicate title and due_date
        $exists = Task::where('title', $request->title)
            ->where('due_date', $request->due_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Task with same title and due date exists'
            ], 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => 'pending'
        ]);

        return response()->json($task, 201);
    }

    // list tasks
    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('due_date', 'asc')
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found'
            ]);
        }

        return response()->json($tasks);
    }

    // Update status
    public function updateStatus(updateStatus $request, $id)
    {
        $task = Task::findOrFail($id);

        $current = $task->status;
        $next = $request->status;

        $validTransitions = [
            'pending' => 'in_progress',
            'in_progress' => 'done'
        ];

        if (!isset($validTransitions[$current]) || $validTransitions[$current] !== $next) {
            return response()->json([
                'error' => 'Invalid status transition'
            ], 422);
        }

        $task->update(['status' => $next]);

        return response()->json($task);
    }

    // Delete tasks
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        if ($task->status !== 'done') {
            return response()->json([
                'error' => 'Only completed tasks can be deleted'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ]);
    }

    // DAILY REPORT
    public function report(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 422);
        }

        $priorities = ['high', 'medium', 'low'];
        $statuses = ['pending', 'in_progress', 'done'];

        $summary = [];

        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {
                $count = Task::where('priority', $priority)
                    ->where('status', $status)
                    ->whereDate('due_date', $date)
                    ->count();

                $summary[$priority][$status] = $count;
            }
        }

        return response()->json([
            'date' => $date,
            'summary' => $summary
        ]);
    }
}