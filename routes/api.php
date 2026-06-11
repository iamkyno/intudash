
use Illuminate\Support\Facades\Route;

Route::post('webhooks/smsportal', [App\Http\Controllers\WebhookController::class, 'smsportal']);
