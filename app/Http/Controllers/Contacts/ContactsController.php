<?php

namespace App\Http\Controllers\Contacts;

use App\Events\Contacts\ContactsEvent;
use Inertia\Inertia;
use App\Models\Contacts;
use App\Http\Controllers\Controller;
use App\Mail\replyContacts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\Contacts\ContactsRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactsController extends Controller
{
    public function __construct()
    {
        $this->model = Contacts::class;
    }
    public function index()
    {
        $this->crumbs = [
            ['name' => 'Liên hệ', 'url' => '/admin/contacts'],
            ['name' => 'Danh sách các liên hệ', 'url' => '/admin/contacts'],
        ];
        $this->data = $this->model::orderByRaw('status = 0 desc')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Contacts/Index', ['contacts' => $this->data, 'crumbs' => $this->crumbs]);
    }
    public function store(ContactsRequest $request)
    {
        $this->data = $request->validated();

        if ($this->data) {
            $dataMail = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'replyMessage' => $request->input('replyMessage'),
            ];
            $this->model::where('email', $this->data['email'])->firstOrFail()->update(['status' => 1]);
            $this->data = $this->model::orderByRaw('status = 0 desc')->orderBy('created_at', 'desc')->get();
            Mail::to($request->input('email'))->send(new replyContacts($dataMail));
            return response()->json(['check' => true, 'message' => 'Email đã được gửi thành công!', 'data' => $this->data], 201);
        }
        return response()->json(['check' => false, 'message' => 'Gửi email thất bại!'], status: 400);
    }
    public function edit(string $id)
    {
        $this->crumbs = [
            ['name' => 'Thông tin liên hệ', 'url' => '/admin/contacts'],
            ['name' => 'Chi tiết Thông tin liên hệ', 'url' => '/admin/contacts/' . $id . '/edit'],
        ];
        $this->data = $this->model::findOrFail($id);
        return Inertia::render('Contacts/Edit', ['contacts' => $this->data, 'crumbs' => $this->crumbs]);
    }

    public function update(ContactsRequest $request, $id)
    {
        $this->data = $request->validated();
        $this->instance = $this->model::findOrFail($id)->update($this->data);
        if ($this->instance) {
            $this->data = $this->model::all();
            return response()->json(['check' => true, 'message' => 'Cập nhật thành công!', 'data' => $this->data], 200);
        }
        return response()->json(['check' => false, 'message' => 'Cập nhật thất bại!'], 400);
    }
    public function destroy($id)
    {
        $this->instance = $this->model::findOrFail($id)->delete();
        if ($this->instance) {
            $this->data = $this->model::all();
            return response()->json(['check' => true, 'message' => 'Xoá thành công!', 'data' => $this->data], 200);
        }
        return response()->json(['check' => false, 'message' => 'Xoá thất bại!'], 400);
    }
    public function addContacts(ContactsRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->data = $request->validated();

            $this->instance = Contacts::create([
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'phone' => $this->data['phone'],
                'message' => $this->data['message'],
                'note' => $this->data['note'] ?? null,
                'status' => 0, // Mặc định là chưa đọc
            ]);

            DB::commit();
            broadcast(new ContactsEvent($this->model::orderByRaw('status = 0 desc')->orderBy('created_at', 'desc')->get()))->toOthers();
            return response()->json(['check' => true, 'message' => 'Thêm liên hệ thành công!'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Add contact failed: " . $e->getMessage());
            return response()->json(['check' => false, 'message' => "Thêm liên hệ thất bại!"], 400);
        }
    }
}
