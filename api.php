use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::prefix('tasks')->group(function () {
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/', [TaskController::class, 'index']);
    Route::patch('/{id}/status', [TaskController::class, 'updateStatus']);
    Route::delete('/{id}', [TaskController::class, 'destroy']);
    Route::get('/report', [TaskController::class, 'report']);
});