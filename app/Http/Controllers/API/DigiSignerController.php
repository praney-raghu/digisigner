<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DigiSignerController extends Controller
{
    public function createDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors()
            ], 422);
        }

        $file = $request->file('document');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.digisigner_api_url')."documents",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERPWD => config('app.digisigner_api_key').":".config('app.digisigner_api_key'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file' => new \CURLFILE($file->getRealPath(), $file->getClientMimeType(), $file->getClientOriginalName())),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpStatus === 200) {
            return response()->json([
                'success' => true,
                'data' => json_decode($response)
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'data' => $error
            ], $httpStatus);
        }
    }

    public function sendSignatureRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors()
            ], 422);
        }

        $body = [
            "documents" => [
                [
                    "document_id" => $request->input('document_id'),
                    "signers" => [
                        [
                            "email" => $request->input('email')
                        ]
                    ]
                ]
            ]
        ];

        $body = json_encode($body);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.digisigner_api_url').'signature_requests',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERPWD => config('app.digisigner_api_key').":".config('app.digisigner_api_key'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpStatus === 200) {
            return response()->json([
                'success' => true,
                'data' => json_decode($response)
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'data' => $error
            ], $httpStatus);
        }
    }
}
