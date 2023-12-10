<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\modelJob;
use App\Models\ModelApply;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use App\Mail\Interview;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function dashboard()
    {

        if (Auth::guard('admin')->check()) {
            return view('adminPage.dashboard');
        }
        return view ('adminPage.dashboard');
    }

    public function dataJob()
    {
        $dataJob = DB::table('job')->get();
        return view('adminPage.dataJob', compact('dataJob'));
    }

    public function addJob (Request $request){
        // Validasi data yang diunggah oleh pengguna
        $validator = Validator::make($request->all(), [
            'field_logo' => 'required',
            'field_job' => 'required',
            'field_email' => 'required|email',
            'field_gaji' => 'required',
            'field_kontak' => 'required',
            'field_company' => 'required',
            'field_lokasi' => 'required',
            'field_hard' => 'required',
            'field_soft' => 'required',
            'field_kategori' => 'required',
            'field_descJob' => 'required',
            'field_descCompany' => 'required',
        ], [
            'field_logo.required' => 'Kolom Logo Perusahaan Wajib diisi.',
            'field_job.required' => 'Kolom Pekerjaan wajib diisi.',
            'field_company.required' => 'Kolom Nama Perusahaan wajib diisi.',
            'field_email.email' => 'Format Email tidak valid.',
            'field_kontak.required' => 'Kolom Kontak Perusahaan wajib diisi.',
            'field_gaji.required' => 'Kolom Gaji wajib diisi.',
            'field_lokasi.required' => 'Kolom Lokasi Pekerjaan wajib diisi.',
            'field_hard.required' => 'Kolom Requitment Hard Skill wajib diisi.',
            'field_soft.required' => 'Kolom Requitment Sost Skill wajib diisi.',
            'field_kategori.required' => 'Kolom Kategori wajib diisi.',
            'field_descJob.required' => 'Kolom Deskripsi Pekerjaan wajib diisi.',
            'field_descCompany.required' => 'Kolom Deskripsi Perusahaan wajib diisi.',

        ]);

        if ($validator->fails()) {
            return redirect('/adminJob')
                ->withErrors($validator)
                ->withInput();
        }

        // Simpan Logo Perusahaan
        $logoFileName = $request->file('field_logo')->getClientOriginalName();

        $addJob = new modelJob;
        $addJob->job = $request->input('field_job');
        $addJob->desc_job = $request->input('field_descJob');
        $addJob->kategori = $request->input('field_kategori');
        $addJob->gaji = $request->input('field_gaji');
        $addJob->email_company = $request->input('field_email');
        $addJob->kontak_company = $request->input('field_kontak');
        $addJob->company = $request->input('field_company');
        $addJob->soft_skill = $request->input('field_soft');
        $addJob->hard_skill = $request->input('field_hard');
        $addJob->desc_company = $request->input('field_descCompany');
        $addJob->lokasi = $request->input('field_lokasi');
         // Simpan nama file ke database
         $addJob->logo_company = $logoFileName;
        // Pindahkan file ke direktori penyimpanan
        $request->file('field_logo')->move(public_path('landingPage/assets/img/icon'), $logoFileName);

        $addJob->save();
        return redirect('/adminJob')->with('success', 'Data Lowongan Pekerjaan berhasil Ditambahkan.');
    }


    public function deleteJob($id)
    {
        $job = modelJob::where('id', $id)->first();

        if (!$job) {
            return redirect('/adminJob')->with('error', 'Pekerjaan tidak ditemukan');
        }

        $job->delete();

        return redirect('/adminJob')->with('delete', 'Data Pekerjaan berhasil dihapus');
    }


    public function editJob(Request $request, $id)
    {
        $job = modelJob::where('id', $id)->first();

        if (!$job) {
            return redirect('/adminJob')->with('error', 'Data Pekerjaan tidak ditemukan');
        }

        // Validasi data yang diinput oleh pengguna
        $cek = Validator::make($request->all(), [
            'field_job' => 'required',
            'field_company' => 'required',
            'field_email' => 'required|email',
            'field_kontak' => 'required',
            'field_tersedia' => 'required',
            'field_lokasi' => 'required',
            'field_descJob' => 'required',
        ]);

        if ($cek->fails()) {
            return redirect('/adminJob')
                ->with('salah', 'Gagal Edit Data.');
        }

        // Simpan perubahan data
        $job->job = $request->input('field_job');
        $job->company = $request->input('field_company');
        $job->email_company = $request->input('field_email');
        $job->kontak_company = $request->input('field_kontak');
        $job->tersedia = $request->input('field_tersedia');
        $job->lokasi = $request->input('field_lokasi');
        $job->desc_job = $request->input('field_descJob');

        $job->update();

        return redirect('/adminJob')->with('edit', 'Data Pekerjaan berhasil diedit');
    }

    public function infoInterview($id, Request $request)
    {
        $interview = ModelApply::find($id);

        if (!$interview) {
            return redirect('/apply')->with('gagal', 'Data Interview tidak ditemukan!');
        }

        // Update nilai tgl_interview dan jam_interview
        $interview->tgl_interview = $request->input('field_tgl');
        $interview->jam_interview = $request->input('field_waktu');

        // Simpan perubahan ke database
        $interview->save();

        // Kirim email
        $mailPelamar = $request->input('field_email');
        try {
            Mail::to($mailPelamar)->send(new Interview($interview));
        } catch (\Swift_TransportException $e) {
            // Terjadi kesalahan pengiriman email
            return redirect('/apply')->with('gagal', 'Email Interview Gagal Dikirim!');
        }

        return redirect('/apply')->with('berhasil', 'Email Interview Berhasil Dikirim!');
    }

    public function applyJob()
    {
        $applicants = ModelApply::with(['user', 'job'])->get();

        return view('adminPage.karyawan', compact('applicants'));
    }


    public function downloadBerkas($id)
    {

        $dokumen = DB::table('apply')->get();

        // Mengambil data sebagai satu baris data
        $dataDokumen = DB::table('users')->where('id', $id)->first();

        if (!$dataDokumen) {
            return redirect()->back()->with('error', 'Data not found.');
        }

        // Eksport dari lokasi penyimpanan berkas
        $cvPath = storage_path('app/' . $dataDokumen->cv);
        $ijazahPath = storage_path('app/' . $dataDokumen->ijazah);
        $sehatPath = storage_path('app/' . $dataDokumen->sehat);


        // Membuat file zip
        $zip = new \ZipArchive();
        $zipFileName = $dataDokumen->name . '.zip';
        if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $zip->addFile($cvPath, 'CV.pdf');
            $zip->addFile($ijazahPath, 'Ijazah.pdf');
            $zip->addFile($sehatPath, 'Surat_Kesehatan.pdf');
            $zip->close();

            // Set appropriate headers for download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFileName));

            // Read and output the zip file
            readfile($zipFileName);

            // Clean up (delete) the temporary zip file
            unlink($zipFileName);

            return;
        }

        return redirect()->back()->with('error', 'Failed to create zip file.');
    }
}
