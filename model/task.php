namespace TaskManagementAPI\model;

use Illuminate\Database\Eloquent\Model;

class Task extends model
{
    protected $fillable = [
        'title',
        'due_date',
        'priority',
        'status'
    ];
}