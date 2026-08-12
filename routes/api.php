use App\Http\Controllers\Api\AsistenciaApiController;
use Illuminate\Support\Facades\Route;

Route::post('/asistencias/marcar', [AsistenciaApiController::class, 'marcarAsistencia']);