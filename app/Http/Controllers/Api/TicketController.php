<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SoapAuditService;
use App\Services\RabbitMQService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function __construct(
        private SoapAuditService $soapService,
        private RabbitMQService $rabbitService
    ) {}

    /**
     * @OA\Get(
     * path="/api/v1/tickets",
     * operationId="getTicketsList",
     * tags={"Tickets"},
     * summary="Mengambil daftar tiket (IAE-T2 Standard)",
     * security={{"ApiKeyAuth": {}}},
     * @OA\Response(
     * response=200, 
     * description="Berhasil mengambil data",
     * @OA\JsonContent() 
     * )
     * )
     */
    public function index()
    {
        $tickets = Ticket::all();
        
        // Wrapper IAE-T2 (Format Sukses)
        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar tiket berhasil diambil',
            'data'    => $tickets,
        ], 200);
    }

    /**
     * @OA\Get(
     * path="/api/v1/tickets/{id}",
     * operationId="getTicketById",
     * tags={"Tickets"},
     * summary="Mengambil detail spesifik tiket",
     * security={{"ApiKeyAuth": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200, 
     * description="Berhasil menemukan tiket",
     * @OA\JsonContent()
     * ),
     * @OA\Response(
     * response=404, 
     * description="Tiket tidak ditemukan",
     * @OA\JsonContent()
     * )
     * )
     */
    public function show($id)
    {
        // Gunakan find() biasa, jangan findOrFail() agar kita bisa mengontrol format JSON error-nya
        $ticket = Ticket::find($id);

        if (!$ticket) {
            // Wrapper IAE-T2 (Format Error 404)
            return response()->json([
                'status'  => 'error',
                'message' => 'Data resource tidak ditemukan',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail tiket berhasil ditemukan',
            'data'    => $ticket,
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/v1/tickets",
     * operationId="storeTicket",
     * tags={"Tickets"},
     * summary="Menambahkan tiket keluhan baru",
     * security={{"ApiKeyAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"listing_id","contract_id","tenant_name","tenant_email","description"},
     * @OA\Property(property="listing_id", type="string", example="1"),
     * @OA\Property(property="contract_id", type="string", example="100"),
     * @OA\Property(property="tenant_name", type="string", example="Dawai"),
     * @OA\Property(property="tenant_email", type="string", example="dawai@telkom.id"),
     * @OA\Property(property="description", type="string", example="AC Bocor")
     * )
     * ),
     * @OA\Response(
     * response=201, 
     * description="Tiket berhasil dibuat",
     * @OA\JsonContent()
     * )
     * )
     */
    public function store(Request $request)
    {
        // Gunakan Validator manual agar format error 422/400 mengikuti standard IAE-T2
        $validator = Validator::make($request->all(), [
            'listing_id'   => 'required|string',
            'contract_id'  => 'required|string',
            'tenant_name'  => 'required|string',
            'tenant_email' => 'required|email',
            'description'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'data'    => $validator->errors()
            ], 400); // Pastikan kode status ini sesuai permintaan dosen, kadang 400 atau 422
        }

        // =============================================
        // STEP 3: Simpan tiket ke database
        // =============================================
        $ticket = Ticket::create([
            'listing_id'   => $request->listing_id,
            'contract_id'  => $request->contract_id,
            'tenant_name'  => $request->tenant_name,
            'tenant_email' => $request->tenant_email,
            'description'  => $request->description,
            'status'       => 'open',
        ]);

        // =============================================
        // STEP 4: Kirim SOAP Audit pakai M2M token
        // =============================================
        $receiptNumber = $this->soapService->sendAudit($ticket->toArray());

        if ($receiptNumber) {
            $ticket->update(['soap_receipt' => $receiptNumber]);
            $ticket->refresh();
        }

        // =============================================
        // STEP 5: Publish event ke RabbitMQ pakai M2M token
        // =============================================
        $this->rabbitService->publishTicketCreated($ticket->toArray());

        // Wrapper IAE-T2 (Format Created 201)
        return response()->json([
            'status'  => 'success',
            'message' => 'Tiket berhasil dibuat',
            'data'    => $ticket,
        ], 201);
    }
}