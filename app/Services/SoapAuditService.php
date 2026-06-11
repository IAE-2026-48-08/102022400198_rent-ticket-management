<?php
 
namespace App\Services;
 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
 
class SoapAuditService
{
    private string $soapUrl = 'https://iae-sso.virtualfri.id/soap/v1/audit';
    private string $teamId  = 'TEAM-08';
 
    public function sendAudit(array $ticketData, string $jwtToken): ?string
    {
        $logContent = json_encode($ticketData);
 
        $soapEnvelope = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$this->teamId}</iae:TeamID>
      <iae:ActivityName>TenantTicketCreated</iae:ActivityName>
      <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;
 
        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'text/xml',
                'Authorization' => 'Bearer ' . $jwtToken,
            ])->withBody($soapEnvelope, 'text/xml')
              ->post($this->soapUrl);
 
            $receiptNumber = $this->parseReceiptNumber($response->body());
 
            Log::info('SOAP Audit berhasil', ['receipt' => $receiptNumber]);
 
            return $receiptNumber;
 
        } catch (\Exception $e) {
            Log::error('SOAP Audit gagal: ' . $e->getMessage());
            return null;
        }
    }
 
    private function parseReceiptNumber(string $xmlResponse): ?string
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            $xml->registerXPathNamespace('iae', 'http://iae.central/audit');
            $nodes = $xml->xpath('//iae:ReceiptNumber');
            return isset($nodes[0]) ? (string) $nodes[0] : null;
        } catch (\Exception $e) {
            Log::error('Gagal parse ReceiptNumber: ' . $e->getMessage());
            return null;
        }
    }
}
 