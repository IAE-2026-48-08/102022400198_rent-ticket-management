<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/v1/tickets",
     * operationId="getTicketsList",
     * tags={"Tickets"},
     * summary="Mengambil daftar riwayat tiket maintenance",
     * security={{"ApiKeyAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Sukses mengambil daftar tiket",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Daftar riwayat tiket berhasil diambil"),
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="listing_id", type="integer", example=1),
     * @OA\Property(property="contract_id", type="integer", example=101),
     * @OA\Property(property="tenant_name", type="string", example="Dawai"),
     * @OA\Property(property="description", type="string", example="Atap bocor"),
     * @OA\Property(property="status", type="string", example="pending")
     * ))
     * )
     * ),
     * @OA\Response(response=401, description="Unauthorized (API Key tidak valid atau hilang)")
     * )
     */
    public function index()
    {
        $tickets = Ticket::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat tiket berhasil diambil',
            'data' => $tickets
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/v1/tickets",
     * operationId="storeTicket",
     * tags={"Tickets"},
     * summary="Menambah data baru saat tenant input tiket kerusakan",
     * security={{"ApiKeyAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"listing_id","contract_id","tenant_name","description"},
     * @OA\Property(property="listing_id", type="integer", example=1),
     * @OA\Property(property="contract_id", type="integer", example=101),
     * @OA\Property(property="tenant_name", type="string", example="Dawai"),
     * @OA\Property(property="description", type="string", example="Atap kamar utama bocor air merembes")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Tiket berhasil disimpan",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Tiket keluhan berhasil disimpan secara resmi"),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="listing_id", type="integer", example=1),
     * @OA\Property(property="contract_id", type="integer", example=101),
     * @OA\Property(property="tenant_name", type="string", example="Dawai"),
     * @OA\Property(property="description", type="string", example="Atap bocor")
     * )
     * )
     * ),
     * @OA\Response(response=400, description="Validasi gagal atau verifikasi service luar ditolak"),
     * @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
            'contract_id' => 'required|integer',
            'tenant_name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Jalur integrasi dimatikan sementara untuk tes lokal
        /*
        $urlListing = env('LISTING_SERVICE_URL') . "/api/v1/listings/" . $request->listing_id; 
        $responseListing = Http::withHeaders(['X-API-KEY' => 'KEY_RAFSAN'])->get($urlListing);

        $urlContract = env('CONTRACT_SERVICE_URL') . "/api/v1/contracts/" . $request->contract_id; 
        $responseContract = Http::withHeaders(['X-API-KEY' => 'KEY_AKHDAN'])->get($urlContract);

        if (!$responseListing->successful() || !$responseContract->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Proses ditolak. Unit properti tidak ditemukan atau masa kontrak sewa sudah tidak aktif.'
            ], 400);
        }
        */

        $ticket = Ticket::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tiket keluhan berhasil disimpan secara resmi',
            'data' => $ticket
        ], 201);
    }

    /**
     * @OA\Get(
     * path="/api/v1/tickets/{id}",
     * operationId="getTicketById",
     * tags={"Tickets"},
     * summary="Mengambil data spesifik satu tiket keluhan",
     * security={{"ApiKeyAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * description="ID Tiket",
     * required=true,
     * in="path",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Sukses mengambil data spesifik tiket",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Detail tiket berhasil ditemukan"),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="tenant_name", type="string", example="Dawai"),
     * @OA\Property(property="status", type="string", example="pending")
     * )
     * )
     * ),
     * @OA\Response(response=404, description="Tiket tidak ditemukan")
     * )
     */
    public function show(int $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket keluhan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail tiket berhasil ditemukan',
            'data' => $ticket
        ], 200);
    }
}