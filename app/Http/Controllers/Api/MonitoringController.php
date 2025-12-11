<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Monitoring;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Wajib untuk Transaction
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MonitoringController extends Controller
{
    /**
     * GET /api/monitoring
     * Mengambil semua data header saja (untuk list di halaman admin)
     */
    public function index()
    {
        try {
            $monitorings = Monitoring::with('user')->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $monitorings
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/monitoring
     * Menyimpan Data Header + 4 Tabel Detail sekaligus
     */
    public function store(Request $request)
    {
        // 1. VALIDASI INPUT
        $validator = Validator::make($request->all(), [
            // Validasi Header
            'nomor_form'    => 'required|unique:monitorings,nomor_form',
            'tanggal'       => 'required|date',
            'tim_pelaksana' => 'required|string',
            'area'          => 'required|string',
            'periode'       => 'required|string',
            
            // Validasi Array Detail (Pastikan frontend mengirim array)
            'fisik'           => 'nullable|array',
            'layanan'         => 'nullable|array',
            'keamanan'        => 'nullable|array',
            'akses_jaringan'  => 'nullable|array',
            
            // Validasi File Upload
            'lampiran'      => 'nullable|array',
            'lampiran.*'    => 'image|mimes:jpg,jpeg,png|max:5120', // Max 5MB

            'fisik.*.komponen'   => 'required|string',
            'fisik.*.hasil'      => 'required|boolean', // Memastikan nilainya 1, 0, true, atau false
        
            'layanan.*.komponen' => 'required|string',
            'layanan.*.hasil'    => 'required|boolean',

            'keamanan.*.komponen' => 'required|string',
            'keamanan.*.hasil'    => 'required|boolean',

            'akses_jaringan.*.komponen' => 'required|string',
            'akses_jaringan.*.hasil'    => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi Gagal', 'errors' => $validator->errors()], 422);
        }

        // 2. MULAI DATABASE TRANSACTION
        DB::beginTransaction();
        try {
            // A. Handle Upload Lampiran (Jika ada)
            $lampiranUrls = [];
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('monitoring-foto', $filename, 'public');
                    $lampiranUrls[] = url('storage/' . $path);
                }
            }

            // B. Simpan Header ke Tabel 'monitorings'
            $monitoring = Monitoring::create([
                'user_id'       => Auth::id(), // Ambil ID user yang login
                'nomor_form'    => $request->nomor_form,
                'tanggal'       => $request->tanggal,
                'tim_pelaksana' => $request->tim_pelaksana,
                'area'          => $request->area,
                'periode'       => $request->periode,
                'status_umum'   => $request->status_umum, // Normal/Ada Temuan
                'ringkasan'     => $request->ringkasan,
                'rencana_tindak_lanjut' => $request->tindak_lanjut, 
                'lampiran'      => $lampiranUrls, // Pastikan model punya casts 'lampiran' => 'array'
            ]);

            // C. Simpan Detail ke Tabel Anak (Menggunakan createMany)
            // Kita cek dulu apakah arraynya ada & tidak kosong sebelum disimpan

            // 1. Tabel Fisik
            if ($request->has('fisik') && is_array($request->fisik)) {
                // Frontend mengirim: [{komponen: 'AC', hasil: 'OK', ...}, {...}]
                $monitoring->fisiks()->createMany($request->fisik);
            }

            // 2. Tabel Layanan
            if ($request->has('layanan') && is_array($request->layanan)) {
                $monitoring->layanans()->createMany($request->layanan);
            }

            // 3. Tabel Keamanan
            if ($request->has('keamanan') && is_array($request->keamanan)) {
                $monitoring->keamanans()->createMany($request->keamanan);
            }

            // 4. Tabel Akses Jaringan
            if ($request->has('akses_jaringan') && is_array($request->akses_jaringan)) {
                // Perhatikan nama fungsi relasi di Model (camelCase)
                $monitoring->aksesJaringans()->createMany($request->akses_jaringan);
            }

            // D. Commit Transaction (Simpan Permanen)
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Laporan Monitoring berhasil disimpan',
                'data'    => $monitoring
            ], 201);

        } catch (\Exception $e) {
            // E. Rollback (Batalkan semua jika error)
            DB::rollback();
            
            // Hapus foto jika sudah terlanjur ter-upload biar server gak penuh sampah
            if (!empty($lampiranUrls)) {
                foreach ($lampiranUrls as $url) {
                    $path = str_replace(url('storage/'), '', $url);
                    Storage::disk('public')->delete($path);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/monitoring/{id}
     * Mengambil detail lengkap beserta tabel anak-anaknya
     */
    public function show($id)
    {
        try {
            // Eager Load semua relasi agar data lengkap
            $monitoring = Monitoring::with([
                'user',
                'fisiks', 
                'layanans', 
                'keamanans', 
                'aksesJaringans'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $monitoring
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    /**
     * PUT/PATCH /api/monitoring/{id}
     * Mengupdate data Header dan Tabel Anak (Fisik, Layanan, dll)
     */
    public function update(Request $request, $id)
    {
        // 1. Cek apakah data ada
        $monitoring = Monitoring::find($id);
        if (!$monitoring) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // 2. Validasi (Mirip store, tapi nomor_form boleh sama kalau punya sendiri)
        $validator = Validator::make($request->all(), [
            'nomor_form'    => 'required|unique:monitorings,nomor_form,' . $id, // Ignore ID sendiri
            'tanggal'       => 'required|date',
            'tim_pelaksana' => 'required|string',
            'area'          => 'required|string',
            'periode'       => 'required|string',
            
            // Validasi Array
            'fisik'           => 'nullable|array',
            'layanan'         => 'nullable|array',
            'keamanan'        => 'nullable|array',
            'akses_jaringan'  => 'nullable|array',
            
            // Validasi isi array (Boolean 1/0)
            'fisik.*.hasil'      => 'required|boolean',
            'layanan.*.hasil'    => 'required|boolean',
            'keamanan.*.hasil'   => 'required|boolean',
            'akses_jaringan.*.hasil' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi Gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 3. Update Header
            $monitoring->update([
                'nomor_form'    => $request->nomor_form,
                'tanggal'       => $request->tanggal,
                'tim_pelaksana' => $request->tim_pelaksana,
                'area'          => $request->area,
                'periode'       => $request->periode,
                'status_umum'   => $request->status_umum,
                'ringkasan'     => $request->ringkasan,
                'rencana_tindak_lanjut' => $request->tindak_lanjut, // Mapping dari request ke DB
            ]);

            // 4. Update Tabel Anak (Cara Paling Aman: Hapus Lama -> Buat Baru)
            // Ini memastikan data sinkron dengan apa yang ada di form React saat ini.

            if ($request->has('fisik')) {
                $monitoring->fisiks()->delete(); // Hapus data fisik lama
                $monitoring->fisiks()->createMany($request->fisik); // Masukkan data fisik baru (editan)
            }

            if ($request->has('layanan')) {
                $monitoring->layanans()->delete();
                $monitoring->layanans()->createMany($request->layanan);
            }

            if ($request->has('keamanan')) {
                $monitoring->keamanans()->delete();
                $monitoring->keamanans()->createMany($request->keamanan);
            }

            if ($request->has('akses_jaringan')) {
                $monitoring->aksesJaringans()->delete();
                $monitoring->aksesJaringans()->createMany($request->akses_jaringan);
            }

            // Catatan: Logic ini tidak mengupdate Foto Lampiran (karena pakai PUT raw json).
            // Jika mau update foto, harus pakai endpoint khusus atau logic POST _method=PUT.

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Monitoring berhasil diupdate',
                'data'    => $monitoring->load(['fisiks', 'layanans']) // Load data terbaru
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/monitoring/{id}
     */
    public function destroy($id)
    {
        try {
            $monitoring = Monitoring::findOrFail($id);

            // Hapus file lampiran fisik di server
            if ($monitoring->lampiran && is_array($monitoring->lampiran)) {
                foreach ($monitoring->lampiran as $url) {
                    $path = str_replace(url('storage/'), '', $url);
                    Storage::disk('public')->delete($path);
                }
            }

            // Hapus data (Tabel anak akan terhapus otomatis karena onDelete cascade di migration)
            $monitoring->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}