<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * GET /api/data-barang
     * Mengambil semua data untuk ditampilkan di tabel
     */
    public function index()
    {
        // Mengurutkan dari yang terbaru
        $barangs = Barang::orderBy('created_at', 'desc')->get();
        
        // Opsional: Mapping balik ke format frontend jika perlu
        // Tapi biasanya frontend menyesuaikan dengan response API
        return response()->json($barangs);
    }

    /**
     * POST /api/data-barang
     * Menyimpan data baru (Form Tambah Barang)
     */
    public function store(Request $request)
    {
        // 1. Validasi nama field sesuai yang dikirim React (camelCase)
        $request->validate([
            'idBarang'     => 'required|unique:barangs,kode_barang',
            'namaBarang'   => 'required|string',
            'jenisBarang'  => 'required|string',
            'satuanBarang' => 'required|string',
            'stok'         => 'nullable|integer', // Stok boleh kosong saat tambah awal
        ]);

        try {
            // 2. Simpan dengan mapping manual (React -> DB)
            $barang = Barang::create([
                'kode_barang'  => $request->idBarang,
                'nama_barang'  => $request->namaBarang,
                'jenis_barang' => $request->jenisBarang,
                'satuan'       => $request->satuanBarang,
                'stok'         => $request->stok ?? 0, // Default 0 jika null
            ]);

            return response()->json([
                'message' => 'Data Barang berhasil disimpan!',
                'data'    => $barang
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/data-barang/{id}
     * Mengambil 1 data untuk Form Edit
     */
    public function show($id)
    {
        // Mencari berdasarkan ID auto-increment database, atau bisa juga berdasarkan kode_barang
        $barang = Barang::where('id', $id)->orWhere('kode_barang', $id)->first();

        if (!$barang) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        return response()->json($barang);
    }

    /**
     * PUT /api/data-barang/{id}
     * Update data (Form Edit Barang)
     */

    // --- METHOD UPDATE (EDIT DATA BARANG) ---
    public function update(Request $request, $id)
    {
        // 1. Validasi Input (Gunakan nama_barang sesuai Postman & Database)
        $request->validate([
            'nama_barang'  => 'required|string',
            'jenis_barang' => 'required|string', // Pastikan di Postman key-nya 'jenis_barang'
            'satuan'       => 'required|string', // Pastikan di Postman key-nya 'satuan'
            'stok'         => 'required|integer|min:0',
        ]);

        try {
            // 2. Cari Barang
            $barang = Barang::findOrFail($id);

            // 3. Update Data
            // Karena nama field di Request sudah sama dengan nama kolom di Database,
            // kita bisa langsung update tanpa mapping manual satu per satu.
            $barang->update([
                'nama_barang'  => $request->nama_barang,
                'jenis_barang' => $request->jenis_barang,
                'satuan'       => $request->satuan,
                'stok'         => $request->stok,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data barang berhasil diperbarui',
                'data'    => $barang
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal update barang: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/data-barang/{id}
     * Hapus data
     */
    public function destroy($id)
    {
        $barang = Barang::where('id', $id)->orWhere('kode_barang', $id)->first();
        
        if ($barang) {
            $barang->delete();
            return response()->json(['message' => 'Data Barang dihapus']);
        }
        
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
}
