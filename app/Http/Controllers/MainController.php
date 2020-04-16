<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

define('ENDPOINT_API', getenv('ENDPOINT_API'));
define('CERT', file_get_contents(config('cert.path') . '/cert.pem'));

class MainController extends Controller
{

    /**
     * Retrieve the user for the given ID.
     *
     * @param  int  $id
     * @return Response
     */
    public function main(Request $request)
    {

        $this->validate($request, [
            'servicekey' => 'required',
            'service' => 'required',
            'token' => 'required',
            'startdate' => 'required',
            'products' => '',
        ]);
        return 'OK';
    }

    public function getConsent(Request $request)
    {

        //Check if the requests meets the requirements
        $this->validate($request, [
            'servicekey' => 'required',
            'service' => 'required',
            'grantType' => 'required',
            'validUntil' => 'required',
            'yourClientBankId' => 'required',
        ]);

        //UUID
        $uuid = Uuid::uuid4()->toString();

        //Read certificate
        $certFile = openssl_x509_read(CERT);
        $certData = openssl_x509_parse($certFile);

        //Info from certificate
        $keyId = "SN={$certData['serialNumberHex']},CA=CN={$certData['issuer']['CN']},OU={$certData['issuer']['OU']},O={$certData['issuer']['O']},C={$certData['issuer']['C']}";

        //Body request
        $validUntil = explode("-", $request['validUntil']);
        $body = [
            "access" => [
                "availableAccounts" => "allAccounts",
                "allPsd2" => "allAccounts"
            ],
            "recurringIndicator" => true,
            "validUntil" =>  implode("-", array_reverse($validUntil)),
            "frequencyPerDay" => 999,
            "combinedServiceIndicator" => false
        ];

        //Serialize and get hash from body
        $serializedBody = json_encode($body);
        $base64Body = $serializedBody;
        $hashedBody = hash('sha256', $base64Body);
        $base64Hash = 'SHA-256=' . base64_encode($hashedBody);
        
        //Signing process
        $rawSignature = "";
        $signatureStr = "";

        //Extract public and private key from cert
        $privKey = openssl_pkey_get_private(CERT);
        $pubKey = openssl_pkey_get_public(CERT);

        $signString = "digest: {$base64Hash}\nx-request-id: {$uuid}";

        //Sign signing string
        openssl_sign($signString, $rawSignature, $privKey, OPENSSL_ALGO_SHA256);

        //Check if signed string is valid
        $check = openssl_verify($signString, $rawSignature, $pubKey, OPENSSL_ALGO_SHA256);

        if($check){
            $signatureStr = base64_encode($rawSignature);

            //signature
            $signatureArr = [
                'keyId' => $keyId,
                'algorithm' => 'SHA-256',
                'headers' => 'digest x-request-id',
                'signature' => $signatureStr
            ];

            $signature = "";
            foreach($signatureArr as $key => $val){
                $signature .= $key . '="' . $val . '",';
            }

            $fullCert = "";
            openssl_x509_export(CERT, $fullCert);

            $tppSignatureCertificate = str_replace([
                '-----BEGIN CERTIFICATE-----',
                '-----END CERTIFICATE-----',
                "\r\n",
                "\n",
            ], [
                '',
                '',
                "\n",
                ''
            ], $fullCert);

            $headers = [
                'Content-Type: application/json',
                'Digest: ' . $base64Hash,
                'X-Request-ID: ' . $uuid,
                'Signature: ' . rtrim($signature, ','),
                'Authorization: Bearer 6yBnsqnMQQ',
                'TPP-Signature-Certificate: ' . $tppSignatureCertificate
            ];

            $ch = curl_init(ENDPOINT_API . '/v1/consents');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $serializedBody);
            $result = curl_exec($ch);

            ///TODO: DEBUG ONLY
            echo(curl_getinfo($ch)['request_header']);

            if($result){
                var_dump($result);
            }else{
                var_dump(curl_error($ch));
            }

            ///////////////////

            exit;

            $response = [
                "message" => "OK",
                "code" => 200,
                "session_id" => '123',
                "additional_info" => [
                    "account_id" => 563675,
                    "description" => "Contract's Name",
                    "session_id" => '123',
                    "token" => getenv('TOKEN')
                ]
            ];

        }else{
            $response = 'Fatal error while signing';
        }

        return response()->json($response);
    }

    public function initiatePayment(Request $request)
    {
        return response()->json(['name' => 'Abigail', 'state' => 'CA']);
    }

    public function getPaymentStatus(Request $request)
    {
        return response()->json(['name' => 'Abigail', 'state' => 'CA']);
    }
}