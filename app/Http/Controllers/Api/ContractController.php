<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('account_id')) {
            $contracts = Contract::where('account_id', $request->account_id)->get();
        } else {
            $contracts = Contract::all();
        }

        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $data = $request->except('active');
        $data['creator_by'] = Auth::id();
        if ($request->filled('active')) {
            Contract::where('account_id', $request->account_id)
                ->where('active', true)
                ->update(['active' => false]);
            $data['active'] = true;
        }
        if ($request->hasFile('files')) {
            $dataFiles = $this->uploadFile($request->file('files'));
            if ($dataFiles == 0) {
                return response()->json([
                    'message' => 'File đã tồn tại!',
                    'errors' => 'File đã tồn tại!',
                ], 409);
            }
            $data['files'] = $dataFiles;
        }
        $contract = Contract::create($data);

        return response()->json($contract);
    }

    public function update(Request $request, int $id)
    {
        $contract = Contract::find($id);
        $data = $request->except('creator_by');
        $contract->update($data);

        return response()->json($contract);
    }

    public function show(Request $request, int $id) {
        $contact = Contract::with(['account', 'creator', 'category'])->find($id);

        return $contact;
    }

    private function uploadFile($files)
    {
        $dataFiles = [];
        if ($files) {
            foreach ($files as $file) {
                $filename = now()->format('Y-m-d') . '_' . $file->getClientOriginalName(); // Ngày + Tên gốc    
                $path = 'public/files/' . $filename;
                // if (Storage::exists($path)) {
                //     return 0;
                // }
                $path = $file->storeAs('/public/files', $filename); // Lưu file với tên mới
                $fileUrl = Storage::url($path);
                $fileSizeMB = round($file->getSize() / (1024 * 1024), 2);
                $dataFiles[] = [
                    'file_name' => $filename,
                    'file_url' => $fileUrl,
                    'file_size' => $fileSizeMB
                ];
            }
        }
        return $dataFiles;
    }
}
