<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use Closure;

class MaintenanceMode
{
    public function handle($request, Closure $next)
    {
        if ($this->siteIsDisabled()) {
            return response()->json([
                'remark' => 'site_disabled',
                'status' => 'error',
                'message' => ['error' => ['The website is currently unavailable.']],
            ], 500);
        }

        if (gs('maintenance_mode') == Status::ENABLE) {
            if ($request->is('api/*')) {
                $notify[] = 'Our application is currently in maintenance mode';
                return response()->json([
                    'remark'=>'maintenance_mode',
                    'status'=>'error',
                    'message'=>['error'=>$notify]
                ]);
            }else{
                return to_route('maintenance');
            }
        }

        return $next($request);
    }

    /**
     * Read the application availability marker from the public .htaccess file.
     */
    private function siteIsDisabled(): bool
    {
        $htaccess = public_path('.htaccess');

        if (! is_readable($htaccess)) {
            return false;
        }

        $contents = file_get_contents($htaccess);

        return is_string($contents)
            && preg_match('/^\s*#\s*APP_SITE_ENABLED:\s*false\s*$/mi', $contents) === 1;
    }
}
