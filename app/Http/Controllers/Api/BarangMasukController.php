<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Wajib ada untuk generate kode acak
use Carbon\Carbon;

class BarangMasukController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tglMasuk'    => 'required|date',
            'kategori'    => 'required|string', // Di Postman namanya 'kategori'
            'namaBarang'  => 'required|string',
            'jumlahMasuk' => 'required|integer|min:1',
            'user'        => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Generate No Transaksi
            $tanggal = Carbon::parse($request->tglMasuk);
            $prefix = 'T-BK-' . $tanggal->format('ymd'); 
            
            $lastTrx = BarangMasuk::where('no_transaksi', 'like', $prefix . '%')
                        ->orderBy('id', 'desc')
                        ->first();
            
            if ($lastTrx) {
                $lastNumber = (int) substr($lastTrx->no_transaksi, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $noTransaksiBaru = $prefix . sprintf('%04d', $newNumber); 

            // 2. Cek/Buat Master Barang
            // PERHATIKAN DISINI: Kita sesuaikan dengan Model Anda
            $barang = Barang::firstOrCreate(
                ['nama_barang' => $request->namaBarang], 
                [
                    // Generate Kode Barang (Solusi Error Not Null)
                    'kode_barang'  => 'BRG-' . strtoupper(Str::random(5)), 
                    
                    // Petakan input 'kategori' ke kolom 'jenis_barang' (Sesuai Model Anda)
                    'jenis_barang' => $request->kategori, 
                    
                    'stok'         => 0,
                    'satuan'       => 'Pcs' // Default value jika satuan wajib diisi
                ]
            );

            // 3. Simpan Transaksi
            $barangMasuk = BarangMasuk::create([
                'no_transaksi' => $noTransaksiBaru,
                'tgl_masuk'    => $request->tglMasuk,
                'kategori'     => $request->kategori,
                'barang_id'    => $barang->id,
                'jumlah_masuk' => $request->jumlahMasuk,
                'user'         => $request->user,
            ]);

            // 4. Update Stok
            $barang->increment('stok', $request->jumlahMasuk);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil disimpan.',
                'data'    => $barangMasuk
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Tampilkan pesan error detail untuk debugging
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function index()
    {
        $barangMasuk = BarangMasuk::with('barang')->orderBy('tgl_masuk', 'desc')->get();
        return response()->json($barangMasuk);
    }

    public function show($id)
    {
        $barangMasuk = BarangMasuk::with('barang')->find($id);
        if (!$barangMasuk) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
        }
        return response()->json($barangMasuk);
    }

   public function update(Request $request, $id)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'tglMasuk'    => 'required|date',
            'kategori'    => 'required|string',
            'namaBarang'  => 'required|string',
            'jumlahMasuk' => 'required|integer|min:1',
            'user'        => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 2. Ambil Data Lama (Sebelum diedit)
            $barangMasuk = BarangMasuk::find($id);
            if (!$barangMasuk) {
                return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
            }

            // --- MULAI LOGIKA UPDATE ---

            // A. KEMBALIKAN STOK LAMA (Revert)
            // Kurangi stok di Master Barang sesuai jumlah lama (batalkan efek transaksi sebelumnya)
            $barangLama = Barang::find($barangMasuk->barang_id);
            if ($barangLama) {
                $barangLama->decrement('stok', $barangMasuk->jumlah_masuk);
            }

            // B. SIAPKAN BARANG BARU (Cek apakah user mengganti nama barang?)
            // Jika nama barang diganti, kita cari ID barunya. Jika sama, pakai ID lama.
            $barangBaru = Barang::firstOrCreate(
                ['nama_barang' => $request->namaBarang], 
                [
                    'kode_barang'  => 'BRG-' . strtoupper(Str::random(5)),
                    'jenis_barang' => $request->kategori, 
                    'stok'         => 0,
                    'satuan'       => 'Pcs'
                ]
            );

            // C. UPDATE DATA TRANSAKSI
            // Timpa data lama dengan data baru dari Frontend
            $barangMasuk->update([
                'tgl_masuk'    => $request->tglMasuk,
                'kategori'     => $request->kategori, 
                'barang_id'    => $barangBaru->id,    // Update ID Barang (Penting!)
                'jumlah_masuk' => $request->jumlahMasuk,
                'user'         => $request->user,
            ]);

            // D. TERAPKAN STOK BARU
            // Tambahkan jumlah yang baru diedit ke stok Master Barang
            $barangBaru->increment('stok', $request->jumlahMasuk);

            // ---------------------------

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil diperbarui dan stok telah disesuaikan.',
                'data'    => $barangMasuk
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal update: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // 1. Cari Data Transaksi
            $barangMasuk = BarangMasuk::find($id);

            if (!$barangMasuk) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Data tidak ditemukan.'
                ], 404);
            }

            // --- BAGIAN INI YANG SEBELUMNYA KOSONG ---

            // 2. KEMBALIKAN STOK (Reverse Stock)
            // Ambil data barang master berdasarkan ID
            $barang = Barang::find($barangMasuk->barang_id);
            
            // Jika barang master masih ada, kurangi stoknya
            if ($barang) {
                // Gunakan decrement agar aman
                $barang->decrement('stok', $barangMasuk->jumlah_masuk);
            }

            // 3. HAPUS DATA TRANSAKSI
            $barangMasuk->delete(); 
            
            // ------------------------------------------

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil dihapus dan stok telah dikembalikan.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}