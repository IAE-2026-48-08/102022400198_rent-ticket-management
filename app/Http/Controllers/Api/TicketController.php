<?php
 
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 
use App\Models\Ticket;
use App\Services\SoapAuditService;
use App\Services\RabbitMQService;
use Illuminate\Http\Request;
 
class TicketController extends Controller
{
    public function __construct(
        private SoapAuditService $soapService,
        private RabbitMQService $rabbitService
    ) {}
 
    // GET /api/v1/tickets
    public function index()
    {
        $tickets = Ticket::all();
        return response()->json([
            'success' => true,
            'data'    => $tickets,
        ]);
    }
 
    // GET /api/v1/tickets/{id}
    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $ticket,
        ]);
    }
 
    // POST /api/v1/tickets
    public function store(Request $request)
    {
        $request->validate([
            'listing_id'   => 'required|string',
            'contract_id'  => 'required|string',
            'tenant_name'  => 'required|string',
            'tenant_email' => 'required|email',
            'description'  => 'required|string',
        ]);
 
        $jwtToken = $request->sso_token;
 
        // STEP 1 & 2: Cross-check Service Listing & Kontrak
        // TODO: Aktifkan kembali setelah service teman siap
        // $listingResponse  = Http::get("http://URL_LISTING/api/v1/listings/{$request->listing_id}");
        // $contractResponse = Http::get("http://URL_KONTRAK/api/v1/contracts/{$request->contract_id}");
 
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
        // STEP 4: Kirim SOAP Audit → simpan soap_receipt
        // =============================================
        $receiptNumber = $this->soapService->sendAudit($ticket->toArray(), $jwtToken);
 
        if ($receiptNumber) {
            $ticket->update(['soap_receipt' => $receiptNumber]);
            $ticket->refresh();
        }
 
        // =============================================
        // STEP 5: Publish event ke RabbitMQ
        // =============================================
        $this->rabbitService->publishTicketCreated($ticket->toArray(), $jwtToken);
 
        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuat',
            'data'    => $ticket,
        ], 201);
    }
}
 