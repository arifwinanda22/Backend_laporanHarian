<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aset;
use Illuminate\Http\Request;

class AsetController extends Controller
{
    // GET: Tampilkan Semua
    public function index()
    {
        $asets = Aset::orderBy('created_at', 'desc')->get();
        return response()->json($asets);
    }

    // POST: Tambah Data Baru
    public function store(Request $request)
    {
        // 1. Validasi (Input dari Frontend pakai format camelCase)
        $request->validate([
            'assetName'        => 'required|string',
            'brandCode'        => 'required|string|unique:asets,kode_aset',
            'category'         => 'required|string',
            // Hapus spasi setelah koma agar validasi akurat
            'jumlah'           => 'nullable|integer|min:0',
            'status'           => 'required|in:Aktif,Tidak Aktif',
            'barcodeUpdateLog' => 'nullable|date',
        ]);

        try {
            // 2. Simpan ke Database (Format snake_case sesuai tabel migration)
            $aset = Aset::create([
                'nama_aset'           => $request->assetName,
                'kode_aset'           => $request->brandCode,
                'kategori'            => $request->category,
                'jumlah'              => $request->jumlah,
                'status'              => $request->status,
                'tanggal_log_barcode' => $request->barcodeUpdateLog,
            ]);

            return response()->json([
                'message' => 'Data Aset berhasil ditambahkan!',
                'data'    => $aset
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    // GET: Detail Satu Aset
    public function show($id)
    {
        $aset = Aset::find($id);
        if (!$aset) return response()->json(['message' => 'Aset tidak ditemukan'], 404);
        return response()->json($aset);
    }

    // PUT: Update Data
    public function update(Request $request, $id)
    {
        $aset = Aset::find($id);
        if (!$aset) return response()->json(['message' => 'Aset tidak ditemukan'], 404);

        // Mapping input (Handle jika frontend kirim namaBarang ATAU assetName)
        $nama = $request->assetName ?? $request->namaBarang ?? $aset->nama_aset;
        $kode = $request->brandCode ?? $request->merkType ?? $aset->kode_aset;
        $kategori = $request->category ?? $request->kategori ?? $aset->kategori;
        $status = $request->status ?? $aset->status;
        $log = $request->barcodeUpdateLog ?? $request->tanggal_log_barcode ?? $aset->tanggal_log_barcode;

        // Validasi unique, abaikan ID ini sendiri
        $request->validate([
            'brandCode' => 'sometimes|unique:asets,kode_aset,'.$id,
        ]);

        try {
            $aset->update([
                'nama_aset'           => $nama,
                'kode_aset'           => $kode,
                'kategori'            => $kategori,
                'status'              => $status,
                'jumlah'              => $request->jumlah ?? $aset->jumlah,
                'tanggal_log_barcode' => $log,
            ]);

            return response()->json(['message' => 'Data Aset berhasil diperbarui!', 'data' => $aset]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    // DELETE: Hapus Data
    public function destroy($id)
    {
        $aset = Aset::find($id);
        if ($aset) {
            $aset->delete();
            return response()->json(['message' => 'Data Aset dihapus']);
        }
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
}