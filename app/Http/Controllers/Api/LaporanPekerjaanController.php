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
                // Lampiran boleh kosong saat awal buat, karena baru "sedang dikerjakan"
                'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', 
            ]);

            // Generate ID Pekerjaan
            $lastLaporan = LaporanPekerjaan::latest('id')->first();
            $nextNumber = $lastLaporan ? $lastLaporan->id + 1 : 1;
            $validated['id_pekerjaan'] = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Handle lampiran jika user langsung upload (opsional)
            $lampiranPaths = [];
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
            }
            $validated['lampiran'] = $lampiranPaths;
            
            $validated['user_id'] = auth()->id();
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