<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpCfdi\Credentials\Credential;
use Illuminate\Support\Facades\File;

class CertificadoController extends Controller
{
    public function firmar_unica_vez(Request $request){
        $request->validate([
            'cer'           =>  'required',
            'key'           =>  'required',
            'contrasena'    =>  'required'
        ]);
    
        $content_cer = File::get($request->file('cer')->getRealPath());
        $content_key = File::get($request->file('key')->getRealPath());
    
        $passPhrase = $request->contrasena;
        
        try {
            
            $fiel = Credential::openFiles(
                'file://'.$request->cer->getRealPath(),
                'file://'.$request->key->getRealPath(),
                $passPhrase
            );
    
            $certificado = $fiel->certificate();

            if (!$certificado->validOn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El certificado no es válido para la fecha actual.'
                ], 422);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Certificado válido.',
                'rfc' => $certificado->rfc(),
                'nombre' => $certificado->legalName(),
                'numero_serie' => $certificado->serialNumber(),
                'valido_desde' => $certificado->validFrom(),
                'valido_hasta' => $certificado->validTo(),
            ]);
    
    
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña incorrecta o error al procesar el certificado.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
