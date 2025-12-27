<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\StokOpname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StokOpnameController extends Controller
{
    /**
     * Cari aset dari QR string
     */
    public function findByQr(Request $request)
    {
        $request->validate([
            'qr' => 'required|string',
        ]);

        $qr = trim($request->qr);

        $aset = Aset::where('kode_scan', $qr)

            ->orWhere('id', $qr)
            ->first();

        if (!$aset) {
            return response()->json([
                'found' => false,
                'message' => 'Aset tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'aset' => [
                'id'         => $aset->id,
                'nama_aset'  => $aset->nama_aset ?? $aset->nama ?? 'Aset',
                'merk_kode'  => $aset->merk_kode ?? $aset->merk ?? '-',
                'status'     => $aset->status ?? 'OK',
            ],
        ]);
    }

    /**
     * Decode QR dari foto upload
     */
    public function decodeUpload(Request $request)
{
    $request->validate([
        'image' => 'required|image|max:4096',
    ]);

    $imgPath = $request->file('image')->getRealPath();

    $qrcode = new \Zxing\QrReader($imgPath);
    $decodedText = $qrcode->text();

    if (!$decodedText) {
        return response()->json([
            'found' => false,
            'message' => 'QR tidak terbaca',
        ], 422);
    }

    // sanitasi dasar
    $decodedText = trim($decodedText);

    // Jika QR berisi JSON, coba ambil field kode_scan atau id
    $possibleKeys = [];
    if ($json = json_decode($decodedText, true)) {
        if (is_array($json)) {
            // tambah kemungkinan langsung
            if (isset($json['kode_scan'])) {
                $possibleKeys[] = trim((string) $json['kode_scan']);
            }
            if (isset($json['code'])) {
                $possibleKeys[] = trim((string) $json['code']);
            }
            if (isset($json['id'])) {
                $possibleKeys[] = trim((string) $json['id']);
            }
            // ambil semua string values sebagai cadangan
            foreach ($json as $v) {
                if (is_scalar($v)) {
                    $possibleKeys[] = trim((string) $v);
                }
            }
        }
    }

    // selalu coba decodedText sendiri sebagai prioritas
    array_unshift($possibleKeys, $decodedText);

    // hapus duplikat & kosong
    $possibleKeys = array_values(array_filter(array_unique($possibleKeys), fn($v) => $v !== ''));

    $aset = null;
    foreach ($possibleKeys as $key) {
        // coba kode_scan dulu
        $aset = Aset::where('kode_scan', $key)->first();
        if ($aset) {
            $matchedKey = $key;
            break;
        }
        // lalu coba id (numeric)
        if (ctype_digit($key)) {
            $aset = Aset::where('id', $key)->first();
            if ($aset) {
                $matchedKey = $key;
                break;
            }
        }
    }

    return response()->json([
        'qr'    => $decodedText,
        'found' => (bool) $aset,
        'matched' => $matchedKey ?? null,
        'aset'  => $aset ? [
            'id'         => $aset->id,
            'nama_aset'  => $aset->nama_aset ?? $aset->nama ?? 'Aset',
            'merk_kode'  => $aset->merk_kode ?? $aset->merk ?? '-',
            'status'     => $aset->status ?? 'OK',
        ] : null,
        'message' => $aset ? null : 'Aset tidak ditemukan',
    ]);
    }

}