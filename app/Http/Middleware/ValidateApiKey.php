<?php

// app/Http/Middleware/ValidateApiKey.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $validKey = '102022400198'; 
        
        // Cek apakah header X-IAE-KEY ada dan sesuai
        if ($request->header('X-IAE-KEY') !== $validKey) {
            // Jika salah atau tidak ada, kembalikan 401 Unauthorized beserta format JSON
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. X-IAE-KEY tidak valid atau hilang.'
            ], 401);
        }

        return $next($request);
    }
}