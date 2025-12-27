<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeminjamanHeader;
use App\Models\PeminjamanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanAsetController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Sesuai Field di UI
        $request->validate([
            // Bagian Atas Form
            'peminjam'    => 'required|string',
            'bagian'      => 'required|string',
            'tglPinjam'   => 'required|date',
            'tglKembali'  => 'required|date',
            
            // Bagian Bawah Form (Array Barang)
            'items'       => 'required|array', 
            'items.*.namaBarang' => 'required|string',
            'items.*.merkKode'   => 'nullable|string',
            'items.*.jumlah'     => 'required|integer',
            'items.*.sisaStok'   => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            // 2. Simpan Header (Data Orang)
            $header = PeminjamanHeader::create([
                'nama_peminjam' => $request->peminjam,
                'bagian'        => $request->bagian,
                'tgl_pinjam'    => $request->tglPinjam,
                'tgl_kembali'   => $request->tglKembali,
            ]);

            // 3. Simpan Detail (Looping Barang)
            foreach ($request->items as $item) {
                PeminjamanDetail::create([
                    'peminjaman_header_id' => $header->id,
                    'nama_barang'          => $item['namaBarang'],
                    'merk_kode'            => $item['merkKode'] ?? '-',
                    'jumlah'               => $item['jumlah'],
                    'sisa_stok'            => $item['sisaStok'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Data berhasil disimpan!',
                'data'    => $header->load('details')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // 1. GET ALL (Tampilkan Semua Data + Barang-barangnya)
    public function index()
    {
        // 'details' adalah nama fungsi relasi di Model PeminjamanHeader
        $data = PeminjamanHeader::with('details') 
                ->orderBy('created_at', 'desc')
                ->get();

        return response()->json($data);
    }

    // 2. GET BY ID (Tampilkan Detail Satu Transaksi)
    public function show($id)
    {
        $data = PeminjamanHeader::with('details')->find($id);

        if (!$data) {
            return response()->json(['message' => 'Data peminjaman tidak ditemukan'], 404);
        }

        return response()->json($data);
    }
}