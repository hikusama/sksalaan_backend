<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SupabaseClient extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $url;
    protected $headers;
    protected $timeout;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL') . "/rest/v1";
        $this->timeout = env('SUPABASE_TIMEOUT', 10);
        $this->headers = [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
        ];
    }

    public function create($data)
    {
        try {
            Http::withHeaders($this->headers)
                ->timeout($this->timeout)
                ->post("{$this->url}/announcement", [
                    'what' => $data->what,
                    'when' => $data->when,
                    'where' => $data->where,
                    'who' => $data->who . ', '. $data->addresses,
                    'description' => $data->description,
                ]);

            return 1;
        } catch (RequestException $e) {
            return 2;
        } catch (Exception $e){
            return 3;
        }
    }

    public function delete($id)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout($this->timeout)
                ->delete("{$this->url}/announcement?id=eq.$id");

            return $response->json();
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Supabase not reachable',
                'message' => $e->getMessage()
            ], 503);
        }
    }
}
