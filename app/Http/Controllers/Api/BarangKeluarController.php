<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangKeluarController extends Controller
{
    public function store(Request $request)
    {
        // 1. VALIDASI
        $request->validate([
            'tglKeluar'    => 'required|date',
            'namaBarang'   => 'required|string', // Terima string bebas
            'namaPenerima' => 'required|string',
            'bagian'       => 'required|string',
            'totalKeluar'  => 'required|integer|min:1',
            'petugas'      => 'required|string',
        ]);

        try {
            // 2. GENERATE NO TRANSAKSI (Format T-BK-YYMMDDxxxx)
            $dateCode = date('ymd');
            $lastTrx = BarangKeluar::where('no_transaksi', 'like', 'T-BK-'.$dateCode.'%')->latest()->first();
            $number = $lastTrx ? intval(substr($lastTrx->no_transaksi, -4)) + 1 : 1;
            $noTransaksi = "T-BK-{$dateCode}" . str_pad($number, 4, '0', STR_PAD_LEFT);

            // 3. SIMPAN LANGSUNG (Tanpa Cek Barang Master & Tanpa Potong Stok)
            $keluar = BarangKeluar::create([
                'no_transaksi'  => $noTransaksi,
                'tgl_keluar'    => $request->tglKeluar,
                
                // INI KUNCINYA: Simpan teks dari input langsung ke database
                'nama_barang'   => $request->namaBarang, 
                
                'nama_penerima' => $request->namaPenerima,
                'bagian'        => $request->bagian,
                'jumlah_keluar' => $request->totalKeluar,
                'petugas'       => $request->petugas,
            ]);

            return response()->json([
                'message' => 'Data berhasil disimpan (Mode Catatan Bebas)',
                'data' => $keluar
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // --- Method Index, Update, Destroy Standar ---

    public function index()
    {
        $data = BarangKeluar::orderBy('tgl_keluar', 'desc')->get();
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
         $keluar = BarangKeluar::find($id);
         if(!$keluar) return response()->json(['message'=>'Not found'], 404);

         $keluar->update([
            'tgl_keluar'    => $request->tglKeluar,
            'nama_barang'   => $request->namaBarang,
            'nama_penerima' => $request->namaPenerima,
            'bagian'        => $request->bagian,
            'jumlah_keluar' => $request->totalKeluar,
            'petugas'       => $request->petugas,
         ]);

         return response()->json(['message' => 'Data berhasil diupdate', 'data' => $keluar]);
    }

    public function destroy($id)
    {
        $keluar = BarangKeluar::find($id);
        if ($keluar) {
            $keluar->delete();
            return response()->json(['message' => 'Data dihapus']);
        }
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
    
    public function show($id)
    {
        $keluar = BarangKeluar::find($id);
        if (!$keluar) return response()->json(['message' => 'Data tidak ditemukan'], 404);
        return response()->json($keluar);
    }
}