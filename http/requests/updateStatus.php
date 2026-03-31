namespace TaskManagementAPI\http\requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateStatus extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'in_progress', 'done'])],
        ];
    }
}