<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanPekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Pastikan import ini ada

class LaporanPekerjaanController extends Controller
{
    // MENAMPILKAN SEMUA DATA (List)
    public function index()
    {
        try {
            $user = Auth::user();
            
            // 1. Ambil Data
            if ($user->role === 'admin') {
                $laporan = LaporanPekerjaan::with('user')->latest()->get();
            } else {
                $laporan = LaporanPekerjaan::where('user_id', $user->id)
                            ->with('user')
                            ->latest()
                            ->get();
            }
            
            // 2. [BARU] Format Tanggal untuk semua data di list
            // Kita gunakan 'transform' untuk memodifikasi setiap item dalam koleksi
            $laporan->transform(function($item) {
                // Tambahkan field baru 'formatted_tanggal'
                $item->formatted_tanggal = \Carbon\Carbon::parse($item->tanggal)
                                            ->locale('id')
                                            ->isoFormat('dddd, D MMMM Y');
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'data' => $laporan
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // [BARU DITAMBAHKAN] MENAMPILKAN DETAIL SATU PEKERJAAN
    // Ini memperbaiki error 500 saat klik tombol "mata" (detail)
    public function show($id)
{
    try {
        $laporan = LaporanPekerjaan::with('user')->findOrFail($id);

        // UBAH BAGIAN INI:
        // Gunakan isoFormat agar muncul nama hari (Senin, Selasa, dst)
        // Pastikan setting locale Laravel sudah 'id' (Indonesia) atau set manual
        $laporan->formatted_tanggal = \Carbon\Carbon::parse($laporan->tanggal)
                                        ->locale('id')
                                        ->isoFormat('dddd, D MMMM Y'); 
        
        // Contoh Output: "Senin, 12 Januari 2026"

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
                'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', 
            ]);

            // --- BAGIAN GENERATE ID BARU ---
            $userId = auth()->id();
            $lastLaporan = LaporanPekerjaan::latest('id')->first();
            $nextNumber = $lastLaporan ? $lastLaporan->id + 1 : 1;
            $validated['id_pekerjaan'] = 'P' . $userId . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            // -------------------------------

            $lampiranPaths = [];
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
            }
            $validated['lampiran'] = $lampiranPaths;
            $validated['user_id'] = $userId;
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

            $lampiranPaths = $laporan->lampiran ?? []; 
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
            }

            $laporan->update([
                'lampiran' => $lampiranPaths,
                'status' => 'Menunggu Persetujuan'
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
            if (Auth::user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $laporan = LaporanPekerjaan::findOrFail($id);
            
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
            $laporan = LaporanPekerjaan::findOrFail($id);

            if (auth()->user()->role !== 'admin' && $laporan->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'tanggal' => 'required|date',
                'jenis_pekerjaan' => 'required|string',
                'bagian' => 'required|string',
                'petugas' => 'required|string',
                'deskripsi' => 'nullable|string',
                'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            $lampiranPaths = $laporan->lampiran ?? [];
            
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lampiran', $filename, 'public');
                    $lampiranPaths[] = $path;
                }
                $validated['lampiran'] = $lampiranPaths;
            }

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

    // MENGHAPUS DATA
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $laporan = LaporanPekerjaan::findOrFail($id);

            if ($user->role === 'admin' || $laporan->user_id === $user->id) {
                
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