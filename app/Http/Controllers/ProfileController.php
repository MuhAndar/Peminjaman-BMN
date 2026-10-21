<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display user profile & digital signature management.
     */
    public function show()
    {
        $user = Auth::user()->load('employee');
        return view('profile.show', compact('user'));
    }

    /**
     * Update borrower personal data for BMN loan documents.
     */
    public function updateBiodata(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30',
            'rank_grade' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:150',
            'employment_status' => 'required|string|max:50',
            'home_address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:30',
        ], [
            'full_name.required' => 'Nama lengkap pegawai wajib diisi.',
            'employment_status.required' => 'Status kepegawaian wajib dipilih.',
            'home_address.required' => 'Alamat rumah wajib diisi untuk kelengkapan Surat Peminjaman dan Berita Acara BMN.',
        ]);

        $employee = $user->employee;
        $isNew = false;
        if (!$employee) {
            $employee = new Employee();
            $employee->id = (string) Str::uuid();
            $employee->email = $user->email;
            $isNew = true;
        }

        $oldData = [
            'full_name' => $employee->full_name,
            'nip' => $employee->nip,
            'rank_grade' => $employee->rank_grade,
            'position' => $employee->position,
            'employment_status' => $employee->employment_status,
            'home_address' => $employee->home_address,
            'phone' => $employee->phone,
        ];

        // Format NIP: jika ada input berformat dengan spasi, ambil hanya digit
        $cleanNip = null;
        if (!empty($validated['nip'])) {
            $digits = preg_replace('/\D/', '', $validated['nip']);
            $cleanNip = !empty($digits) ? $digits : trim($validated['nip']);
        }

        $employee->full_name = trim($validated['full_name']);
        $employee->nip = $cleanNip;
        $employee->rank_grade = !empty($validated['rank_grade']) ? trim($validated['rank_grade']) : null;
        $employee->position = !empty($validated['position']) ? trim($validated['position']) : null;
        $employee->employment_status = trim($validated['employment_status']);
        $employee->home_address = trim($validated['home_address']);
        $employee->phone = !empty($validated['phone']) ? trim($validated['phone']) : null;
        $employee->save();

        // Sinkronkan nama dan relasi user
        $user->name = $employee->full_name;
        if ($user->employee_id !== $employee->id) {
            $user->employee_id = $employee->id;
        }
        $user->save();

        AuditLog::record(
            $isNew ? 'CREATE_PROFILE_BIODATA' : 'UPDATE_PROFILE_BIODATA',
            'Employee',
            (string)$employee->id,
            $isNew ? null : $oldData,
            [
                'full_name' => $employee->full_name,
                'nip' => $employee->nip,
                'rank_grade' => $employee->rank_grade,
                'position' => $employee->position,
                'employment_status' => $employee->employment_status,
                'home_address' => $employee->home_address,
                'phone' => $employee->phone,
            ]
        );

        return redirect()->route('profile.show', ['tab' => 'biodata'])
            ->with('success', 'Data diri peminjaman BMN berhasil diperbarui! Data ini akan otomatis digunakan pada setiap pengajuan peminjaman dan dokumen resmi.');
    }

    /**
     * Save or update user digital signature (from Canvas pad or File upload).
     */
    public function updateSignature(Request $request)
    {
        $user = Auth::user();

        // 1. Opsi A: Tanda tangan dari papan gambar kanvas (Base64 PNG)
        if ($request->filled('signature_data')) {
            $dataUri = $request->input('signature_data');

            // Validasi format data URI image/png
            if (!preg_match('/^data:image\/(png|jpeg);base64,/', $dataUri)) {
                return back()->with('error', 'Format gambar tanda tangan kanvas tidak valid.');
            }

            $encodedImage = substr($dataUri, strpos($dataUri, ',') + 1);
            $decodedImage = base64_decode($encodedImage);

            if ($decodedImage === false) {
                return back()->with('error', 'Gagal memproses gambar tanda tangan.');
            }

            // Hapus tanda tangan lama jika ada
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }

            $fileName = 'signatures/sig_user_' . $user->id . '_' . time() . '_' . Str::random(6) . '.png';
            Storage::disk('public')->put($fileName, $decodedImage);

            $user->signature_path = $fileName;
            $user->save();

            AuditLog::record(
                'UPDATE_SIGNATURE_PAD',
                'User',
                (string)$user->id,
                null,
                ['source' => 'signature_pad', 'file' => $fileName]
            );

            return back()->with('success', 'Tanda tangan digital berhasil digambar dan disimpan ke profil Anda.');
        }

        // 2. Opsi B: Tanda tangan dari berkas unggahan
        if ($request->hasFile('signature_file')) {
            $request->validate([
                'signature_file' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            ], [
                'signature_file.image' => 'Berkas harus berupa gambar.',
                'signature_file.mimes' => 'Format berkas harus PNG atau JPG.',
                'signature_file.max' => 'Ukuran berkas maksimal 2 MB.',
            ]);

            // Hapus tanda tangan lama jika ada
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }

            $file = $request->file('signature_file');
            $fileName = 'signatures/sig_user_' . $user->id . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('signatures', basename($fileName), 'public');

            $user->signature_path = $path;
            $user->save();

            AuditLog::record(
                'UPDATE_SIGNATURE_FILE',
                'User',
                (string)$user->id,
                null,
                ['source' => 'file_upload', 'file' => $path]
            );

            return back()->with('success', 'Berkas gambar tanda tangan berhasil diunggah ke profil Anda.');
        }

        return back()->with('error', 'Silakan gambar tanda tangan di papan kanvas atau pilih berkas gambar tanda tangan.');
    }

    /**
     * Delete user digital signature.
     */
    public function deleteSignature()
    {
        $user = Auth::user();

        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $user->signature_path = null;
        $user->save();

        AuditLog::record('DELETE_SIGNATURE', 'User', (string)$user->id);

        return back()->with('success', 'Tanda tangan digital berhasil dihapus dari profil.');
    }
}
