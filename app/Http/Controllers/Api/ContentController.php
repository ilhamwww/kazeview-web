<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    /**
     * Mengambil daftar Content.
     *
     * Query string yang didukung:
     * - per_page : jumlah item per halaman (default 15, max 100)
     * - page     : nomor halaman
     * - search   : kata kunci pencarian (title / body / link)
     * - order_by : kolom untuk sorting (default: urut)
     * - order    : arah sorting asc|desc (default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $orderBy = $request->query('order_by', 'urut');
        $order   = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedOrderColumns = ['id', 'urut', 'title', 'price', 'created_at', 'updated_at'];
        if (! in_array($orderBy, $allowedOrderColumns, true)) {
            $orderBy = 'urut';
        }

        $query = Content::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                foreach (['title', 'body', 'link'] as $col) {
                    $q->orWhere($col, 'like', $like);
                }
            });
        }

        $contents = $query->orderBy($orderBy, $order)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar content berhasil diambil.',
            'data'    => $contents->items(),
            'meta'    => [
                'current_page' => $contents->currentPage(),
                'per_page'     => $contents->perPage(),
                'total'        => $contents->total(),
                'last_page'    => $contents->lastPage(),
            ],
        ]);
    }

    /**
     * Menampilkan detail satu Content.
     */
    public function show(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'Content tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail content berhasil diambil.',
            'data'    => $content,
        ]);
    }

    /**
     * Menambahkan Content baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'            => ['required', 'string', 'max:255'],
            'body'             => ['nullable', 'string'],
            'user_id'          => ['nullable', 'integer'],
            'is_price_enabled' => ['nullable', 'boolean'],
            'price'            => ['nullable', 'integer', 'min:0'],
            'link'             => ['nullable', 'string', 'max:255'],
            'urut'             => ['nullable', 'integer', 'min:0'],
            'image'            => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Terapkan default value sesuai requirement
        $data['user_id']          = 1;
        $data['body']             = '-';
        $data['is_price_enabled'] = array_key_exists('is_price_enabled', $data)
            ? filter_var($data['is_price_enabled'], FILTER_VALIDATE_BOOLEAN)
            : true;

        if (! $data['is_price_enabled']) {
            $data['price'] = null;
        }

        // Auto-increment urut bila tidak dikirim: data terakhir + 1
        if (! array_key_exists('urut', $data) || $data['urut'] === null) {
            $data['urut'] = (int) (Content::max('urut') ?? 0) + 1;
        }

        // Simpan file gambar ke disk public
        $data['image'] = $request->file('image')->store('contents', 'public');

        try {
            $content = Content::create($data);
        } catch (\Throwable $e) {
            // Rollback file bila penyimpanan DB gagal
            if (isset($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan content.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content berhasil ditambahkan.',
            'data'    => $content->fresh(),
        ], 201);
    }

    /**
     * Memperbarui Content yang sudah ada.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $content = Content::find($id);

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'Content tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'body'             => ['sometimes', 'nullable', 'string'],
            'is_price_enabled' => ['sometimes', 'nullable', 'boolean'],
            'price'            => ['sometimes', 'nullable', 'integer', 'min:0'],
            'link'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'urut'             => ['sometimes', 'nullable', 'integer', 'min:0'],
            'image'            => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('is_price_enabled', $data)) {
            $data['is_price_enabled'] = filter_var($data['is_price_enabled'], FILTER_VALIDATE_BOOLEAN);
            if (! $data['is_price_enabled']) {
                $data['price'] = null;
            }
        }

        // Handle replace image bila dikirim
        $newImagePath = null;
        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('contents', 'public');
            $data['image'] = $newImagePath;
        }

        try {
            $content->update($data);
        } catch (\Throwable $e) {
            // Rollback file baru bila gagal
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui content.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content berhasil diperbarui.',
            'data'    => $content->fresh(),
        ]);
    }

    /**
     * Menghapus Content. File gambar terkait akan ikut terhapus
     * oleh model boot (event "deleting").
     */
    public function destroy(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'Content tidak ditemukan.',
            ], 404);
        }

        try {
            $content->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus content.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content berhasil dihapus.',
        ]);
    }
}
