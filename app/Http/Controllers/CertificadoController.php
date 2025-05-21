<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpCfdi\Credentials\Credential;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use PhpCfdi\Credentials\Exceptions\InvalidCertificateException;
use PhpCfdi\Credentials\Exceptions\SignatureException;

class CertificadoController extends Controller
{
    public function firmar_unica_vez(Request $request){
        $request->validate([
            'archivo'       =>  'required',
            'cer'           =>  'required',
            'key'           =>  'required',
            'contrasena'    =>  'required'
        ]);
    
        $content_cer = File::get($request->file('cer')->getRealPath());
        $content_key = File::get($request->file('key')->getRealPath());
        $content_pdf = File::get($request->file('archivo')->getRealPath());
    
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

            // Validamos si la firma es una firma real, ya sea fiel 
            if(!$fiel->isFiel()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El certificado no es FIEL válido.'
                ], 400);
            }
                
            $firma = $fiel->sign($content_pdf);
            $firma_b64 = base64_encode($firma);
    
            $pdf = new Fpdi();
            $pdf->SetFont('helvetica','',6);

            $pagecount = $pdf->setSourceFile('file://'.$request->archivo->path());

    
            for ($n = 1; $n <= $pagecount; $n++) {
                $pdf->AddPage();
                $tplId = $pdf->importPage($n);
                $pdf->useTemplate($tplId, null, null, null, 210, true);
            }
    
            $tplId = $pdf->importPage($pagecount);
            $pdf->useTemplate($tplId, null, null, null, 210, true);
            $pdf->SetXY(10,185);
            $text = "La siguiente Firma Electrónica corresponde a un archivo con el nombre {$request->file('archivo')->getClientOriginalName()} el cual consta de {$pagecount} páginas originales más las páginas que pudiesen ser agregadas debido a la extensión de la firma.\n";
            $datos = "Esta firma corresponde a {$certificado->legalName()} con RFC: {$certificado->rfc()}\n";
            $text = utf8_decode( $text );
            $pdf->Write(4, $text.$datos.$firma_b64);

            $pdfContent = $pdf->Output('archivo_firmado.pdf', 'S');

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="archivo_firmado.pdf"');

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ], 500);
        }
    }
}

// return response()->json([
            //     'success' => true,
            //     'message' => 'Certificado válido.',
            //     'rfc' => $certificado->rfc(),
            //     'nombre' => $certificado->legalName(),
            //     'valido_desde' => $certificado->validFrom(),
            //     'valido_hasta' => $certificado->validTo(),
            // ]);
            
            // 'numero_serie' => $certificado->serialNumber(),