use App\Jobs\CheckServerJob;
use App\Models\Server;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Server::query()->each(function ($server) {
        CheckServerJob::dispatch($server);
    });
})->everyFiveMinutes();