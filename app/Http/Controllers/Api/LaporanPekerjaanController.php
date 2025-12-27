<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanPekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LaporanPekerjaanController extends Controller
{
    // MENAMPILKAN DATA
    public function index()
    {
        try {
            $user = Auth::user();
            
            // LOGIKA: Jika Admin, lihat SEMUA. Jika User, lihat PUNYA SENDIRI.
            if ($user->role === 'admin') {
                $laporan = LaporanPekerjaan::with('user')->latest()->get();
            } else {
                $laporan = LaporanPekerjaan::where('user_id', $user->id)
                            ->with('user')
                            ->latest()
                            ->get();
            }
            
            return response()->json([
                'success' => true,
                'data' => $laporan
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // USER MEMBUAT PEKERJAAN (Otomatis Status: Dikerjakan)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'jenis_pekerjaan' => 'required|string',
                'bagian' => 'required|string',
                'petugas' => 'required|string',
                'deskripsi' => 'nullable|string',
                // Lampiran boleh kosong saat awal buat
                'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', 
            ]);

            // --- BAGIAN GENERATE ID BARU ---
            // 1. Ambil ID User yang sedang login
            $userId = auth()->id();

            // 2. Ambil nomor urut terakhir
            $lastLaporan = LaporanPekerjaan::latest('id')->first();
            $nextNumber = $lastLaporan ? $lastLaporan->id + 1 : 1;

            // 3. Gabungkan: P + UserID + '-' + Nomor Urut (4 digit)
            // Contoh Hasil: P3-0001 (Jika User ID 3 dan data pertama)
            $validated['id_pekerjaan'] = 'P' . $userId . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            // -------------------------------

            // Handle lampiran jika user langsung upload
            $lampiranPaths = [];
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
            }
            $validated['lampiran'] = $lampiranPaths;
            
            $validated['user_id'] = $userId; // Gunakan variabel yang sudah diambil diatas
            // FLOW: Otomatis set status Dikerjakan saat input baru
            $validated['status'] = 'Dikerjakan'; 

            $laporan = LaporanPekerjaan::create($validated);

            return response()->json([
                'success' => true, 
                'message' => 'Pekerjaan dimulai', 
                'data' => $laporan
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // USER SELESAI MENGERJAKAN (Upload Bukti -> Status jadi Menunggu Persetujuan)
    public function uploadBukti(Request $request, $id)
    {
        try {
            $laporan = LaporanPekerjaan::where('user_id', auth()->id())->findOrFail($id);
            
            $request->validate([
                'lampiran' => 'required', // Wajib ada bukti
                'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            // Proses Upload File Baru
            $lampiranPaths = $laporan->lampiran ?? []; // Ambil lampiran lama jika ada
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
            }

            // Update Data
            $laporan->update([
                'lampiran' => $lampiranPaths,
                'status' => 'Menunggu Persetujuan' // Flow berubah disini
            ]);

            return response()->json(['success' => true, 'message' => 'Bukti terupload, menunggu approval admin', 'data' => $laporan]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ADMIN APPROVE PEKERJAAN
    public function approvePekerjaan(Request $request, $id)
    {
        try {
            // Cek apakah yang akses adalah Admin
            if (Auth::user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $laporan = LaporanPekerjaan::findOrFail($id);
            
            // Validasi input status dari admin (Selesai atau Ditolak)
            $request->validate([
                'status' => 'required|in:Selesai,Ditolak'
            ]);

            $laporan->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Status pekerjaan diperbarui oleh Admin', 
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // UPDATE DATA PEKERJAAN (Edit)
    public function update(Request $request, $id)
    {
        try {
            // 1. Cari Data
            $laporan = LaporanPekerjaan::findOrFail($id);

            // 2. Cek Otorisasi (Hanya Pemilik atau Admin yang boleh edit)
            if (auth()->user()->role !== 'admin' && $laporan->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // 3. Validasi
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'jenis_pekerjaan' => 'required|string',
                'bagian' => 'required|string',
                'petugas' => 'required|string',
                'deskripsi' => 'nullable|string',
                'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            // 4. Handle Lampiran Baru (Jika ada upload baru)
            $lampiranPaths = $laporan->lampiran ?? []; // Ambil lampiran lama
            
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path; // Tambahkan ke array lama
                }
                $validated['lampiran'] = $lampiranPaths;
            }

            // 5. Update Database
            $laporan->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan berhasil diperbarui',
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // MENGHAPUS DATA (Admin Hapus Semua, User Hapus Punya Sendiri)
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $laporan = LaporanPekerjaan::findOrFail($id);

            // LOGIKA IZIN HAPUS
            // Boleh hapus jika: Role Admin ATAU (Role User DAN itu miliknya sendiri)
            if ($user->role === 'admin' || $laporan->user_id === $user->id) {
                
                // Hapus file fisik
                if ($laporan->lampiran && is_array($laporan->lampiran)) {
                    foreach ($laporan->lampiran as $file) {
                        Storage::disk('public')->delete($file);
                    }
                }
                
                $laporan->delete();

                return response()->json(['success' => true, 'message' => 'Laporan berhasil dihapus']);
            }

            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses hapus'], 403);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}